<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Billing Notification</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f9fafb;
            color: #333;
            line-height: 1.6;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        .header {
            background-color: #4F46E5;
            color: #ffffff;
            text-align: center;
            padding: 24px;
        }
        .content {
            padding: 32px;
        }
        .footer {
            background-color: #f3f4f6;
            color: #6b7280;
            text-align: center;
            padding: 16px;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>RUMU CRM</h2>
        </div>
        <div class="content">
            {!! $bodyHtml !!}
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} RUMU CRM. All rights reserved.
        </div>
    </div>
</body>
</html>
