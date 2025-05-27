<!DOCTYPE html>
<html lang="fa">

<head>
    <meta charset="UTF-8">
    <title>{{ $mailData['title'] }}</title>
</head>

<body style="background-color:rgb(182, 181, 181); color:rgb(190, 190, 190); font-family: Tahoma, Arial, sans-serif; padding: 20px; line-height: 1.6; direction: rtl; text-align: right;">

    <div style="max-width: 600px; margin: auto; background-color:rgb(46, 46, 46); padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(177, 177, 177, 0.2);">
        <h1 style="color:rgb(252, 252, 252); font-size: 24px;">{{ $mailData['title'] }}</h1>

        <p style="color:rgb(255, 255, 255); font-size: 16px;">
            {!! $mailData['body'] !!}
        </p>

        <hr style="border-color: #cccccc; margin: 30px 0;">

        <!-- <p style="color: #666666; font-size: 14px;">
            با معرفی هر یک از مشترکان جدید به info@tamasha.me یک ماه اشتراک اضافه رایگان دریافت نمایید
        </p> -->

        <p style="color: #777777; font-size: 13px; text-align: center; margin-top: 40px;">
            تمامی حقوق محفوظ است &copy; {{ date('Y') }} | {{ env('APP_NAME', 'نام سایت شما') }}
        </p>
    </div>

</body>

</html>
