<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Phiếu Chứng nhận Chất lượng</title>
</head>
<body style="font-family: Arial, sans-serif; font-size: 14px; color: #222;">
    <p>Kính gửi Quý khách hàng,</p>

    <p>
        Công ty Cổ phần Nhựa Thiếu niên Tiền Phong gửi tới Quý khách
        <strong>Phiếu Chứng nhận Chất lượng</strong>:
    </p>

    <table cellpadding="6" cellspacing="0" border="0">
        <tr>
            <td><strong>Số phiếu:</strong></td>
            <td>{{ $certificate->certificate_no }}</td>
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
            <td>
                {{ $certificate->request->delivery_date ? $certificate->request->delivery_date->format('d/m/Y') : '' }}
            </td>
        </tr>
        <tr>
            <td><strong>Số hóa đơn:</strong></td>
            <td>{{ $certificate->request->invoice_no ?? '' }}</td>
        </tr>
    </table>

    <p>
        Phiếu Chứng nhận Chất lượng được đính kèm trong email này dưới dạng file PDF.
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