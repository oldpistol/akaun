<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            color: #1f2937;
            font-size: 14px;
            line-height: 1.6;
        }

        .container {
            padding: 40px;
            max-width: 800px;
            margin: 0 auto;
        }

        .header {
            margin-bottom: 40px;
            border-bottom: 3px solid #292524;
            padding-bottom: 20px;
        }

        .header-content {
            display: table;
            width: 100%;
        }

        .company-info {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }

        .company-name {
            font-size: 28px;
            font-weight: bold;
            color: #292524;
            margin-bottom: 8px;
        }

        .company-details {
            font-size: 12px;
            color: #57534e;
            line-height: 1.8;
        }

        .invoice-title {
            display: table-cell;
            width: 50%;
            text-align: right;
            vertical-align: top;
        }

        .invoice-title h1 {
            font-size: 36px;
            color: #292524;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .invoice-number {
            font-size: 14px;
            color: #57534e;
        }

        .info-section {
            display: table;
            width: 100%;
            margin-bottom: 40px;
        }

        .bill-to,
        .invoice-details {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }

        .section-title {
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            color: #78716c;
            margin-bottom: 12px;
            letter-spacing: 0.5px;
        }

        .customer-name {
            font-size: 16px;
            font-weight: bold;
            color: #292524;
            margin-bottom: 6px;
        }

        .customer-details {
            font-size: 13px;
            color: #44403c;
            line-height: 1.8;
        }

        .invoice-details {
            text-align: right;
        }

        .detail-row {
            margin-bottom: 8px;
            font-size: 13px;
        }

        .detail-label {
            display: inline-block;
            width: 120px;
            color: #78716c;
            font-weight: 600;
        }

        .detail-value {
            color: #292524;
            font-weight: 500;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-draft {
            background-color: #f5f5f4;
            color: #57534e;
        }

        .status-pending {
            background-color: #fef3c7;
            color: #92400e;
        }

        .status-paid {
            background-color: #d1fae5;
            color: #065f46;
        }

        .status-overdue {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .status-cancelled {
            background-color: #f3f4f6;
            color: #6b7280;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .items-table thead {
            background-color: #292524;
            color: white;
        }

        .items-table th {
            padding: 12px;
            text-align: left;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .items-table th.text-center {
            text-align: center;
        }

        .items-table th.text-right {
            text-align: right;
        }

        .items-table tbody tr {
            border-bottom: 1px solid #e7e5e4;
        }

        .items-table tbody tr:last-child {
            border-bottom: none;
        }

        .items-table td {
            padding: 14px 12px;
            font-size: 13px;
            color: #44403c;
        }

        .items-table td.text-center {
            text-align: center;
        }

        .items-table td.text-right {
            text-align: right;
        }

        .item-description {
            font-weight: 600;
            color: #292524;
            margin-bottom: 4px;
        }

        .item-notes {
            font-size: 11px;
            color: #78716c;
            font-style: italic;
        }

        .totals-section {
            margin-left: auto;
            width: 350px;
            margin-bottom: 40px;
        }

        .total-row {
            display: table;
            width: 100%;
            padding: 10px 0;
            border-bottom: 1px solid #e7e5e4;
        }

        .total-row.grand-total {
            border-top: 2px solid #292524;
            border-bottom: 3px double #292524;
            padding: 16px 0;
            margin-top: 8px;
        }

        .total-label {
            display: table-cell;
            font-size: 14px;
            color: #57534e;
            font-weight: 600;
        }

        .total-row.grand-total .total-label {
            font-size: 16px;
            color: #292524;
            font-weight: bold;
        }

        .total-value {
            display: table-cell;
            text-align: right;
            font-size: 14px;
            color: #292524;
            font-weight: 600;
        }

        .total-row.grand-total .total-value {
            font-size: 18px;
            font-weight: bold;
        }

        .notes-section {
            background-color: #fafaf9;
            border-left: 4px solid #292524;
            padding: 20px;
            margin-bottom: 40px;
        }

        .notes-title {
            font-size: 13px;
            font-weight: bold;
            color: #292524;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .notes-content {
            font-size: 13px;
            color: #44403c;
            line-height: 1.8;
            white-space: pre-line;
        }

        .footer {
            text-align: center;
            padding-top: 30px;
            border-top: 1px solid #e7e5e4;
            color: #78716c;
            font-size: 11px;
        }

        .footer-line {
            margin-bottom: 4px;
        }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="container">
        {{-- Header --}}
        <div class="header">
            <div class="header-content">
                <div class="company-info">
                    <div class="company-name">Your Company Name</div>
                    <div class="company-details">
                        123 Business Street<br>
                        City, State 12345<br>
                        Phone: (555) 123-4567<br>
                        Email: info@yourcompany.com
                    </div>
                </div>
                <div class="invoice-title">
                    <h1>INVOICE</h1>
                    <div class="invoice-number">#{{ $invoice->invoice_number }}</div>
                </div>
            </div>
        </div>

        {{-- Bill To & Invoice Details --}}
        <div class="info-section">
            <div class="bill-to">
                <div class="section-title">Bill To</div>
                <div class="customer-name">{{ $customer->name }}</div>
                <div class="customer-details">
                    @if($customer->email)
                        {{ $customer->email }}<br>
                    @endif
                    @if($customer->phone_primary)
                        {{ $customer->phone_primary }}<br>
                    @endif
                    @if($address)
                        {{ $address->address_line1 }}<br>
                        @if($address->address_line2)
                            {{ $address->address_line2 }}<br>
                        @endif
                        {{ $address->city }}, {{ $address->state?->code ?? '' }} {{ $address->postcode }}<br>
                    @endif
                    @if($customer->gst_number)
                        GST: {{ $customer->gst_number }}<br>
                    @endif
                </div>
            </div>
            <div class="invoice-details">
                <div class="detail-row">
                    <span class="detail-label">Status:</span>
                    <span class="status-badge status-{{ strtolower($invoice->status->value) }}">
                        {{ $invoice->status->value }}
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Invoice Date:</span>
                    <span class="detail-value">{{ $invoice->issued_at->format('M d, Y') }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Due Date:</span>
                    <span class="detail-value">{{ $invoice->due_at->format('M d, Y') }}</span>
                </div>
                @if($invoice->paid_at)
                <div class="detail-row">
                    <span class="detail-label">Paid Date:</span>
                    <span class="detail-value">{{ $invoice->paid_at->format('M d, Y') }}</span>
                </div>
                @endif
            </div>
        </div>

        {{-- Items Table --}}
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 50%;">Description</th>
                    <th class="text-center" style="width: 10%;">Qty</th>
                    <th class="text-right" style="width: 15%;">Unit Price</th>
                    <th class="text-center" style="width: 10%;">Tax</th>
                    <th class="text-right" style="width: 15%;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                <tr>
                    <td>
                        <div class="item-description">{{ $item->description }}</div>
                        @if($item->notes)
                        <div class="item-notes">{{ $item->notes }}</div>
                        @endif
                    </td>
                    <td class="text-center">{{ number_format($item->quantity, 2) }}</td>
                    <td class="text-right">${{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-center">{{ number_format($item->tax_rate, 1) }}%</td>
                    <td class="text-right">${{ number_format($item->quantity * $item->unit_price * (1 + $item->tax_rate / 100), 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Totals --}}
        <div class="totals-section">
            <div class="total-row">
                <span class="total-label">Subtotal:</span>
                <span class="total-value">${{ number_format($invoice->subtotal, 2) }}</span>
            </div>
            <div class="total-row">
                <span class="total-label">Tax Total:</span>
                <span class="total-value">${{ number_format($invoice->tax_total, 2) }}</span>
            </div>
            <div class="total-row grand-total">
                <span class="total-label">Total Due:</span>
                <span class="total-value">${{ number_format($invoice->total, 2) }}</span>
            </div>
        </div>

        {{-- Notes --}}
        @if($invoice->notes)
        <div class="notes-section">
            <div class="notes-title">Notes</div>
            <div class="notes-content">{{ $invoice->notes }}</div>
        </div>
        @endif

        {{-- Footer --}}
        <div class="footer">
            <div class="footer-line">Thank you for your business!</div>
            <div class="footer-line">For questions about this invoice, please contact us at info@yourcompany.com</div>
        </div>
    </div>
</body>
</html>
