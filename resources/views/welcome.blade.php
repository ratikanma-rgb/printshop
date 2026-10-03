<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PrintShop | ระบบรับพิมพ์งานออนไลน์</title>
    <style>
        *{box-sizing:border-box}body{margin:0;font-family:Tahoma,Arial,sans-serif;background:linear-gradient(135deg,#f4f7fb,#eef3f9);color:#1f2937}.nav{height:72px;background:#173b69;color:#fff;display:flex;align-items:center;justify-content:space-between;padding:0 7%;box-shadow:0 2px 12px rgba(0,0,0,.12)}.brand{font-size:23px;font-weight:bold}.nav-actions{display:flex;gap:10px}.btn{display:inline-block;text-decoration:none;padding:11px 18px;border-radius:9px;font-weight:bold}.btn-login{background:#ef7d22;color:#fff}.btn-register{border:1px solid rgba(255,255,255,.7);color:#fff}.hero{max-width:1180px;margin:0 auto;min-height:calc(100vh - 72px);padding:70px 24px;display:grid;grid-template-columns:1.2fr .8fr;gap:50px;align-items:center}.eyebrow{color:#ef7d22;font-weight:bold;margin-bottom:12px}.hero h1{color:#173b69;font-size:48px;line-height:1.18;margin:0 0 18px}.hero p{font-size:18px;line-height:1.8;color:#5b6472;max-width:680px}.cta{display:flex;gap:12px;margin-top:28px;flex-wrap:wrap}.cta .primary{background:#173b69;color:#fff}.cta .secondary{background:#ef7d22;color:#fff}.card{background:#fff;border-radius:22px;padding:30px;box-shadow:0 12px 35px rgba(23,59,105,.13)}.card h2{margin-top:0;color:#173b69}.feature{display:flex;gap:13px;padding:14px 0;border-bottom:1px solid #edf0f4}.feature:last-child{border-bottom:0}.icon{width:42px;height:42px;flex:0 0 42px;border-radius:12px;background:#fff2e8;display:flex;align-items:center;justify-content:center;font-size:20px}.feature strong{display:block;color:#173b69;margin-bottom:4px}.feature span{font-size:14px;color:#6b7280;line-height:1.5}@media(max-width:850px){.hero{grid-template-columns:1fr;padding-top:45px}.hero h1{font-size:36px}.nav{padding:0 20px}.nav-actions .btn-register{display:none}}
    </style>
    <link rel="stylesheet" href="{{ asset('css/printshop-theme.css') }}">
</head>
<body class="ps-page ps-welcome ps-public">
<div class="nav">
    <div class="brand">🖨️ PrintShop</div>
    <div class="nav-actions">
        @auth
            <a class="btn btn-login" href="{{ route('dashboard') }}">เข้าสู่ Dashboard</a>
        @else
            <a class="btn btn-register" href="{{ route('register') }}">สมัครสมาชิก</a>
            <a class="btn btn-login" href="{{ route('login') }}">เข้าสู่ระบบ</a>
        @endauth
    </div>
</div>
<main class="hero">
    <section>
        <div class="eyebrow">PRINTSHOP MANAGEMENT SYSTEM</div>
        <h1>ระบบรับพิมพ์งานและถ่ายเอกสารออนไลน์</h1>
        <p>ส่งไฟล์ เลือกรูปแบบงาน ติดตามคิว ชำระเงิน และรับการแจ้งเตือนสถานะได้ในระบบเดียว พร้อมเครื่องมือสำหรับพนักงานและผู้ดูแลระบบ</p>
        <div class="cta">
            @auth
                <a class="btn primary" href="{{ route('dashboard') }}">ไปที่ Dashboard</a>
            @else
                <a class="btn primary" href="{{ route('login') }}">เข้าสู่ระบบ</a>
                <a class="btn secondary" href="{{ route('register') }}">สมัครสมาชิกใหม่</a>
            @endauth
        </div>
    </section>
    <aside class="card">
        <h2>ฟังก์ชันหลัก</h2>
        <div class="feature"><div class="icon">📄</div><div><strong>ส่งงานออนไลน์</strong><span>อัปโหลด PDF, Word และรูปภาพ พร้อมกำหนดรายละเอียดการพิมพ์</span></div></div>
        <div class="feature"><div class="icon">🔔</div><div><strong>ติดตามสถานะ</strong><span>แจ้งเตือนเมื่อร้านตรวจงาน รับชำระเงิน เริ่มพิมพ์ และพร้อมรับงาน</span></div></div>
        <div class="feature"><div class="icon">🖨️</div><div><strong>เครื่องมือพนักงาน</strong><span>เปิดไฟล์ สั่งพิมพ์ ตรวจสลิป และจัดการ Workflow ของงาน</span></div></div>
        <div class="feature"><div class="icon">📊</div><div><strong>บริหารร้าน</strong><span>จัดการบริการ ผู้ใช้งาน สิทธิ์ และรายงานยอดงาน/รายรับ</span></div></div>
    </aside>
</main>
</body>
</html>
