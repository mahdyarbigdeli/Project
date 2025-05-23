<!DOCTYPE html>
<html lang="fa">

<head>
    <meta charset="UTF-8">
    <title>{{ $mailData['title'] }}</title>
</head>

<body style="background-color: #333; color: #ffffff; font-family: Tahoma, Arial, sans-serif; padding: 20px; line-height: 1.6; direction: rtl; text-align: right;">

    <div style="max-width: 600px; margin: auto; background-color: #2a2a2a; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.4);">
        <h1 style="color: #ffffff; font-size: 24px;">{{ $mailData['title'] }}</h1>

        <p style="color: #cccccc; font-size: 16px;">
            {{ $mailData['body'] }}
        </p>

        <hr style="border-color: #444; margin: 30px 0;">

        <p style="color: #999999; font-size: 14px;">
            اگر این ایمیل به اشتباه برای شما ارسال شده است، لطفاً آن را نادیده بگیرید. در غیر این صورت، برای هرگونه سوال یا پشتیبانی با ما در تماس باشید.
        </p>

        <p style="color: #666666; font-size: 13px; text-align: center; margin-top: 40px;">
            تمامی حقوق محفوظ است &copy; {{ date('Y') }} | {{ env('APP_NAME', 'نام سایت شما') }}
        </p>
    </div>

</body>

</html>
