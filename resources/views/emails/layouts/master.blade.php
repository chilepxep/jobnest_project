<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>@yield('title', 'Thông báo từ JobNest')</title>
    <style>
    body {
        font-family: Arial, sans-serif;
        background-color: #f4f4f9;
        margin: 0;
        padding: 0;
    }

    .email-container {
        max-width: 600px;
        margin: 20px auto;
        background: #ffffff;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    }

    .header {
        background-color: #696cff43;
        padding: 20px;
        text-align: center;
    }

    .header img {
        max-height: 50px;
    }

    .body {
        padding: 30px;
        color: #333333;
        line-height: 1.6;
    }

    .footer {
        background-color: #f8f9fa;
        padding: 15px;
        text-align: center;
        font-size: 12px;
        color: #888888;
        border-top: 1px solid #eeeeee;
    }

    .btn {
        display: inline-block;
        padding: 12px 25px;
        background-color: #696cff;
        color: #ffffff !important;
        text-decoration: none;
        border-radius: 5px;
        font-weight: bold;
        margin-top: 15px;
    }
    </style>
</head>

<body>
    <div class="email-container">
        <!-- Header dùng chung -->
        <div class="header">
            <img src="{{ asset('images/logo_jobnest.png') }}" alt="JobNest Logo">
        </div>

        <!-- Nội dung thay đổi theo từng loại mail sẽ được bơm vào đây -->
        <div class="body">
            @yield('content')
        </div>

        <!-- Footer dùng chung -->
        <div class="footer">
            © {{ date('Y') }} JobNest - Nền tảng tuyển dụng hàng đầu.<br>
            Bạn nhận được email này vì đã sử dụng dịch vụ của chúng tôi.
        </div>
    </div>
</body>

</html>