@extends('layouts.app')

@section('title', 'Invoice ' . $invoice->invoice_number . ' - RUMU CRM')

@section('header-title', 'Invoice ' . $invoice->invoice_number)
@section('header-subtitle', 'View and download your invoice details.')

@section('content')
    <div class="min-h-screen bg-[#f7f7fb]">
        @include('realtor.partials.sidebar')

        <!-- Main Content -->
        <main class="lg:ml-64 min-h-screen">
            @include('realtor.partials.header')

            <!-- Content Area -->
            <div class="p-4 sm:p-6 lg:p-8">
                <div class="mb-6 flex justify-between items-center max-w-[900px] mx-auto">
                    <a href="{{ route('realtor.billing.index') }}" class="text-sm font-medium text-gray-500 hover:text-gray-700 flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                        Back to Billing
                    </a>
                    <a href="{{ route('realtor.billing.invoice.download', $invoice) }}" class="bg-primary hover:bg-primary/90 text-white px-4 py-2 rounded-lg font-medium transition text-sm flex items-center shadow-sm">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                        Download PDF
                    </a>
                </div>

                <div class="animate-fade-in max-w-[900px] mx-auto pb-12">
                    
                    <style>
                        .custom-invoice {
                            font-family: 'Poppins', sans-serif;
                            color: #2d2d2d;
                            background: white;
                            border-radius: 18px;
                            overflow: hidden;
                            box-shadow: 0 10px 40px rgba(0,0,0,.08);
                        }
                        
                        .custom-invoice .header {
                            padding: 40px;
                            background: linear-gradient(135deg, #74005F, #4D004D);
                            color: white;
                            display: flex;
                            justify-content: space-between;
                            align-items: flex-start;
                        }
                        
                        .custom-invoice .logo {
                            font-size: 38px;
                            font-weight: 700;
                        }
                        
                        .custom-invoice .invoice-title {
                            text-align: right;
                        }
                        
                        .custom-invoice .invoice-title h1 {
                            font-size: 48px;
                            margin-bottom: 8px;
                            color: white;
                            line-height: 1.1;
                        }
                        
                        .custom-invoice .status {
                            margin-top: 20px;
                            display: inline-block;
                            background: #20c997;
                            padding: 8px 22px;
                            border-radius: 30px;
                            font-size: 13px;
                            font-weight: 600;
                            color: white;
                        }
                        
                        .custom-invoice .content {
                            padding: 40px;
                        }
                        
                        .custom-invoice .row {
                            display: flex;
                            gap: 25px;
                            margin-bottom: 25px;
                        }
                        
                        .custom-invoice .card {
                            flex: 1;
                            background: white;
                            border-radius: 14px;
                            padding: 25px;
                            border: 1px solid #ececec;
                            box-shadow: 0 5px 20px rgba(0,0,0,.05);
                        }
                        
                        .custom-invoice .card h3 {
                            font-size: 16px;
                            color: #74005F;
                            margin-bottom: 18px;
                        }
                        
                        .custom-invoice .card h2 {
                            font-size: 24px;
                            margin-bottom: 8px;
                        }
                        
                        .custom-invoice .card p {
                            margin-bottom: 8px;
                            font-size: 14px;
                        }
                        
                        .custom-invoice table.main-table {
                            width: 100%;
                            border-collapse: collapse;
                            margin-top: 15px;
                        }
                        
                        .custom-invoice table.main-table thead {
                            background: #74005F;
                            color: white;
                        }
                        
                        .custom-invoice table.main-table thead th {
                            padding: 18px;
                            text-align: left;
                        }
                        
                        .custom-invoice table.main-table tbody td {
                            padding: 20px;
                            border-bottom: 1px solid #efefef;
                            font-size: 14px;
                        }
                        
                        .custom-invoice .summary {
                            margin-top: 30px;
                            display: flex;
                            justify-content: flex-end;
                        }
                        
                        .custom-invoice .summary-box {
                            width: 360px;
                            border-radius: 15px;
                            overflow: hidden;
                            border: 1px solid #eee;
                        }
                        
                        .custom-invoice .summary-box table {
                            margin: 0;
                            width: 100%;
                            border-collapse: collapse;
                        }
                        
                        .custom-invoice .summary-box tr td {
                            padding: 16px 20px;
                            font-size: 14px;
                        }
                        
                        .custom-invoice .total {
                            background: #74005F;
                            color: white;
                            font-size: 24px;
                            font-weight: 700;
                        }
                        
                        .custom-invoice .footer {
                            margin-top: 40px;
                            padding-top: 25px;
                            border-top: 1px solid #ececec;
                            display: flex;
                            justify-content: space-between;
                            font-size: 13px;
                            color: #666;
                        }
                    </style>

                    @php 
                        $currency = $invoice->payment ? $invoice->payment->currency : 'INR';
                        if ($currency === 'INR') { $currency = '₹'; } 
                        elseif ($currency === 'USD') { $currency = '$'; } 
                        elseif ($currency === 'EUR') { $currency = '€'; } 
                        elseif ($currency === 'GBP') { $currency = '£'; }
                    @endphp

                    <div class="custom-invoice">
                        <div class="header">
                            <div>
                                <div class="logo">
                                    @if($settings->get('company_logo'))
                                        <img src="{{ storage_path('app/public/' . $settings->get('company_logo')) }}" style="max-height:60px; filter: brightness(0) invert(1);" alt="Logo">
                                    @else
                                        RUMU
                                    @endif
                                </div>
                            </div>
                            <div class="invoice-title">
                                <h1>INVOICE</h1>
                                <p>Invoice #{{ $invoice->invoice_number }}</p>
                                <div class="status" style="{{ $invoice->status === 'paid' ? 'background:#20c997;' : 'background:#f59e0b;' }}">
                                    {{ strtoupper($invoice->status) }}
                                </div>
                            </div>
                        </div>

                        <div class="content">
                            <div class="row">
                                <div class="card">
                                    <h3>BILL TO</h3>
                                    <h2>{{ $invoice->user->name }}</h2>
                                    <p>{{ $invoice->user->email }}</p>
                                    @if($invoice->user->company)
                                        <p>{{ $invoice->user->company }}</p>
                                    @endif
                                    @if($invoice->user->phone)
                                        <p>{{ $invoice->user->phone }}</p>
                                    @endif
                                </div>

                                <div class="card">
                                    <h3>INVOICE DETAILS</h3>
                                    <p><strong>Invoice Date :</strong> {{ $invoice->created_at->format('d M Y') }}</p>
                                    <p><strong>Due Date :</strong> {{ $invoice->created_at->addDays(30)->format('d M Y') }}</p>
                                    <p><strong>Terms :</strong> Net 30</p>
                                    @if($invoice->payment)
                                        <p><strong>Payment :</strong> {{ ucfirst(str_replace('_', ' ', $invoice->payment->payment_method)) }}</p>
                                    @else
                                        <p><strong>Payment :</strong> Pending</p>
                                    @endif
                                </div>
                            </div>

                            <div class="row">
                                <div class="card">
                                    <h3>FROM</h3>
                                    <p><strong>{{ $settings->get('company_name', 'RUMU CRM') }}</strong></p>
                                    @if($settings->get('company_legal_name'))
                                        <p>{{ $settings->get('company_legal_name') }}</p>
                                    @endif
                                    <p>{!! nl2br(e($settings->get('company_address', "123 Business Avenue\nTech District, City"))) !!}</p>
                                    @if($settings->get('company_gst'))
                                        <p>GSTIN : {{ $settings->get('company_gst') }}</p>
                                    @endif
                                    @if($settings->get('company_pan'))
                                        <p>PAN : {{ $settings->get('company_pan') }}</p>
                                    @endif
                                </div>
                            </div>

                            <table class="main-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Description</th>
                                        <th>Qty</th>
                                        <th>Price</th>
                                        <th>Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($invoice->invoiceItems as $index => $item)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>
                                                <strong>{{ $item->description }}</strong>
                                                <br>
                                                <span style="color:#777; font-size:12px;">
                                                    Service Period : {{ $invoice->created_at->format('F Y') }}
                                                </span>
                                            </td>
                                            <td>{{ $item->quantity }}</td>
                                            <td>{{ $currency }}{{ number_format($item->unit_price, 2) }}</td>
                                            <td>{{ $currency }}{{ number_format($item->total, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            <div class="summary">
                                <div class="summary-box">
                                    <table>
                                        <tr>
                                            <td>Subtotal</td>
                                            <td align="right">{{ $currency }}{{ number_format($invoice->subtotal, 2) }}</td>
                                        </tr>
                                        @if($invoice->discount > 0)
                                            <tr>
                                                <td>Discount</td>
                                                <td align="right" style="color:#e11d48;">-{{ $currency }}{{ number_format($invoice->discount, 2) }}</td>
                                            </tr>
                                        @endif
                                        @if($invoice->tax > 0)
                                            <tr>
                                                <td>{{ $settings->get('tax_label', 'GST') }}</td>
                                                <td align="right">{{ $currency }}{{ number_format($invoice->tax, 2) }}</td>
                                            </tr>
                                        @endif
                                        <tr class="total">
                                            <td>Total</td>
                                            <td align="right">{{ $currency }}{{ number_format($invoice->total, 2) }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            <div class="footer">
                                <div>
                                    <strong>Need Help?</strong><br>
                                    support@rumucrm.com
                                </div>
                                <div>
                                    <strong>Visit Us</strong><br>
                                    www.rumucrm.com
                                </div>
                                <div>
                                    <strong>RUMU CRM</strong><br>
                                    © {{ date('Y') }} All Rights Reserved
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </main>
    </div>
@endsection
