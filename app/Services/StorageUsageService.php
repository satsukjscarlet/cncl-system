<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class StorageUsageService
{
    public function scan(?string $root = null, float $seconds = 20): array
    {
        $root ??= storage_path();
        $started = microtime(true);
        $result = ['files' => 0, 'bytes' => 0, 'pdf_files' => 0, 'pdf_bytes' => 0,
            'groups' => [], 'pdf_directories' => [], 'pdf_pending' => [], 'warnings' => [], 'complete' => true];
        $this->walk($root, $root, $started + $seconds, $result);
        $this->classifyPdfs($result);
        unset($result['pdf_pending']);
        $total = @disk_total_space($root);
        $free = @disk_free_space($root);
        $result['disk'] = ['total' => $total, 'free' => $free,
            'used_percent' => $total > 0 && $free !== false ? round(($total - $free) / $total * 100, 1) : null];
        $result['database'] = ['available' => false, 'tables' => [], 'data' => 0, 'indexes' => 0];
        try {
            if (DB::connection()->getDriverName() === 'mysql') {
                $tables = DB::select('SELECT TABLE_NAME AS name, COALESCE(DATA_LENGTH, 0) AS data_bytes, COALESCE(INDEX_LENGTH, 0) AS index_bytes FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? ORDER BY (COALESCE(DATA_LENGTH, 0) + COALESCE(INDEX_LENGTH, 0)) DESC', [DB::connection()->getDatabaseName()]);
                $result['database'] = ['available' => true, 'tables' => $tables,
                    'data' => array_sum(array_column($tables, 'data_bytes')),
                    'indexes' => array_sum(array_column($tables, 'index_bytes'))];
            } else {
                $result['warnings'][] = 'Thống kê database hiện hỗ trợ MySQL/MariaDB.';
            }
        } catch (Throwable $e) {
            report($e);
            $result['warnings'][] = 'Không đọc được dung lượng database. Vui lòng kiểm tra quyền truy cập thông tin hệ thống MySQL.';
        }
        arsort($result['groups']);
        $result['scanned_at'] = now()->format('d/m/Y H:i:s');
        $result['duration'] = round(microtime(true) - $started, 2);
        return $result;
    }

    private function walk(string $directory, string $root, float $deadline, array &$result): void
    {
        if (microtime(true) >= $deadline) {
            $result['complete'] = false;
            return;
        }
        try {
            $iterator = new \FilesystemIterator($directory, \FilesystemIterator::SKIP_DOTS);
            foreach ($iterator as $file) {
                if (microtime(true) >= $deadline) {
                    $result['complete'] = false;
                    return;
                }
                // Do not follow symlinks: avoid loops and counting the public storage link twice.
                if ($file->isLink()) {
                    continue;
                }
                $relativePath = str_replace('\\', '/', substr($file->getPathname(), strlen(rtrim($root, '/\\')) + 1));
                if ($relativePath === 'app/private/system-metrics') {
                    continue;
                }
                if ($file->isDir()) {
                    $this->walk($file->getPathname(), $root, $deadline, $result);
                } elseif ($file->isFile()) {
                    $bytes = $file->getSize();
                    $relative = substr($file->getPathname(), strlen(rtrim($root, '/\\')) + 1);
                    $parts = preg_split('~[/\\\\]+~', $relative);
                    $group = implode('/', array_slice($parts, 0, min(3, count($parts) - 1))) ?: '(gốc storage)';
                    $result['groups'][$group] = ($result['groups'][$group] ?? 0) + $bytes;
                    $result['files']++;
                    $result['bytes'] += $bytes;
                    if (strtolower($file->getExtension()) === 'pdf') {
                        $result['pdf_files']++;
                        $result['pdf_bytes'] += $bytes;
                        $result['pdf_pending'][] = ['path' => $relativePath, 'bytes' => $bytes];
                        if (count($result['pdf_pending']) >= 200) {
                            $this->classifyPdfs($result);
                        }
                    }
                }
                if (!$result['complete']) {
                    return;
                }
            }
        } catch (Throwable $e) {
            $result['warnings'][] = 'Không đọc đầy đủ thư mục: ' . str_replace($root, 'storage', $directory);
            $result['complete'] = false;
        }
    }

    private function classifyPdfs(array &$result): void
    {
        if (!$result['pdf_pending']) {
            return;
        }
        $prefix = str_replace('\\', '/', (string) config('filesystems.disks.local.root'));
        $storage = str_replace('\\', '/', storage_path()) . '/';
        $prefix = str_starts_with($prefix, $storage) ? substr($prefix, strlen($storage)) . '/' : null;
        $paths = [];
        foreach ($result['pdf_pending'] as $file) {
            if ($prefix && str_starts_with($file['path'], $prefix)) {
                $paths[] = substr($file['path'], strlen($prefix));
            }
        }
        $references = [];
        try {
            if ($paths && Schema::hasTable('quality_certificates')) {
                foreach (DB::table('quality_certificates')->whereIn('pdf_path', $paths)
                    ->get(['pdf_path', 'signed_at']) as $certificate) {
                    $references[$certificate->pdf_path] = $certificate->signed_at ? 'signed' : 'unsigned';
                }
            }
        } catch (Throwable $e) {
            $warning = 'Không đối chiếu được PDF với database; chỉ phân loại theo cấu trúc lưu trữ đã biết.';
            if (!in_array($warning, $result['warnings'], true)) $result['warnings'][] = $warning;
        }
        foreach ($result['pdf_pending'] as $file) {
            $path = $file['path'];
            $local = $prefix && str_starts_with($path, $prefix) ? substr($path, strlen($prefix)) : '';
            $type = $references[$local] ?? 'other';
            if ($local && preg_match('~^quality-certificates/smartca/\d+/pades-[^/]+/(unsigned|prepared)\.pdf$~i', $local, $match)) {
                $type = strtolower($match[1]) === 'unsigned' ? 'unsigned' : 'intermediate';
            } elseif ($type === 'other' && preg_match('~^(quality-certificates/smartca|quality-certificates-finalized)/\d+/[^/]+_signed\.pdf$~i', $local)) {
                $type = 'signed';
            }
            // Group transaction subfolders by certificate storage area, not by transaction ID.
            $directory = dirname($path);
            if ($local && preg_match('~^(quality-certificates/smartca|quality-certificates-finalized)/~', $local, $match)) {
                $directory = $prefix . $match[1];
            }
            $key = $directory . '|' . $type;
            $result['pdf_directories'][$key] ??= ['directory' => $directory, 'type' => $type, 'files' => 0, 'bytes' => 0];
            $result['pdf_directories'][$key]['files']++;
            $result['pdf_directories'][$key]['bytes'] += $file['bytes'];
        }
        $result['pdf_pending'] = [];
    }
}
