<?php

namespace Tests\Unit;

use App\Services\ExifService;
use PHPUnit\Framework\TestCase;

class ExifServiceTest extends TestCase
{
    private ExifService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ExifService();
    }

    public function test_service_extracts_gps_from_dms_format(): void
    {
        // This test would need actual image files with EXIF data
        // For unit testing, we can test the coordinate conversion methods
        $reflection = new \ReflectionClass($this->service);
        
        $convertDms = $reflection->getMethod('convertDmsToDecimal');
        $convertDms->setAccessible(true);
        
        // Test DMS to decimal conversion
        $result = $convertDms->invoke($this->service, 40, 26, 46);
        $this->assertEqualsWithDelta(40.446111, $result, 0.0001);
    }

    public function test_parse_rational_number(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $parseRational = $reflection->getMethod('parseRational');
        $parseRational->setAccessible(true);

        $this->assertEquals(10, $parseRational->invoke($this->service, '10/1'));
        $this->assertEquals(0.55, $parseRational->invoke($this->service, '55/100'));
        $this->assertEquals(42, $parseRational->invoke($this->service, 42));
    }

    public function test_parse_exif_date(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $parseDate = $reflection->getMethod('parseExifDate');
        $parseDate->setAccessible(true);

        $result = $parseDate->invoke($this->service, '2024:02:23 14:30:00');
        $this->assertEquals('2024-02-23 14:30:00', $result);

        $result = $parseDate->invoke($this->service, '2024-02-23 14:30:00');
        $this->assertEquals('2024-02-23 14:30:00', $result);
    }

    public function test_extract_returns_expected_structure(): void
    {
        // For a non-existent file, should return structure with null values
        $result = $this->service->extract('/nonexistent/file.jpg');

        $this->assertArrayHasKey('lat', $result);
        $this->assertArrayHasKey('lng', $result);
        $this->assertArrayHasKey('taken_at', $result);
        $this->assertArrayHasKey('taken_at_source', $result);
        $this->assertArrayHasKey('raw_exif', $result);
    }
}
