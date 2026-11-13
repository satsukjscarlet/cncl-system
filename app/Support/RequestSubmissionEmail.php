<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Validation\ValidationException;

class RequestSubmissionEmail
{
    public const MESSAGE = 'Tài khoản của bạn chưa có email. Vui lòng bổ sung email tại Tài khoản của tôi trước khi gửi yêu cầu.';

    public static function missing(User $user): bool
    {
        return $user->isExternalAccount() && trim((string) $user->email) === '';
    }

    public static function validate(User $user): void
    {
        if (self::missing($user)) {
            throw ValidationException::withMessages(['account_email' => self::MESSAGE]);
        }
    }
}
