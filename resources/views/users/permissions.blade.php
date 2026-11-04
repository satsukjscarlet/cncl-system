@extends('adminlte::page')
@section('title', 'Phân quyền tài khoản')
@section('css')
<link rel="stylesheet" href="{{ asset('css/cncl-ui.css') }}">
<style>
    .account-permission-toolbar { position: sticky; top: 0; z-index: 5; background: #fff; border-bottom: 1px solid #dee2e6; padding: 12px 16px; display: flex; flex-wrap: wrap; gap: 12px; align-items: center; justify-content: space-between; }
    .account-permission-search { width: 340px; max-width: 100%; }
    .account-permission-table { min-width: 760px; }
    .account-permission-table td { vertical-align: middle; }
    .account-permission-table .permission-mode { width: 170px; }
    .account-permission-group th { background: #edf3f8; }
    .permission-metrics { display: flex; flex-wrap: wrap; gap: 16px; margin-bottom: 12px; }
    .permission-code { display: block; color: #6c757d; font-size: 12px; overflow-wrap: anywhere; }
    .permission-result { white-space: nowrap; }
    @media(max-width: 575.98px) { .account-permission-search { width: 100%; } }
</style>
@stop
@section('content_header')
<div class="d-flex flex-wrap justify-content-between align-items-center">
    <div><h1 class="m-0">Phân quyền tài khoản</h1><div class="text-muted">{{ $target->name }} · {{ $target->username }}</div></div>
    <a href="{{ route('users.index') }}" class="btn btn-outline-secondary mt-2"><i class="fas fa-arrow-left mr-1"></i> Danh sách tài khoản</a>
</div>
@stop
@section('content')
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
@if($protectedAccount)<div class="alert alert-info">Tài khoản Admin được bảo vệ để tránh khóa quyền quản trị. Màn hình này chỉ tra cứu, không thay đổi quyền riêng của Admin.</div>@endif
@unless($ready)<div class="alert alert-warning">Chưa chạy migration phân quyền tài khoản. Chưa thể lưu ngoại lệ; quyền hiện tại vẫn giữ nguyên.</div>@endunless
<div class="mb-3">
    <strong>Nhóm hiện tại:</strong> {{ $target->roles->pluck('name')->implode(', ') ?: 'Chưa gán nhóm' }}.
    <span>Ưu tiên: <strong>Chặn riêng → Cấp riêng → Kế thừa nhóm</strong>.</span>
    <p class="text-muted small mt-1 mb-0">Cấp riêng không mở rộng dữ liệu ngoài Trung tâm/phòng được phép, không bỏ qua trạng thái nghiệp vụ hay các thao tác an toàn chỉ dành cho Admin. Quyền nhạy cảm như quản lý người dùng/phân quyền chỉ nên cấp cho người được ủy quyền. Muốn trở lại quyền nhóm, chọn Kế thừa nhóm.</p>
</div>
<form method="POST" action="{{ route('users.permissions.update', $target) }}" id="account-permission-form" class="card card-primary card-outline">
    @csrf
    @method('PUT')
    <input type="hidden" name="version" value="{{ $state['version'] }}">
    <div class="account-permission-toolbar">
        <input type="search" id="permission-search" class="form-control account-permission-search" placeholder="Tìm chức năng hoặc mã quyền" aria-label="Tìm quyền">
        <div class="d-flex align-items-center">
            <span class="text-muted small mr-3" id="permission-change-count">Chưa thay đổi</span>
            @if($ready && !$protectedAccount)
                <button class="btn btn-primary"><i class="fas fa-save mr-1"></i> Lưu quyền riêng</button>
            @endif
        </div>
    </div>
    <div class="px-3 pt-3 permission-metrics small">
        <span>Kế thừa: <strong id="count-inherit">{{ collect($state['rows'])->where('mode', 'inherit')->count() }}</strong></span>
        <span class="text-success">Cấp riêng: <strong id="count-allow">{{ collect($state['rows'])->where('mode', 'allow')->count() }}</strong></span>
        <span class="text-danger">Chặn riêng: <strong id="count-deny">{{ collect($state['rows'])->where('mode', 'deny')->count() }}</strong></span>
    </div>
    <div class="table-responsive">
        <table class="table table-bordered table-hover mb-0 account-permission-table">
            <thead class="thead-light"><tr><th>Chức năng</th><th>Quyền từ nhóm</th><th>Thiết lập tài khoản</th><th>Quyền chức năng sau lưu</th></tr></thead>
            @foreach($permissionGroups as $group => $permissions)
                <tbody class="permission-group">
                    <tr class="account-permission-group"><th colspan="4">{{ $group }}</th></tr>
                    @foreach($permissions as $permission)
                        @php($row = $state['rows'][$permission['name']])
                        <tr class="permission-row" data-search="{{ $group.' '.$permission['label'].' '.$permission['name'] }}">
                            <td><label class="mb-0" for="permission-{{ $permission['name'] }}">{{ $permission['label'] }}</label><span class="permission-code">{{ $permission['name'] }}</span>@if($row['restricted'])<span class="text-muted small">Chỉ dành cho tài khoản nội bộ</span>@endif</td>
                            <td>{{ $row['inherited'] ? 'Có' : 'Không' }}</td>
                            <td><select id="permission-{{ $permission['name'] }}" class="form-control form-control-sm permission-mode" name="overrides[{{ $permission['name'] }}]" data-initial="{{ $row['mode'] }}" data-inherited="{{ $row['inherited'] ? '1' : '0' }}" data-restricted="{{ $row['restricted'] ? '1' : '0' }}" @disabled(!$ready || $protectedAccount)>
                                @foreach(['inherit' => 'Kế thừa nhóm', 'allow' => 'Cấp riêng', 'deny' => 'Chặn riêng'] as $value => $label)
                                    <option value="{{ $value }}" @selected($row['mode'] === $value) @disabled($value === 'allow' && $row['restricted'])>{{ $label }}</option>
                                @endforeach
                            </select></td>
                            <td class="permission-result {{ $row['effective'] ? 'text-success' : 'text-muted' }}">{{ $row['effective'] ? 'Được phép' : 'Không được phép' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            @endforeach
        </table>
    </div>
</form>
@stop
@section('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modes = Array.from(document.querySelectorAll('.permission-mode'));
    function update() {
        const counts = {inherit: 0, allow: 0, deny: 0};
        let changes = 0;
        modes.forEach(function (select) {
            counts[select.value]++;
            if (select.value !== select.dataset.initial) changes++;
            const allowed = select.dataset.restricted !== '1' && (select.value === 'allow' || (select.value === 'inherit' && select.dataset.inherited === '1'));
            const result = select.closest('tr').querySelector('.permission-result');
            result.textContent = allowed ? 'Được phép' : 'Không được phép';
            result.className = 'permission-result ' + (allowed ? 'text-success' : 'text-muted');
        });
        Object.keys(counts).forEach(key => document.getElementById('count-' + key).textContent = counts[key]);
        document.getElementById('permission-change-count').textContent = changes ? changes + ' quyền thay đổi' : 'Chưa thay đổi';
    }
    modes.forEach(select => select.addEventListener('change', update));
    const normalize = value => value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/[đĐ]/g, 'd').toLowerCase();
    document.getElementById('permission-search').addEventListener('input', function () {
        const term = normalize(this.value.trim());
        document.querySelectorAll('.permission-group').forEach(function (group) {
            let visible = 0;
            group.querySelectorAll('.permission-row').forEach(function (row) {
                row.hidden = !normalize(row.dataset.search).includes(term);
                if (!row.hidden) visible++;
            });
            group.hidden = visible === 0;
        });
    });
});
</script>
@stop
