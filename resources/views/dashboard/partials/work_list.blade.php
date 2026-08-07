<div class="card card-outline card-secondary">
    <div class="card-header">
        <h3 class="card-title">
            <i class="{{ $list['icon'] }}"></i> {{ $list['title'] }}
        </h3>
        <div class="card-tools">
            <span class="badge badge-info">{{ $list['items']->count() }}</span>
        </div>
    </div>

    <div class="card-body table-responsive p-0">
        <table class="table table-sm table-hover mb-0 work-table">
            <thead class="thead-light">
                <tr>
                    <th style="width: 150px;">Mã</th>
                    <th>Thông tin</th>
                    <th style="width: 130px;">Trạng thái</th>
                    <th style="width: 115px;">Ngày tạo</th>
                </tr>
            </thead>
            <tbody>
                @forelse($list['items'] as $item)
                    <tr>
                        <td>
                            <a href="{{ $item['url'] }}" class="font-weight-bold">
                                {{ $item['code'] }}
                            </a>
                            @if(!empty($item['urgent']))
                                <span class="badge badge-danger ml-1">Gấp</span>
                            @endif
                        </td>
                        <td>
                            <strong>{{ $item['title'] }}</strong>
                            <div class="text-muted small">{{ $item['subtitle'] }}</div>
                        </td>
                        <td>
                            <span class="status-pill">{{ $item['status'] }}</span>
                        </td>
                        <td class="text-muted small">{{ $item['date'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">
                            {{ $list['empty'] }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
