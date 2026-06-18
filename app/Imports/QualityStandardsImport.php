<?php

namespace App\Imports;

use App\Models\QualityStandard;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class QualityStandardsImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        $code = trim($row['ma_tieu_chuan'] ?? '');
        $name = trim($row['ten_tieu_chuan'] ?? '');

        if (empty($code) || empty($name)) {
            return null;
        }

        return QualityStandard::updateOrCreate(
            ['code' => $code],
            [
                'name' => $name,
                'description' => $row['mo_ta'] ?? null,
                'is_active' => true,
            ]
        );
    }
}