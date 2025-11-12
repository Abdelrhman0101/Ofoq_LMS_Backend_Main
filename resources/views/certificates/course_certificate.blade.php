<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>شهادة إتمام المقرر</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap');
        
        body {
            font-family: 'Tajawal', sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .certificate-container {
            width: 800px;
            height: 600px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }
        
        .certificate-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
        }
        
        .certificate-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.1);
        }
        
        .certificate-title {
            font-size: 28px;
            font-weight: 700;
            margin: 0;
            position: relative;
            z-index: 1;
        }
        
        .certificate-subtitle {
            font-size: 16px;
            margin: 10px 0 0 0;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }
        
        .certificate-body {
            padding: 40px;
            text-align: center;
        }
        
        .certificate-text {
            font-size: 18px;
            color: #333;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        
        .student-name {
            font-size: 32px;
            font-weight: 700;
            color: #667eea;
            margin: 20px 0;
            padding: 10px;
            border-bottom: 2px solid #667eea;
            display: inline-block;
        }
        
        .course-name {
            font-size: 24px;
            font-weight: 500;
            color: #764ba2;
            margin: 20px 0;
        }
        
        .certificate-details {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        
        .detail-item {
            text-align: center;
        }
        
        .detail-label {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .detail-value {
            font-size: 14px;
            font-weight: 500;
            color: #333;
        }
        
        .serial-number {
            font-family: 'Courier New', monospace;
            background: #e9ecef;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
        }
        
        .certificate-footer {
            position: absolute;
            bottom: 20px;
            left: 40px;
            right: 40px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #eee;
            padding-top: 15px;
        }
        
        .verification-link {
            font-size: 10px;
            color: #999;
            margin-top: 5px;
        }
        
        .seal {
            position: absolute;
            top: 50%;
            right: 50px;
            transform: translateY(-50%);
            width: 100px;
            height: 100px;
            background: radial-gradient(circle, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 14px;
            font-weight: 700;
            opacity: 0.8;
        }
    </style>
</head>
<body>
    <div class="certificate-container">
        <div class="certificate-header">
            <h1 class="certificate-title">شهادة إتمام المقرر</h1>
            <p class="certificate-subtitle">Course Completion Certificate</p>
        </div>
        
        <div class="certificate-body">
            <p class="certificate-text">
                يُمنح هذه الشهادة إلى
            </p>
            
            <div class="student-name">{{ $student_name }}</div>
            
            <p class="certificate-text">
                لإتمام المقرر بنجاح
            </p>
            
            <div class="course-name">{{ $course_title }}</div>
            
            <div class="certificate-details">
                <div class="detail-item">
                    <div class="detail-label">تاريخ الإصدار</div>
                    <div class="detail-value">{{ $issued_date }}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">رقم التسلسل</div>
                    <div class="detail-value serial-number">{{ $serial_number }}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">رمز التحقق</div>
                    <div class="detail-value">{{ substr($verification_token, 0, 8) }}...</div>
                </div>
            </div>
        </div>
        
        <div class="seal">
            معتمد
        </div>
        
        <div class="certificate-footer">
            <p>هذه الشهادة صالحة للتحقق عبر الموقع الإلكتروني</p>
            <div class="verification-link">
                رابط التحقق: {{ url("/api/certificate/verify/{$verification_token}") }}
            </div>
        </div>
    </div>
</body>
</html>