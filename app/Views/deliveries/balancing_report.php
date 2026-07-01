<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $data['title'] ?></title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            color: #333;
            background: #fff;
            margin: 0;
            padding: 20px;
            font-size: 13px;
            line-height: 1.5;
        }
        
        .report-header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
            margin-bottom: 30px;
        }
        
        .report-title {
            margin: 0;
            font-size: 22px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .report-subtitle {
            margin: 5px 0 0 0;
            font-size: 13px;
            color: #666;
            font-weight: 500;
        }

        .meta-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 30px;
            background: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
        }

        .meta-item {
            font-size: 12.5px;
        }

        .meta-item strong {
            color: #111;
        }

        .section-title {
            font-size: 14px;
            font-weight: 800;
            text-transform: uppercase;
            border-bottom: 1px solid #ccc;
            padding-bottom: 6px;
            margin: 25px 0 15px 0;
            letter-spacing: 0.3px;
        }

        .kpi-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 25px;
        }

        .kpi-card {
            border: 1px solid #ccc;
            border-radius: 6px;
            padding: 12px 15px;
            background: #fff;
        }

        .kpi-card-title {
            font-size: 11px;
            text-transform: uppercase;
            font-weight: bold;
            color: #666;
            margin-bottom: 8px;
            display: block;
        }

        .kpi-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 4px;
            font-size: 12.5px;
        }

        .kpi-row:last-child {
            margin-bottom: 0;
        }

        .kpi-row strong {
            font-family: monospace;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 12px;
        }

        table th, table td {
            padding: 8px 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        table th {
            background: #f2f2f2;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 10.5px;
            color: #444;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .monospace {
            font-family: monospace;
        }

        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .badge-success {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
        }

        .signatures {
            margin-top: 60px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 50px;
        }

        .signature-block {
            border-top: 1px solid #333;
            padding-top: 10px;
            text-align: center;
            font-size: 12px;
        }

        .signature-block p {
            margin: 5px 0;
            color: #555;
        }

        .btn-print {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #007aff;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: bold;
            cursor: pointer;
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        @media print {
            body {
                padding: 0;
            }
            .btn-print {
                display: none;
            }
        }
    </style>
</head>
<body>

    <button class="btn-print" onclick="window.print()">🖨️ Print Report</button>

    <div class="report-header">
        <h1 class="report-title">Delivery Trip Settle Balancing & Audit Report</h1>
        <p class="report-subtitle">Final Settlement, Cash Count Variance, Cheques, and Remaining Stock Reconciliation</p>
    </div>

    <?php 
        $d = $data['delivery'];
        $b = $data['balancing'];
    ?>

    <div class="meta-grid">
        <div class="meta-item">
            Route Name: <strong><?= htmlspecialchars($d->route_name) ?></strong><br>
            Representative: <strong><?= htmlspecialchars($d->first_name . ' ' . $d->last_name) ?></strong><br>
            Delivery Date: <strong><?= date('l, F d, Y', strtotime($d->delivery_date)) ?></strong>
        </div>
        <div class="meta-item">
            Vehicle Number: <strong><?= htmlspecialchars($d->vehicle_number) ?></strong><br>
            Driver Name: <strong><?= htmlspecialchars($d->driver_name) ?></strong><br>
            Partner/Helper: <strong><?= htmlspecialchars($d->partner_name ?: 'None') ?></strong>
        </div>
        <div class="meta-item">
            Arranged At: <strong><?= date('M d, Y h:i A', strtotime($d->created_at)) ?></strong><br>
            Status: <span class="badge badge-success"><?= $d->status ?></span><br>
            Audit Run Date: <strong><?= date('Y-m-d H:i:s') ?></strong>
        </div>
    </div>

    <div class="section-title">📊 Trip Financial Summary</div>
    <div class="kpi-container">
        <div class="kpi-card">
            <span class="kpi-card-title">Today's Sales Summary</span>
            <div class="kpi-row">
                <span>Cash Sales Value:</span>
                <strong style="color: #2e7d32;">Rs <?= number_format($b['cash_sales'], 2) ?></strong>
            </div>
            <div class="kpi-row">
                <span>Credit Sales Value:</span>
                <strong style="color: #ef6c00;">Rs <?= number_format($b['credit_sales'], 2) ?></strong>
            </div>
            <div class="kpi-row" style="border-top: 1px dashed #ccc; padding-top: 4px; margin-top: 6px; font-weight: bold;">
                <span>Total Route Sales:</span>
                <strong>Rs <?= number_format($b['cash_sales'] + $b['credit_sales'], 2) ?></strong>
            </div>
        </div>

        <div class="kpi-card">
            <span class="kpi-card-title">Driver Collections Today</span>
            <div class="kpi-row">
                <span>Cash:</span>
                <strong>Rs <?= number_format($b['cash_collections'], 2) ?></strong>
            </div>
            <div class="kpi-row">
                <span>Cheque:</span>
                <strong>Rs <?= number_format($b['cheque_collections'], 2) ?></strong>
            </div>
            <div class="kpi-row">
                <span>Bank Transfer:</span>
                <strong>Rs <?= number_format($b['bank_collections'], 2) ?></strong>
            </div>
            <div class="kpi-row" style="border-top: 1px dashed #ccc; padding-top: 4px; margin-top: 6px; font-weight: bold;">
                <span>Total Collected:</span>
                <strong>Rs <?= number_format($b['cash_collections'] + $b['cheque_collections'] + $b['bank_collections'], 2) ?></strong>
            </div>
        </div>

        <div class="kpi-card">
            <span class="kpi-card-title">Odometer & Mileage Audit</span>
            <div class="kpi-row">
                <span>Start Odometer:</span>
                <strong><?= number_format($d->start_meter, 0) ?> KM</strong>
            </div>
            <div class="kpi-row">
                <span>End Odometer:</span>
                <strong><?= number_format($d->end_meter, 0) ?> KM</strong>
            </div>
            <?php $dist = max(0, $d->end_meter - $d->start_meter); ?>
            <div class="kpi-row" style="border-top: 1px dashed #ccc; padding-top: 4px; margin-top: 6px; font-weight: bold;">
                <span>Distance Traveled:</span>
                <strong><?= number_format($dist, 0) ?> KM</strong>
            </div>
        </div>
    </div>

    <div class="section-title">💵 Cash Count Denomination Audit</div>
    <div style="display: flex; gap: 40px; margin-bottom: 25px;">
        <div style="flex: 1;">
            <?php 
                $cashDenoms = [];
                try {
                    if ($d->cash_denominations) {
                        $cashDenoms = json_decode($d->cash_denominations, true);
                    }
                } catch(Exception $e) {}
                
                $denomList = [5000, 2000, 1000, 500, 100, 50, 20, 'coins'];
                $totalCashEntered = 0.0;
            ?>
            <table>
                <thead>
                    <tr>
                        <th>Denomination</th>
                        <th class="text-center">Count</th>
                        <th class="text-right">Total Amount (Rs)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($denomList as $den): ?>
                        <?php 
                            $count = isset($cashDenoms[$den]) ? intval($cashDenoms[$den]) : 0;
                            $val = 0.0;
                            $label = '';
                            if ($den === 'coins') {
                                $val = isset($cashDenoms['coins']) ? floatval($cashDenoms['coins']) : 0.0;
                                $label = 'Coins';
                            } else {
                                $val = $den * $count;
                                $label = 'Rs ' . $den;
                            }
                            $totalCashEntered += $val;
                        ?>
                        <tr>
                            <td style="font-weight: 600;"><?= $label ?></td>
                            <td class="text-center"><?= $den === 'coins' ? '-' : $count ?></td>
                            <td class="text-right monospace"><?= number_format($val, 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div style="width: 320px; background: #fafafa; border: 1px solid #ccc; padding: 20px; border-radius: 6px; display: flex; flex-direction: column; justify-content: center; height: fit-content; margin-top: 10px;">
            <div class="kpi-row" style="margin-bottom: 12px;">
                <span>Expected Cash Collections:</span>
                <strong class="monospace">Rs <?= number_format($b['cash_collections'], 2) ?></strong>
            </div>
            <div class="kpi-row" style="margin-bottom: 12px;">
                <span>Actual Cash Count Entered:</span>
                <strong class="monospace">Rs <?= number_format($totalCashEntered, 2) ?></strong>
            </div>
            <?php $variance = $totalCashEntered - $b['cash_collections']; ?>
            <div class="kpi-row" style="border-top: 1px solid #aaa; padding-top: 12px; font-weight: bold; font-size: 14px;">
                <span>Variance (Difference):</span>
                <strong class="monospace" style="color: <?= abs($variance) < 0.01 ? '#2e7d32' : ($variance < 0 ? '#c62828' : '#ef6c00') ?>;">
                    <?= $variance >= 0 ? '+' : '' ?>Rs <?= number_format($variance, 2) ?>
                </strong>
            </div>
        </div>
    </div>

    <div class="section-title">💳 PDC Cheques Collected Audit</div>
    <table>
        <thead>
            <tr>
                <th>Customer Name</th>
                <th>Bank Name</th>
                <th>Cheque Number</th>
                <th>Banking Date</th>
                <th class="text-right">Amount (Rs)</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($b['cheques'])): ?>
                <tr>
                    <td colspan="5" class="text-center" style="color: #666; padding: 15px;">No cheques collected on this trip.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($b['cheques'] as $ch): ?>
                    <tr>
                        <td style="font-weight: 600;"><?= htmlspecialchars($ch->customer_name) ?></td>
                        <td><?= htmlspecialchars($ch->bank_name) ?></td>
                        <td class="monospace"><?= htmlspecialchars($ch->cheque_number) ?></td>
                        <td><?= date('M d, Y', strtotime($ch->banking_date)) ?></td>
                        <td class="text-right monospace" style="font-weight: 700;"><?= number_format($ch->amount, 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="section-title">📦 Vehicle Stock Balance Audit</div>
    <table>
        <thead>
            <tr>
                <th>Product / Item Description</th>
                <th class="text-center" style="width: 100px;">Loaded Qty</th>
                <th class="text-center" style="width: 100px;">Delivered Qty</th>
                <th class="text-center" style="width: 100px;">Returned Qty</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($b['stock_items'])): ?>
                <tr>
                    <td colspan="4" class="text-center" style="color: #666; padding: 15px;">No stock loaded on this trip.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($b['stock_items'] as $st): ?>
                    <tr>
                        <td style="font-weight: 600;"><?= htmlspecialchars($st->item_name) ?></td>
                        <td class="text-center" style="font-weight: 700;"><?= intval($st->loaded_qty) ?></td>
                        <td class="text-center" style="font-weight: 700; color: #2e7d32;"><?= intval($st->delivered_qty) ?></td>
                        <td class="text-center" style="font-weight: 700; color: #ef6c00;"><?= intval($st->remaining_qty) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="signatures">
        <div class="signature-block">
            <div style="height: 60px;"></div>
            <p><strong>Representative / Driver Signature</strong></p>
            <p>Name: <?= htmlspecialchars($d->driver_name) ?></p>
            <p>Date: ____ / ____ / ________</p>
        </div>
        <div class="signature-block">
            <div style="height: 60px;"></div>
            <p><strong>Auditing Administrator Signature</strong></p>
            <p>Name: _______________________________</p>
            <p>Date: ____ / ____ / ________</p>
        </div>
    </div>

</body>
</html>
