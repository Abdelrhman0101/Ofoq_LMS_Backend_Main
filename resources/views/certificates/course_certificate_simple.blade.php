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
            padding-top: 165px;
        }

        /* عنوان الشهادة */
        .title {
            font-size: 36px;
            font-weight: 700;
            color: #2b3487; /* بنفسجي مزرق قريب من اللي في التصميم */
            margin-bottom: 6px;
            letter-spacing: .3px;
        }

        /* السطر التعريفي */
        .subtitle {
            font-size: 25px;
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
            font-size: 30px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 10px;
        }

        /* سطر "قد حضر..." */
        .pre-course {
            font-size: 20px;
            color: #475569;
            margin-bottom: 12px;
            font-weight: 700;

        }

        /* عنوان الكورس */
        .course-title {
            font-size: 25px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 20px;
            line-height: 1.5;
        }

        /* تفاصيل الساعات والدبلومة */
        .details {
            font-size: 20px;
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
            font-size:20px;
            margin: 0 4px;
        }

        /* جملة الدعاء */
        .closing-text {
            margin-top: 20px;
            font-size: 25px;
            color: #475569;
            font-weight: 700;
        }

        /* التاريخ */
        .footer-note {
            font-size: 14px;
            color: #2d2e31ff;
            margin-top: 25px;
            font-weight: 700;
        }

        /* الرقم التسلسلي */
        .serial-number {
            font-size: 13px;
            color: #888;
            margin-top: 15px; /* مسافة بسيطة فوقه */
        }
    </style>
</head>
<body>
    <div class="content-container">
        <h1 class="title">شــهــادة</h1>
        <p class="subtitle">تـشـهـد مـنـصـة أفــق لـلـتـعـلـيـم والـتـدريـب أن الـطـالـب</p>
        <div class="divider"></div>

        <h2 class="student-name">{{ $student_name }}</h2>

        <p class="pre-course">قد حضر المقرر الدراسي</p>

        <h3 class="course-title">{{ $course_name }}</h3>

        <p class="details">
            بواقع
            <span class="highlight">{{ $course_hours }}</span>
        </p>
        <p class="details">
            ضمن دبلومة
            <span class="highlight">{{ $diploma_name }}</span>
            وقد اجتاز الاختبار بنجاح وهذه شهادة منّا بذلك 
            سائلين المولى عز وجل له دوام التوفيق والسداد.
        </p>


        <p class="footer-note">
        {{ $completion_date }}
        </p>
        
        <p class="footer-note">
            Serial No: {{ $serial_number }}
        </p>
    </div>
</body>
</html>
