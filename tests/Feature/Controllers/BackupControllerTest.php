<?php

namespace Tests\Feature\Controllers;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\ModuleTestCase;

class BackupControllerTest extends ModuleTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loginAs('admin');
    }

    public function test_index_lists_backups()
    {
        Storage::fake('local');
        Storage::put('backups/test.sql', 'dump content');

        $response = $this->get(route('backups.index'));

        $response->assertOk();
        $response->assertSee('test.sql');
    }

    public function test_index_shows_empty_when_no_backups()
    {
        Storage::fake('local');

        $response = $this->get(route('backups.index'));

        $response->assertOk();
    }

    public function test_create_runs_backup_command()
    {
        Artisan::shouldReceive('call')
            ->once()
            ->with('backup:database');
        Artisan::shouldReceive('output')
            ->once()
            ->andReturn('Backup created successfully.');

        $response = $this->get(route('backups.create'));

        $response->assertRedirect(route('backups.index'));
        $response->assertSessionHas('success');
    }

    public function test_download()
    {
        Storage::fake('local');
        Storage::put('backups/test.sql', 'dump content');

        $response = $this->get(route('backups.download', 'test.sql'));

        $response->assertOk();
        $response->assertDownload('test.sql');
    }

    public function test_download_returns_404_when_not_found()
    {
        Storage::fake('local');

        $response = $this->get(route('backups.download', 'nonexistent.sql'));

        $response->assertNotFound();
    }

    public function test_destroy()
    {
        Storage::fake('local');
        Storage::put('backups/test.sql', 'dump content');

        $response = $this->delete(route('backups.destroy', 'test.sql'));

        $response->assertRedirect(route('backups.index'));
        $response->assertSessionHas('success');
        Storage::assertMissing('backups/test.sql');
    }

    public function test_destroy_handles_missing_file()
    {
        Storage::fake('local');

        $response = $this->delete(route('backups.destroy', 'ghost.sql'));

        $response->assertRedirect(route('backups.index'));
        $response->assertSessionHas('success');
    }

    public function test_update_settings()
    {
        $response = $this->put(route('backups.settings'), [
            'enabled' => '1',
            'frequency' => 'weekly',
            'time' => '03:30',
            'compress' => '1',
            'keep_days' => 45,
        ]);

        $response->assertRedirect(route('backups.index'));
        $response->assertSessionHas('success');

        $this->assertEquals('weekly', \App\Models\Setting::get('backup.frequency'));
        $this->assertEquals('03:30', \App\Models\Setting::get('backup.time'));
        $this->assertEquals('45', \App\Models\Setting::get('backup.keep_days'));
    }

    public function test_test_destination_endpoint()
    {
        $tempDir = sys_get_temp_dir();
        $response = $this->postJson(route('backups.test-destination'), [
            'custom_path' => $tempDir,
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
    }

    public function test_restore_requires_confirmation_string()
    {
        Storage::fake('local');
        Storage::put('backups/test.sqlite', 'SQLite format 3' . "\0" . str_repeat('A', 100));

        $response = $this->post(route('backups.restore'), [
            'filename' => 'test.sqlite',
            'confirmation' => 'ERRADO',
        ]);

        $response->assertSessionHasErrors('confirmation');
    }

    public function test_cleanup_action()
    {
        $response = $this->post(route('backups.cleanup'));
        $response->assertRedirect(route('backups.index'));
        $response->assertSessionHas('success');
    }
}
