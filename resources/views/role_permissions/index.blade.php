@extends('adminlte::page')

@section('title', 'Phân quyền')
@section('css')
    <link rel="stylesheet" href="{{ asset('css/cncl-ui.css?v=20260611-3') }}">
@stop

@section('content_header')
<div class="d-flex flex-wrap justify-content-between align-items-center">
    <div>
        <h1 class="m-0">Phân quyền</h1>
        <small class="text-muted">Cấu hình quyền truy cập chức năng theo từng vai trò</small>
    </div>
</div>
@stop

@section('content')
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-circle"></i> Dữ liệu phân quyền chưa hợp lệ.
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
@endif

<div class="row">
    @foreach($roles as $role)
        @php
            $assigned = $role->permissions->pluck('name')->all();
            $formId = 'role-permission-form-' . $role->id;
            $isAdmin = $role->name === 'Admin';
        @endphp

        <div class="col-lg-6">
            <div class="card card-primary card-outline">
                <div class="card-header bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="card-title mb-0">
                                <i class="fas fa-user-shield"></i> {{ $role->name }}
                            </h3>
                            <div class="text-muted small mt-1">
                                {{ count($assigned) }} quyền đang gán
                            </div>
                        </div>

                        @if($isAdmin)
                            <span class="badge badge-danger">Toàn quyền</span>
                        @else
                            <button type="submit" form="{{ $formId }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-save"></i> Lưu
                            </button>
                        @endif
                    </div>
                </div>

                <form id="{{ $formId }}" method="POST" action="{{ route('role-permissions.update', $role) }}">
                    @csrf
                    @method('PUT')

                    <div class="card-body">
                        @if($isAdmin)
                            <div class="alert alert-info">
                                Vai trò Admin luôn được giữ toàn bộ quyền để tránh khóa hệ thống.
                            </div>
                        @endif

                        <div class="mb-3">
                            <button type="button"
                                class="btn btn-outline-secondary btn-sm js-check-all"
                                data-target="{{ $formId }}"
                                {{ $isAdmin ? 'disabled' : '' }}>
                                <i class="fas fa-check-square"></i> Chọn tất cả
                            </button>

                            <button type="button"
                                class="btn btn-outline-secondary btn-sm js-uncheck-all"
                                data-target="{{ $formId }}"
                                {{ $isAdmin ? 'disabled' : '' }}>
                                <i class="far fa-square"></i> Bỏ chọn
                            </button>
                        </div>

                        <div class="accordion" id="accordion-role-{{ $role->id }}">
                            @foreach($permissionGroups as $groupName => $permissions)
                                @php
                                    $groupId = 'role-' . $role->id . '-group-' . $loop->index;
                                    $checkedCount = collect($permissions)
                                        ->whereIn('name', $assigned)
                                        ->count();
                                @endphp

                                <div class="card mb-2">
                                    <div class="card-header py-2" id="{{ $groupId }}-heading">
                                        <button class="btn btn-link btn-sm p-0 text-left" type="button"
                                            data-toggle="collapse"
                                            data-target="#{{ $groupId }}"
                                            aria-expanded="{{ $loop->first ? 'true' : 'false' }}">
                                            <strong>{{ $groupName }}</strong>
                                            <span class="badge badge-light ml-1">
                                                {{ $checkedCount }}/{{ count($permissions) }}
                                            </span>
                                        </button>
                                    </div>

                                    <div id="{{ $groupId }}"
                                        class="collapse {{ $loop->first ? 'show' : '' }}"
                                        data-parent="#accordion-role-{{ $role->id }}">
                                        <div class="card-body py-2">
                                            @foreach($permissions as $permission)
                                                <div class="custom-control custom-checkbox mb-2">
                                                    <input type="checkbox"
                                                        name="permissions[]"
                                                        value="{{ $permission['name'] }}"
                                                        class="custom-control-input"
                                                        id="role-{{ $role->id }}-permission-{{ $permission['name'] }}"
                                                        {{ in_array($permission['name'], $assigned, true) ? 'checked' : '' }}
                                                        {{ $isAdmin ? 'disabled' : '' }}>
                                                    <label class="custom-control-label"
                                                        for="role-{{ $role->id }}-permission-{{ $permission['name'] }}">
                                                        {{ $permission['label'] }}
                                                        <span class="text-muted small">({{ $permission['name'] }})</span>
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    @if(!$isAdmin)
                        <div class="card-footer text-right">
                            <button class="btn btn-primary">
                                <i class="fas fa-save"></i> Lưu phân quyền
                            </button>
                        </div>
                    @endif
                </form>
            </div>
        </div>
    @endforeach
</div>
@stop

@section('js')
<script>
    document.querySelectorAll('.js-check-all').forEach(function (button) {
        button.addEventListener('click', function () {
            document.querySelectorAll('#' + button.dataset.target + ' input[type="checkbox"]:not(:disabled)')
                .forEach(function (checkbox) {
                    checkbox.checked = true;
                });
        });
    });

    document.querySelectorAll('.js-uncheck-all').forEach(function (button) {
        button.addEventListener('click', function () {
            document.querySelectorAll('#' + button.dataset.target + ' input[type="checkbox"]:not(:disabled)')
                .forEach(function (checkbox) {
                    checkbox.checked = false;
                });
        });
    });
</script>
@stop
