<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Đăng nhập | {{ config('app.name', 'CNCL NTP') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --ntp-green: #77bf35;
            --ntp-green-dark: #2f7d32;
            --ntp-red: #d71920;
            --ink: #10233d;
            --muted: #607083;
            --line: #dbe4ee;
        }

        * {
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            margin: 0;
            color: var(--ink);
            background:
                linear-gradient(112deg, rgba(119, 191, 53, .10) 0%, rgba(119, 191, 53, .04) 38%, transparent 39%),
                linear-gradient(135deg, #f4f8f1 0%, #eef4f8 50%, #f8fbff 100%);
            font-family: "Segoe UI", Arial, sans-serif;
        }

        .login-page {
            min-height: 100vh;
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(400px, 520px);
        }

        .login-hero {
            position: relative;
            display: flex;
            align-items: center;
            padding: 64px clamp(48px, 7vw, 104px);
            overflow: hidden;
        }

        .login-hero::before {
            content: "";
            position: absolute;
            inset: 0;
            background:
                linear-gradient(90deg, rgba(255, 255, 255, .76), rgba(255, 255, 255, .32)),
                repeating-linear-gradient(135deg, rgba(16, 35, 61, .035) 0 1px, transparent 1px 18px);
        }

        .hero-content {
            position: relative;
            z-index: 1;
            max-width: 720px;
        }

        .brand-mark {
            display: flex;
            align-items: center;
            gap: 18px;
            margin-bottom: 48px;
        }

        .brand-logo {
            width: 116px;
            height: auto;
            filter: drop-shadow(0 14px 24px rgba(16, 35, 61, .12));
        }

        .brand-title {
            display: block;
            color: var(--ntp-red);
            font-size: 24px;
            line-height: 1.15;
            font-weight: 800;
            text-transform: uppercase;
        }

        .brand-subtitle {
            display: block;
            margin-top: 6px;
            color: var(--ntp-green-dark);
            font-size: 15px;
            line-height: 1.35;
            font-weight: 700;
        }

        .hero-title {
            max-width: 780px;
            margin: 0;
            color: #10233d;
            font-size: clamp(36px, 4.4vw, 58px);
            line-height: 1.08;
            font-weight: 850;
            letter-spacing: 0;
        }

        .hero-strip {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 34px;
        }

        .hero-strip span {
            display: inline-flex;
            align-items: center;
            min-height: 34px;
            padding: 0 15px;
            border: 1px solid rgba(47, 125, 50, .18);
            border-radius: 999px;
            background: rgba(255, 255, 255, .74);
            color: #24465f;
            font-size: 13px;
            font-weight: 800;
            box-shadow: 0 8px 22px rgba(16, 35, 61, .05);
        }

        .login-panel {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
            background: rgba(255, 255, 255, .78);
            border-left: 1px solid rgba(16, 35, 61, .08);
            backdrop-filter: blur(14px);
        }

        .login-card {
            width: 100%;
            max-width: 430px;
            padding: 34px;
            border: 1px solid rgba(16, 35, 61, .08);
            border-radius: 16px;
            background: #fff;
            box-shadow: 0 26px 70px rgba(26, 46, 64, .14);
        }

        .card-logo {
            display: flex;
            align-items: center;
            gap: 13px;
            margin-bottom: 26px;
        }

        .card-logo img {
            width: 72px;
            height: auto;
        }

        .card-logo strong {
            display: block;
            color: var(--ntp-red);
            font-size: 18px;
            line-height: 1.2;
            text-transform: uppercase;
        }

        .card-logo span {
            display: block;
            margin-top: 3px;
            color: #557086;
            font-size: 12px;
            font-weight: 700;
        }

        .card-title {
            margin: 0;
            color: #112944;
            font-size: 27px;
            line-height: 1.2;
            font-weight: 850;
        }

        .card-subtitle {
            margin: 8px 0 26px;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.5;
        }

        .form-group {
            margin-bottom: 17px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            color: #1e293b;
            font-size: 14px;
            font-weight: 800;
        }

        .form-input {
            width: 100%;
            height: 48px;
            border: 1px solid var(--line);
            border-radius: 9px;
            padding: 0 14px;
            color: #0f172a;
            background: #fcfdff;
            font-size: 15px;
            transition: border-color .15s ease, box-shadow .15s ease, background-color .15s ease;
        }

        .form-input::placeholder {
            color: #9aa7b6;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--ntp-green);
            background: #fff;
            box-shadow: 0 0 0 4px rgba(119, 191, 53, .16);
        }

        .error-box {
            display: flex;
            gap: 10px;
            align-items: flex-start;
            padding: 12px 14px;
            margin-bottom: 18px;
            border: 1px solid #fecdd3;
            border-radius: 9px;
            background: #fff1f2;
            color: #be123c;
            font-size: 14px;
            line-height: 1.45;
        }

        .error-mark {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
            width: 20px;
            height: 20px;
            border-radius: 999px;
            background: #e11d48;
            color: #fff;
            font-size: 13px;
            font-weight: 900;
        }

        .remember-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            margin: 4px 0 24px;
            color: #475569;
            font-size: 14px;
        }

        .remember-row label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }

        .remember-row input {
            width: 16px;
            height: 16px;
            accent-color: var(--ntp-green);
        }

        .forgot-link {
            color: #2563eb;
            text-decoration: none;
            font-weight: 800;
            white-space: nowrap;
        }

        .forgot-link:hover {
            text-decoration: underline;
        }

        .login-button {
            width: 100%;
            height: 49px;
            border: 0;
            border-radius: 9px;
            background: linear-gradient(135deg, var(--ntp-green) 0%, #2f9e44 100%);
            color: #fff;
            font-size: 15px;
            font-weight: 850;
            cursor: pointer;
            box-shadow: 0 14px 28px rgba(47, 158, 68, .28);
            transition: transform .15s ease, box-shadow .15s ease;
        }

        .login-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 18px 32px rgba(47, 158, 68, .34);
        }

        .login-footer {
            margin-top: 24px;
            padding-top: 18px;
            border-top: 1px solid #eef2f7;
            color: #718096;
            font-size: 12px;
            line-height: 1.45;
            text-align: center;
        }

        @media (max-width: 900px) {
            .login-page {
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 28px 16px;
            }

            .login-hero {
                display: none;
            }

            .login-panel {
                width: 100%;
                padding: 0;
                border-left: none;
                background: transparent;
                backdrop-filter: none;
            }

            .login-card {
                max-width: 440px;
                padding: 28px 22px;
                border-radius: 14px;
            }

            .card-logo {
                justify-content: center;
                text-align: left;
            }

            .remember-row {
                align-items: flex-start;
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>

<body>
    <main class="login-page">
        <section class="login-hero" aria-label="Giới thiệu hệ thống">
            <div class="hero-content">
                <div class="brand-mark">
                    <img class="brand-logo" src="{{ asset('images/logo.png') }}" alt="Tiền Phong Plastic">
                    <div>
                        <strong class="brand-title">CNCL NTP</strong>
                        <span class="brand-subtitle">Hệ thống quản lý Phiếu Chứng nhận Chất lượng</span>
                    </div>
                </div>

                <h1 class="hero-title">Số hóa quy trình cấp Phiếu chứng nhận chất lượng nhanh chóng, chính xác, minh bạch</h1>

                <div class="hero-strip">
                    <span>Tập trung</span>
                    <span>Minh bạch</span>
                    <span>Xuyên suốt</span>
                    <span>Dễ truy vết</span>
                </div>
            </div>
        </section>

        <section class="login-panel">
            <div class="login-card">
                <div class="card-logo">
                    <img src="{{ asset('images/logo.png') }}" alt="Tiền Phong Plastic">
                    <div>
                        <strong>CNCL NTP</strong>
                        <span>Tiền Phong Plastic</span>
                    </div>
                </div>

                <h2 class="card-title">Đăng nhập</h2>
                <p class="card-subtitle">Nhập tài khoản được cấp để truy cập hệ thống CNCL.</p>

                <x-auth-session-status class="mb-4" :status="session('status')" />

                @if ($errors->any())
                    <div class="error-box">
                        <span class="error-mark" aria-hidden="true">!</span>
                        <div>
                            @foreach ($errors->all() as $error)
                                <div>{{ $error }}</div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" novalidate>
                    @csrf

                    <div class="form-group">
                        <label class="form-label" for="username">Tên đăng nhập</label>
                        <input id="username" class="form-input" type="text" name="username"
                            value="{{ old('username') }}" required autofocus autocomplete="username"
                            placeholder="Tên đăng nhập">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password">Mật khẩu</label>
                        <input id="password" class="form-input" type="password" name="password" required
                            autocomplete="current-password" placeholder="Nhập mật khẩu">
                    </div>

                    <div class="remember-row">
                        <label for="remember_me">
                            <input id="remember_me" type="checkbox" name="remember">
                            <span>Ghi nhớ đăng nhập</span>
                        </label>

                        @if (Route::has('password.request'))
                            <a class="forgot-link" href="{{ route('password.request') }}">Quên mật khẩu?</a>
                        @endif
                    </div>

                    <button type="submit" class="login-button">Đăng nhập hệ thống</button>
                </form>

                <div class="login-footer">
                    Công ty Cổ phần Nhựa Thiếu niên Tiền Phong
                </div>
            </div>
        </section>
    </main>
</body>

</html>
