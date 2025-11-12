<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta charset="UTF-8">
    <style>
        @page {
            size: A4 landscape;
            margin: 0;
        }
        @font-face {
            font-family: 'Tajawal';
            src: url("{{ public_path('fonts/Tajawal-Regular.ttf') }}") format('truetype');
            font-weight: normal;
            font-style: normal;
        }
        body {
            font-family: 'Tajawal', sans-serif;
            margin: 0;
            padding: 0;
            /* !! حل مشكلة الصفحتين: تحديد أبعاد ثابتة !! */
            height: 792px; /* ارتفاع A4 landscape بالـ pixels */
            width: 1123px; /* عرض A4 landscape بالـ pixels */
            overflow: hidden; /* منع أي محتوى زائد */
        }
        .background-image {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
        }
        .content-container {
            position: relative;
            z-index: 10;
            text-align: center;
            /* !! استخدام PX بدل % للتحكم الدقيق !! */
            padding-top: 140px;
            width: 800px; /* تحديد عرض ثابت */
            margin: 0 auto;
        }
        h1 {
            font-size: 32px;
            font-weight: bold;
            color: #333;
        }
        h2 {
            font-size: 28px;
            font-weight: bold;
            color: #0056b3;
        }
        p {
            font-size: 18px;
            line-height: 1.6;
            color: #555;
        }
        h3 {
            font-size: 22px;
            font-weight: bold;
            color: #000;
            margin-top: 20px;
            margin-bottom: 20px;
        }
        .footer-note {
             font-size: 16px;
             margin-top: 40px;
        }
    </style>
</head>
<body>
    <img src="{{ public_path('storage/certificate_bg.svg') }}" class="background-image">

    <div class="content-container">
        <h1>{!! $title_text !!}</h1>

        <h2>{!! $student_name !!}</h2>

        <p>{!! $p1_text !!}</p>

        <h3>{!! $course_name !!}</h3>

        <p>
            {!! $course_hours !!}
            <br>
            {!! $p2_line1_text !!}
            <br>
            {!! $p2_line2_text !!}
            <br>
            {!! $p2_line3_text !!}
        </p>

        <p class="footer-note">
            {!! $footer_text !!} {!! $completion_date !!}
        </p>
    </div>
</body>
</html>