<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Invoice {{ $invoice->invoice_number ?? '' }}</title>
<style>
    @page { margin: 40px 50px; }
    body {
        margin: 0;
        font-family: 'DejaVu Sans', 'Helvetica Neue', Helvetica, Arial, sans-serif;
        color: #000000;
        background: #ffffff;
        font-size: 12px;
    }
    table { width: 100%; border-collapse: collapse; }
    
    /* Header */
    .header-table { margin-bottom: 20px; }
    .logo-img { max-width: 180px; max-height: 60px; }
    .invoice-title {
        color: #800080;
        font-size: 32px;
        font-weight: bold;
        text-align: right;
        margin: 0;
        letter-spacing: 1px;
    }
    .invoice-no {
        color: #000000;
        font-size: 11px;
        text-align: right;
        margin-top: 5px;
    }
    
    .divider { border-bottom: 2px solid #800080; margin-bottom: 30px; }

    /* Top Section */
    .top-section { margin-bottom: 30px; }
    .section-title {
        color: #800080;
        font-size: 11px;
        font-weight: bold;
        text-transform: uppercase;
        margin-bottom: 5px;
        letter-spacing: 0.5px;
    }
    .section-line {
        border-top: 1px solid #800080; margin-bottom: 15px; width: 90%;
    }
    
    .bill-to h3 { font-size: 14px; margin: 0 0 5px 0; color: #000; }
    .bill-to p { margin: 2px 0; color: #000; font-size: 11px; line-height: 1.4; }
    .bill-to .email { color: #800080; } /* email link color */
    
    .invoice-details-box {
        background: #ffffff;
        border: 1px solid #800080;
        border-radius: 4px;
        padding: 15px;
    }
    .detail-row td { padding: 6px 0; font-size: 11px; }
    .detail-label { color: #000; width: 50%; }
    .detail-value { color: #000; font-weight: bold; text-align: right; }

    /* Items Table */
    .items-table { margin-bottom: 20px; }
    .items-table th {
        background: #800080;
        color: #ffffff;
        padding: 10px 15px;
        font-size: 10px;
        text-transform: uppercase;
        font-weight: bold;
        text-align: left;
    }
    .items-table th.center { text-align: center; }
    .items-table th.right { text-align: right; }
    
    .items-table td {
        padding: 12px 15px;
        border-bottom: 1px solid #d8b4d8;
        vertical-align: top;
    }
    .items-table td.center { text-align: center; }
    .items-table td.right { text-align: right; }
    
    .item-name { font-weight: bold; color: #800080; font-size: 12px; margin-bottom: 4px; }
    .item-desc { color: #000; font-size: 10px; }

    /* Totals */
    .totals-wrapper { width: 45%; float: right; margin-top: 10px; border: 1px solid #800080; }
    .totals-table td { padding: 10px 15px; }
    .totals-label { color: #000; }
    .totals-val { font-weight: bold; text-align: right; color: #000; }
    .discount-val { color: #10b981; font-weight: bold; text-align: right; }
    
    .total-final {
        background: #800080;
        color: #ffffff;
    }
    .total-final td { padding: 12px 15px; font-weight: bold; font-size: 14px; }
    .total-final-val { text-align: right; }

    .clearfix { clear: both; }



    /* Footer */
    .footer {
        margin-top: 40px;
        padding-top: 15px;
        text-align: center;
        color: #800080;
        font-size: 12px;
        font-weight: bold;
    }
</style>
</head>
<body>
@php 
    $currency = $invoice->payment ? $invoice->payment->currency : 'INR';
    if ($currency === 'INR') { $currency = '₹'; } 
    elseif ($currency === 'USD') { $currency = '$'; } 
    elseif ($currency === 'EUR') { $currency = '€'; } 
    elseif ($currency === 'GBP') { $currency = '£'; }
@endphp

<table class="header-table">
    <tr>
        <td style="width: 50%; vertical-align: top;">
            <img class="logo-img" src="{{ public_path('images/logo.png') }}" alt="Logo">
        </td>
        <td style="width: 50%; vertical-align: top; text-align: right;">
            <h1 class="invoice-title">INVOICE</h1>
            <div class="invoice-no">Invoice No: #{{ $invoice->invoice_number }}</div>
        </td>
    </tr>
</table>

<div class="divider"></div>

<table class="top-section">
    <tr>
        <td style="width: 55%; vertical-align: top; padding-right: 30px;">
            <div class="section-title">BILL TO</div>
            <div class="section-line"></div>
            
            <div class="bill-to">
                <h3>{{ $invoice->user->name }}</h3>
                @if($invoice->user->company)
                    <p>{{ $invoice->user->company }}</p>
                @endif
                <p style="color: #20368c;">{{ $invoice->user->email }}</p>
                @if($invoice->user->phone)
                    <p>{{ $invoice->user->phone }}</p>
                @endif
            </div>
        </td>
        <td style="width: 45%; vertical-align: top;">
            <div class="section-title">INVOICE DETAILS</div>
            <div class="section-line" style="width: 100%;"></div>
            
            <div class="invoice-details-box">
                <table style="width: 100%;">
                    <tr class="detail-row">
                        <td class="detail-label">Invoice Date:</td>
                        <td class="detail-value">{{ $invoice->created_at->format('d M, Y') }}</td>
                    </tr>
                    <tr class="detail-row">
                        <td class="detail-label">Due Date:</td>
                        <td class="detail-value">{{ $invoice->created_at->addDays(30)->format('d M, Y') }}</td>
                    </tr>
                    <tr class="detail-row">
                        <td class="detail-label">Payment Terms:</td>
                        <td class="detail-value">Net 30</td>
                    </tr>
                </table>
            </div>
        </td>
    </tr>
</table>

<table class="items-table">
    <thead>
        <tr>
            <th style="width: 5%;">#</th>
            <th style="width: 45%;">DESCRIPTION</th>
            <th class="center" style="width: 15%;">QTY</th>
            <th class="right" style="width: 15%;">UNIT PRICE</th>
            <th class="right" style="width: 20%;">AMOUNT</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($invoice->invoiceItems as $i => $item)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>
                    <div class="item-name">{{ $item->description }}</div>
                    <div class="item-desc">Service Period: {{ $invoice->created_at->format('F Y') }}</div>
                </td>
                <td class="center">{{ $item->quantity }}</td>
                <td class="right">{{ $currency }}{{ number_format($item->unit_price, 2) }}</td>
                <td class="right">{{ $currency }}{{ number_format($item->total, 2) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<div class="totals-wrapper">
    <table class="totals-table">
        <tr>
            <td class="totals-label">Subtotal</td>
            <td class="totals-val">{{ $currency }}{{ number_format($invoice->subtotal, 2) }}</td>
        </tr>
        @if($invoice->discount > 0)
        <tr>
            <td class="totals-label">Discount</td>
            <td class="discount-val">-{{ $currency }}{{ number_format($invoice->discount, 2) }}</td>
        </tr>
        @endif
        @if($invoice->tax > 0)
        <tr>
            <td class="totals-label">{{ $settings->get('tax_label', 'GST (18%)') }}</td>
            <td class="totals-val">{{ $currency }}{{ number_format($invoice->tax, 2) }}</td>
        </tr>
        @endif
        <tr class="total-final">
            <td>Total Amount</td>
            <td class="total-final-val">{{ $currency }}{{ number_format($invoice->total, 2) }}</td>
        </tr>
    </table>
</div>

<div class="clearfix"></div>



<div class="footer">
    ICPG Technologies Pvt. Ltd.
</div>

</body>
</html>
