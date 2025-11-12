<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تم إصدار شهادتك بنجاح</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 20px;
            direction: rtl;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: bold;
        }
        .content {
            padding: 30px;
        }
        .certificate-info {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid #28a745;
        }
        .info-item {
            margin: 10px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .info-label {
            font-weight: bold;
            color: #495057;
        }
        .info-value {
            color: #212529;
            font-weight: 500;
        }
        .verification-link {
            background-color: #e3f2fd;
            border: 1px solid #2196f3;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
            text-align: center;
        }
        .verification-link a {
            color: #1976d2;
            text-decoration: none;
            font-weight: bold;
            font-size: 14px;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            color: #6c757d;
            font-size: 14px;
        }
        .congratulations {
            color: #28a745;
            font-size: 18px;
            font-weight: bold;
            text-align: center;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎓 مبروك! تم إصدار شهادتك بنجاح</h1>
        </div>
        
        <div class="content">
            <div class="congratulations">
                تهانينا {{ $userName }}! لقد أكملت بنجاح دورة {{ $courseTitle }}
            </div>
            
            <p>نحن سعداء لإبلاغك بأنه تم إصدار شهادتك الرسمية بنجاح. تفاصيل الشهادة كالتالي:</p>
            
            <div class="certificate-info">
                <div class="info-item">
                    <span class="info-label">اسم الدورة:</span>
                    <span class="info-value">{{ $courseTitle }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">رقم الشهادة:</span>
                    <span class="info-value">{{ $certificateId }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">تاريخ الإصدار:</span>
                    <span class="info-value">{{ $issuedAt }}</span>
                </div>
            </div>
            
            <div class="verification-link">
                <p><strong>رابط التحقق من الشهادة:</strong></p>
                <a href="{{ $verificationUrl }}" target="_blank">
                    {{ $verificationUrl }}
                </a>
            </div>
            
            <p>تم إرفاق نسخة PDF من الشهادة مع هذا البريد الإلكتروني. يمكنك تنزيلها والاحتفاظ بها لسجلاتك.</p>
            
            <p>نأمل أن تكون قد استفدت من هذه الدورة التدريبية، ونتطلع لرؤيتك في دوراتنا المستقبلية.</p>
            
            <p>مع أطيب التحيات،<br>
            فريق منصة أفق التعليمية</p>
        </div>
        
        <div class="footer">
            <p>إذا واجهتك أي مشاكل أو لديك أي استفسارات، لا تتردد في التواصل مع فريق الدعم.</p>
        </div>
    </div>
</body>
</html>