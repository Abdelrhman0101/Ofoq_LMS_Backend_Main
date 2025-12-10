<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>شهادة إتمام</title>
    <style>
        /* تصفير الهوامش الافتراضية للمتصفح لضمان التحكم الكامل */
        h1, h2, h3, h4, h5, h6, p {
            margin: 0;
            padding: 0;
        }

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
            font-weight: 700;
            margin: 0;
            padding: 0;
            width: 1123px; /* A4 Landscape */
            height: 792px;
            background-image: url("{{ $backgroundImageBase64 }}");
            background-size: 100% 100%;
            background-repeat: no-repeat;
            color: #0f172a;
            line-height: 1.1; /* تقليل المسافة بين الأسطر المتداخلة */
        }

        /* الحاوية */
        .content-container {
            width: 80%;
            margin: 0 auto;
            text-align: center;
            /* يمكنك تعديل هذه القيمة لتحريك الكتلة كاملة للأعلى أو الأسفل */
            padding-top: 280px; 
        }

        /* السطر التعريفي */
        .subtitle {
            font-size: 27px;
            font-weight: 700;
            color: #4b5563;
            margin-bottom: 5px; /* تم تقليلها */
        }

        /* اسم الطالب */
        .student-name {
            font-size: 32px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 2px; /* تم تقليلها */
            line-height: 1.2;
        }

        /* خط فاصل ديكور */
        .divider {
            width: 110px;
            height: 3px;
            background: linear-gradient(90deg, #2b3487 0%, #0ea5e9 100%);
            margin: 5px auto 10px; /* تقليل المسافات حول الخط */
            border-radius: 999px;
        }

        /* سطر "قد حضر..." */
        .pre-course {
            font-size: 22px;
            color: #475569;
            margin-bottom: 2px; /* تم تقليلها */
            font-weight: 700;
        }

        /* عنوان الكورس */
        .course-title {
            font-size: 27px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 5px; /* تم تقليلها */
            line-height: 1.2;
            padding-top: 2px;
        }

        /* تفاصيل الساعات والدبلومة */
        .details {
            font-size: 22px;
            font-weight: 700;
            color: #4b5563;
            line-height: 1.3;
            margin-bottom: 8px; /* مسافة بسيطة قبل النص الختامي */
        }

        /* البادج اللي فيها الساعات/اسم الدبلومة */
        .highlight {
            display: inline-block;
            background: linear-gradient(90deg, #0ea5e9 0%, #6366f1 100%);
            color: #fff;
            padding: 2px 14px 4px; /* تصغير الـ padding */
            border-radius: 999px;
            font-weight: 700;
            font-size: 22px;
            margin: 0 4px;
            vertical-align: middle;
        }

        /* جملة الدعاء والنص الختامي */
        .closing-text {
            /* تم دمج المسافات */
            margin-bottom: 4px; 
            font-size: 24px; /* تم تصغير الخط قليلاً ليتناسب مع التقارب */
            color: #475569;
            font-weight: 700;
            line-height: 1.3;
        }

        /* الفوتر */
        /* الفوتر (الرقم التسلسلي والتاريخ) - أسفل اليمين (أقرب للمنتصف) */
        .footer-container {
            position: absolute;
            bottom: 40px;
            right: 120px;
            text-align: right;
            display: flex;
            flex-direction: column;
            gap: 5px;
            margin: 0;
            padding: 0;
        }

        .footer-note {
            font-size: 16px;
            color: #2d2e31ff;
            font-weight: 700;
            margin: 0;
        }

        /* QR Code */
        .qr-container {
            position: absolute;
            bottom: 30px;
            left: 120px;
            text-align: center;
        }
        .qr-container img {
            width: 90px;
            height: 90px;
        }
        .qr-label {
            font-size: 10px;
            color: #666;
            margin-top: 4px;
            font-weight: 400;
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

        <p class="closing-text">
            واجتاز جميع المهام والتكليفات المقررة، ملتزماً بمعايير الجودة الأكاديمية ومستوى الأداء المطلوب.
        </p>
        <p class="closing-text">
            سائلين الله تعالى أن يوفقه في مسيرته العلمية والعملية.
        </p>
        <br/><br/><br/><br/>
        <div class="footer-container">
            <p class="footer-note">رقم المسلسل: {{ $serial_number }}</p>
            <p class="footer-note">تاريخ الإصدار: {{ $issued_date }}</p>
        </div>

        @if(!empty($qrCodeBase64))
        <div class="qr-container">
            <img src="{{ $qrCodeBase64 }}" alt="QR Code" />
            <p class="qr-label">امسح للتحقق</p>
        </div>
        @endif
    </div>
</body>
</html>