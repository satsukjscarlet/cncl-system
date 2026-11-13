@if(\App\Support\RequestSubmissionEmail::missing(auth()->user()))
    <div class="alert alert-warning" role="alert" data-account-email-warning>
        <i class="fas fa-exclamation-triangle"></i>
        {{ \App\Support\RequestSubmissionEmail::MESSAGE }}
        <a class="alert-link" href="{{ route('profile.edit') }}" target="_blank" rel="noopener">Bổ sung email</a>.
        Bạn vẫn có thể lưu nháp hoặc lưu chỉnh sửa. Sau khi cập nhật email, tải lại trang này để gửi yêu cầu.
    </div>
@endif
