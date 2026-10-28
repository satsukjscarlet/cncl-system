<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class HardCopySupplementExport implements WithMultipleSheets
{
    public function __construct(private $items, private array $summary, private $groups, private array $filters, private $monthly) {}
    public function sheets(): array
    {
        return [
            new HardCopySupplementSheet('Theo thang', ['Tháng theo loại ngày đã lọc', 'Lượt yêu cầu', 'Phiếu CNCL không trùng trong tháng', 'Bổ sung đã duyệt', 'Bổ sung hoàn tất', 'Tờ In đơn dự kiến', 'Tờ In bộ dự kiến'],
                $this->monthly->map(fn ($r) => [$r['month'], $r['requests'], $r['certificates'], $r['approved'], $r['completed'], $r['single_sheets'], $r['batch_sheets']])->values()->all()),
            new HardCopySupplementSheet('Tong hop', ['Chỉ tiêu', 'Giá trị'], [
                ['Số lượt yêu cầu', $this->summary['requests']], ['Số phiếu CNCL không trùng', $this->summary['certificates']],
                ['Số bản ban đầu của các phiếu trong kết quả', $this->summary['original']],
                ['Bổ sung đã duyệt', $this->summary['approved']], ['Bổ sung đã hoàn tất (nằm trong đã duyệt)', $this->summary['completed']],
                ['Tổng nhu cầu được duyệt', $this->summary['total']], ['Tờ In đơn bổ sung dự kiến', $this->summary['single_sheets']],
                ['Tờ In bộ bổ sung dự kiến', $this->summary['batch_sheets']], ['Bản bổ sung chưa chọn mẫu', $this->summary['unassigned']],
                ['Bộ lọc', json_encode($this->filters, JSON_UNESCAPED_UNICODE)],
                ['Định nghĩa', 'Không lấy số lượt mở file làm số bản in. Mẫu in và số tờ dự kiến căn cứ mẫu mở gần nhất, không cộng đôi khi mở cả hai mẫu. Bản ban đầu chỉ tính một lần/phiếu trong kết quả, không phải phát sinh trong kỳ. Phiếu hủy không cộng vào số bản hợp lệ.'],
            ]),
            new HardCopySupplementSheet('Theo trung tam', ['Trung tâm', 'Lượt yêu cầu', 'Phiếu CNCL', 'Bản ban đầu', 'Bổ sung đã duyệt', 'Bổ sung hoàn tất', 'Tổng được duyệt', 'Tờ In đơn dự kiến', 'Tờ In bộ dự kiến', 'Bản chưa chọn mẫu'],
                $this->groups->map(fn ($r) => [$r['center'], $r['requests'], $r['certificates'], $r['original'], $r['approved'], $r['completed'], $r['total'], $r['single_sheets'], $r['batch_sheets'], $r['unassigned']])->values()->all()),
            new HardCopySupplementSheet('Chi tiet', ['Yêu cầu bổ sung', 'Phiếu CNCL', 'Trung tâm', 'Đơn vị bán hàng', 'Số bản', 'Trạng thái', 'Ngày gửi', 'Ngày duyệt', 'Ngày hoàn tất', 'Mẫu mở gần nhất', 'Số trang/mẫu', 'Tờ dự kiến', 'Người yêu cầu', 'Lý do', 'Ghi chú hoàn tất'],
                $this->items->map(fn ($i) => [$i->number, $i->certificate?->certificate_no, $i->certificate?->request?->distributionCenter?->name,
                    $i->certificate?->request?->salesUnit?->name, $i->quantity, $i->status_label, $i->submitted_at?->format('d/m/Y H:i'),
                    $i->approved_at?->format('d/m/Y H:i'), $i->completed_at?->format('d/m/Y H:i'), $i->print_template,
                    $i->page_count, $i->page_count ? $i->quantity * $i->page_count : null, $i->requester?->name, $i->reason, $i->completion_note])->all()),
        ];
    }
}

class HardCopySupplementSheet extends DefaultValueBinder implements FromArray, WithHeadings, WithTitle, ShouldAutoSize, WithCustomValueBinder
{
    public function __construct(private string $name, private array $headers, private array $rows) {}
    public function title(): string { return $this->name; }
    public function headings(): array { return $this->headers; }
    public function array(): array { return $this->rows; }
    public function bindValue(Cell $cell, mixed $value): bool
    {
        if (is_string($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);
            return true;
        }
        return parent::bindValue($cell, $value);
    }
}
