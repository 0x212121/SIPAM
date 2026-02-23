<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function csv(Project $project)
    {
        Gate::authorize('view', $project);

        $filename = "project_{$project->code}_" . date('Y-m-d') . '.csv';

        $response = new StreamedResponse(function () use ($project) {
            $handle = fopen('php://output', 'w');

            // Headers
            fputcsv($handle, [
                'stored_path',
                'lat',
                'lng',
                'gps_status',
                'taken_at',
                'taken_at_source',
                'caption',
                'uploaded_by',
                'uploaded_at',
                'sha256',
            ]);

            // Data
            $project->photoEvidences()
                ->with('uploader')
                ->chunk(100, function ($photos) use ($handle) {
                    foreach ($photos as $photo) {
                        fputcsv($handle, [
                            $photo->original_path,
                            $photo->exif_lat,
                            $photo->exif_lng,
                            $photo->gps_status,
                            $photo->taken_at?->format('Y-m-d H:i:s'),
                            $photo->taken_at_source,
                            $photo->caption,
                            $photo->uploader->name,
                            $photo->uploaded_at?->format('Y-m-d H:i:s'),
                            $photo->sha256_hash,
                        ]);
                    }
                });

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response;
    }

    public function geojson(Project $project)
    {
        Gate::authorize('view', $project);

        $filename = "project_{$project->code}_" . date('Y-m-d') . '.geojson';

        $features = [];

        $project->photoEvidences()
            ->whereNotNull('exif_lat')
            ->whereNotNull('exif_lng')
            ->with('uploader')
            ->chunk(100, function ($photos) use (&$features) {
                foreach ($photos as $photo) {
                    $features[] = [
                        'type' => 'Feature',
                        'geometry' => [
                            'type' => 'Point',
                            'coordinates' => [(float) $photo->exif_lng, (float) $photo->exif_lat],
                        ],
                        'properties' => [
                            'id' => $photo->id,
                            'caption' => $photo->caption,
                            'gps_status' => $photo->gps_status,
                            'taken_at' => $photo->taken_at?->format('Y-m-d H:i:s'),
                            'taken_at_source' => $photo->taken_at_source,
                            'uploaded_by' => $photo->uploader->name,
                            'uploaded_at' => $photo->uploaded_at?->format('Y-m-d H:i:s'),
                            'sha256' => $photo->sha256_hash,
                            'thumb_url' => route('photos.thumb', [$photo->project_id, $photo->id]),
                        ],
                    ];
                }
            });

        $geojson = [
            'type' => 'FeatureCollection',
            'properties' => [
                'project_code' => $project->code,
                'project_name' => $project->name,
                'export_date' => now()->toIso8601String(),
            ],
            'features' => $features,
        ];

        return response()->json($geojson, 200, [
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
