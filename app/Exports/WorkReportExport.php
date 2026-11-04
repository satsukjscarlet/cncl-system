<?php

namespace App\Exports;

use App\Services\WorkReportService;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class WorkReportExport implements WithMultipleSheets
{
    public function __construct(private readonly array $report) {}

    public function sheets(): array
    {
        $report = $this->report;
        $matrix = [];
        foreach ($report['sections'] as $section) {
            foreach ($section['rows'] as $index => $row) {
                $matrix[] = array_merge([$section['label'], $index + 1, $row['label']], array_values($row['days']), [$row['total']]);
            }
            $matrix[] = array_merge([$section['label'], '', 'Tổng'], array_values($section['days']), [$section['total']]);
        }
        $headings = ['Công việc', 'STT', $report['filters']['group_by'] === 'center' ? 'Trung tâm' : 'Tài khoản'];
        foreach (range(1, $report['start']->daysInMonth) as $day) {
            $headings[] = sprintf('%02d', $day);
        }
        $headings[] = 'Tổng';
        $details = $report['events']->sortByDesc('occurred_at')->map(fn ($event) => [
            $event['occurred_at'], WorkReportService::KINDS[$event['kind']][0], WorkReportService::DEPARTMENTS[$event['department']],
            $event['actor_label'], $event['center_label'], $event['object_no'], $event['request_no'], $event['source'],
        ])->values()->all();
        $filters = $report['filters'];
        $notes = [
            ['Phạm vi truy cập', $report['scope']['label']],
            ['Tháng', $report['start']->format('m/Y')],
            ['Phòng ban', WorkReportService::DEPARTMENTS[$filters['department']] ?? 'Tất cả'],
            ['Tài khoản', $report['users']->get((int) $filters['user_id'])?->name ?? 'Tất cả'],
            ['Trung tâm', $report['centers']->get((int) $filters['distribution_center_id'])?->code ?? 'Tất cả'],
            ['Công việc', WorkReportService::KINDS[$filters['kind']][0] ?? 'Tất cả'],
            ['Cách nhóm', $filters['group_by'] === 'center' ? 'Theo Trung tâm' : 'Theo tài khoản'],
            ['Đơn vị', 'Lượt hồ sơ theo loại công việc, tài khoản và ngày; một hồ sơ lặp cùng tài khoản/ngày chỉ tính một lần.'],
            ['Tổng tháng', 'Cộng các ngày; cùng hồ sơ xử lý ngày khác hoặc tài khoản khác được tính lượt riêng. Không phải số phiếu duy nhất.'],
            ['Ký số', 'Kết quả hệ thống theo ngày ký, không gán cho người kiểm tra kết quả. Lọc tài khoản sẽ không gồm kết quả chưa xác định người ký.'],
            ['Phòng ban', 'Đơn vị phụ trách bước nghiệp vụ, không suy từ vai trò hiện tại của tài khoản.'],
            ['Dữ liệu cũ', 'Dùng lịch sử hoặc mốc nghiệp vụ; thiếu người xử lý hiển thị Chưa xác định / hệ thống. Không suy đoán theo người tạo.'],
            ['Phạm vi', 'Giữ công việc đã thực hiện kể cả hồ sơ về sau bị hủy, trả lại hoặc thay thế. Không phải báo cáo phiếu còn hiệu lực.'],
            ['Excel', 'Toàn bộ tháng theo bộ lọc chính; không giới hạn ô chi tiết hoặc trang đang xem.'],
        ];

        return [new WorkReportSheet('Theo ngay', $headings, $matrix, $report['start']),
            new WorkReportSheet('Chi tiet', ['Thời điểm', 'Công việc', 'Phòng ban', 'Người thực hiện', 'Trung tâm', 'Hồ sơ', 'Số yêu cầu', 'Nguồn dữ liệu'], $details),
            new WorkReportSheet('Dinh nghia', ['Nội dung', 'Giá trị'], $notes)];
    }
}

class WorkReportSheet extends DefaultValueBinder implements FromArray, ShouldAutoSize, WithCustomValueBinder, WithHeadings, WithStyles, WithTitle
{
    public function __construct(private readonly string $name, private readonly array $headers, private readonly array $rows, private readonly ?Carbon $month = null) {}

    public function title(): string
    {
        return $this->name;
    }

    public function headings(): array
    {
        return $this->headers;
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function bindValue(Cell $cell, mixed $value): bool
    {
        if (is_string($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->freezePane($this->month ? 'D2' : 'A2');
        if ($this->month) {
            foreach (range(1, $this->month->daysInMonth) as $day) {
                if ($this->month->copy()->day($day)->isSunday()) {
                    $column = Coordinate::stringFromColumnIndex($day + 3);
                    $sheet->getStyle($column.'1:'.$column.(count($this->rows) + 1))->getFill()->setFillType('solid')->getStartColor()->setARGB('FFFFF3CD');
                }
            }
        }

        return [1 => ['font' => ['bold' => true]]];
    }
}
