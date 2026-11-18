<?php

namespace Tests\Unit\Services;

use App\Models\Setting;
use App\Services\Backup\BackupService;
use Tests\TestCase;

class BackupServiceTest extends TestCase
{
    protected BackupService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new BackupService();
    }

    public function test_get_and_save_settings()
    {
        $this->service->saveSettings([
            'enabled' => '1',
            'frequency' => 'every_twelve_hours',
            'time' => '04:15',
            'compress' => '1',
            'keep_days' => 60,
            'custom_path' => 'C:\Backups',
        ]);

        $settings = $this->service->getSettings();

        $this->assertTrue($settings['enabled']);
        $this->assertEquals('every_twelve_hours', $settings['frequency']);
        $this->assertEquals('04:15', $settings['time']);
        $this->assertTrue($settings['compress']);
        $this->assertEquals(60, $settings['keep_days']);
        $this->assertEquals('C:\Backups', $settings['custom_path']);
    }

    public function test_test_destination()
    {
        $valid = $this->service->testDestination(sys_get_temp_dir());
        $this->assertTrue($valid['success']);

        $empty = $this->service->testDestination('');
        $this->assertFalse($empty['success']);
    }

    public function test_format_bytes()
    {
        $this->assertEquals('500 B', $this->service->formatBytes(500));
        $this->assertEquals('2.00 KB', $this->service->formatBytes(2048));
        $this->assertEquals('5.00 MB', $this->service->formatBytes(5 * 1024 * 1024));
        $this->assertEquals('1.50 GB', $this->service->formatBytes((int) (1.5 * 1024 * 1024 * 1024)));
    }
}
