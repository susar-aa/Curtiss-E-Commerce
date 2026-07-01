<?php
$db = new Database();

// Failsafe: Fetch the customer's true total outstanding balance for the "Previous Balance" calculation
$db->query("
    SELECT 
        COALESCE(SUM(total_amount - COALESCE(CASE WHEN global_discount_type = '%' THEN (total_amount * global_discount_val / 100) ELSE global_discount_val END, 0) + COALESCE(tax_amount, 0)), 0) as total_billed
    FROM invoices WHERE customer_id = :id AND status != 'Voided'
");
$db->bind(':id', $data['invoice']->customer_id);
$billed = $db->single()->total_billed ?? 0;

$db->query("SELECT COALESCE(SUM(amount), 0) as total_paid FROM customer_payments WHERE customer_id = :id");
$db->bind(':id', $data['invoice']->customer_id);
$paid = $db->single()->total_paid ?? 0;

$db->query("SELECT COALESCE(SUM(total_amount), 0) as total_credited FROM credit_notes WHERE customer_id = :id");
$db->bind(':id', $data['invoice']->customer_id);
$credited = $db->single()->total_credited ?? 0;

$totalOutstanding = $billed - $paid - $credited;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice <?= htmlspecialchars($data['invoice']->invoice_number) ?> - <?= APP_NAME ?></title>
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

        .btn-excel {
            background-color: #107c41; /* Microsoft Excel Green */
            border-color: #107c41;
            margin-right: 8px;
        }

        .btn-excel:hover {
            background-color: #0c5e31;
        }

        .btn-pdf {
            background-color: #d32f2f; /* PDF Red */
            border-color: #d32f2f;
            margin-right: 8px;
        }

        .btn-pdf:hover {
            background-color: #b71c1c;
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
            }

            .print-controls {
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
<body>
    <div class="page-wrapper">
        
        <!-- Screen Controls -->
        <?php if (!isset($_GET['hide_buttons']) || $_GET['hide_buttons'] !== '1'): ?>
        <div class="print-controls">
            <button onclick="exportToExcel()" class="btn-print btn-excel"><i class="ph ph-chart-bar"></i> Export to Excel</button>
            <a href="<?= APP_URL ?>/sales/download_pdf/<?= $data['invoice']->id ?>" class="btn-print btn-pdf"><i class="ph ph-file-text"></i> Download PDF</a>
            <button onclick="window.print()" class="btn-print"><i class="ph ph-printer"></i> Print Document</button>
        </div>
        <?php endif; ?>

        <div class="main-content">
            <!-- Header -->
            <div class="invoice-header">
                <div class="company-info">
                    <?php if(!empty($data['company']->logo_path)): ?>
                        <img src="<?= APP_URL ?>/uploads/<?= htmlspecialchars($data['company']->logo_path) ?>" class="company-logo" alt="Company Logo">
                    <?php else: ?>
                        <div class="company-name"><?= htmlspecialchars($data['company']->company_name) ?></div>
                    <?php endif; ?>
                    
                    <div class="company-details">
                        <?php if(!empty($data['company']->address)) echo nl2br(htmlspecialchars($data['company']->address)) . '<br>'; ?>
                        <?php if(!empty($data['company']->phone)) echo 'Tel: ' . htmlspecialchars($data['company']->phone) . '<br>'; ?>
                        <?php if(!empty($data['company']->email)) echo 'Email: ' . htmlspecialchars($data['company']->email) . '<br>'; ?>
                        <?php if(!empty($data['company']->tax_number)) echo 'VAT/Tax Reg: ' . htmlspecialchars($data['company']->tax_number); ?>
                    </div>
                </div>

                <div class="invoice-meta">
                    <div class="document-title">Invoice</div>
                    <table class="meta-table">
                        <tr>
                            <th>Invoice No:</th>
                            <td><?= htmlspecialchars($data['invoice']->invoice_number) ?></td>
                        </tr>
                        <tr>
                            <th>Date:</th>
                            <td><?= date('d-M-Y', strtotime($data['invoice']->invoice_date)) ?></td>
                        </tr>
                        <tr>
                            <th>Due Date:</th>
                            <td><?= date('d-M-Y', strtotime($data['invoice']->due_date)) ?></td>
                        </tr>
                        <?php if(!empty($data['invoice']->cheque_date)): ?>
                        <tr>
                            <th>Cheque Date:</th>
                            <td><?= date('d-M-Y', strtotime($data['invoice']->cheque_date)) ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <th>Status:</th>
                            <td><strong><?= strtoupper($data['invoice']->status) ?></strong></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Customer Section -->
            <div class="customer-section">
                <div class="bill-to">
                    <div class="section-heading">Bill To</div>
                    <div class="customer-name"><?= htmlspecialchars($data['invoice']->customer_name) ?></div>
                    <div class="customer-details">
                        <?php if(!empty($data['invoice']->address)) echo nl2br(htmlspecialchars($data['invoice']->address)) . '<br>'; ?>
                        <?php if(!empty($data['invoice']->phone)) echo 'Tel: ' . htmlspecialchars($data['invoice']->phone); ?>
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
                    <?php $rowNum = 1; foreach($data['items'] as $item): ?>
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

            <?php 
                // Calculations
                $subTotal = $data['invoice']->total_amount;
                $globalDiscountAmount = 0;
                
                if($data['invoice']->global_discount_val > 0) {
                    if ($data['invoice']->global_discount_type == '%') {
                        $globalDiscountAmount = $subTotal * ($data['invoice']->global_discount_val / 100);
                    } else {
                        $globalDiscountAmount = $data['invoice']->global_discount_val;
                    }
                }
                
                $netSubTotal = $subTotal - $globalDiscountAmount;
                if ($netSubTotal < 0) $netSubTotal = 0;

                $thisInvoiceGrandTotal = $netSubTotal + $data['invoice']->tax_amount;

                $previousBalance = $totalOutstanding;
                if (in_array($data['invoice']->status, ['Unpaid', 'Draft'])) {
                    $previousBalance -= $thisInvoiceGrandTotal;
                }
                $amountDueNow = $previousBalance + $thisInvoiceGrandTotal;
                $showUnpaid = in_array($data['invoice']->status, ['Unpaid', 'Draft']) && ($previousBalance > 0.01 || $previousBalance < -0.01);
            ?>

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
                        <?php if($data['invoice']->global_discount_val > 0): ?>
                            <tr>
                                <th>Subtotal:</th>
                                <td><?= number_format($subTotal, 2) ?></td>
                            </tr>
                            <tr>
                                <th>Discount (<?= $data['invoice']->global_discount_type == '%' ? number_format($data['invoice']->global_discount_val, 2) . '%' : 'Flat' ?>):</th>
                                <td>(<?= number_format($globalDiscountAmount, 2) ?>)</td>
                            </tr>
                        <?php endif; ?>

                        <tr>
                            <th>Net Subtotal:</th>
                            <td><?= number_format($netSubTotal, 2) ?></td>
                        </tr>
                        
                        <?php if($data['invoice']->tax_amount > 0): ?>
                        <tr>
                            <th>Tax (<?= htmlspecialchars($data['invoice']->tax_name ?? 'Tax') ?> <?= $data['invoice']->rate_percentage ?? '' ?>%):</th>
                            <td><?= number_format($data['invoice']->tax_amount, 2) ?></td>
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
            <div>Generated by <?= APP_NAME ?> on <?= date('d-M-Y H:i') ?></div>
            <div>Page 1 of 1</div>
        </div>

    </div>

    <!-- Excel Export Script -->
    <script>
        function exportToExcel() {
            let html = `
                <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
                <head>
                    <meta charset="utf-8">
                    <style>
                        table { border-collapse: collapse; width: 100%; font-family: 'Segoe UI', Arial, sans-serif; font-size: 10pt; }
                        td, th { vertical-align: top; }
                        .mso-num { mso-number-format:"\\#\\,\\#\\#0\\.00"; text-align: right; }
                        .mso-int { mso-number-format:"0"; text-align: right; }
                    </style>
                </head>
                <body>
                    <table>
                        <!-- Header -->
                        <tr>
                            <td colspan="4" style="font-size: 16pt; font-weight: bold; text-transform: uppercase;">
                                <?= htmlspecialchars($data['company']->company_name) ?>
                            </td>
                            <td colspan="2" style="font-size: 24pt; font-weight: bold; text-align: right; text-transform: uppercase;">
                                INVOICE
                            </td>
                        </tr>
                        <tr>
                            <td colspan="4" style="color: #444; border-bottom: 2px solid #000; padding-bottom: 10px;">
                                <?php if(!empty($data['company']->address)) echo nl2br(htmlspecialchars($data['company']->address)) . '<br>'; ?>
                                <?php if(!empty($data['company']->phone)) echo 'Tel: ' . htmlspecialchars($data['company']->phone) . '<br>'; ?>
                                <?php if(!empty($data['company']->email)) echo 'Email: ' . htmlspecialchars($data['company']->email) . '<br>'; ?>
                                <?php if(!empty($data['company']->tax_number)) echo 'VAT/Tax Reg: ' . htmlspecialchars($data['company']->tax_number); ?>
                            </td>
                            <td colspan="2" style="text-align: right; border-bottom: 2px solid #000; padding-bottom: 10px;">
                                <strong>Invoice No:</strong> <?= htmlspecialchars($data['invoice']->invoice_number) ?><br>
                                <strong>Date:</strong> <?= date('d-M-Y', strtotime($data['invoice']->invoice_date)) ?><br>
                                <strong>Due Date:</strong> <?= date('d-M-Y', strtotime($data['invoice']->due_date)) ?><br>
                                <?php if(!empty($data['invoice']->cheque_date)): ?>
                                <strong>Cheque Date:</strong> <?= date('d-M-Y', strtotime($data['invoice']->cheque_date)) ?><br>
                                <?php endif; ?>
                                <strong>Status:</strong> <?= strtoupper($data['invoice']->status) ?>
                            </td>
                        </tr>
                        <tr><td colspan="6"></td></tr>

                        <!-- Customer -->
                        <tr>
                            <td colspan="6" style="font-size: 10pt; font-weight: bold; text-transform: uppercase; border-bottom: 1px solid #000;">Bill To</td>
                        </tr>
                        <tr>
                            <td colspan="6" style="padding-top: 5px;">
                                <strong style="font-size: 11pt;"><?= htmlspecialchars($data['invoice']->customer_name) ?></strong><br>
                                <?php if(!empty($data['invoice']->address)) echo nl2br(htmlspecialchars($data['invoice']->address)) . '<br>'; ?>
                                <?php if(!empty($data['invoice']->phone)) echo 'Tel: ' . htmlspecialchars($data['invoice']->phone); ?>
                            </td>
                        </tr>
                        <tr><td colspan="6"></td></tr>

                        <!-- Items Header -->
                        <tr>
                            <th style="border-top: 2px solid #000; border-bottom: 2px solid #000; padding: 6px; text-transform: uppercase; text-align: center;">#</th>
                            <th style="border-top: 2px solid #000; border-bottom: 2px solid #000; padding: 6px; text-transform: uppercase; text-align: left;">Description</th>
                            <th style="border-top: 2px solid #000; border-bottom: 2px solid #000; padding: 6px; text-transform: uppercase; text-align: right;">Qty</th>
                            <th style="border-top: 2px solid #000; border-bottom: 2px solid #000; padding: 6px; text-transform: uppercase; text-align: right;">Price</th>
                            <th style="border-top: 2px solid #000; border-bottom: 2px solid #000; padding: 6px; text-transform: uppercase; text-align: right;">Disc.</th>
                            <th style="border-top: 2px solid #000; border-bottom: 2px solid #000; padding: 6px; text-transform: uppercase; text-align: right;">Total</th>
                        </tr>
                        
                        <!-- Items Data -->
                        <?php 
                        $rowNum = 1; 
                        $totalItems = count($data['items']); 
                        foreach($data['items'] as $index => $item): 
                            $isLast = ($index === $totalItems - 1);
                            $bottomBorder = $isLast ? 'border-bottom: 2px solid #000;' : 'border-bottom: 1px solid #eaeaea;';
                        ?>
                        <tr>
                            <td style="text-align: center; padding: 6px; <?= $bottomBorder ?>"><?= $rowNum++ ?></td>
                            <td style="padding: 6px; <?= $bottomBorder ?>"><?= htmlspecialchars($item->description) ?></td>
                            <td class="mso-int" style="padding: 6px; <?= $bottomBorder ?>"><?= $item->quantity ?></td>
                            <td class="mso-num" style="padding: 6px; <?= $bottomBorder ?>"><?= $item->unit_price ?></td>
                            <td class="mso-num" style="padding: 6px; <?= $bottomBorder ?>">
                                <?php if($item->discount_value > 0): ?>
                                    <?= $item->discount_type == '%' ? $item->discount_value . '%' : $item->discount_value ?>
                                <?php else: ?>
                                    0
                                <?php endif; ?>
                            </td>
                            <td class="mso-num" style="padding: 6px; <?= $bottomBorder ?>"><?= $item->total ?></td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <tr><td colspan="6"></td></tr>

                        <?php
                            // Calculate exactly how many rows the totals section takes up so we can rowspan the terms box perfectly
                            $totalsRows = 3; 
                            if($data['invoice']->global_discount_val > 0) $totalsRows++;
                            if($data['invoice']->tax_amount > 0) $totalsRows++;
                            if($showUnpaid) $totalsRows++; // Adding 1 for previous balance
                        ?>

                        <!-- Summary & Payment Box -->
                        <tr>
                            <td colspan="4" rowspan="<?= $totalsRows ?>" style="border: 1px solid #000; padding: 10px; background-color: #fafafa;">
                                <strong style="font-size: 11pt; border-bottom: 1px solid #ccc;">Payment & Terms</strong><br><br>
                                <strong>Cheques:</strong> To be drawn in favour of "Falcon Stationary PVT (LTD)".<br><br>
                                <strong>Bank Deposits:</strong><br>
                                • 1122015325 - Commercial Bank<br>
                                • 101100120033403 - Peoples Bank<br><br>
                                <strong>Returns:</strong> Market reaturns are allowed within a three months period only.
                            </td>
                            <th style="text-align: right; padding: 4px;">Subtotal:</th>
                            <td class="mso-num" style="padding: 4px;"><?= $subTotal ?></td>
                        </tr>

                        <?php if($data['invoice']->global_discount_val > 0): ?>
                        <tr>
                            <th style="text-align: right; padding: 4px;">Discount (<?= $data['invoice']->global_discount_type == '%' ? number_format($data['invoice']->global_discount_val, 2) . '%' : 'Flat' ?>):</th>
                            <td class="mso-num" style="padding: 4px; color: #cc0000;">-<?= $globalDiscountAmount ?></td>
                        </tr>
                        <?php endif; ?>

                        <tr>
                            <th style="text-align: right; padding: 4px;">Net Subtotal:</th>
                            <td class="mso-num" style="padding: 4px;"><?= $netSubTotal ?></td>
                        </tr>

                        <?php if($data['invoice']->tax_amount > 0): ?>
                        <tr>
                            <th style="text-align: right; padding: 4px;">Tax (<?= htmlspecialchars($data['invoice']->tax_name ?? 'Tax') ?> <?= $data['invoice']->rate_percentage ?? '' ?>%):</th>
                            <td class="mso-num" style="padding: 4px;"><?= $data['invoice']->tax_amount ?></td>
                        </tr>
                        <?php endif; ?>

                        <tr>
                            <th style="border-top: 2px solid #000; border-bottom: 2px solid #000; text-align: right; padding: 6px;">Current Invoice Total:</th>
                            <td class="mso-num" style="border-top: 2px solid #000; border-bottom: 2px solid #000; font-weight: bold; padding: 6px;"><?= $thisInvoiceGrandTotal ?></td>
                        </tr>

                        <?php if($showUnpaid): ?>
                            <tr>
                                <th style="text-align: right; padding: 4px;">Previous Balance:</th>
                                <td class="mso-num" style="padding: 4px;"><?= $previousBalance ?></td>
                            </tr>
                            <tr>
                                <td colspan="4"></td>
                                <th style="border-bottom: 2px solid #000; text-align: right; padding: 6px;">Total Amount Due:</th>
                                <td class="mso-num" style="border-bottom: 2px solid #000; font-weight: bold; padding: 6px;"><?= $amountDueNow ?></td>
                            </tr>
                        <?php endif; ?>

                    </table>
                </body>
                </html>
            `;

            // Create Blob and Trigger Download
            let blob = new Blob([html], { type: 'application/vnd.ms-excel' });
            let link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = 'Invoice_<?= htmlspecialchars($data['invoice']->invoice_number) ?>.xls';
            
            // Append, click, and cleanup
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    </script>
</body>
</html>