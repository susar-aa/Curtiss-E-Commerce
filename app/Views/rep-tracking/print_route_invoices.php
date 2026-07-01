<?php
$company = $data['company'];
$invoices = $data['invoices'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Route Invoices Print - <?= htmlspecialchars($data['route']->route_name) ?> - <?= APP_NAME ?></title>
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        /* Base Reset & Typography */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: #e5e5e5;
            font-family: "SF Pro Display", "SF Pro Text", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            font-size: 8.5pt; 
            color: #000000;
            line-height: 1.3; 
            -webkit-font-smoothing: antialiased;
        }

        /* Screen Wrapper */
        .page-wrapper {
            max-width: 210mm;
            margin: 20px auto;
            background: #ffffff;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 10mm; 
            position: relative;
            min-height: 297mm;
            display: flex;
            flex-direction: column;
            page-break-after: always;
        }

        .page-wrapper:last-child {
            page-break-after: avoid;
        }

        .main-content {
            flex: 1;
        }

        /* Action Buttons (Screen Only) */
        .print-controls {
            text-align: right;
            margin-bottom: 15px;
        }

        .btn-print {
            background-color: #000;
            color: #fff;
            border: 1px solid #000;
            padding: 6px 12px;
            font-size: 9pt;
            cursor: pointer;
            border-radius: 4px;
            font-family: inherit;
            text-decoration: none;
            display: inline-block;
            transition: background 0.2s;
        }

        .btn-print:hover {
            background-color: #333;
        }

        /* Header Section */
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px; 
            border-bottom: 2px solid #000; 
            padding-bottom: 10px; 
        }

        .company-info {
            width: 55%;
        }

        .company-logo {
            max-width: 120px; 
            max-height: 45px;
            margin-bottom: 5px;
            object-fit: contain;
        }

        .company-name {
            font-size: 12pt;
            font-weight: 800; 
            margin-bottom: 2px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .company-details {
            font-size: 8pt;
            color: #222;
        }

        .invoice-meta {
            width: 40%;
            text-align: right;
        }

        .document-title {
            font-size: 18pt; 
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
        }

        .meta-table th, .meta-table td {
            padding: 2px 0; 
            font-size: 8.5pt;
            text-align: right;
        }

        .meta-table th {
            font-weight: 700;
            padding-right: 10px;
            color: #000;
            white-space: nowrap;
            text-transform: uppercase; 
            letter-spacing: 0.5px;
            font-size: 7.5pt;
        }

        .meta-table td {
            font-weight: 500;
            font-variant-numeric: tabular-nums; 
        }

        /* Customer Section */
        .customer-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px; 
        }

        .bill-to {
            width: 48%;
        }

        .section-heading {
            font-size: 8pt;
            font-weight: 800; 
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #000;
            border-bottom: 1px solid #000;
            padding-bottom: 3px;
            margin-bottom: 6px;
        }

        .customer-name {
            font-size: 10pt;
            font-weight: 800;
            margin-bottom: 2px;
        }

        .customer-details {
            font-size: 8.5pt;
        }

        /* Items Table - Clean Professional List */
        .table-items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px; 
        }

        .table-items th, .table-items td {
            padding: 6px 4px; 
            font-size: 8.5pt;
        }

        .table-items th {
            border-top: 2px solid #000; 
            border-bottom: 2px solid #000;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 7.5pt;
            text-align: left;
            color: #000;
        }

        .table-items td {
            border-bottom: 1px solid #eaeaea; 
        }

        .table-items tr:last-child td {
            border-bottom: 1px solid #000; 
        }

        .table-items th.num, .table-items td.num {
            text-align: right;
            font-variant-numeric: tabular-nums; 
        }

        .table-items th.center, .table-items td.center {
            text-align: center;
        }

        /* Bottom Section: Payment Info & Totals side-by-side */
        .bottom-section {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            gap: 20px;
        }

        /* Payment & Bank Details Block */
        .payment-info {
            flex: 1;
            font-size: 8pt;
            border: 1px solid #000; 
            padding: 10px;
            background-color: #fafafa;
        }

        .payment-info .section-heading {
            border-bottom: 1px solid #ccc;
            margin-bottom: 8px;
            padding-bottom: 4px;
        }

        .terms-text {
            line-height: 1.5;
        }

        .terms-text strong {
            color: #000;
            font-weight: 700;
        }

        /* Totals Section */
        .summary-section {
            width: 300px; 
        }

        .table-totals {
            width: 100%; 
            border-collapse: collapse;
        }

        .table-totals th, .table-totals td {
            padding: 4px 6px; 
            font-size: 8.5pt;
            border-bottom: 1px solid #f0f0f0;
        }

        .table-totals th {
            text-align: right;
            font-weight: 600;
            color: #444;
            width: 60%;
        }

        .table-totals td {
            text-align: right;
            font-weight: 500;
            width: 40%;
            font-variant-numeric: tabular-nums; 
        }

        .table-totals tr.bold-row th,
        .table-totals tr.bold-row td {
            border-top: 2px solid #000; 
            border-bottom: 2px solid #000;
            font-weight: 800;
            font-size: 9.5pt;
            color: #000;
        }

        .table-totals tr.due-row th,
        .table-totals tr.due-row td {
            border-bottom: 2px solid #000;
            font-weight: 800;
            font-size: 10pt;
            color: #000;
        }

        /* Signatures Section */
        .signature-section {
            display: flex;
            justify-content: space-between;
            margin-top: 30px; 
            page-break-inside: avoid;
        }

        .signature-box {
            width: 200px;
            text-align: center;
        }

        .signature-line {
            border-bottom: 1px solid #000;
            margin-bottom: 4px;
            height: 30px; 
        }

        .signature-label {
            font-size: 7.5pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #000; 
        }

        /* Footer */
        .document-footer {
            margin-top: auto; 
            border-top: 1px solid #ccc;
            padding-top: 5px;
            font-size: 7.5pt;
            color: #666;
            display: flex;
            justify-content: space-between;
        }

        .no-print { 
            margin-bottom: 15px; 
            text-align: right; 
            background: #f8f9fa; 
            padding: 10px; 
            border-radius: 6px; 
            border: 1px solid #ddd;
        }
        
        .no-print button { 
            padding: 6px 14px; 
            font-size: 12px; 
            font-weight: bold; 
            border-radius: 4px; 
            cursor: pointer; 
        }
        .btn-print-action { background: #3f51b5; color: #fff; border: none; }
        .btn-close { background: #fff; color: #333; border: 1px solid #ccc; margin-left: 8px; }

        /* Print Specific Styles */
        @media print {
            @page {
                size: A4 portrait;
                margin: 10mm; 
            }
            
            body {
                background: none;
                margin: 0;
                padding: 0;
            }

            .page-wrapper {
                margin: 0;
                padding: 0;
                box-shadow: none;
                border: none;
                width: 100%;
                max-width: none;
                min-height: 0;
                display: block; 
                page-break-after: always;
            }

            .page-wrapper:last-child {
                page-break-after: avoid;
            }

            .no-print {
                display: none !important;
            }

            .table-items th {
                -webkit-print-color-adjust: exact;
                color-adjust: exact;
            }

            .table-items thead {
                display: table-header-group;
            }

            .table-items tr {
                page-break-inside: avoid;
            }

            .bottom-section, .signature-section {
                page-break-inside: avoid;
            }
            
            .document-footer {
                margin-top: 20px; 
            }
        }
    </style>
</head>
<body onload="window.print()">
    
    <div class="no-print" style="max-width: 210mm; margin: 20px auto 0 auto; background: #f8f9fa; padding: 10px; border-radius: 6px; border: 1px solid #ddd; display: flex; justify-content: space-between; align-items: center;">
        <div style="font-weight: bold; font-size: 13px; color: #333;">
            <i class="ph ph-files"></i> Route Invoices: <?= htmlspecialchars($data['route']->route_name) ?> (<?= count($invoices) ?> Invoices)
        </div>
        <div>
            <button class="btn-print-action" onclick="window.print()"><i class="ph ph-printer"></i> Print All Invoices</button>
            <button class="btn-close" onclick="window.close()">Close</button>
        </div>
    </div>

    <?php if (empty($invoices)): ?>
        <div class="page-wrapper" style="min-height: auto; text-align: center; padding: 40px; justify-content: center; align-items: center;">
            <div style="font-size: 16px; font-weight: bold; color: #666;">No active invoices found on this route.</div>
        </div>
    <?php else: ?>
        <?php foreach ($invoices as $invData): 
            $invoice = $invData['invoice'];
            $items = $invData['items'];
            $invoicePaid = $invData['invoice_paid'];
            $totalOutstanding = $invData['total_outstanding'];
            
            // Calculations
            $subTotal = $invoice->total_amount;
            $globalDiscountAmount = 0;
            
            if($invoice->global_discount_val > 0) {
                if ($invoice->global_discount_type == '%') {
                    $globalDiscountAmount = $subTotal * ($invoice->global_discount_val / 100);
                } else {
                    $globalDiscountAmount = $invoice->global_discount_val;
                }
            }
            
            $netSubTotal = $subTotal - $globalDiscountAmount;
            if ($netSubTotal < 0) $netSubTotal = 0;

            $thisInvoiceGrandTotal = $netSubTotal + $invoice->tax_amount;

            $previousBalance = $totalOutstanding;
            if (in_array($invoice->status, ['Unpaid', 'Draft'])) {
                $previousBalance -= $thisInvoiceGrandTotal;
            }
            $amountDueNow = $previousBalance + $thisInvoiceGrandTotal;
            $showUnpaid = in_array($invoice->status, ['Unpaid', 'Draft']) && ($previousBalance > 0.01 || $previousBalance < -0.01);
        ?>
        <div class="page-wrapper">
            <div class="main-content">
                <!-- Header -->
                <div class="invoice-header">
                    <div class="company-info">
                        <?php if(!empty($company->logo_path)): ?>
                            <img src="<?= APP_URL ?>/uploads/<?= htmlspecialchars($company->logo_path) ?>" class="company-logo" alt="Company Logo">
                        <?php else: ?>
                            <div class="company-name"><?= htmlspecialchars($company->company_name) ?></div>
                        <?php endif; ?>
                        
                        <div class="company-details">
                            <?php if(!empty($company->address)) echo nl2br(htmlspecialchars($company->address)) . '<br>'; ?>
                            <?php if(!empty($company->phone)) echo 'Tel: ' . htmlspecialchars($company->phone) . '<br>'; ?>
                            <?php if(!empty($company->email)) echo 'Email: ' . htmlspecialchars($company->email) . '<br>'; ?>
                            <?php if(!empty($company->tax_number)) echo 'VAT/Tax Reg: ' . htmlspecialchars($company->tax_number); ?>
                        </div>
                    </div>

                    <div class="invoice-meta">
                        <div class="document-title">Invoice</div>
                        <table class="meta-table">
                            <tr>
                                <th>Invoice No:</th>
                                <td><?= htmlspecialchars($invoice->invoice_number) ?></td>
                            </tr>
                            <tr>
                                <th>Date:</th>
                                <td><?= date('d-M-Y', strtotime($invoice->invoice_date)) ?></td>
                            </tr>
                            <tr>
                                <th>Due Date:</th>
                                <td><?= date('d-M-Y', strtotime($invoice->due_date)) ?></td>
                            </tr>
                            <?php if(!empty($invoice->cheque_date)): ?>
                            <tr>
                                <th>Cheque Date:</th>
                                <td><?= date('d-M-Y', strtotime($invoice->cheque_date)) ?></td>
                            </tr>
                            <?php endif; ?>
                            <tr>
                                <th>Status:</th>
                                <td><strong><?= strtoupper($invoice->status) ?></strong></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Customer Section -->
                <div class="customer-section">
                    <div class="bill-to">
                        <div class="section-heading">Bill To</div>
                        <div class="customer-name"><?= htmlspecialchars($invoice->customer_name) ?></div>
                        <div class="customer-details">
                            <?php if(!empty($invoice->address)) echo nl2br(htmlspecialchars($invoice->address)) . '<br>'; ?>
                            <?php if(!empty($invoice->phone)) echo 'Tel: ' . htmlspecialchars($invoice->phone); ?>
                        </div>
                    </div>
                </div>

                <!-- Items Table (Clean Professional List) -->
                <table class="table-items">
                    <thead>
                        <tr>
                            <th class="center" style="width: 5%;">#</th>
                            <th style="width: 45%;">Description</th>
                            <th class="num" style="width: 10%;">Qty</th>
                            <th class="num" style="width: 13%;">Price</th>
                            <th class="num" style="width: 12%;">Disc.</th>
                            <th class="num" style="width: 15%;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $rowNum = 1; foreach($items as $item): ?>
                        <tr>
                            <td class="center"><?= $rowNum++ ?></td>
                            <td><?= htmlspecialchars($item->description) ?></td>
                            <td class="num"><?= number_format($item->quantity, 0) ?></td>
                            <td class="num"><?= number_format($item->unit_price, 2) ?></td>
                            <td class="num">
                                <?php if($item->discount_value > 0): ?>
                                    <?= $item->discount_type == '%' ? $item->discount_value . '%' : number_format($item->discount_value, 2) ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td class="num"><?= number_format($item->total, 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Bottom Section: Payment Info & Totals -->
                <div class="bottom-section">
                    
                    <!-- Dedicated Bank & Payment Details Block -->
                    <div class="payment-info">
                        <div class="section-heading">Payment & Terms</div>
                        <div class="terms-text">
                            <strong>Cheques:</strong> To be drawn in favour of "Falcon Stationary PVT (LTD)".<br><br>
                            <strong>Bank Deposits:</strong><br>
                            • 1122015325 - Commercial Bank<br>
                            • 101100120033403 - Peoples Bank<br><br>
                            <strong>Returns:</strong> Market reaturns are allowed within a three months period only.
                        </div>
                    </div>

                    <!-- Summary / Totals -->
                    <div class="summary-section">
                        <table class="table-totals">
                            <?php if($invoice->global_discount_val > 0): ?>
                                <tr>
                                    <th>Subtotal:</th>
                                    <td><?= number_format($subTotal, 2) ?></td>
                                </tr>
                                <tr>
                                    <th>Discount (<?= $invoice->global_discount_type == '%' ? number_format($invoice->global_discount_val, 2) . '%' : 'Flat' ?>):</th>
                                    <td>(<?= number_format($globalDiscountAmount, 2) ?>)</td>
                                </tr>
                            <?php endif; ?>

                            <tr>
                                <th>Net Subtotal:</th>
                                <td><?= number_format($netSubTotal, 2) ?></td>
                            </tr>
                            
                            <?php if($invoice->tax_amount > 0): ?>
                            <tr>
                                <th>Tax (<?= htmlspecialchars($invoice->tax_name ?? 'Tax') ?> <?= $invoice->rate_percentage ?? '' ?>%):</th>
                                <td><?= number_format($invoice->tax_amount, 2) ?></td>
                            </tr>
                            <?php endif; ?>

                            <tr class="bold-row">
                                <th>Current Invoice Total:</th>
                                <td><?= number_format($thisInvoiceGrandTotal, 2) ?></td>
                            </tr>

                            <?php if($showUnpaid): ?>
                                <tr>
                                    <th>Previous Balance:</th>
                                    <td><?= number_format($previousBalance, 2) ?></td>
                                </tr>
                                <tr class="due-row">
                                    <th>Total Amount Due:</th>
                                    <td><?= number_format($amountDueNow, 2) ?></td>
                                </tr>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>

                <!-- Signatures -->
                <div class="signature-section">
                    <div class="signature-box">
                        <div class="signature-line"></div>
                        <div class="signature-label">Customer Signature</div>
                    </div>
                    <div class="signature-box">
                        <div class="signature-line"></div>
                        <div class="signature-label">Authorized Signatory</div>
                    </div>
                </div>
            </div>

            <!-- System Footer -->
            <div class="document-footer">
                <div>Generated by <?= APP_NAME ?> on <?= date('d-M-Y H:i') ?> | Route: <?= htmlspecialchars($data['route']->route_name) ?></div>
                <div>Page 1 of 1</div>
            </div>

        </div>
        <?php endforeach; ?>
    <?php endif; ?>

</body>
</html>
