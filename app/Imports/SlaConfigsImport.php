<?php

namespace App\Imports;

use App\Models\SlaConfig;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class SlaConfigsImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        $code = trim($row['ma_sla'] ?? '');
        $name = trim($row['ten_sla'] ?? '');
        $processStep = trim($row['cong_doan'] ?? '');

        if (empty($code) || empty($name) || empty($processStep)) {
            return null;
        }

        return SlaConfig::updateOrCreate(
            ['code' => $code],
            [
                'name' => $name,
                'process_step' => $processStep,
                'warning_minutes' => (int) ($row['canh_bao_phut'] ?? 0),
                'limit_minutes' => (int) ($row['qua_han_phut'] ?? 0),
                'description' => $row['mo_ta'] ?? null,
                'is_active' => true,
            ]
        );
    }
}