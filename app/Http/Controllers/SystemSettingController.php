<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLogger;
use App\Models\SystemSetting;
use Illuminate\Http\Request;

class SystemSettingController extends Controller
{
    public function index()
    {
        $autoSendEmail = SystemSetting::getValue('auto_send_email_after_sign', true);

        return view('system_settings.index', compact('autoSendEmail'));
    }

    public function update(Request $request)
    {
        $oldValue = SystemSetting::getValue('auto_send_email_after_sign', true);

        $autoSendEmail = $request->boolean('auto_send_email_after_sign');

        SystemSetting::updateOrCreate(
            ['key' => 'auto_send_email_after_sign'],
            [
                'value' => $autoSendEmail ? '1' : '0',
                'type' => 'boolean',
                'description' => 'Tự động gửi email cho khách hàng sau khi ký số/phát hành phiếu CNCL.',
            ]
        );

        ActivityLogger::log(
            'Cấu hình hệ thống',
            'update',
            'Cập nhật cấu hình tự động gửi email sau ký số',
            ['auto_send_email_after_sign' => $oldValue],
            ['auto_send_email_after_sign' => $autoSendEmail]
        );

        return redirect()
            ->route('system-settings.index')
            ->with('success', 'Cập nhật cấu hình hệ thống thành công.');
    }
}