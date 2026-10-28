<?php

namespace Tests\Feature;

use App\Services\StorageUsageService;
use Tests\TestCase;

class StorageUsageTest extends TestCase
{
    public function test_concurrent_scan_is_rejected_and_lock_is_released(): void
    {
        $lock = \Illuminate\Support\Facades\Cache::lock('storage-usage-scan', 120);
        $this->assertTrue($lock->get());
        $service = $this->mock(StorageUsageService::class);
        $service->shouldNotReceive('scan');
        $controller = new \App\Http\Controllers\StorageUsageController;
        try {
            $this->assertSame(409, $controller->scan($service)->getStatusCode());
        } finally {
            $lock->release();
        }
        $this->app->forgetInstance(StorageUsageService::class);
        $service = $this->mock(StorageUsageService::class);
        $service->shouldReceive('scan')->once()->andReturn(['files' => 1]);
        $this->mock(\App\Services\StorageUsageHistoryService::class)
            ->shouldReceive('record')->once()->with(['files' => 1])->andReturn([]);
        $this->assertSame(200, $controller->scan($service)->getStatusCode());
        $nextLock = \Illuminate\Support\Facades\Cache::lock('storage-usage-scan', 120);
        $this->assertTrue($nextLock->get());
        $nextLock->release();
    }

    public function test_pdf_categories_and_history_are_consistent_and_partial_scans_are_not_saved(): void
    {
        $root = sys_get_temp_dir() . '/cncl-metrics-' . bin2hex(random_bytes(6));
        $pdf = $root . '/app/private/quality-certificates/smartca/1/pades-example';
        $history = $root . '/app/private/system-metrics';
        mkdir($pdf, 0770, true);
        mkdir($history, 0770, true);
        $files = [$pdf . '/unsigned.pdf' => '12', $pdf . '/prepared.pdf' => '123',
            dirname($pdf) . '/txn_pades_signed.pdf' => '1234', $root . '/other.pdf' => '1'];
        foreach ($files as $path => $content) file_put_contents($path, $content);
        try {
            $scan = app(StorageUsageService::class)->scan($root);
            $types = array_column(array_values($scan['pdf_directories']), 'files', 'type');
            $this->assertSame(['unsigned' => 1, 'intermediate' => 1, 'signed' => 1, 'other' => 1], array_replace(array_fill_keys(['unsigned', 'intermediate', 'signed', 'other'], 0), $types));
            $this->assertSame(4, array_sum(array_column($scan['pdf_directories'], 'files')));
            $this->assertSame(10, array_sum(array_column($scan['pdf_directories'], 'bytes')));
            $service = app(\App\Services\StorageUsageHistoryService::class);
            $this->travelTo(\Illuminate\Support\Carbon::parse('2026-10-27 10:00:00'));
            $first = $service->record($scan, $history);
            $this->assertNull($first['previous']);
            $this->assertTrue($first['saved']);
            $this->assertCount(1, $first['daily']);
            $this->assertSame(4, app(StorageUsageService::class)->scan($root)['files']);
            $this->travelTo(\Illuminate\Support\Carbon::parse('2026-10-28 10:00:00'));
            $scan['pdf_bytes'] += 20;
            $second = $service->record($scan, $history);
            $this->assertSame(10, $second['first']['pdf_bytes']);
            $this->assertSame(30, $second['current']['pdf_bytes']);
            $this->assertCount(2, $second['daily']);
            $scan['complete'] = false;
            $partial = $service->record($scan, $history);
            $this->assertFalse($partial['saved']);
            $this->assertNull($partial['current']);
            $this->assertCount(2, $partial['daily']);
            $this->travelTo(\Illuminate\Support\Carbon::parse('2026-11-01 10:00:00'));
            $scan['complete'] = true;
            $next = $service->record($scan, $history);
            $this->assertNull($next['previous']);
            $this->assertCount(1, $next['daily']);
        } finally {
            $this->travelBack();
            foreach (array_keys($files) as $path) unlink($path);
            foreach (glob($history . '/*.jsonl') as $path) unlink($path);
            foreach ([$pdf, dirname($pdf), dirname($pdf, 2), dirname($pdf, 3), $history, $root . '/app/private', $root . '/app', $root] as $path) rmdir($path);
        }
    }

    public function test_scan_counts_real_files_without_reading_contents(): void
    {
        $root = sys_get_temp_dir() . '/cncl-storage-' . bin2hex(random_bytes(6));
        mkdir($root);
        mkdir($root . '/logs');
        file_put_contents($root . '/one.PDF', '12345');
        file_put_contents($root . '/logs/app.log', '123');
        try {
            $result = app(StorageUsageService::class)->scan($root);
            $this->assertSame(2, $result['files']);
            $this->assertSame(8, $result['bytes']);
            $this->assertSame(1, $result['pdf_files']);
            $this->assertSame(5, $result['pdf_bytes']);
            $this->assertTrue($result['complete']);
            $this->assertSame(3, $result['groups']['logs']);
            $this->assertSame('12345', file_get_contents($root . '/one.PDF'));
            $partial = app(StorageUsageService::class)->scan($root, 0);
            $this->assertFalse($partial['complete']);
            $this->assertSame(0, $partial['files']);
        } finally {
            unlink($root . '/one.PDF');
            unlink($root . '/logs/app.log');
            rmdir($root . '/logs');
            rmdir($root);
        }
    }

    public function test_routes_require_admin_and_view_compiles(): void
    {
        foreach (['reports.storage-usage', 'reports.storage-usage.scan'] as $name) {
            $this->assertContains('role:Admin', app('router')->getRoutes()->getByName($name)->gatherMiddleware());
        }
        $compiled = app('blade.compiler')->compileString(file_get_contents(resource_path('views/reports/storage_usage.blade.php')));
        $this->assertStringNotContainsString('@endsection', $compiled);
        view()->share('errors', new \Illuminate\Support\ViewErrorBag);
        $html = view('reports.storage_usage')->render();
        $this->assertStringContainsString('pdf-directory-rows', $html);
        $this->assertStringContainsString('history-chart', $html);
        if ($directory = getenv('CNCL_STORAGE_UI_DIR')) {
            if (!is_dir($directory)) mkdir($directory, 0770, true);
            file_put_contents($directory . '/storage.html', $html);
        }
        $ability = \Illuminate\Support\Facades\Gate::abilities()['storage-usage.view'];
        $this->assertFalse($ability(new class {
            public function hasRole($role) { return false; }
        }));
        $this->assertTrue($ability(new class {
            public function hasRole($role) { return $role === 'Admin'; }
        }));
    }
}
