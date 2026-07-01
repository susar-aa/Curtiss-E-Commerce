<?php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Goods Receipt Note <?= htmlspecialchars($data['grn']->grn_number) ?> - <?= APP_NAME ?></title>
    <style>
        /* CRITICAL PRINT FIX */
        @media print { 
            @page { margin: 0mm; size: auto; } 
            body { padding: 1.5cm !important; background: #fff !important; margin: 0; -webkit-print-color-adjust: exact; } 
            .po-box { box-shadow: none !important; padding: 0 !important; max-width: 100% !important;} 
            .controls { display: none !important; } 
        }

        body { background-color: #e5e7eb; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; margin: 0; padding: 40px 20px; color: #333; }
        .po-box { max-width: 800px; margin: auto; padding: 50px; background: #fff; border-radius: 8px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border-top: 8px solid #2e7d32;}
        .header { display: flex; justify-content: space-between; margin-bottom: 40px; border-bottom: 2px solid #2e7d32; padding-bottom: 20px;}
        .logo { max-height: 80px; max-width: 200px; object-fit: contain; margin-bottom: 15px; }
        .title { font-size: 32px; font-weight: 300; color: #2e7d32; margin: 0 0 10px 0; letter-spacing: 1px; }
        
        .parties { display: flex; justify-content: space-between; margin-bottom: 40px; }
        .vendor-box h4 { margin: 0 0 5px 0; color: #888; text-transform: uppercase; font-size: 12px; }
        
        table.items { width: 100%; text-align: left; border-collapse: collapse; margin-bottom: 30px; }
        table.items th { background: #f8f9fa; padding: 12px; border-bottom: 2px solid #ddd; }
        table.items td { padding: 12px; border-bottom: 1px solid #eee; }
        .num { text-align: right; }

        .controls { text-align: center; margin-bottom: 30px; }
        .btn { padding: 10px 20px; background: #2e7d32; color: #fff; text-decoration: none; border-radius: 4px; cursor: pointer; border: none; margin: 0 5px; }
        
        .signature-block { margin-top: 60px; width: 250px; text-align: center; font-size: 13px; color: #333;}
        .signature-img { max-height: 70px; max-width: 200px; object-fit: contain; margin-bottom: 5px;}
        .signature-line { border-top: 1px solid #333; padding-top: 5px; font-weight: 600;}
    </style>
</head>
<body>
    <div class="controls">
        <a href="<?= APP_URL ?>/grn" class="btn" style="background:transparent; border: 1px solid #2e7d32; color: #2e7d32;">&larr; Back to GRNs</a>
        <button onclick="window.print()" class="btn">Print Document</button>
    </div>

    <div class="po-box">
        <div class="header">
            <div>
                <?php if(!empty($data['company']->logo_path)): ?>
                    <img src="<?= APP_URL ?>/uploads/<?= htmlspecialchars($data['company']->logo_path) ?>" class="logo">
                <?php else: ?>
                    <h2 style="margin:0;"><?= htmlspecialchars($data['company']->company_name) ?></h2>
                <?php endif; ?>
            </div>
            <div style="text-align: right;">
                <h1 class="title">GOODS RECEIPT</h1>
                <div style="font-size: 14px;">
                    <strong>GRN Number:</strong> <?= htmlspecialchars($data['grn']->grn_number) ?><br>
                    <strong>Date Received:</strong> <?= date('F j, Y', strtotime($data['grn']->grn_date)) ?><br>
                    <?php if(!empty($data['grn']->receipt_number)): ?>
                        <strong>Supplier Invoice:</strong> <?= htmlspecialchars($data['grn']->receipt_number) ?><br>
                    <?php endif; ?>
                    <?php if($data['grn']->po_number): ?>
                        <strong>PO Reference:</strong> <?= htmlspecialchars($data['grn']->po_number) ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="parties">
            <div class="vendor-box">
                <h4>Supplier Received From</h4>
                <strong><?= htmlspecialchars($data['grn']->vendor_name) ?></strong><br>
                <?php if(!empty($data['grn']->address)) echo nl2br(htmlspecialchars($data['grn']->address)); ?>
            </div>
            <div class="vendor-box" style="text-align: right;">
                <h4>Received At</h4>
                <strong><?= htmlspecialchars($data['company']->company_name) ?></strong><br>
                <?php if(!empty($data['company']->address)) echo nl2br(htmlspecialchars($data['company']->address)); ?>
            </div>
        </div>

        <?php
        // Enrich GRN items with pricing data from items table
        $db = new Database();
        foreach ($data['items'] as $grnItem) {
            $grnItem->retail_price = 0;
            $grnItem->wholesale_price = 0;
            if (!empty($grnItem->item_id)) {
                $db->query("SELECT price, wholesale_price FROM items WHERE id = :id");
                $db->bind(':id', $grnItem->item_id);
                $itemPrices = $db->single();
                if ($itemPrices) {
                    $grnItem->retail_price = floatval($itemPrices->price ?? 0);
                    $grnItem->wholesale_price = floatval($itemPrices->wholesale_price ?? 0);
                }
            }
        }
        ?>
        <table class="items">
            <thead>
                <tr>
                    <th style="width: 30px;">#</th>
                    <th>Item / Variation Description</th>
                    <th class="num">Qty</th>
                    <th class="num">Unit Cost</th>
                    <th class="num" style="color: #1565c0;">Retail Price</th>
                    <th class="num" style="color: #7b1fa2;">Wholesale B2B</th>
                    <th class="num">Line Total</th>
                </tr>
            </thead>
            <tbody>
                <?php $rowNum = 1; $grandTotal = 0; foreach($data['items'] as $item): ?>
                <?php $lineTotal = floatval($item->quantity) * floatval($item->unit_cost); $grandTotal += $lineTotal; ?>
                <tr>
                    <td style="text-align:center; color:#888;"><?= $rowNum++ ?></td>
                    <td><strong style="color:#2e7d32;">✓</strong> <?= htmlspecialchars($item->description) ?></td>
                    <td class="num" style="font-weight:bold;"><?= number_format($item->quantity, 0) ?></td>
                    <td class="num"><?= number_format($item->unit_cost, 2) ?></td>
                    <td class="num" style="color: #1565c0;"><?= number_format($item->retail_price, 2) ?></td>
                    <td class="num" style="color: #7b1fa2; font-weight: bold;"><?= number_format($item->wholesale_price, 2) ?></td>
                    <td class="num" style="font-weight:bold;"><?= number_format($lineTotal, 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="border-top: 2px solid #2e7d32;">
                    <td colspan="6" style="text-align: right; font-weight: bold; padding: 12px;">Grand Total (Cost):</td>
                    <td class="num" style="font-weight: bold; font-size: 16px; color: #2e7d32;"><?= number_format($grandTotal, 2) ?></td>
                </tr>
            </tfoot>
        </table>

        <?php if(!empty($data['grn']->notes)): ?>
            <h4 style="margin: 0 0 5px 0; color: #888; font-size: 12px; text-transform: uppercase;">Inspection Notes</h4>
            <p style="margin: 0; font-size: 13px; color: #444; line-height: 1.5; padding: 15px; background: #f8f9fa; border-radius: 6px;">
                <?= nl2br(htmlspecialchars($data['grn']->notes)) ?>
            </p>
        <?php endif; ?>
        
        <div style="display: flex; justify-content: space-between; margin-top: 60px; align-items: flex-end;">
            <div class="signature-block" style="margin-top: 0;">
                <?php if(!empty($data['grn']->creator_signature)): ?>
                    <img src="<?= APP_URL ?>/uploads/<?= htmlspecialchars($data['grn']->creator_signature) ?>" class="signature-img">
                <?php else: ?>
                    <div style="height: 70px;"></div>
                <?php endif; ?>
                <div class="signature-line">
                    Received & Verified By<br>
                    <span style="font-size:11px; color:#888; font-weight:normal;"><?= htmlspecialchars($data['grn']->creator_name) ?></span>
                </div>
            </div>

            <?php if($data['grn']->is_approved): ?>
            <div class="signature-block" style="margin-top: 0;">
                <div style="height: 70px; display: flex; align-items: center; justify-content: center; margin-bottom: 5px;">
                    <span style="color: #2e7d32; border: 2px dashed #2e7d32; padding: 4px 8px; border-radius: 4px; font-weight: bold; text-transform: uppercase; transform: rotate(-5deg); font-size: 14px;">✓ APPROVED</span>
                </div>
                <div class="signature-line">
                    Approved By<br>
                    <span style="font-size:11px; color:#888; font-weight:normal;"><?= htmlspecialchars($data['grn']->approver_name) ?><br><?= date('M d, Y H:i', strtotime($data['grn']->approved_at)) ?></span>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>