@php
    $statusMeta = [
        'done' => ['class' => 'success', 'text' => 'Hoàn tất'],
        'current' => ['class' => 'primary', 'text' => 'Đang xử lý'],
        'pending' => ['class' => 'secondary', 'text' => 'Chưa tới bước'],
        'danger' => ['class' => 'danger', 'text' => 'Cần xử lý'],
        'skipped' => ['class' => 'light', 'text' => 'Không áp dụng'],
    ];
@endphp

<div class="card card-primary card-outline">
    <div class="card-header bg-white">
        <h3 class="card-title"><i class="fas fa-route"></i> Tiến trình xử lý</h3>
    </div>

    <div class="card-body">
        <div class="row">
            @foreach($steps as $step)
                @php
                    $meta = $statusMeta[$step['status']] ?? $statusMeta['pending'];
                @endphp

                <div class="col-lg col-md-4 col-sm-6 mb-3 mb-lg-0">
                    <div class="border rounded p-3 h-100 bg-white">
                        <div class="d-flex align-items-start">
                            <span class="btn btn-sm btn-{{ $meta['class'] }} mr-2" style="width:34px;height:34px;pointer-events:none">
                                <i class="{{ $step['icon'] }}"></i>
                            </span>
                            <div class="min-w-0">
                                <div class="font-weight-bold" style="overflow-wrap:anywhere">{{ $step['title'] }}</div>
                                <span class="badge badge-{{ $meta['class'] }}">{{ $meta['text'] }}</span>
                            </div>
                        </div>

                        <div class="small text-muted mt-2" style="overflow-wrap:anywhere">
                            {{ $step['description'] }}
                        </div>

                        <div class="small mt-2">
                            <i class="far fa-clock text-muted"></i>
                            {{ $step['time'] ? $step['time']->format('d/m/Y H:i') : '-' }}
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

