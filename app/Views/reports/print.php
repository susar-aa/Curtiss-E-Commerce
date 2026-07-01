<?php
$reportKey = $data['reportKey'];
$metadata = $data['metadata'];
$rows = $data['rows'];
$grandTotals = $data['grandTotals'];
$company = $data['company'];
$filterLabels = $data['filterLabels'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($metadata['title']) ?> - Print</title>
    <style>
        /* CSS Reset for high fidelity printing */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: "Calibri", "Arial", sans-serif;
            font-size: 13.5px;
            color: #000000;
            background: #ffffff;
            line-height: 1.3;
            padding: 1.5cm 1.2cm;
        }

        /* Title block */
        .header-title-block {
            text-align: center;
            margin-bottom: 25px;
        }

        .header-title-block h1 {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .header-title-block h2 {
            font-size: 16px;
            font-weight: bold;
            color: #222;
        }

        /* Metadata Block Grid */
        .meta-grid-table {
            width: 100%;
            margin-bottom: 20px;
            font-size: 13px;
        }

        .meta-grid-table td {
            padding: 4px 0;
            vertical-align: top;
        }

        .meta-label {
            font-weight: bold;
            width: 15%;
            white-space: nowrap;
        }

        .meta-separator {
            width: 2%;
            text-align: center;
        }

        .meta-value {
            width: 33%;
            border-bottom: 1px dotted #ccc;
            padding-left: 5px;
        }

        /* Accounting-Grade Report Table */
        .accounting-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 13px;
        }

        .accounting-table th {
            font-weight: bold;
            text-align: left;
            padding: 6px 8px;
            border-top: 1.5px solid #000;
            border-bottom: 1.5px solid #000;
            background: #ffffff;
        }

        .accounting-table td {
            padding: 6px 8px;
            vertical-align: top;
            border-bottom: 1px solid #f2f2f2;
        }

        /* Alignments */
        .text-right {
            text-align: right !important;
        }

        .text-center {
            text-align: center !important;
        }

        /* Totals Rows */
        .total-row td {
            border-top: 1.5px solid #000000;
            border-bottom: 4px double #000000 !important; /* Standard double underline accounting rule */
            font-weight: bold;
            padding-top: 8px;
            padding-bottom: 8px;
        }

        /* Nested hierarchical sub-tables (Contra accounts, batched allocations) */
        .nested-row-container {
            margin: 4px 0 4px 20px;
            border-left: 2px solid #ddd;
            padding-left: 10px;
        }

        .nested-item-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12.5px;
            margin-top: 4px;
        }

        .nested-item-table td {
            border-bottom: none;
            padding: 3px 6px;
            color: #333;
        }

        .nested-totals {
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            font-weight: bold;
        }

        /* Page layout controls */
        @media print {
            @page {
                size: A4 portrait;
                margin: 1.5cm 1cm 1.5cm 1cm;
            }

            body {
                padding: 0;
            }

            .no-print {
                display: none !important;
            }

            /* Prevent rows breaking in middle of print pages */
            tr {
                page-break-inside: avoid;
            }

            .page-footer {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                display: flex;
                justify-content: space-between;
                font-size: 11px;
                font-family: Arial, sans-serif;
                color: #555;
                border-top: 1px solid #aaa;
                padding-top: 6px;
            }

            .page-footer-left {
                text-align: left;
            }

            .page-footer-right {
                text-align: right;
            }

            .page-footer-right::after {
                content: "Page " counter(page);
            }
        }

        /* Screen Preview styles */
        .page-footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            color: #64748b;
        }
    </style>
</head>
<body>

    <!-- Corporate Header Block -->
    <div class="header-title-block">
        <h1><?= htmlspecialchars($company->company_name) ?></h1>
        <h2><?= htmlspecialchars($metadata['title']) ?></h2>
    </div>

    <!-- Filter Context Grid -->
    <table class="meta-grid-table">
        <tr>
            <td class="meta-label">BU</td>
            <td class="meta-separator">:</td>
            <td class="meta-value">Primary BU</td>
            <td class="meta-label">From Date</td>
            <td class="meta-separator">:</td>
            <td class="meta-value"><?= htmlspecialchars($filterLabels['From Date'] ?? '') ?></td>
        </tr>
        <tr>
            <td class="meta-label">Account / Context</td>
            <td class="meta-separator">:</td>
            <td class="meta-value">
                <?php
                $contexts = [];
                if (!empty($filterLabels['Customer'])) $contexts[] = 'Cust: ' . $filterLabels['Customer'];
                if (!empty($filterLabels['Supplier'])) $contexts[] = 'Vend: ' . $filterLabels['Supplier'];
                if (!empty($filterLabels['Product'])) $contexts[] = 'Prod: ' . $filterLabels['Product'];
                if (!empty($filterLabels['Warehouse'])) $contexts[] = 'WH: ' . $filterLabels['Warehouse'];
                if (!empty($filterLabels['Customer/Supplier Group'])) $contexts[] = 'Grp: ' . $filterLabels['Customer/Supplier Group'];
                if (!empty($filterLabels['Territory'])) $contexts[] = 'Terr: ' . $filterLabels['Territory'];
                echo !empty($contexts) ? htmlspecialchars(implode(', ', $contexts)) : 'All Accounts / Entities';
                ?>
            </td>
            <td class="meta-label">To Date</td>
            <td class="meta-separator">:</td>
            <td class="meta-value"><?= htmlspecialchars($filterLabels['To Date'] ?? '') ?></td>
        </tr>
        <tr>
            <td class="meta-label">Route & Logistics</td>
            <td class="meta-separator">:</td>
            <td class="meta-value">
                <?php
                $logistics = [];
                if (!empty($filterLabels['Route'])) $logistics[] = 'Rt: ' . $filterLabels['Route'];
                if (!empty($filterLabels['Sales Rep'])) $logistics[] = 'Rep: ' . $filterLabels['Sales Rep'];
                if (!empty($filterLabels['Vehicle'])) $logistics[] = 'Veh: ' . $filterLabels['Vehicle'];
                if (!empty($filterLabels['Driver'])) $logistics[] = 'Dr: ' . $filterLabels['Driver'];
                if (!empty($filterLabels['Partner'])) $logistics[] = 'Prt: ' . $filterLabels['Partner'];
                echo !empty($logistics) ? htmlspecialchars(implode(', ', $logistics)) : '_';
                ?>
            </td>
            <td class="meta-label">Acc Typ / Cat</td>
            <td class="meta-separator">:</td>
            <td class="meta-value">
                <?php
                $types = [];
                if (!empty($filterLabels['Category'])) $types[] = $filterLabels['Category'];
                if (!empty($filterLabels['Brand'])) $types[] = 'Brand: ' . $filterLabels['Brand'];
                if (!empty($filterLabels['Status'])) $types[] = 'Status: ' . $filterLabels['Status'];
                if (!empty($filterLabels['Payment Method'])) $types[] = 'PM: ' . $filterLabels['Payment Method'];
                echo !empty($types) ? htmlspecialchars(implode(', ', $types)) : '_';
                ?>
            </td>
        </tr>
    </table>

    <!-- Main Accounting Table -->
    <table class="accounting-table">
        <thead>
            <tr>
                <?php foreach ($metadata['columns'] as $c): ?>
                    <th class="<?= ($c['type'] === 'currency' || $c['type'] === 'number' || ($c['align'] ?? '') === 'right') ? 'text-right' : '' ?>">
                        <?= htmlspecialchars($c['label']) ?>
                    </th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
                <tr>
                    <td colspan="<?= count($metadata['columns']) ?>" class="text-center" style="padding: 40px; color: #666;">
                        No records match the requested filter context.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($rows as $index => $r): ?>
                    <tr>
                        <?php foreach ($metadata['columns'] as $colKey => $c): ?>
                            <td class="<?= ($c['type'] === 'currency' || $c['type'] === 'number' || ($c['align'] ?? '') === 'right') ? 'text-right' : '' ?>">
                                <?php
                                $val = $r->$colKey ?? '';
                                if ($c['type'] === 'currency') {
                                    $numericVal = floatval($val ?: 0);
                                    // Add Dr/Cr indicators if the report key matches general_ledger or customer_statement
                                    $suffix = '';
                                    if ($reportKey === 'general_ledger' || $reportKey === 'customer_statement' || $reportKey === 'trial_balance') {
                                        if ($colKey === 'debit' || $colKey === 'credit' || $colKey === 'balance') {
                                            $suffix = ($colKey === 'credit') ? ' Cr' : ' Dr';
                                        }
                                    }
                                    echo number_format($numericVal, 2) . $suffix;
                                } else {
                                    echo htmlspecialchars($val);
                                }
                                ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>

                <?php endforeach; ?>
            <?php endif; ?>

            <!-- Accounting Grand Totals Footer -->
            <tr class="total-row">
                <?php 
                $firstCol = true; 
                foreach ($metadata['columns'] as $colKey => $c): 
                ?>
                    <td class="<?= ($c['type'] === 'currency' || $c['type'] === 'number' || ($c['align'] ?? '') === 'right') ? 'text-right' : '' ?>">
                        <?php
                        if ($firstCol) {
                            echo 'Grand Total';
                            $firstCol = false;
                        } elseif ($c['total'] === 'sum' && isset($grandTotals[$colKey])) {
                            echo number_format(floatval($grandTotals[$colKey]), 2);
                        } else {
                            echo '-';
                        }
                        ?>
                    </td>
                <?php endforeach; ?>
            </tr>
        </tbody>
    </table>

    <!-- Running footer element -->
    <div class="page-footer">
        <div class="page-footer-left">
            Printed Date: <?= date('d/m/Y') ?> &nbsp;&nbsp;&nbsp; Time: <?= date('h:i A') ?>
        </div>
        <div class="page-footer-right">
            <!-- Automatically counts page pages on printing -->
        </div>
    </div>

    <!-- Trigger OS print / PDF preview on load -->
    <script>
        window.onload = function() {
            // Automatically launch native browser print layout selector
            window.print();
        };
    </script>
</body>
</html>
