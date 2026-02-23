<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class ExifService
{
    /**
     * Extract EXIF data from an image file
     * Returns array with: lat, lng, taken_at, taken_at_source, raw_exif
     */
    public function extract(string $filePath): array
    {
        $result = [
            'lat' => null,
            'lng' => null,
            'taken_at' => null,
            'taken_at_source' => 'missing',
            'raw_exif' => null,
        ];

        // Try exiftool first (more reliable)
        $exifData = $this->extractWithExifTool($filePath);
        
        if ($exifData === null) {
            // Fallback to PHP exif_read_data
            $exifData = $this->extractWithPhpExif($filePath);
        }

        if ($exifData) {
            $result['raw_exif'] = $exifData;
            
            // Extract GPS coordinates
            $gps = $this->extractGps($exifData);
            $result['lat'] = $gps['lat'];
            $result['lng'] = $gps['lng'];
            
            // Extract taken_at datetime
            $takenAt = $this->extractTakenAt($exifData, $filePath);
            $result['taken_at'] = $takenAt['datetime'];
            $result['taken_at_source'] = $takenAt['source'];
        }

        return $result;
    }

    /**
     * Extract EXIF using exiftool command line
     */
    private function extractWithExifTool(string $filePath): ?array
    {
        try {
            $process = new Process(['exiftool', '-json', '-c', '%+.6f', $filePath]);
            $process->setTimeout(30);
            $process->run();

            if (!$process->isSuccessful()) {
                Log::warning('ExifTool failed: ' . $process->getErrorOutput());
                return null;
            }

            $output = $process->getOutput();
            $data = json_decode($output, true);
            
            if (is_array($data) && !empty($data)) {
                return $data[0]; // exiftool returns array of results
            }
        } catch (\Exception $e) {
            Log::warning('ExifTool exception: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Extract EXIF using PHP's native exif_read_data
     */
    private function extractWithPhpExif(string $filePath): ?array
    {
        if (!function_exists('exif_read_data')) {
            return null;
        }

        try {
            $exif = @exif_read_data($filePath, 'ANY_TAG', true);
            
            if ($exif === false) {
                return null;
            }

            // Flatten the array for consistency with exiftool format
            $flattened = [];
            foreach ($exif as $section => $data) {
                foreach ($data as $key => $value) {
                    $flattened[$key] = $value;
                }
            }

            return $flattened;
        } catch (\Exception $e) {
            Log::warning('PHP exif_read_data exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Extract GPS coordinates from EXIF data
     */
    private function extractGps(array $exif): array
    {
        $lat = null;
        $lng = null;

        // Try exiftool format first
        if (isset($exif['GPSLatitude']) && isset($exif['GPSLongitude'])) {
            $lat = $this->parseCoordinate($exif['GPSLatitude']);
            $lng = $this->parseCoordinate($exif['GPSLongitude']);
            
            // Handle reference (N/S, E/W)
            if (isset($exif['GPSLatitudeRef']) && $exif['GPSLatitudeRef'] === 'S') {
                $lat = -$lat;
            }
            if (isset($exif['GPSLongitudeRef']) && $exif['GPSLongitudeRef'] === 'W') {
                $lng = -$lng;
            }
        }
        // Try PHP exif format
        elseif (isset($exif['GPSLatitude']) && is_array($exif['GPSLatitude'])) {
            $lat = $this->convertGpsArrayToDecimal($exif['GPSLatitude']);
            $lng = $this->convertGpsArrayToDecimal($exif['GPSLongitude']);
            
            if (isset($exif['GPSLatitudeRef']) && $exif['GPSLatitudeRef'] === 'S') {
                $lat = -$lat;
            }
            if (isset($exif['GPSLongitudeRef']) && $exif['GPSLongitudeRef'] === 'W') {
                $lng = -$lng;
            }
        }

        return ['lat' => $lat, 'lng' => $lng];
    }

    /**
     * Parse coordinate from various formats
     */
    private function parseCoordinate($value): ?float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }
        
        if (is_string($value)) {
            // Try to parse "deg min sec" format
            if (preg_match('/(\d+)\s+(\d+)\s+([\d.]+)/', $value, $matches)) {
                return $this->convertDmsToDecimal($matches[1], $matches[2], $matches[3]);
            }
            // Try direct float parsing
            return (float) $value;
        }

        return null;
    }

    /**
     * Convert DMS array to decimal degrees
     */
    private function convertGpsArrayToDecimal(array $dms): ?float
    {
        if (count($dms) < 3) {
            return null;
        }

        $degrees = $this->parseRational($dms[0]);
        $minutes = $this->parseRational($dms[1]);
        $seconds = $this->parseRational($dms[2]);

        return $this->convertDmsToDecimal($degrees, $minutes, $seconds);
    }

    /**
     * Parse rational number (e.g., "10/1" or "55/100")
     */
    private function parseRational($value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        if (is_string($value) && strpos($value, '/') !== false) {
            list($numerator, $denominator) = explode('/', $value);
            return (float) $numerator / (float) $denominator;
        }

        return 0;
    }

    /**
     * Convert degrees, minutes, seconds to decimal
     */
    private function convertDmsToDecimal($degrees, $minutes, $seconds): float
    {
        return (float) $degrees + ($minutes / 60) + ($seconds / 3600);
    }

    /**
     * Extract taken_at datetime from EXIF
     */
    private function extractTakenAt(array $exif, string $filePath): array
    {
        $datetime = null;
        $source = 'missing';

        // Priority order for datetime fields
        $fields = [
            'DateTimeOriginal' => 'DateTimeOriginal',
            'CreateDate' => 'CreateDate',
            'DateTime' => 'DateTime',
            'DateTimeDigitized' => 'DateTimeDigitized',
        ];

        foreach ($fields as $field => $sourceName) {
            if (!empty($exif[$field])) {
                $datetime = $this->parseExifDate($exif[$field]);
                if ($datetime) {
                    $source = $sourceName;
                    break;
                }
            }
        }

        // Fallback to file modification time
        if ($datetime === null) {
            $datetime = date('Y-m-d H:i:s', filemtime($filePath));
            $source = 'FileTimestamp';
        }

        return ['datetime' => $datetime, 'source' => $source];
    }

    /**
     * Parse EXIF date format (YYYY:MM:DD HH:MM:SS)
     */
    private function parseExifDate($dateString): ?string
    {
        if (empty($dateString)) {
            return null;
        }

        // Replace colons in date portion with dashes
        $dateString = preg_replace('/^(\d{4}):(\d{2}):(\d{2})/', '$1-$2-$3', $dateString);
        
        $timestamp = strtotime($dateString);
        
        if ($timestamp === false) {
            return null;
        }

        return date('Y-m-d H:i:s', $timestamp);
    }
}
