<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Quality certificate email recipients
    |--------------------------------------------------------------------------
    |
    | Phiếu CNCL đã ký sẽ gửi chính cho email tài khoản Trung tâm phân phối
    | đã tạo yêu cầu. Email khách hàng chỉ đưa vào CC nếu có.
    | Các nhóm dưới đây được đưa vào CC. Có thể cấu hình nhanh bằng .env
    | với nhiều email ngăn cách bằng dấu phẩy.
    |
    */

    'quality_certificate' => [
        'cc_customer_email' => true,

        'cc' => [
            'dvkh' => array_filter(array_map('trim', explode(',', env('CNCL_CERT_MAIL_CC_DVKH', '')))),
            'ptn' => array_filter(array_map('trim', explode(',', env('CNCL_CERT_MAIL_CC_PTN', '')))),
            'extra' => array_filter(array_map('trim', explode(',', env('CNCL_CERT_MAIL_CC_EXTRA', '')))),
        ],
    ],
];
