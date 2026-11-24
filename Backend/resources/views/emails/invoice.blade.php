<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <p>Hi {{ $invoice->user->name }},</p>

    <p>Thank you for choosing RUMU CRM! We truly appreciate your business.</p>

    <p>Please find attached your invoice (<strong>{{ $invoice->invoice_number }}</strong>) for your recent subscription purchase.</p>

    <h3 style="color: #4a5568;">Subscription Details</h3>
    <ul>
        <li><strong>Plan:</strong> {{ $invoice->subscription->plan_name }}</li>
        <li><strong>Duration:</strong> {{ $invoice->subscription->duration_months }} Months</li>
        <li><strong>Total Amount:</strong> {{ $invoice->payment ? $invoice->payment->currency : 'INR' }} {{ number_format($invoice->total, 2) }}</li>
    </ul>

    <p>If you have any questions or need further assistance, please feel free to reach out to our support team at <a href="mailto:support@rumu.com">support@rumu.com</a>.</p>

    <br>
    <p>Best regards,<br>
    <strong>The RUMU Team</strong></p>
</body>
</html>
