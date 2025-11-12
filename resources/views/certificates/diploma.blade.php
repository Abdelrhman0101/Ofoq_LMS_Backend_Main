<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>شهادة الدبلومة</title>
    <style>
        @page { margin: 0; size: A4 landscape; }
        body { font-family: 'Georgia', serif; margin: 0; padding: 40px; background: #f5f7fb; color: #333; height: 100vh; box-sizing: border-box; direction: rtl; }
        .certificate { background: white; border: 20px solid #f8f9fa; border-radius: 20px; padding: 60px; text-align: center; height: calc(100% - 120px); position: relative; box-shadow: 0 0 30px rgba(0,0,0,0.1); }
        .title { font-size: 44px; font-weight: bold; color: #2b6cb0; margin-bottom: 10px; }
        .subtitle { font-size: 22px; color: #666; margin-bottom: 30px; }
        .recipient-name { font-size: 36px; font-weight: bold; color: #333; border-bottom: 2px solid #2b6cb0; display: inline-block; padding-bottom: 10px; margin-bottom: 25px; }
        .diploma-title { font-size: 28px; font-weight: bold; color: #764ba2; margin-bottom: 15px; }
        .completion-date { font-size: 18px; color: #666; margin: 20px 0; }
        .footer { position: absolute; bottom: 60px; left: 60px; right: 60px; display: flex; justify-content: space-between; align-items: center; }
        .verification { text-align: right; }
        .verification-label { font-size: 12px; color: #666; margin-bottom: 5px; }
        .verification-token { font-size: 10px; color: #999; font-family: monospace; }
        .signature { text-align: left; }
        .signature-line { border-top: 2px solid #333; width: 200px; margin-bottom: 10px; }
        .signature-label { font-size: 14px; color: #666; }
        .qr-code { position: absolute; bottom: 80px; left: 80px; width: 80px; height: 80px; background: white; border: 2px solid #2b6cb0; display: flex; align-items: center; justify-content: center; font-size: 10px; color: #2b6cb0; text-align: center; }
        .serial { position: absolute; bottom: 18px; left: 30px; font-size: 12px; color: #000; letter-spacing: 0.5px; }
    </style>
</head>
<body>
    <div class="certificate">
        <div class="title">شهادة الدبلومة</div>
        <div class="subtitle">تشهد إدارة المنصة بأن</div>

        <div class="recipient-name">{{ $user_name }}</div>
        <div class="subtitle">قد أكمل بنجاح جميع مقررات دبلومة</div>
        <div class="diploma-title">{{ $diploma_name }}</div>

        <div class="completion-date">أُكملت بتاريخ {{ $completion_date }}</div>

        <div class="footer">
            <div class="verification">
                <div class="verification-label">رقم الشهادة:</div>
                <div class="verification-token">{{ $certificate_id }}</div>
                <div class="verification-label" style="margin-top: 10px;">رمز التحقق:</div>
                <div class="verification-token">{{ $verification_token }}</div>
            </div>
            <div class="signature">
                <div class="signature-line"></div>
                <div class="signature-label">توقيع الإدارة</div>
            </div>
        </div>

        <div class="qr-code">
            رمز QR<br>
            رابط التحقق:<br>
            <small>{{ $verification_url }}</small>
        </div>
        <div class="serial">{{ $serial_number }}</div>
    </div>
</body>
</html>