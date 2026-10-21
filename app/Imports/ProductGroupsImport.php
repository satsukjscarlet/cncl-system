<?php

namespace App\Imports;

use App\Models\ProductGroup;
use App\Models\QualityStandard;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProductGroupsImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows): void
    {
        $prepared = [];
        $errors = [];
        $seen = [];
        foreach ($rows as $index => $row) {
            $row = $row instanceof Collection ? $row->all() : $row;
            if (collect($row)->every(fn ($value) => trim((string) $value) === '')) {
                continue;
            }
            $line = $index + 2;
            $name = trim((string) ($row['ten_nhom_san_pham'] ?? ''));
            $code = trim((string) ($row['ma_nhom_san_pham'] ?? ''));
            $group = $code !== '' ? ProductGroup::where('code', $code)->first() : ProductGroup::where('name', $name)->first();
            $code = $code ?: ($group?->code ?: strtoupper(Str::slug($name, '-')));
            $data = ['code' => $code, 'name' => $name];
            if (array_key_exists('ghi_chu', $row) || array_key_exists('tieu_chuan', $row)) {
                // Legacy "tieu_chuan" is a note, never a standard foreign key.
                $data['description'] = trim((string) ($row['ghi_chu'] ?? $row['tieu_chuan'] ?? '')) ?: null;
            }
            $status = trim((string) ($row['trang_thai'] ?? ''));
            if ($status !== '') {
                $data['is_active'] = $status;
            } elseif (!$group) {
                $data['is_active'] = true;
            }
            $standardCode = trim((string) ($row['ma_tieu_chuan_mac_dinh'] ?? ''));
            if ($standardCode !== '') {
                $standard = QualityStandard::where('code', $standardCode)->first();
                if (!$standard || (!$standard->is_active && $group?->default_quality_standard_id != $standard->id)) {
                    $errors[] = "Dòng {$line}: mã tiêu chuẩn \"{$standardCode}\" không tồn tại hoặc đã ngừng sử dụng.";
                } else {
                    $data['default_quality_standard_id'] = $standard->id;
                }
            }
            $validator = Validator::make($data, [
                'code' => ['required', 'string', 'max:50', Rule::unique('product_groups', 'code')->ignore($group?->id)],
                'name' => ['required', 'string', 'max:255'],
                'is_active' => ['sometimes', 'boolean'],
            ], [
                'code.required' => 'Không xác định được mã nhóm.',
                'code.max' => 'Mã nhóm tối đa 50 ký tự.',
                'code.unique' => 'Mã nhóm đã tồn tại, có thể thuộc nhóm đã xóa mềm.',
                'name.required' => 'Thiếu tên nhóm sản phẩm.',
                'name.max' => 'Tên nhóm tối đa 255 ký tự.',
                'is_active.boolean' => 'Trạng thái chỉ nhận 1 (đang dùng) hoặc 0 (ngừng sử dụng).',
            ]);
            foreach ($validator->errors()->all() as $message) {
                $errors[] = "Dòng {$line}: {$message}";
            }
            $key = mb_strtolower($code);
            if (isset($seen[$key])) {
                $errors[] = "Dòng {$line}: mã nhóm {$code} bị lặp với dòng {$seen[$key]}.";
            }
            $seen[$key] = $line;
            $prepared[] = [$group, $data];
            if (count($errors) >= 30) {
                break;
            }
        }
        if ($errors || !$prepared) {
            throw ValidationException::withMessages(['file' => $errors ?: ['File không có dữ liệu nhóm sản phẩm.']]);
        }
        DB::transaction(function () use ($prepared) {
            foreach ($prepared as [$group, $data]) {
                $group ? $group->update($data) : ProductGroup::create($data);
            }
        });
    }
}
