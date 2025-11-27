<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>شهادة إتمام</title>
    <style>
        /* الخط */
        @font-face {
            font-family: 'Cairo';
            src: url("{{ public_path('fonts/Cairo-Regular.ttf') }}");
            font-weight: 400;
        }
        @font-face {
            font-family: 'Cairo';
            src: url("{{ public_path('fonts/Cairo-Bold.ttf') }}");
            font-weight: 700;
        }

        body {
            font-family: 'Cairo', sans-serif;
            margin: 0;
            padding: 0;
            width: 1123px; /* A4 Landscape */
            height: 792px;
            background-image: url("{{ $backgroundImageBase64 }}");
            background-size: 100% 100%;
            background-repeat: no-repeat;
            color: #0f172a;
        }

        /* الحاوية */
        .content-container {
            /* نطلّعها شوية لتحت حسب الخلفية بتاعتك */
            width: 60%;
            margin: 0 auto;
            text-align: center;
            padding-top: 190px;
        }

        /* عنوان الشهادة */
        .title {
            font-size: 34px;
            font-weight: 700;
            color: #2b3487; /* بنفسجي مزرق قريب من اللي في التصميم */
            margin-bottom: 6px;
            letter-spacing: .3px;
        }

        /* السطر التعريفي */
        .subtitle {
            font-size: 23px;
            font-weight: 700;
            color: #4b5563;
            margin-bottom: 28px;
        }

        /* خط فاصل ديكور */
        .divider {
            width: 110px;
            height: 3px;
            background: linear-gradient(90deg, #2b3487 0%, #0ea5e9 100%);
            margin: 0 auto 30px;
            border-radius: 999px;
        }

        /* اسم الطالب */
        .student-name {
            font-size: 28px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 10px;
        }

        /* سطر "قد حضر..." */
        .pre-course {
            font-size: 18px;
            color: #475569;
            margin-bottom: 12px;
            font-weight: 700;

        }

        /* عنوان الكورس */
        .course-title {
            font-size: 23px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 20px;
            line-height: 1.5;
        }

        /* تفاصيل الساعات والدبلومة */
        .details {
            font-size: 18px;
            font-weight: 700;
            color: #4b5563;
            line-height: 1.9;
            margin-bottom: 0;
        }

        /* البادج اللي فيها الساعات/اسم الدبلومة */
        .highlight {
            display: inline-block;
            background: linear-gradient(90deg, #0ea5e9 0%, #6366f1 100%);
            color: #fff;
            padding: 4px 14px 5px;
            border-radius: 999px;
            font-weight: 700;
            font-size:18px;
            margin: 0 4px;
        }

        /* جملة الدعاء */
        .closing-text {
            margin-top: 20px;
            font-size: 23px;
            color: #475569;
            font-weight: 700;
        }

        /* التاريخ */
        .footer-note {
            font-size: 12px;
            color: #2d2e31ff;
            font-weight: 700;
        }

        /* الرقم التسلسلي */
        .serial-number {
            font-size: 11px;
            color: #888;
            margin-top: 15px; /* مسافة بسيطة فوقه */
        }

        .footer-container {
            display: flex;
            justify-content: space-between;
            margin-top: 50px;
        }
    </style>
</head>
<body>
    <div class="content-container">

        <p class="subtitle">تشهد منصة أفق للتعليم والتدريب بأن:</p>

        <h2 class="student-name">{{ $student_name }}</h2>
        <div class="divider"></div>

        <p class="pre-course">قد أتمّ بنجاح متطلبات الكورس التدريبي، بعنوان:</p>

        <h3 class="course-title">{{ $course_name }}</h3>

        <p class="details">
            بواقع
            <span class="highlight">{{ $lectures_count }}</span>
            محاضرة.
        </p>
        <p class="details">
            واجتاز جميع المهام والتكليفات المقررة، ملتزماً بمعايير الجودة الأكاديمية ومستوى الأداء المطلوب، سائلين الله تعالى أن يوفقه في مسيرته العلمية والعملية.
        </p>


        <div class="footer-container">
            <p class="footer-note">رقم الشهادة: {{ $serial_number }}</p>
            <p class="footer-note">تاريخ الإصدار: {{ $issued_date }}</p>
        </div>
    </div>
</body>
</html>
