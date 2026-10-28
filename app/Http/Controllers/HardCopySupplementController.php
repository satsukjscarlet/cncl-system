<?php

namespace App\Http\Controllers;

use App\Exports\HardCopySupplementExport;
use App\Models\DistributionCenter;
use App\Models\HardCopySupplement;
use App\Models\PrintLog;
use App\Models\QualityCertificate;
use App\Models\SalesUnit;
use App\Services\DocumentNumberService;
use App\Services\HardCopyBatchCertificatePdfService;
use App\Services\HardCopyCertificatePdfService;
use App\Services\HardCopySupplementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class HardCopySupplementController extends Controller
{
    public function __construct(private HardCopySupplementService $service) {}

    private function query(Request $request)
    {
        abort_unless($this->service->canView($request->user()), 403);
        $request->validate([
            'status' => ['nullable', Rule::in(array_keys(HardCopySupplement::LABELS))],
            'work_queue' => ['nullable', 'boolean'],
            'date_basis' => ['nullable', 'in:submitted_at,approved_at,completed_at,signed_at'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => array_filter(['nullable', 'date_format:Y-m-d', $request->filled('date_from') ? 'after_or_equal:date_from' : null]),
            'print_template' => ['nullable', 'in:single,batch'],
            'keyword' => ['nullable', 'string', 'max:255'],
            'distribution_center_id' => ['nullable', 'integer'], 'sales_unit_id' => ['nullable', 'integer'],
        ]);
        $query = HardCopySupplement::visibleTo($request->user())
            ->withExists(['certificate as certificate_is_eligible' => fn ($q) => $q->hardCopySupplementEligible()])
            ->withCount('printLogs')
            ->with(['certificate.request.distributionCenter', 'certificate.request.salesUnit', 'certificate.request.customer', 'requester', 'completer']);
        if ($request->boolean('work_queue')) {
            $query->whereIn('hard_copy_supplements.id', $this->service->pendingQuery($request->user())->select('hard_copy_supplements.id'));
        }
        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('print_template')) $query->where('print_template', $request->print_template);
        if (!$request->user()->hasRole('TrungTam') && $request->filled('distribution_center_id')) {
            $query->whereHas('certificate.request', fn ($q) => $q->where('distribution_center_id', $request->distribution_center_id));
        }
        if ($request->filled('sales_unit_id')) $query->whereHas('certificate.request', fn ($q) => $q->where('sales_unit_id', $request->sales_unit_id));
        if ($request->filled('keyword')) {
            $term = '%' . trim($request->keyword) . '%';
            $id = preg_match('/^(?:BSKT-)?0*([1-9][0-9]*)$/i', trim($request->keyword), $matches) ? $matches[1] : null;
            $query->where(function ($q) use ($term, $id) {
                $q->whereHas('certificate', fn ($c) => $c->where('certificate_no', 'like', $term))
                    ->orWhereHas('certificate.request', fn ($r) => $r->where('request_no', 'like', $term)->orWhere('requester_name', 'like', $term))
                    ->orWhereHas('certificate.request.customer', fn ($c) => $c->where('customer_name', 'like', $term)->orWhere('project_name', 'like', $term))
                    ->orWhereHas('requester', fn ($u) => $u->where('name', 'like', $term))
                    ->orWhereHas('certificate.request.salesUnit', fn ($s) => $s->where('code', 'like', $term)->orWhere('name', 'like', $term));
                if ($id !== null) $q->orWhere('hard_copy_supplements.id', $id);
            });
        }
        $basis = $request->input('date_basis') ?: 'submitted_at';
        foreach (['date_from' => '>=', 'date_to' => '<='] as $key => $operator) {
            if (!$request->filled($key)) continue;
            if ($basis === 'signed_at') $query->whereHas('certificate', fn ($q) => $q->whereDate('signed_at', $operator, $request->input($key)));
            else $query->whereDate($basis, $operator, $request->input($key));
        }
        return $query;
    }

    private function filters(Request $request): array
    {
        $centers = DistributionCenter::where('is_active', true)->orderBy('name')->get();
        $salesUnits = SalesUnit::query();
        if ($request->user()->hasRole('TrungTam')) $salesUnits->where('distribution_center_id', $request->user()->distribution_center_id);
        elseif ($request->filled('distribution_center_id')) $salesUnits->where('distribution_center_id', $request->distribution_center_id);
        return ['centers' => $centers, 'salesUnits' => $salesUnits->orderBy('name')->get()];
    }

    public function index(Request $request)
    {
        $query = $this->query($request);
        $countRequest = clone $request;
        $countRequest->query->remove('status');
        $countRequest->query->remove('work_queue');
        $statusCounts = $this->query($countRequest)->toBase()->select('status')->selectRaw('COUNT(*) AS total')->groupBy('status')->pluck('total', 'status');
        [$sort, $direction] = $this->sortInput($request, ['id', 'quantity', 'status', 'submitted_at', 'approved_at', 'completed_at', 'certificate_no'], 'id');
        if ($sort === 'certificate_no') {
            $query->orderBy(QualityCertificate::select('certificate_no')->whereColumn('quality_certificates.id', 'hard_copy_supplements.quality_certificate_id'), $direction);
        } else {
            $query->orderBy('hard_copy_supplements.' . $sort, $direction);
        }
        return view('hard_copy_supplements.index', $this->filters($request) + [
            'items' => $query->orderByDesc('hard_copy_supplements.id')->paginate(20)->withQueryString(), 'statusCounts' => $statusCounts,
        ]);
    }

    public function show(Request $request, HardCopySupplement $supplement)
    {
        abort_unless($this->service->canView($request->user()), 403);
        abort_unless(HardCopySupplement::visibleTo($request->user())->whereKey($supplement->id)->exists(), 403);
        $supplement->load(['certificate.request.customer', 'certificate.request.salesUnit', 'certificate.request.distributionCenter', 'requester', 'approver', 'completer', 'events.user'])->loadCount('printLogs');
        return view('hard_copy_supplements.show', ['item' => $supplement, 'service' => $this->service,
            'validCertificate' => $supplement->certificate->allowsHardCopySupplement()]);
    }

    public function store(Request $request, QualityCertificate $qualityCertificate)
    {
        $item = $this->service->create($request->user(), $qualityCertificate, $this->requestData($request));
        return redirect()->route('hard-copy-supplements.show', $item)->with('success', 'Đã gửi yêu cầu bổ sung bản ký tươi đến DVKH.');
    }

    private function requestData(Request $request): array
    {
        return $request->validate(['quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'reason' => ['required', 'string', 'max:2000'], 'note' => ['nullable', 'string', 'max:2000']], [
            'quantity.required' => 'Vui lòng nhập số bản bổ sung.', 'quantity.integer' => 'Số bản phải là số nguyên.',
            'quantity.min' => 'Số bản phải từ 1 trở lên.', 'quantity.max' => 'Số bản tối đa 100.000.', 'reason.required' => 'Vui lòng nhập lý do bổ sung.',
        ]);
    }

    public function action(Request $request, HardCopySupplement $supplement, string $action)
    {
        $data = match ($action) {
            'resubmit' => $this->requestData($request),
            'return', 'cancel' => $request->validate(['comment' => ['required', 'string', 'max:2000'], 'confirm_stop_printing' => ['nullable', 'boolean']], ['comment.required' => 'Vui lòng nhập lý do.']),
            'complete' => $request->validate(['completion_note' => ['nullable', 'string', 'max:2000']]),
            'approve' => [],
            default => abort(404),
        };
        $this->service->transition($request->user(), $supplement, $action, $data);
        return redirect()->route('hard-copy-supplements.show', $supplement)->with('success', 'Đã cập nhật yêu cầu bổ sung bản ký tươi.');
    }

    public function print(Request $request, HardCopySupplement $supplement)
    {
        abort_unless($this->service->ptn($request->user()), 403);
        $data = $request->validate(['print_template' => ['required', 'in:single,batch']]);
        // Render before recording a successful file-open. Recheck state under lock after rendering.
        $certificate = $supplement->certificate;
        $this->service->requireValid($certificate);
        $this->service->expect($supplement->status, ['WAIT_PTN']);
        $renderer = $data['print_template'] === 'batch' ? app(HardCopyBatchCertificatePdfService::class) : app(HardCopyCertificatePdfService::class);
        $certificate->load(['request.customer', 'request.distributionCenter', 'details.product', 'creator']);
        $content = $renderer->render($certificate);
        $pageCount = $renderer->pageCount($certificate);
        DB::transaction(function () use ($request, $supplement, $data, $pageCount) {
            $certificate = QualityCertificate::lockForUpdate()->findOrFail($supplement->quality_certificate_id);
            $item = HardCopySupplement::lockForUpdate()->findOrFail($supplement->id);
            $this->service->requireValid($certificate);
            $this->service->expect($item->status, ['WAIT_PTN']);
            $previous = $item->events()->where('action', 'printed')
                ->where('data->print_template', $data['print_template'])->orderByDesc('id')->first();
            $previousPages = $previous?->data['page_count']
                ?? ($item->print_template === $data['print_template'] ? $item->page_count : null);
            if ($previousPages !== null && (int) $previousPages !== $pageCount) {
                throw ValidationException::withMessages(['supplement' => 'Số trang của mẫu in này đã thay đổi so với lần mở trước. Vui lòng liên hệ quản trị viên.']);
            }
            // Keep one reporting basis (latest opened template); events retain both templates independently.
            $item->update(['print_template' => $data['print_template'], 'page_count' => $pageCount]);
            $certificate->increment('print_count');
            PrintLog::create(['quality_certificate_id' => $certificate->id, 'hard_copy_supplement_id' => $item->id,
                'user_id' => $request->user()->id, 'reason' => $item->number . ': ' . $item->reason,
                'print_no' => $certificate->print_count, 'print_template' => $item->print_template, 'print_mode' => 'normal']);
            $this->service->record($item, $request->user(), 'printed', $item->status);
        });
        return response($content, 200, ['Content-Type' => 'application/pdf', 'Cache-Control' => 'private, no-store',
            'Content-Disposition' => 'inline; filename="' . app(DocumentNumberService::class)->safeFileName($certificate->certificate_no . '_' . $supplement->number) . '"']);
    }

    private function stats($items): array
    {
        $eligible = $items->where('certificate_is_eligible', true);
        $approved = $eligible->whereIn('status', ['WAIT_PTN', 'COMPLETED']);
        $original = $eligible->unique('quality_certificate_id')->sum(fn ($i) => $i->certificate->request?->require_hard_copy ? (int) $i->certificate->request->hard_copy_quantity : 0);
        return ['requests' => $items->count(), 'certificates' => $eligible->pluck('quality_certificate_id')->unique()->count(),
            'original' => $original, 'approved' => $approved->sum('quantity'), 'completed' => $approved->where('status', 'COMPLETED')->sum('quantity'),
            'total' => $original + $approved->sum('quantity'),
            'single_sheets' => $approved->where('print_template', 'single')->sum(fn ($i) => $i->quantity * $i->page_count),
            'batch_sheets' => $approved->where('print_template', 'batch')->sum(fn ($i) => $i->quantity * $i->page_count),
            'unassigned' => $approved->whereNull('print_template')->sum('quantity')];
    }

    public function report(Request $request)
    {
        abort_unless($request->user()->can($request->routeIs('reports.hard-copy-supplements.export') ? 'report.export' : 'report.view'), 403);
        $query = $this->query($request);
        $all = (clone $query)->get();
        $summary = $this->stats($all);
        $groups = $all->groupBy(fn ($i) => $i->certificate?->request?->distribution_center_id ?? 0)->map(fn ($rows) =>
            ['center' => $rows->first()->certificate?->request?->distributionCenter?->name ?? 'Không xác định'] + $this->stats($rows));
        $basis = $request->input('date_basis') ?: 'submitted_at';
        $monthly = $all->groupBy(fn ($i) => ($basis === 'signed_at' ? $i->certificate?->signed_at : $i->{$basis})?->format('Y-m') ?? 'Chưa có ngày')
            ->sortKeys()->map(fn ($rows, $month) => ['month' => $month] + $this->stats($rows));
        if ($request->routeIs('reports.hard-copy-supplements.export')) {
            abort_unless($request->user()->can('report.export'), 403);
            return Excel::download(new HardCopySupplementExport($all, $summary, $groups, $request->query(), $monthly), 'bao_cao_bo_sung_ky_tuoi.xlsx');
        }
        return view('hard_copy_supplements.report', $this->filters($request) + [
            'items' => $query->latest('id')->paginate(20)->withQueryString(), 'summary' => $summary, 'groups' => $groups, 'monthly' => $monthly]);
    }
}
