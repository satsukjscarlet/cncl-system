<?php

namespace App\Services;

use RuntimeException;

class StorageUsageHistoryService
{
    public function record(array $scan, ?string $directory = null): array
    {
        $directory ??= storage_path('app/private/system-metrics');
        $now = now();
        // Scope history to this installation and database, even after copying storage to another server.
        $scope = substr(hash('sha256', base_path() . '|' . config('app.url') . '|' . config('database.default') . '|'
            . config('database.connections.' . config('database.default') . '.host') . '|'
            . config('database.connections.' . config('database.default') . '.database')), 0, 16);
        if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
            throw new RuntimeException('Cannot create storage metrics directory.');
        }
        $path = $directory . '/' . $scope . '-' . $now->format('Y-m') . '.jsonl';
        $handle = fopen($path, 'c+');
        if (!$handle) throw new RuntimeException('Cannot open storage history.');
        try {
            if (!flock($handle, LOCK_EX)) throw new RuntimeException('Cannot lock storage history.');
            $first = null;
            $previous = null;
            $daily = [];
            while (($line = fgets($handle)) !== false) {
                $entry = json_decode($line, true);
                if (!is_array($entry) || !isset($entry['at'], $entry['bytes'], $entry['pdf_files'], $entry['pdf_bytes'])) continue;
                $first ??= $entry;
                $previous = $entry;
                $daily[substr($entry['at'], 0, 10)] = $entry;
            }
            $current = ['at' => $now->toIso8601String(), 'bytes' => $scan['bytes'],
                'pdf_files' => $scan['pdf_files'], 'pdf_bytes' => $scan['pdf_bytes'],
                'database_bytes' => $scan['database']['available'] ? $scan['database']['data'] + $scan['database']['indexes'] : null,
                'types' => []];
            foreach ($scan['pdf_directories'] ?? [] as $group) {
                $type = $group['type'];
                $current['types'][$type] ??= ['files' => 0, 'bytes' => 0];
                $current['types'][$type]['files'] += $group['files'];
                $current['types'][$type]['bytes'] += $group['bytes'];
            }
            // Incomplete scans must never become growth baselines.
            if ($scan['complete']) {
                fseek($handle, 0, SEEK_END);
                $line = json_encode($current, JSON_THROW_ON_ERROR) . "\n";
                if (fwrite($handle, $line) !== strlen($line) || !fflush($handle)) throw new RuntimeException('Cannot save storage history.');
                $first ??= $current;
                $daily[$now->format('Y-m-d')] = $current;
            }
            return ['month' => $now->format('m/Y'), 'saved' => $scan['complete'], 'first' => $first,
                'previous' => $previous, 'current' => $scan['complete'] ? $current : null,
                'daily' => array_values($daily)];
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
}
