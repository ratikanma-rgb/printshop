<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | PrintShop</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Tahoma, Arial, sans-serif;
            background: #f4f6f9;
            color: #222;
        }

        .navbar {
            background: #173b69;
            color: white;
            padding: 18px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .navbar h2 {
            margin: 0;
        }

        .container {
            max-width: 1100px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .welcome {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,.08);
        }

        .welcome h1 {
            margin-top: 0;
            color: #173b69;
        }

        .role {
            display: inline-block;
            background: #fff0e3;
            color: #d46312;
            padding: 7px 13px;
            border-radius: 20px;
            font-weight: bold;
        }

        .logout-btn {
            background: #e87925;
            color: white;
            border: none;
            padding: 10px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 15px;
        }

        .logout-btn:hover {
            background: #cf651d;
        }
    </style>
    <link rel="stylesheet" href="{{ asset('css/printshop-theme.css') }}">
</head>

<body class="ps-page ps-dashboard ps-public">

<div class="navbar">
    <h2>🖨️ PrintShop</h2>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button class="logout-btn" type="submit">
            ออกจากระบบ
        </button>
    </form>
</div>

<div class="container">

    <div class="welcome">

        <h1>
            ยินดีต้อนรับ {{ auth()->user()->name }}
        </h1>

        <p>
            อีเมล: {{ auth()->user()->email }}
        </p>

        <p>
            สิทธิ์ผู้ใช้งาน:
            <span class="role">
                {{ auth()->user()->role }}
            </span>
        </p>

        <hr>

        <h3>ระบบจัดการร้านรับพิมพ์งานและถ่ายเอกสาร</h3>

        <p>
            ระบบพร้อมสำหรับการพัฒนาฟังก์ชันสั่งพิมพ์งาน
            จัดการคิว และติดตามสถานะ
        </p>

    </div>

</div>

</body>
</html>