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
            --ntp-green: #78c33d;
            --ntp-green-dark: #23783a;
            --ntp-red: #d71920;
            --ink: #10233d;
            --muted: #64748b;
            --line: #d7e0ea;
        }

        * {
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            margin: 0;
            color: var(--ink);
            background:
                radial-gradient(circle at 12% 16%, rgba(255, 255, 255, .96) 0 130px, transparent 132px),
                radial-gradient(circle at 82% 82%, rgba(215, 25, 32, .12) 0 190px, transparent 192px),
                linear-gradient(135deg, #eaf6df 0%, #f8fbf4 38%, #eef7ff 100%);
            font-family: "Segoe UI", Arial, sans-serif;
        }

        .login-page {
            min-height: 100vh;
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
        }

        .login-hero {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: clamp(36px, 5vw, 72px);
            overflow: hidden;
        }

        .login-hero::before {
            content: "";
            position: absolute;
            inset: 24px 0 24px 24px;
            border-radius: 0 36px 36px 0;
            background:
                linear-gradient(135deg, rgba(35, 120, 58, .92), rgba(120, 195, 61, .82)),
                repeating-linear-gradient(135deg, rgba(255, 255, 255, .12) 0 1px, transparent 1px 18px);
            box-shadow: 0 28px 80px rgba(22, 101, 52, .22);
        }

        .login-hero::after {
            content: "";
            position: absolute;
            right: 10%;
            bottom: 9%;
            width: 260px;
            height: 260px;
            border: 34px solid rgba(255, 255, 255, .14);
            border-radius: 999px;
        }

        .hero-content {
            position: relative;
            z-index: 1;
            width: min(100%, 520px);
            color: #fff;
        }

        .brand-mark {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 34px;
        }

        .brand-logo-wrap {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 118px;
            height: 82px;
            border-radius: 20px;
            background: rgba(255, 255, 255, .94);
            box-shadow: 0 20px 44px rgba(15, 23, 42, .18);
        }

        .brand-logo {
            width: 98px;
            height: auto;
        }

        .brand-title {
            display: block;
            color: #fff;
            font-size: 25px;
            line-height: 1.12;
            font-weight: 900;
            text-transform: uppercase;
        }

        .brand-subtitle {
            display: block;
            margin-top: 6px;
            color: rgba(255, 255, 255, .88);
            font-size: 14px;
            line-height: 1.35;
            font-weight: 700;
        }

        .hero-title {
            max-width: 520px;
            margin: 0;
            color: #fff;
            font-size: clamp(28px, 3.3vw, 43px);
            line-height: 1.14;
            font-weight: 900;
            letter-spacing: 0;
        }

        .hero-copy {
            max-width: 460px;
            margin: 18px 0 0;
            color: rgba(255, 255, 255, .9);
            font-size: 15px;
            line-height: 1.65;
            font-weight: 600;
        }

        .hero-strip {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
            margin-top: 30px;
            max-width: 440px;
        }

        .hero-strip span {
            display: inline-flex;
            align-items: center;
            min-height: 42px;
            padding: 0 15px;
            border: 1px solid rgba(255, 255, 255, .24);
            border-radius: 10px;
            background: rgba(255, 255, 255, .14);
            color: #fff;
            font-size: 13px;
            font-weight: 850;
            backdrop-filter: blur(8px);
        }

        .login-panel {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: clamp(32px, 5vw, 72px);
            background:
                linear-gradient(180deg, rgba(255, 255, 255, .92), rgba(255, 255, 255, .76)),
                radial-gradient(circle at top right, rgba(120, 195, 61, .18), transparent 42%);
        }

        .login-card {
            width: min(100%, 540px);
            padding: 44px 46px;
            border: 1px solid rgba(16, 35, 61, .09);
            border-radius: 22px;
            background: rgba(255, 255, 255, .96);
            box-shadow: 0 30px 86px rgba(26, 46, 64, .16);
        }

        .card-logo {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 30px;
        }

        .card-logo img {
            width: 88px;
            height: auto;
        }

        .card-logo strong {
            display: block;
            color: var(--ntp-red);
            font-size: 21px;
            line-height: 1.2;
            text-transform: uppercase;
        }

        .card-logo span {
            display: block;
            margin-top: 4px;
            color: var(--ntp-green-dark);
            font-size: 13px;
            font-weight: 800;
        }

        .card-title {
            margin: 0;
            color: #112944;
            font-size: 35px;
            line-height: 1.15;
            font-weight: 900;
        }

        .card-subtitle {
            margin: 10px 0 30px;
            color: var(--muted);
            font-size: 15px;
            line-height: 1.55;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 9px;
            color: #1e293b;
            font-size: 15px;
            font-weight: 850;
        }

        .form-input {
            width: 100%;
            height: 56px;
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 0 16px;
            color: #0f172a;
            background: #fbfdff;
            font-size: 16px;
            transition: border-color .15s ease, box-shadow .15s ease, background-color .15s ease;
        }

        .form-input::placeholder {
            color: #9aa7b6;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--ntp-green);
            background: #fff;
            box-shadow: 0 0 0 4px rgba(120, 195, 61, .18);
        }

        .error-box {
            display: flex;
            gap: 11px;
            align-items: flex-start;
            padding: 13px 15px;
            margin-bottom: 20px;
            border: 1px solid #fecdd3;
            border-radius: 12px;
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
            width: 22px;
            height: 22px;
            border-radius: 999px;
            background: #e11d48;
            color: #fff;
            font-size: 14px;
            font-weight: 900;
        }

        .remember-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            margin: 6px 0 28px;
            color: #475569;
            font-size: 14px;
        }

        .remember-row label {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            cursor: pointer;
        }

        .remember-row input {
            width: 17px;
            height: 17px;
            accent-color: var(--ntp-green);
        }

        .forgot-link {
            color: #1769d2;
            text-decoration: none;
            font-weight: 850;
            white-space: nowrap;
        }

        .forgot-link:hover {
            text-decoration: underline;
        }

        .login-button {
            width: 100%;
            height: 56px;
            border: 0;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--ntp-green) 0%, #279245 100%);
            color: #fff;
            font-size: 16px;
            font-weight: 900;
            cursor: pointer;
            box-shadow: 0 16px 32px rgba(39, 146, 69, .3);
            transition: transform .15s ease, box-shadow .15s ease;
        }

        .login-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 20px 38px rgba(39, 146, 69, .35);
        }

        .login-footer {
            margin-top: 26px;
            padding-top: 18px;
            border-top: 1px solid #eef2f7;
            color: #718096;
            font-size: 12px;
            line-height: 1.45;
            text-align: center;
        }

        @media (max-width: 980px) {
            .login-page {
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 28px 16px;
                background:
                    linear-gradient(135deg, rgba(35, 120, 58, .9), rgba(120, 195, 61, .72)),
                    linear-gradient(135deg, #eaf6df, #eef7ff);
            }

            .login-hero {
                display: none;
            }

            .login-panel {
                width: 100%;
                padding: 0;
                background: transparent;
            }

            .login-card {
                width: min(100%, 480px);
                padding: 32px 24px;
                border-radius: 18px;
            }

            .card-logo {
                justify-content: center;
                text-align: left;
            }

            .card-title,
            .card-subtitle {
                text-align: center;
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
                    <span class="brand-logo-wrap">
                        <img class="brand-logo" src="{{ asset('images/logo.png') }}" alt="Tiền Phong Plastic">
                    </span>
                    <div>
                        <strong class="brand-title">CNCL NTP</strong>
                        <span class="brand-subtitle">Hệ thống quản lý Phiếu Chứng nhận Chất lượng</span>
                    </div>
                </div>

                <h1 class="hero-title">Số hóa quy trình cấp phiếu chính xác và dễ kiểm soát</h1>
                <p class="hero-copy">Theo dõi yêu cầu, lập phiếu, ký số và gửi kết quả trong một hệ thống tập trung.</p>

                <div class="hero-strip">
                    <span>Tập trung dữ liệu</span>
                    <span>Minh bạch trạng thái</span>
                    <span>Ký số SmartCA</span>
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
                <p class="card-subtitle">Nhập tài khoản được cấp để truy cập hệ thống quản lý phiếu CNCL.</p>

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
                            placeholder="Nhập tên đăng nhập">
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
