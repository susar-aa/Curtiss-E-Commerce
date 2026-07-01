<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Route Loading Report - <?= htmlspecialchars($data['route']->route_name) ?></title>
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm 15mm 12mm 15mm;
        }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif; 
            margin: 0; 
            padding: 0; 
            font-size: 11px; 
            color: #111; 
            background: #fff;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .report-container {
            width: 100%;
            max-width: 190mm;
            margin: 0 auto;
        }
        .header-section { 
            margin-bottom: 15px; 
            border-bottom: 2px solid #000; 
            padding-bottom: 8px;
        }
        .header-section h1 { 
            margin: 0 0 4px 0; 
            font-size: 18px; 
            text-transform: uppercase; 
            letter-spacing: 0.5px;
            color: #000;
        }
        .meta-grid {
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 10px;
            margin-top: 5px;
        }
        .meta-group p { margin: 2px 0; line-height: 1.3; font-size: 11px; }
        .stats-summary {
            background: #f5f5f5 !important;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 6px 10px;
            display: flex;
            justify-content: space-around;
            align-items: center;
        }
        .stat-item { text-align: center; }
        .stat-item span { display: block; font-size: 9px; text-transform: uppercase; color: #555; font-weight: bold; }
        .stat-item strong { font-size: 13px; color: #000; font-family: monospace, sans-serif; }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 5px; 
        }
        th { 
            background-color: #eaeaea !important; 
            font-weight: bold; 
            text-transform: uppercase; 
            font-size: 9px; 
            border: 1px solid #000;
            padding: 5px 6px;
        }
        td { 
            border: 1px solid #666; 
            padding: 4px 6px;
            font-size: 11px;
            line-height: 1.2;
        }
        tr:nth-child(even) { background-color: #fcfcfc; }
        .tick-box-placeholder {
            display: inline-block;
            width: 12px;
            height: 12px;
            border: 1px solid #444;
            border-radius: 2px;
            vertical-align: middle;
            background: #fff;
        }
        .line-input-placeholder {
            display: inline-block;
            width: 90%;
            border-bottom: 1px dotted #444;
            height: 12px;
            vertical-align: bottom;
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
        .btn-print { background: #3f51b5; color: #fff; border: none; }
        .btn-close { background: #fff; color: #333; border: 1px solid #ccc; margin-left: 8px; }
        .category-header-row {
            background-color: #f1f3f9 !important;
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
        }
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; }
            th { background-color: #eaeaea !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .stats-summary { background-color: #f5f5f5 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="report-container">
        <div class="no-print">
            <button class="btn-print" onclick="window.print()"><i class="ph ph-printer"></i> Print Document</button>
            <button class="btn-close" onclick="window.close()">Close Window</button>
        </div>

        <div class="header-section">
            <h1>
                <?php 
                if ($data['type'] === 'final') {
                    echo 'Final Loading Verification Sheet';
                } elseif ($data['type'] === 'summary') {
                    echo 'Route Loading Summary & Dispatch Report';
                } else {
                    echo 'Pre-Loading Picking Sheet';
                }
                ?>
            </h1>
            <div class="meta-grid">
                <div class="meta-group">
                    <p><strong>Route Name:</strong> <?= htmlspecialchars($data['route']->route_name) ?></p>
                    <p><strong>Assigned Rep:</strong> <?= htmlspecialchars($data['route']->first_name . ' ' . $data['route']->last_name) ?></p>
                    <p><strong>Printed On:</strong> <?= date('Y-m-d g:i A') ?></p>
                </div>
                <div class="stats-summary">
                    <div class="stat-item">
                        <span>Active Bills</span>
                        <strong><?= $data['route']->bill_count ?></strong>
                    </div>
                    <div style="width: 1px; height: 20px; background: #ccc;"></div>
                    <div class="stat-item">
                        <span>Total Route Value</span>
                        <strong>Rs. <?= number_format($data['route']->total_sales, 2) ?></strong>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($data['type'] === 'final'): ?>
            <!-- FINAL LOADING TABLE -->
            <table>
                <thead>
                    <tr>
                        <th style="width:5%; text-align:center;">No</th>
                        <th style="text-align:left; width:50%;">Product Name</th>
                        <th style="text-align:center; width:15%;">Qty</th>
                        <th style="text-align:right; width:15%;">Unit Price</th>
                        <th style="width:15%;"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $counter = 1;
                    if (empty($data['items'])): 
                    ?>
                        <tr>
                            <td colspan="5" style="text-align:center; padding: 15px; color: #555;">No picking items verified for final loading.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($data['items'] as $item): ?>
                            <tr>
                                <td style="text-align:center;"><?= $counter++ ?></td>
                                <td style="font-weight: 500;"><?= htmlspecialchars($item->item_name) ?></td>
                                <td style="text-align:center; font-family: monospace; font-size:11px; font-weight:bold;"><?= $item->final_loaded_qty !== null ? floatval($item->final_loaded_qty) : floatval($item->required_qty) ?></td>
                                <td style="text-align:right; font-family: monospace; font-size:11px;"><?= number_format($item->unit_price, 2) ?></td>
                                <td style="text-align:center;"><span class="tick-box-placeholder"></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Route Variance Summary Box -->
            <div style="margin-top: 20px; border: 1px solid #000; border-radius: 4px; padding: 10px; background: #fafafa;">
                <h3 style="margin: 0 0 8px 0; font-size: 12px; text-transform: uppercase;">Total Route Summary & Verification</h3>
                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; font-size: 11px;">
                    <div>
                        <strong>Total Items Count:</strong><br>
                        <?= count($data['items']) ?> items
                    </div>
                    <div>
                        <strong>Total Shortages:</strong><br>
                        <?php 
                        $shortages = 0;
                        foreach($data['items'] as $item) {
                            if (floatval($item->variance) < 0) $shortages += abs(floatval($item->variance));
                        }
                        echo $shortages . ' pcs';
                        ?>
                    </div>
                    <div>
                        <strong>Total Overages:</strong><br>
                        <?php 
                        $overages = 0;
                        foreach($data['items'] as $item) {
                            if (floatval($item->variance) > 0) $overages += floatval($item->variance);
                        }
                        echo $overages . ' pcs';
                        ?>
                    </div>
                    <div>
                        <strong>Physical Verification:</strong><br>
                        <span style="display:inline-block; border-bottom:1px solid #444; width:100px; height:12px;"></span>
                    </div>
                </div>
            </div>

        <?php elseif ($data['type'] === 'summary'): ?>
            <!-- LOADING SUMMARY: ORDER GROUPING & CUSTOMER BREAKDOWN -->
            <h3 style="margin-top: 15px; margin-bottom: 5px; font-size: 12px; text-transform: uppercase;">1. Sales Orders Grouping (Customer-wise)</h3>
            <table>
                <thead>
                    <tr>
                        <th style="width: 5%; text-align: center;">#</th>
                        <th style="width: 25%; text-align: left;">Customer Name</th>
                        <th style="width: 25%; text-align: left;">Invoice / Sales Order No</th>
                        <th style="width: 20%; text-align: center;">Date & Time</th>
                        <th style="width: 15%; text-align: right;">Grand Total (Rs)</th>
                        <th style="width: 10%; text-align: center;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $counter = 1;
                    $grandTotal = 0;
                    if (empty($data['bills'])):
                    ?>
                        <tr>
                            <td colspan="6" style="text-align:center; padding: 15px; color: #555;">No orders bound to this route.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($data['bills'] as $bill): ?>
                            <?php $grandTotal += floatval($bill->true_grand_total); ?>
                            <tr>
                                <td style="text-align: center;"><?= $counter++ ?></td>
                                <td style="font-weight: bold;"><?= htmlspecialchars($bill->customer_name) ?></td>
                                <td style="font-family: monospace; font-weight: bold;"><?= htmlspecialchars($bill->invoice_number) ?></td>
                                <td style="text-align: center;"><?= date('Y-m-d g:i A', strtotime($bill->created_at)) ?></td>
                                <td style="text-align: right; font-family: monospace; font-weight: bold;">Rs. <?= number_format($bill->true_grand_total, 2) ?></td>
                                <td style="text-align: center; text-transform: uppercase; font-weight: bold; color: <?= $bill->status === 'Paid' ? '#2e7d32' : '#ef6c00' ?>;"><?= htmlspecialchars($bill->status) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr style="background-color: #eaeaea; font-weight: bold;">
                            <td colspan="4" style="text-align: right; text-transform: uppercase;">Route Dispatch Grand Total:</td>
                            <td style="text-align: right; font-family: monospace; font-size: 12px;">Rs. <?= number_format($grandTotal, 2) ?></td>
                            <td></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <h3 style="margin-top: 25px; margin-bottom: 5px; font-size: 12px; text-transform: uppercase;">2. Customer Dispatch breakdown</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 5px;">
                <div style="border: 1px solid #ccc; border-radius: 4px; padding: 10px; background: #fafafa;">
                    <h4 style="margin: 0 0 8px 0; font-size: 11px; text-transform: uppercase;">Delivery Checklist</h4>
                    <ul style="margin: 0; padding-left: 15px; line-height: 1.6;">
                        <li>Ensure all physical collections are registered</li>
                        <li>Match invoice counts with warehouse loading sheets</li>
                        <li>Have rep sign off on vehicle loading verification</li>
                        <li>Record dispatch vehicle departure odometer reading</li>
                    </ul>
                </div>
                <div style="border: 1px solid #ccc; border-radius: 4px; padding: 10px; background: #fafafa; display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <h4 style="margin: 0 0 5px 0; font-size: 11px; text-transform: uppercase;">Odometer Verification</h4>
                        <p style="margin: 2px 0;">Departure Odometer: ___________________ km</p>
                        <p style="margin: 2px 0;">Arrival Odometer: _____________________ km</p>
                    </div>
                    <div>
                        <p style="margin: 0; font-size: 9px; color: #555;">Verify helper/partner crew status prior to gate pass approval.</p>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- PRE LOADING TABLE -->
            <table>
                <thead>
                    <tr>
                        <th style="width:5%; text-align:center;">No</th>
                        <th style="text-align:left; width:50%;">Product Name</th>
                        <th style="text-align:center; width:15%;">Qty</th>
                        <th style="text-align:right; width:15%;">Unit Price</th>
                        <th style="width:15%;"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $counter = 1;
                    if (empty($data['items'])): 
                    ?>
                        <tr>
                            <td colspan="5" style="text-align:center; padding: 15px; color: #555;">No items found for pre-loading on this route.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($data['items'] as $item): ?>
                            <tr>
                                <td style="text-align:center;"><?= $counter++ ?></td>
                                <td style="font-weight: 500;"><?= htmlspecialchars($item->item_name) ?></td>
                                <td style="text-align:center; font-family: monospace; font-size:12px; font-weight:bold;"><?= floatval($item->total_qty) ?></td>
                                <td style="text-align:right; font-family: monospace; font-size:11px;"><?= number_format($item->unit_price, 2) ?></td>
                                <td style="text-align:center;"><span class="tick-box-placeholder"></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <!-- Signature Block -->
        <div style="margin-top: 40px; display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; text-align: center; font-size: 10px;">
            <div>
                <p style="margin-bottom: 25px;">Prepared By:</p>
                <p>___________________________</p>
                <p style="color: #666;">Logistics / Admin</p>
            </div>
            <div>
                <p style="margin-bottom: 25px;">Verified By (OIC):</p>
                <p>___________________________</p>
                <p style="color: #666;">Warehouse Keeper</p>
            </div>
            <div>
                <p style="margin-bottom: 25px;">Acknowledged By:</p>
                <p>___________________________</p>
                <p style="color: #666;">Driver / Representative</p>
            </div>
        </div>
    </div>
</body>
</html>