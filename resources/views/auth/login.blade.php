<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>เข้าสู่ระบบ | PrintShop</title>
    <link rel="stylesheet" href="{{ asset('css/portal-shell.css') }}">
</head>
<body>
<div class="auth-page">
    <div class="auth-shell">
        <section class="auth-visual">
            <div class="brand">
                <div class="brand-logo">🖨️</div>
                <div class="brand-name">PrintShop</div>
            </div>
            <h1>ระบบรับพิมพ์งานออนไลน์</h1>
            <p>ส่งไฟล์ ติดตามสถานะงาน และชำระเงินหลังร้านตรวจสอบราคา</p>
        </section>

        <section class="auth-card">
            <h2>เข้าสู่ระบบ</h2>
            <p>กรอกอีเมลและรหัสผ่านเพื่อเข้าสู่ระบบ</p>

            @if (session('warning'))
                <div class="flash" style="background:#fff5df;border:1px solid #f2d39b;color:#8d5b09">
                    {{ session('warning') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="flash error">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('login.store') }}">
                @csrf

                <div class="field">
                    <label for="email">อีเมล</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email">
                </div>

                <div class="field">
                    <label for="password">รหัสผ่าน</label>
                    <input id="password" type="password" name="password" required autocomplete="current-password">
                </div>

                <label class="remember">
                    <input type="checkbox" name="remember" value="1"> จดจำการเข้าสู่ระบบ
                </label>

                <button class="btn btn-primary" type="submit">เข้าสู่ระบบ</button>
            </form>

            <div class="auth-link">
                ยังไม่มีบัญชี? <a href="{{ route('register') }}">สมัครสมาชิก</a>
            </div>
        </section>
    </div>
</div>
</body>
</html>
