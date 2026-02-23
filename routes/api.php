<?php

use App\Models\AuditLog;
use App\Models\PhotoEvidence;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

// API routes for project photos (for map and gallery)
Route::middleware(['auth'])->group(function () {
    Route::get('/projects/{project}/photos', function (Project $project) {
        if (!auth()->user()->isAdmin() && !$project->hasMember(auth()->user())) {
            abort(403);
        }

        return $project->photoEvidences()
            ->select('id', 'project_id', 'caption', 'exif_lat', 'exif_lng', 'gps_status', 'taken_at', 'taken_at_source', 'thumb_path', 'preview_path')
            ->get()
            ->map(function ($photo) {
                return [
                    'id' => $photo->id,
                    'caption' => $photo->caption,
                    'exif_lat' => $photo->exif_lat,
                    'exif_lng' => $photo->exif_lng,
                    'gps_status' => $photo->gps_status,
                    'taken_at' => $photo->taken_at?->format('Y-m-d H:i:s'),
                    'thumb_url' => route('photos.thumb', [$photo->project_id, $photo->id]),
                    'preview_url' => $photo->preview_path ? route('photos.preview', [$photo->project_id, $photo->id]) : null,
                    'url' => route('photos.show', [$photo->project_id, $photo->id]),
                ];
            });
    });

    Route::get('/projects/{project}/audit-logs', function (Project $project) {
        if (!auth()->user()->isAdmin() && !$project->hasMember(auth()->user())) {
            abort(403);
        }

        return $project->auditLogs()
            ->with('actor')
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();
    });
});
