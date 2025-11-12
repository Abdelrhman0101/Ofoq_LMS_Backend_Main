<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>شهادة إتمام الدورة</title>
    <style>
        @page { 
            margin: 0; 
            size: A4 landscape; 
        }
        
        body { 
            font-family: 'DejaVu Sans', 'Arial', 'Tahoma', 'Amiri', 'Noto Naskh Arabic', sans-serif; 
            margin: 0; 
            padding: 0;
            background: #f8f9fa;
            color: #2c3e50;
            direction: rtl;
            position: relative;
            text-align: right;
        }
        
        .certificate-container {
            width: 297mm;
            height: 210mm;
            position: relative;
            background-image: url('{{ public_path("storage/certifecate_cover.jpg") }}');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        
        .certificate-content {
            width: 80%;
            max-width: 800px;
            text-align: center;
            padding: 60px 40px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }
        
        .certificate-header {
            margin-bottom: 40px;
        }
        
        .certificate-title {
            font-size: 42px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
            font-family: 'DejaVu Sans', 'Arial', 'Tahoma', 'Amiri', 'Noto Naskh Arabic', sans-serif;
            text-align: center;
        }
        
        .certificate-subtitle {
            font-size: 20px;
            color: #7f8c8d;
            margin-bottom: 30px;
            font-family: 'DejaVu Sans', 'Arial', 'Tahoma', 'Amiri', 'Noto Naskh Arabic', sans-serif;
        }
        
        .recipient-section {
            margin: 40px 0;
        }
        
        .recipient-label {
            font-size: 18px;
            color: #7f8c8d;
            margin-bottom: 15px;
        }
        
        .recipient-name {
            font-size: 36px;
            font-weight: bold;
            color: #e74c3c;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid #3498db;
            display: inline-block;
            font-family: 'DejaVu Sans', 'Arial', 'Tahoma', 'Amiri', 'Noto Naskh Arabic', sans-serif;
        }
        
        .course-section {
            margin: 40px 0;
        }
        
        .course-description {
            font-size: 20px;
            color: #34495e;
            margin-bottom: 15px;
            line-height: 1.6;
        }
        
        .course-title {
            font-size: 28px;
            font-weight: bold;
            color: #27ae60;
            margin-bottom: 10px;
        }
        
        .completion-date {
            font-size: 18px;
            color: #7f8c8d;
            margin: 20px 0;
        }
        
        .certificate-details {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: 50px;
            padding-top: 30px;
            border-top: 2px solid #ecf0f1;
        }
        
        .verification-section {
            text-align: right;
        }
        
        .verification-label {
            font-size: 14px;
            color: #7f8c8d;
            margin-bottom: 5px;
        }
        
        .verification-token {
            font-size: 12px;
            color: #95a5a6;
            font-family: monospace;
            background: #f8f9fa;
            padding: 5px 10px;
            border-radius: 5px;
            border: 1px solid #dee2e6;
        }
        
        .serial-number {
            font-size: 14px;
            color: #7f8c8d;
            font-weight: bold;
        }
        
        .qr-section {
            text-align: center;
        }
        
        .qr-code {
            width: 80px;
            height: 80px;
            background: white;
            border: 2px solid #3498db;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            color: #3498db;
            margin: 0 auto 10px;
        }
        
        .qr-label {
            font-size: 12px;
            color: #7f8c8d;
        }
        
        .digital-seal {
            position: absolute;
            bottom: 30px;
            right: 30px;
            width: 100px;
            height: 100px;
            opacity: 0.8;
        }
        
        .exam-score {
            background: #e8f5e8;
            border: 2px solid #27ae60;
            border-radius: 10px;
            padding: 15px;
            margin: 20px 0;
            font-size: 16px;
            color: #27ae60;
            font-weight: bold;
        }
        
        @media print {
            .certificate-container {
                background-image: url('{{ public_path("storage/certifecate_cover.jpg") }}') !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <div class="certificate-container">
        <div class="certificate-content">
            <div class="certificate-header">
                <div class="certificate-title">شهادة إتمام الدورة التدريبية</div>
                <div class="certificate-subtitle">تشهد إدارة منصة أُفُق بأن</div>
            </div>
            
            <div class="recipient-section">
                <div class="recipient-label">الطالب/ة</div>
                <div class="recipient-name">{{ $user_name }}</div>
            </div>
            
            <div class="course-section">
                <div class="course-description">قد اجتاز بنجاح دورة</div>
                <div class="course-title">{{ $course_title }}</div>
                @if(isset($final_exam_score) && $final_exam_score > 0)
                <div class="exam-score">
                    بنسبة نجاح: {{ $final_exam_score }}%
                </div>
                @endif
                <div class="completion-date">
                    وذلك بتاريخ: {{ $completion_date }}
                </div>
            </div>
            
            <div class="certificate-details">
                <div class="verification-section">
                    <div class="verification-label">رقم الشهادة</div>
                    <div class="serial-number">{{ $serial_number }}</div>
                    <div class="verification-label" style="margin-top: 10px;">رمز التحقق</div>
                    <div class="verification-token">{{ $verification_token }}</div>
                </div>
                
                <div class="qr-section">
                    <div class="qr-code">
                        @if(isset($qr_code_image))
                        <img src="{{ $qr_code_image }}" alt="QR Code" style="width: 100%; height: 100%;">
                        @else
                        رمز QR
                        @endif
                    </div>
                    <div class="qr-label">للتحقق اضغط هنا</div>
                </div>
            </div>
        </div>
        
        @if(isset($digital_seal))
        <div class="digital-seal">
            <img src="{{ $digital_seal }}" alt="Digital Seal" style="width: 100%; height: 100%;">
        </div>
        @endif
    </div>
</body>
</html>