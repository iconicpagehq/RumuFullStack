<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset Verification Code</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; color: #1f2937; margin: 0; padding: 24px; background-color: #f9fafb;">
    <div style="max-width: 520px; margin: 0 auto; background-color: #ffffff; border: 1px solid #e5e7eb; border-radius: 16px; padding: 36px 32px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
        
        <div style="text-align: center; margin-bottom: 28px;">
            <h1 style="color: #6b21a8; font-size: 28px; font-weight: 800; margin: 0; letter-spacing: -0.5px;">RUMU</h1>
            <p style="color: #6b7280; font-size: 13px; margin: 4px 0 0 0;">Password Reset Verification</p>
        </div>

        <p style="font-size: 15px; margin-bottom: 16px;">Hi <strong>{{ $name }}</strong>,</p>

        <p style="font-size: 14px; color: #4b5563; margin-bottom: 24px;">
            We received a request to reset the password for your Rumu account. Use the verification code below to proceed:
        </p>

        <div style="text-align: center; margin: 28px 0; background: #faf5ff; border: 1.5px dashed #c084fc; border-radius: 12px; padding: 20px;">
            <span style="font-size: 34px; font-weight: 800; letter-spacing: 8px; color: #6b21a8; font-family: monospace;">
                {{ $otp }}
            </span>
            <p style="font-size: 12px; color: #7e22ce; margin: 8px 0 0 0; font-weight: 500;">
                Valid for 15 minutes
            </p>
        </div>

        <p style="font-size: 13px; color: #6b7280; margin-bottom: 20px;">
            Enter this code on the password reset screen to set a new password. If you didn't request this password reset, you can safely ignore this email — your account remains secure.
        </p>

        <hr style="border: none; border-top: 1px solid #f3f4f6; margin: 24px 0;" />

        <p style="font-size: 12px; color: #9ca3af; text-align: center; margin: 0;">
            Need help? Contact our support team at <a href="mailto:billing@rumu.in" style="color: #6b21a8; text-decoration: none;">billing@rumu.in</a>.
        </p>
    </div>
</body>
</html>
