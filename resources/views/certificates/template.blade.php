<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate of Completion</title>
    <style>
        @page {
            margin: 0;
            size: A4 landscape;
        }
        
        body {
            font-family: 'Georgia', serif;
            margin: 0;
            padding: 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #333;
            height: 100vh;
            box-sizing: border-box;
        }
        
        .certificate {
            background: white;
            border: 20px solid #f8f9fa;
            border-radius: 20px;
            padding: 60px;
            text-align: center;
            height: calc(100% - 120px);
            position: relative;
            box-shadow: 0 0 30px rgba(0,0,0,0.1);
        }
        
        .certificate::before {
            content: '';
            position: absolute;
            top: 40px;
            left: 40px;
            right: 40px;
            bottom: 40px;
            border: 3px solid #667eea;
            border-radius: 10px;
        }
        
        .header {
            margin-bottom: 40px;
        }
        
        .title {
            font-size: 48px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 3px;
        }
        
        .subtitle {
            font-size: 24px;
            color: #666;
            margin-bottom: 40px;
        }
        
        .recipient {
            margin: 40px 0;
        }
        
        .recipient-label {
            font-size: 18px;
            color: #666;
            margin-bottom: 10px;
        }
        
        .recipient-name {
            font-size: 36px;
            font-weight: bold;
            color: #333;
            border-bottom: 2px solid #667eea;
            display: inline-block;
            padding-bottom: 10px;
            margin-bottom: 30px;
        }
        
        .course-info {
            margin: 40px 0;
        }
        
        .course-label {
            font-size: 18px;
            color: #666;
            margin-bottom: 10px;
        }
        
        .course-title {
            font-size: 28px;
            font-weight: bold;
            color: #764ba2;
            margin-bottom: 20px;
        }
        
        .completion-date {
            font-size: 18px;
            color: #666;
            margin: 30px 0;
        }
        
        .footer {
            position: absolute;
            bottom: 60px;
            left: 60px;
            right: 60px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .verification {
            text-align: left;
        }
        
        .verification-label {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .verification-token {
            font-size: 10px;
            color: #999;
            font-family: monospace;
        }
        
        .signature {
            text-align: right;
        }
        
        .signature-line {
            border-top: 2px solid #333;
            width: 200px;
            margin-bottom: 10px;
        }
        
        .signature-label {
            font-size: 14px;
            color: #666;
        }
        
        .decorative-element {
            position: absolute;
            width: 100px;
            height: 100px;
            opacity: 0.1;
        }
        
        .decorative-element.top-left {
            top: 20px;
            left: 20px;
            background: radial-gradient(circle, #667eea 0%, transparent 70%);
        }
        
        .decorative-element.top-right {
            top: 20px;
            right: 20px;
            background: radial-gradient(circle, #764ba2 0%, transparent 70%);
        }
        
        .decorative-element.bottom-left {
            bottom: 20px;
            left: 20px;
            background: radial-gradient(circle, #764ba2 0%, transparent 70%);
        }
        
        .decorative-element.bottom-right {
            bottom: 20px;
            right: 20px;
            background: radial-gradient(circle, #667eea 0%, transparent 70%);
        }
        
        .qr-code {
            position: absolute;
            bottom: 80px;
            right: 80px;
            width: 80px;
            height: 80px;
            background: white;
            border: 2px solid #667eea;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            color: #667eea;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="certificate">
        <div class="decorative-element top-left"></div>
        <div class="decorative-element top-right"></div>
        <div class="decorative-element bottom-left"></div>
        <div class="decorative-element bottom-right"></div>
        
        <div class="header">
            <div class="title">Certificate</div>
            <div class="subtitle">of Completion</div>
        </div>
        
        <div class="recipient">
            <div class="recipient-label">This is to certify that</div>
            <div class="recipient-name">{{ $user_name }}</div>
        </div>
        
        <div class="course-info">
            <div class="course-label">has successfully completed the course</div>
            <div class="course-title">{{ $course_title }}</div>
        </div>
        
        <div class="completion-date">
            Completed on {{ $completion_date }}
        </div>
        
        <div class="footer">
            <div class="verification">
                <div class="verification-label">Certificate ID:</div>
                <div class="verification-token">{{ $certificate_id }}</div>
                <div class="verification-label" style="margin-top: 10px;">Verification Token:</div>
                <div class="verification-token">{{ $verification_token }}</div>
            </div>
            
            <div class="signature">
                <div class="signature-line"></div>
                <div class="signature-label">Authorized Signature</div>
            </div>
        </div>
        
        <div class="qr-code">
            QR Code<br>
            Verify at:<br>
            <small>{{ $verification_url }}</small>
        </div>
    </div>
</body>
</html>