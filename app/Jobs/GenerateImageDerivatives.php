<?php

namespace App\Jobs;

use App\Models\PhotoEvidence;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class GenerateImageDerivatives implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(
        private PhotoEvidence $photoEvidence
    ) {}

    public function handle(): void
    {
        try {
            $originalPath = $this->photoEvidence->original_path;
            
            if (!Storage::disk('evidence_originals')->exists($originalPath)) {
                Log::error("Original file not found: {$originalPath}");
                return;
            }

            $originalFullPath = Storage::disk('evidence_originals')->path($originalPath);
            
            // Create preview (max 1024px width)
            $previewPath = $this->generateDerivative($originalFullPath, 1024, 80, 'preview');
            
            // Create thumbnail (max 300px width)
            $thumbPath = $this->generateDerivative($originalFullPath, 300, 60, 'thumb');

            // Update the photo evidence record
            $this->photoEvidence->update([
                'preview_path' => $previewPath,
                'thumb_path' => $thumbPath,
            ]);

            Log::info("Generated derivatives for photo {$this->photoEvidence->id}");
        } catch (\Exception $e) {
            Log::error("Failed to generate derivatives for photo {$this->photoEvidence->id}: " . $e->getMessage());
            throw $e;
        }
    }

    private function generateDerivative(string $sourcePath, int $maxWidth, int $quality, string $type): string
    {
        $manager = new ImageManager(new Driver());
        $image = $manager->read($sourcePath);

        // Get current dimensions
        $width = $image->width();
        $height = $image->height();

        // Resize if larger than max width
        if ($width > $maxWidth) {
            $image->scale(width: $maxWidth);
        }

        // Generate filename
        $filename = pathinfo($this->photoEvidence->original_path, PATHINFO_FILENAME);
        $extension = pathinfo($this->photoEvidence->original_path, PATHINFO_EXTENSION);
        $derivativeFilename = "{$filename}_{$type}.jpg";
        
        // Store in derivatives disk
        $derivativePath = "{$this->photoEvidence->project_id}/{$derivativeFilename}";
        $fullDerivativePath = Storage::disk('evidence_derivatives')->path($derivativePath);
        
        // Ensure directory exists
        $directory = dirname($fullDerivativePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        // Save the derivative
        $image->encode(new \Intervention\Image\Encoders\JpegEncoder(quality: $quality));
        $image->save($fullDerivativePath);

        return $derivativePath;
    }
}
