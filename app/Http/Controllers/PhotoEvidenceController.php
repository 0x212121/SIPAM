<?php

namespace App\Http\Controllers;

use App\Http\Requests\PhotoUploadRequest;
use App\Jobs\GenerateImageDerivatives;
use App\Models\PhotoEvidence;
use App\Models\Project;
use App\Services\AuditLogService;
use App\Services\ExifService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class PhotoEvidenceController extends Controller
{
    public function __construct(
        private ExifService $exifService
    ) {}

    public function show(Project $project, PhotoEvidence $photo)
    {
        Gate::authorize('view', $photo);

        $photo->load(['uploader', 'auditLogs.actor']);

        return view('photos.show', compact('project', 'photo'));
    }

    public function store(PhotoUploadRequest $request, Project $project)
    {
        Gate::authorize('create', [PhotoEvidence::class, $project]);

        $uploadedFiles = [];
        $errors = [];

        foreach ($request->file('photos') as $file) {
            try {
                // Compute SHA256 hash
                $sha256 = hash_file('sha256', $file->getRealPath());

                // Check for duplicate
                $existing = PhotoEvidence::where('sha256_hash', $sha256)->first();
                if ($existing) {
                    $errors[] = "File '{$file->getClientOriginalName()}' already exists as photo #{$existing->id}.";
                    continue;
                }

                // Store original file
                $filename = $sha256 . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs($project->id, $filename, 'evidence_originals');

                // Extract EXIF data
                $fullPath = Storage::disk('evidence_originals')->path($path);
                $exifData = $this->exifService->extract($fullPath);

                // Create photo evidence record
                $photo = PhotoEvidence::create([
                    'project_id' => $project->id,
                    'original_path' => $path,
                    'exif_lat' => $exifData['lat'],
                    'exif_lng' => $exifData['lng'],
                    'gps_status' => $exifData['lat'] && $exifData['lng'] ? 'original' : 'missing',
                    'taken_at' => $exifData['taken_at'],
                    'taken_at_source' => $exifData['taken_at_source'],
                    'uploaded_by' => auth()->id(),
                    'uploaded_at' => now(),
                    'sha256_hash' => $sha256,
                    'raw_exif_json' => $exifData['raw_exif'],
                ]);

                // Dispatch job to generate derivatives
                GenerateImageDerivatives::dispatch($photo);

                AuditLogService::logCreate($photo);
                $uploadedFiles[] = $photo;
            } catch (\Exception $e) {
                $errors[] = "Failed to upload '{$file->getClientOriginalName()}': {$e->getMessage()}";
            }
        }

        $message = count($uploadedFiles) > 0 
            ? 'Uploaded ' . count($uploadedFiles) . ' photo(s) successfully.' 
            : 'No photos were uploaded.';

        if (count($errors) > 0) {
            return back()
                ->with('success', $message)
                ->with('errors', $errors);
        }

        return back()->with('success', $message);
    }

    public function update(Request $request, Project $project, PhotoEvidence $photo)
    {
        Gate::authorize('update', $photo);

        $validated = $request->validate([
            'caption' => ['nullable', 'string', 'max:1000'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
            'exif_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'exif_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'taken_at' => ['nullable', 'date'],
        ]);

        $oldValues = $photo->only(['caption', 'tags', 'exif_lat', 'exif_lng', 'taken_at', 'taken_at_source']);

        $updateData = [];

        // Handle caption
        if (isset($validated['caption'])) {
            $updateData['caption'] = $validated['caption'];
        }

        // Handle tags
        if (isset($validated['tags'])) {
            $updateData['tags'] = $validated['tags'];
        }

        // Handle manual GPS
        if (isset($validated['exif_lat']) && isset($validated['exif_lng'])) {
            $updateData['exif_lat'] = $validated['exif_lat'];
            $updateData['exif_lng'] = $validated['exif_lng'];
            $updateData['gps_status'] = 'manual';
        }

        // Handle manual taken_at
        if (isset($validated['taken_at'])) {
            $updateData['taken_at'] = $validated['taken_at'];
            $updateData['taken_at_source'] = 'manual';
        }

        $photo->update($updateData);

        // Log changes
        foreach ($updateData as $field => $newValue) {
            if (isset($oldValues[$field]) && $oldValues[$field] !== $newValue) {
                AuditLogService::logUpdate($photo, $field, $oldValues[$field], $newValue);
            }
        }

        return back()->with('success', 'Photo updated successfully.');
    }

    public function destroy(Project $project, PhotoEvidence $photo)
    {
        Gate::authorize('delete', $photo);

        // Delete files
        Storage::disk('evidence_originals')->delete($photo->original_path);
        if ($photo->preview_path) {
            Storage::disk('evidence_derivatives')->delete($photo->preview_path);
        }
        if ($photo->thumb_path) {
            Storage::disk('evidence_derivatives')->delete($photo->thumb_path);
        }

        AuditLogService::logDelete($photo);
        $photo->delete();

        return back()->with('success', 'Photo deleted successfully.');
    }

    // Streaming endpoints
    public function original(Project $project, PhotoEvidence $photo)
    {
        Gate::authorize('view', $photo);
        return $this->streamImage($photo->original_path, 'evidence_originals', 'inline');
    }

    public function preview(Project $project, PhotoEvidence $photo)
    {
        Gate::authorize('view', $photo);
        
        if (!$photo->preview_path) {
            // If preview not ready yet, return original
            return $this->streamImage($photo->original_path, 'evidence_originals', 'inline');
        }
        
        return $this->streamImage($photo->preview_path, 'evidence_derivatives', 'inline');
    }

    public function thumb(Project $project, PhotoEvidence $photo)
    {
        Gate::authorize('view', $photo);
        
        if (!$photo->thumb_path) {
            // If thumb not ready yet, return original
            return $this->streamImage($photo->original_path, 'evidence_originals', 'inline');
        }
        
        return $this->streamImage($photo->thumb_path, 'evidence_derivatives', 'inline');
    }

    private function streamImage(string $path, string $disk, string $disposition = 'inline')
    {
        if (!Storage::disk($disk)->exists($path)) {
            abort(404, 'Image not found');
        }

        $fullPath = Storage::disk($disk)->path($path);
        $mimeType = mime_content_type($fullPath);
        $fileSize = filesize($fullPath);

        $response = response()->stream(function () use ($fullPath) {
            $stream = fopen($fullPath, 'rb');
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => $mimeType,
            'Content-Length' => $fileSize,
            'Content-Disposition' => $disposition . '; filename="' . basename($path) . '"',
            'Cache-Control' => 'private, max-age=86400',
        ]);

        return $response;
    }
}
