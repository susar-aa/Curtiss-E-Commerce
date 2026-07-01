<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($data['title']) ?></title>
    <style>
        :root {
            --bg-color: #f7f9fa;
            --text-color: #2c3e50;
            --border-color: #e2e8f0;
            --primary-color: #007aff;
            --primary-dark: #0056b3;
            --success-color: #2e7d32;
            --warning-color: #d84315;
            --panel-bg: #ffffff;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --bg-color: #0f0f1a;
                --text-color: #e2e8f0;
                --border-color: #2a2a3e;
                --primary-color: #3b82f6;
                --primary-dark: #2563eb;
                --success-color: #10b981;
                --warning-color: #f59e0b;
                --panel-bg: #181824;
            }
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            margin: 0;
            padding: 30px;
            box-sizing: border-box;
            font-size: 14px;
            line-height: 1.5;
        }

        .spreadsheet-container {
            max-width: 1200px;
            margin: 0 auto;
            background: var(--panel-bg);
            border-radius: 12px;
            border: 1px solid var(--border-color);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            padding: 40px;
            transition: all 0.3s ease;
        }

        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 25px;
            margin-bottom: 30px;
        }

        .title-area h1 {
            margin: 0 0 8px 0;
            font-size: 24px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .title-area p {
            margin: 0;
            color: #888;
            font-weight: 500;
        }

        .actions-area {
            display: flex;
            gap: 12px;
        }

        .btn-sheet {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            font-size: 13.5px;
            text-decoration: none;
            transition: all 0.2s ease;
            background: var(--panel-bg);
            color: var(--text-color);
        }

        .btn-sheet:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
            transform: translateY(-1px);
        }

        .btn-sheet.btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: #fff;
            border: none;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.25);
        }

        .btn-sheet.btn-primary:hover {
            color: #fff;
            box-shadow: 0 6px 18px rgba(59, 130, 246, 0.35);
        }

        /* Metadata Grid */
        .meta-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
            background: rgba(0,0,0,0.01);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 20px;
        }
        @media (prefers-color-scheme: dark) {
            .meta-grid {
                background: rgba(255,255,255,0.01);
            }
        }

        .meta-box {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .meta-box span {
            font-size: 10.5px;
            font-weight: 700;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .meta-box strong {
            font-size: 15px;
            font-weight: 600;
        }

        /* Tables & Grid styling */
        .grid-header {
            margin: 40px 0 15px 0;
            font-size: 16px;
            font-weight: 700;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 8px;
        }

        .excel-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 40px;
        }

        .excel-table th, .excel-table td {
            border: 1px solid var(--border-color);
            padding: 12px 18px;
            text-align: left;
        }

        .excel-table th {
            background-color: rgba(0, 0, 0, 0.02);
            font-weight: 700;
            font-size: 12px;
            text-transform: uppercase;
            color: #888;
            letter-spacing: 0.3px;
        }
        @media (prefers-color-scheme: dark) {
            .excel-table th {
                background-color: rgba(255, 255, 255, 0.02);
            }
        }

        .excel-table tr:hover td {
            background-color: rgba(0, 122, 255, 0.02);
        }

        .qty-badge {
            font-weight: 700;
            font-size: 15px;
            font-family: monospace;
            background: rgba(0,0,0,0.03);
            padding: 2px 8px;
            border-radius: 4px;
        }
        @media (prefers-color-scheme: dark) {
            .qty-badge {
                background: rgba(255,255,255,0.06);
            }
        }

        .checkbox-cell {
            text-align: center;
            width: 120px;
        }

        .custom-chk {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        /* Verification Badge */
        .badge-status {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .badge-status.paid {
            background-color: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
        }

        .badge-status.unpaid {
            background-color: rgba(245, 158, 11, 0.1);
            color: var(--warning-color);
        }

        /* Print formatting */
        @media print {
            body {
                background-color: #fff !important;
                color: #000 !important;
                padding: 0 !important;
            }
            .spreadsheet-container {
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
                background: none !important;
            }
            .actions-area, .no-print {
                display: none !important;
            }
            .excel-table th {
                background-color: #f1f5f9 !important;
                color: #000 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .qty-badge {
                background: none !important;
                border: 1px solid #ccc !important;
            }
            .custom-chk {
                border: 1px solid #000 !important;
            }
        }
    </style>
</head>
<body>

<div class="spreadsheet-container">
    <!-- Header -->
    <div class="header-section">
        <div class="title-area">
            <h1>📊 Loading Spreadsheet & Summary</h1>
            <p>Route Delivery Audit & Warehousing Load Sheets</p>
        </div>
        <div class="actions-area">
            <button class="btn-sheet no-print" onclick="window.print()">🖨️ Print Sheet</button>
            <a class="btn-sheet btn-primary no-print" href="<?= APP_URL ?>/delivery/export_csv/<?= $data['delivery']->id ?>">📥 Export to CSV</a>
            <button class="btn-sheet no-print" onclick="window.close()" style="margin-left:8px;">✕ Close Window</button>
        </div>
    </div>

    <!-- Metadata Grid -->
    <div class="meta-grid">
        <div class="meta-box">
            <span>Route Name</span>
            <strong><?= htmlspecialchars($data['delivery']->route_name) ?></strong>
        </div>
        <div class="meta-box">
            <span>Delivery Representative</span>
            <strong><?= htmlspecialchars($data['delivery']->first_name . ' ' . $data['delivery']->last_name) ?></strong>
        </div>
        <div class="meta-box">
            <span>Assigned Vehicle</span>
            <strong>🚚 <?= htmlspecialchars($data['delivery']->vehicle_number) ?></strong>
        </div>
        <div class="meta-box">
            <span>Assigned Driver</span>
            <strong>👤 <?= htmlspecialchars($data['delivery']->driver_name) ?></strong>
        </div>
        <div class="meta-box">
            <span>Helper / Partner</span>
            <strong><?= htmlspecialchars($data['delivery']->partner_name ?: 'None') ?></strong>
        </div>
        <div class="meta-box">
            <span>Scheduled Date</span>
            <strong>📅 <?= date('M d, Y', strtotime($data['delivery']->delivery_date)) ?></strong>
        </div>
    </div>

    <!-- Aggregate Loading Sheet -->
    <div class="grid-header">
        <span>📦 Warehouse Loading Grid (Products summary to load into the vehicle)</span>
        <span class="no-print" style="font-size:12px; color:#888; font-weight:normal;">Tick products as you pack them.</span>
    </div>
    
    <table class="excel-table">
        <thead>
            <tr>
                <th>Product Description</th>
                <th style="text-align: right; width: 180px;">Total Qty to Load</th>
                <th class="checkbox-cell">Loaded State</th>
                <th>Remarks / Notes</th>
            </tr>
        </thead>
        <tbody>
            <?php if(empty($data['items'])): ?>
                <tr>
                    <td colspan="4" style="text-align: center; padding: 30px; color: #888;">No invoice items generated on this route.</td>
                </tr>
            <?php else: ?>
                <?php foreach($data['items'] as $item): ?>
                    <tr>
                        <td style="font-weight: 600;"><?= htmlspecialchars($item->item_name) ?></td>
                        <td style="text-align: right;"><span class="qty-badge"><?= number_format($item->total_qty) ?></span></td>
                        <td class="checkbox-cell"><input type="checkbox" class="custom-chk"></td>
                        <td><span style="color:#aaa; font-style:italic;" class="no-print">Double click to write...</span></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Accounts Receivable Invoice / Collection Sheet -->
    <div class="grid-header">
        <span>💳 Outstanding Receivables & Collection Sheet (Invoice Breakdown)</span>
    </div>

    <table class="excel-table">
        <thead>
            <tr>
                <th>Invoice #</th>
                <th>Time / Date</th>
                <th>Customer Name</th>
                <th style="text-align: right;">Grand Total (Rs)</th>
                <th style="text-align: center; width: 140px;">Payment Status</th>
                <th>Collection Verification / Notes</th>
            </tr>
        </thead>
        <tbody>
            <?php if(empty($data['bills'])): ?>
                <tr>
                    <td colspan="6" style="text-align: center; padding: 30px; color: #888;">No invoices registered on this delivery.</td>
                </tr>
            <?php else: ?>
                <?php foreach($data['bills'] as $bill): ?>
                    <tr>
                        <td style="font-weight: 700; color: var(--primary-color);"><?= htmlspecialchars($bill->invoice_number) ?></td>
                        <td style="color:#888;"><?= date('h:i A', strtotime($bill->created_at)) ?></td>
                        <td style="font-weight: 600;"><?= htmlspecialchars($bill->customer_name) ?></td>
                        <td style="text-align: right; font-weight: 700; font-family: monospace; font-size:14.5px;">
                            <?= number_format($bill->true_grand_total, 2) ?>
                        </td>
                        <td style="text-align: center;">
                            <span class="badge-status <?= strtolower($bill->status) ?>">
                                <?= htmlspecialchars($bill->status) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($bill->status === 'Paid'): ?>
                                <span style="color:var(--success-color); font-weight:bold; font-size:11px;">✓ Paid Online/Cash</span>
                            <?php else: ?>
                                <span style="color:var(--warning-color); font-weight:bold; font-size:11px;">⚖ Pending Collection</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
    // Double click to write simple inline remarks on remarks cell
    document.querySelectorAll('.excel-table td:nth-child(4)').forEach(td => {
        td.addEventListener('dblclick', function() {
            const originalText = this.innerText === 'Double click to write...' ? '' : this.innerText;
            const input = document.createElement('input');
            input.type = 'text';
            input.value = originalText;
            input.style.width = '100%';
            input.style.padding = '4px 8px';
            input.style.boxSizing = 'border-box';
            input.style.border = '1px solid var(--primary-color)';
            input.style.borderRadius = '4px';
            
            this.innerHTML = '';
            this.appendChild(input);
            input.focus();
            
            const done = () => {
                const val = input.value.trim();
                this.innerHTML = val || '<span style="color:#aaa; font-style:italic;" class="no-print">Double click to write...</span>';
            };
            
            input.addEventListener('blur', done);
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    done();
                }
            });
        });
    });
</script>
</body>
</html>
