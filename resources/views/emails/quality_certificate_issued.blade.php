<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Phiếu Chứng nhận Chất lượng</title>
</head>
<body style="font-family: Arial, sans-serif; font-size: 14px; color: #222; line-height: 1.5;">
    @php
        $lookupUrl = route('quality-certificates.show', $certificate);
    @endphp

    <p>Kính gửi Trung tâm phân phối,</p>

    <p>
        Công ty Cổ phần Nhựa Thiếu niên Tiền Phong gửi tới Anh/Chị
        <strong>Phiếu Chứng nhận Chất lượng</strong> đã được ký/phát hành.
    </p>

    <table cellpadding="6" cellspacing="0" border="0">
        <tr>
            <td><strong>Số phiếu:</strong></td>
            <td>{{ $certificate->certificate_no }}</td>
        </tr>
        <tr>
            <td><strong>Số yêu cầu:</strong></td>
            <td>{{ $certificate->request->request_no ?? '' }}</td>
        </tr>
        <tr>
            <td><strong>Trung tâm:</strong></td>
            <td>{{ $certificate->request->distributionCenter->name ?? '' }}</td>
        </tr>
        <tr>
            <td><strong>Khách hàng:</strong></td>
            <td>{{ $certificate->request->customer->customer_name ?? '' }}</td>
        </tr>
        <tr>
            <td><strong>Công trình:</strong></td>
            <td>{{ $certificate->request->customer->project_name ?? '' }}</td>
        </tr>
        <tr>
            <td><strong>Ngày xuất hàng:</strong></td>
            <td>{{ $certificate->request->delivery_date ? $certificate->request->delivery_date->format('d/m/Y') : '' }}</td>
        </tr>
        <tr>
            <td><strong>Số hóa đơn:</strong></td>
            <td>{{ $certificate->request->invoice_no ?? '' }}</td>
        </tr>
    </table>

    <p>
        Phiếu Chứng nhận Chất lượng được đính kèm trong email này dưới dạng file PDF.
        Anh/Chị cũng có thể tra cứu lại phiếu trực tiếp trên hệ thống theo liên kết dưới đây.
    </p>

    <p style="margin: 22px 0;">
        <a href="{{ $lookupUrl }}"
           style="background: #007bff; color: #fff; padding: 10px 16px; text-decoration: none; border-radius: 4px; display: inline-block;">
            Tra cứu phiếu trên hệ thống
        </a>
    </p>

    <p style="font-size: 13px; color: #555;">
        Nếu không bấm được nút trên, vui lòng sao chép đường dẫn sau vào trình duyệt:<br>
        <a href="{{ $lookupUrl }}">{{ $lookupUrl }}</a>
    </p>

    <p>
        Trân trọng,<br>
        <strong>Công ty Cổ phần Nhựa Thiếu niên Tiền Phong</strong>
    </p>

    <hr>

    <p style="font-size: 12px; color: #666;">
        Đây là email được gửi tự động từ hệ thống cấp Phiếu Chứng nhận Chất lượng.
        Vui lòng không trả lời trực tiếp email này.
    </p>
</body>
</html>
