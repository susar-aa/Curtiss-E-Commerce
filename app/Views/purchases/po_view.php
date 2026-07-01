<?php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Purchase Order <?= htmlspecialchars($data['po']->po_number) ?> - <?= APP_NAME ?></title>
    <style>
        /* CRITICAL PRINT FIX: This removes browser URLs and Timestamps from Header/Footer */
        @media print { 
            @page { margin: 0mm; size: auto; } 
            body { padding: 1.5cm !important; background: #fff !important; margin: 0; -webkit-print-color-adjust: exact; } 
            .po-box { box-shadow: none !important; padding: 0 !important; max-width: 100% !important;} 
            .controls { display: none !important; } 
        }

        body { background-color: #e5e7eb; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; margin: 0; padding: 40px 20px; color: #333; }
        .po-box { max-width: 800px; margin: auto; padding: 50px; background: #fff; border-radius: 8px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .header { display: flex; justify-content: space-between; margin-bottom: 40px; border-bottom: 2px solid #0066cc; padding-bottom: 20px;}
        .logo { max-height: 80px; max-width: 200px; object-fit: contain; margin-bottom: 15px; }
        .title { font-size: 32px; font-weight: 300; color: #0066cc; margin: 0 0 10px 0; letter-spacing: 1px; }
        
        .parties { display: flex; justify-content: space-between; margin-bottom: 40px; }
        .vendor-box h4 { margin: 0 0 5px 0; color: #888; text-transform: uppercase; font-size: 12px; }
        
        table.items { width: 100%; text-align: left; border-collapse: collapse; margin-bottom: 30px; }
        table.items th { background: #f8f9fa; padding: 12px; border-bottom: 2px solid #ddd; }
        table.items td { padding: 12px; border-bottom: 1px solid #eee; }
        .num { text-align: right; }
        
        .totals-box { width: 300px; float: right; }
        .grand-total { border-top: 2px solid #333; font-weight: bold; font-size: 18px; padding-top: 12px; color: #0066cc; display: flex; justify-content: space-between;}

        .controls { text-align: center; margin-bottom: 30px; }
        .btn { padding: 10px 20px; background: #0066cc; color: #fff; text-decoration: none; border-radius: 4px; cursor: pointer; border: none; margin: 0 5px; }
        
        /* Signature block styles */
        .signature-block { margin-top: 80px; width: 250px; text-align: center; font-size: 13px; color: #333;}
        .signature-img { max-height: 70px; max-width: 200px; object-fit: contain; margin-bottom: 5px;}
        .signature-line { border-top: 1px solid #333; padding-top: 5px; font-weight: 600;}
    </style>
</head>
<body>
    <div class="controls">
        <!-- Strictly checks the $data array so the button hides perfectly on email attachments -->
        <?php if(empty($data['is_email'])): ?>
            <a href="<?= APP_URL ?>/purchase" class="btn" style="background:transparent; border: 1px solid #0066cc; color: #0066cc;">&larr; Back to POs</a>
        <?php endif; ?>
        <button onclick="window.print()" class="btn">Print / Save as PDF</button>
    </div>

    <div class="po-box">
        <div class="header">
            <div>
                <?php if(!empty($data['company']->logo_path)): ?>
                    <img src="<?= APP_URL ?>/uploads/<?= htmlspecialchars($data['company']->logo_path) ?>" class="logo">
                <?php else: ?>
                    <h2 style="margin:0;"><?= htmlspecialchars($data['company']->company_name) ?></h2>
                <?php endif; ?>
                <div style="font-size: 13px; color: #666; margin-top: 10px;">
                    <?php if(!empty($data['company']->address)) echo nl2br(htmlspecialchars($data['company']->address)) . '<br>'; ?>
                    <?= htmlspecialchars($data['company']->phone) ?>
                </div>
            </div>
            <div style="text-align: right;">
                <h1 class="title">PURCHASE ORDER</h1>
                <div style="font-size: 14px;">
                    <strong>PO Number:</strong> <?= htmlspecialchars($data['po']->po_number) ?><br>
                    <strong>Date:</strong> <?= date('F j, Y', strtotime($data['po']->po_date)) ?><br>
                    <strong>Expected By:</strong> <?= date('F j, Y', strtotime($data['po']->expected_date)) ?>
                </div>
            </div>
        </div>

        <div class="parties">
            <div class="vendor-box">
                <h4>Vendor / Supplier</h4>
                <strong><?= htmlspecialchars($data['po']->vendor_name) ?></strong><br>
                <?php if(!empty($data['po']->address)) echo nl2br(htmlspecialchars($data['po']->address)) . '<br>'; ?>
                <?= htmlspecialchars($data['po']->email) ?><br>
                <?= htmlspecialchars($data['po']->phone) ?>
            </div>
            <div class="vendor-box" style="text-align: right;">
                <h4>Ship To</h4>
                <strong><?= htmlspecialchars($data['company']->company_name) ?></strong><br>
                <?php if(!empty($data['company']->address)) echo nl2br(htmlspecialchars($data['company']->address)); ?>
            </div>
        </div>

        <table class="items">
            <thead>
                <tr>
                    <th style="width:40px;">#</th>
                    <th>Item Description</th>
                    <th class="num">Qty</th>
                    <th class="num">Unit Cost</th>
                    <th class="num">Total Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php $rowNum = 1; foreach($data['items'] as $item): ?>
                <tr>
                    <td style="text-align:center; color:#888;"><?= $rowNum++ ?></td>
                    <td><?= htmlspecialchars($item->description) ?></td>
                    <td class="num"><?= number_format($item->quantity, 0) ?></td>
                    <td class="num">Rs: <?= number_format($item->unit_price, 2) ?></td>
                    <td class="num">Rs: <?= number_format($item->total, 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div style="overflow: hidden;">
            <div style="float: left; width: 60%;">
                <?php if(!empty($data['po']->notes)): ?>
                    <h4 style="margin: 0 0 5px 0; color: #888; font-size: 12px; text-transform: uppercase;">Notes & Instructions</h4>
                    <p style="margin: 0; font-size: 13px; color: #444; line-height: 1.5; padding: 15px; background: #f8f9fa; border-radius: 6px;">
                        <?= nl2br(htmlspecialchars($data['po']->notes)) ?>
                    </p>
                <?php endif; ?>
            </div>
            
            <div class="totals-box">
                <div class="grand-total">
                    <span>Authorized Total:</span>
                    <span>Rs: <?= number_format($data['po']->total_amount, 2) ?></span>
                </div>
            </div>
        </div>
        
        <!-- NEW: Digital Signature Engine Integration -->
        <div class="signature-block">
            <?php if(!empty($data['po']->creator_signature)): ?>
                <img src="<?= APP_URL ?>/uploads/<?= htmlspecialchars($data['po']->creator_signature) ?>" class="signature-img" alt="Digital Signature">
            <?php else: ?>
                <div style="height: 70px;"></div> <!-- Spacer for physical signing -->
            <?php endif; ?>
            
            <div class="signature-line">
                Authorized Signature<br>
                <span style="font-size:11px; color:#888; font-weight:normal;">Electronically signed by: <?= htmlspecialchars($data['po']->creator_name) ?></span>
            </div>
        </div>
    </div>
</body>
</html>