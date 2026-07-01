<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Order <?= htmlspecialchars($data['order']->order_number) ?> - <?= APP_NAME ?></title>
    <style>
        /* A4 Page Formatting */
        body { 
            background-color: #525659; 
            font-family: Arial, sans-serif; 
            display: flex; 
            justify-content: center; 
            padding: 40px 20px; 
            margin: 0; 
            color: #333; 
        }
        
        .a4-container { 
            width: 210mm; 
            min-height: 297mm; 
            background: #fff; 
            padding: 15mm 20mm; 
            box-shadow: 0 0 20px rgba(0,0,0,0.5); 
            box-sizing: border-box; 
            position: relative;
        }

        .controls { text-align: center; margin-bottom: 20px; width: 100%; position: absolute; top: -50px; left: 0;}
        .btn { padding: 10px 20px; background: #0066cc; color: #fff; text-decoration: none; border-radius: 4px; font-weight: bold; cursor: pointer; border: none; margin: 0 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.2);}
        
        /* Internal Typography & Grid */
        .header-area { display: flex; justify-content: space-between; border-bottom: 3px solid #7994b5; padding-bottom: 15px; margin-bottom: 20px;}
        .logo { max-height: 70px; max-width: 250px; object-fit: contain; margin-bottom: 10px; }
        .company-name { font-size: 26px; font-weight: bold; margin: 0 0 5px 0; color: #111; }
        .company-details { font-size: 12px; color: #555; line-height: 1.4;}
        
        .invoice-title { font-size: 34px; color: #7994b5; font-weight: bold; margin: 0 0 10px 0; letter-spacing: 1px; }
        .invoice-meta { font-size: 13px; color: #333; }
        .invoice-meta strong { display: inline-block; width: 90px; }

        .billing-grid { display: flex; justify-content: space-between; margin-bottom: 30px; font-size: 13px;}
        .bill-to h4 { margin: 0 0 8px 0; color: #fff; background: #7994b5; padding: 4px 10px; display: inline-block; text-transform: uppercase; font-size: 11px;}
        .bill-to p { margin: 0; line-height: 1.5; }
        
        /* Table */
        table.items { width: 100%; text-align: left; border-collapse: collapse; margin-bottom: 30px; font-size: 13px;}
        table.items th { background: #f0f4f8; padding: 10px; border-bottom: 2px solid #ccc; font-weight: bold; color: #333;}
        table.items td { padding: 10px; border-bottom: 1px solid #ddd; }
        table.items th.num, table.items td.num { text-align: right; }
        
        .totals-section { width: 100%; display: flex; justify-content: flex-end; }
        .totals-box { width: 300px; font-size: 13px;}
        .totals-row { display: flex; justify-content: space-between; padding: 6px 0; }
        .totals-row.grand-total { border-top: 2px solid #000; font-weight: bold; font-size: 16px; padding-top: 10px; margin-top: 5px;}
        
        .footer-notes { position: absolute; bottom: 15mm; left: 20mm; right: 20mm; border-top: 1px solid #ccc; padding-top: 10mm; font-size: 11px; color: #666; text-align: center; }

        /* Print Media Query */
        @media print { 
            @page { size: A4; margin: 0; }
            body { background: #fff; padding: 0; display: block; } 
            .a4-container { width: 100%; min-height: auto; box-shadow: none; padding: 15mm 20mm; } 
            .controls { display: none !important; } 
            .footer-notes { position: relative; bottom: 0; left: 0; right: 0; margin-top: 40px;}
        }
    </style>
</head>
<body>
    <div class="a4-container">
        <div class="controls">
            <?php if(isset($_SESSION['user_id'])): ?>
                <a href="<?= APP_URL ?>/sales" class="btn" style="background:#fff; color:#7994b5; border: 1px solid #7994b5;">&larr; Back to Invoices</a>
                <a href="<?= APP_URL ?>/sales/edit/<?= $data['order']->id ?>?type=sales_order" class="btn" style="background:#f0ad4e; color:#fff; border: none;">✏️ Edit Order</a>
            <?php endif; ?>
            <button onclick="window.print()" class="btn" style="background:#7994b5;">Print / PDF (A4)</button>
        </div>

        <div class="header-area">
            <div>
                <?php if(!empty($data['company']->logo_path)): ?>
                    <img src="<?= APP_URL ?>/uploads/<?= htmlspecialchars($data['company']->logo_path) ?>" class="logo" alt="Logo">
                <?php else: ?>
                    <h2 class="company-name"><?= htmlspecialchars($data['company']->company_name) ?></h2>
                <?php endif; ?>
                
                <div class="company-details">
                    <?php if(!empty($data['company']->address)) echo nl2br(htmlspecialchars($data['company']->address)) . '<br>'; ?>
                    <?php if(!empty($data['company']->phone)) echo htmlspecialchars($data['company']->phone) . '<br>'; ?>
                    <?php if(!empty($data['company']->email)) echo htmlspecialchars($data['company']->email) . '<br>'; ?>
                </div>
            </div>
            
            <div style="text-align: right;">
                <h1 class="invoice-title">SALES ORDER</h1>
                <div class="invoice-meta">
                    <div><strong>Order No:</strong> <?= htmlspecialchars($data['order']->order_number) ?></div>
                    <div><strong>Order Date:</strong> <?= date('d/m/Y', strtotime($data['order']->order_date)) ?></div>
                    <div><strong>Due Date:</strong> <?= date('d/m/Y', strtotime($data['order']->due_date)) ?></div>
                    <?php if(!empty($data['order']->po_number)): ?>
                        <div><strong>P.O. No:</strong> <?= htmlspecialchars($data['order']->po_number) ?></div>
                    <?php endif; ?>
                    <?php if(!empty($data['order']->rep_name)): ?>
                        <div><strong>Sales Rep:</strong> <?= htmlspecialchars($data['order']->rep_name) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="billing-grid">
            <div class="bill-to">
                <h4>Customer Details</h4>
                <p>
                    <strong style="font-size:14px;"><?= htmlspecialchars($data['order']->customer_name) ?></strong><br>
                    <?php if(!empty($data['order']->customer_phone)) echo 'Phone: ' . htmlspecialchars($data['order']->customer_phone) . '<br>'; ?>
                    <?php if(!empty($data['order']->mca)) echo 'Route: ' . htmlspecialchars($data['order']->mca) . '<br>'; ?>
                </p>
            </div>
            <div class="bill-to" style="text-align: right;">
                <h4>Stock Status</h4>
                <div style="display:inline-block; font-size: 16px; padding: 5px 15px; border: 2px solid #7994b5; color: #7994b5; font-weight: bold; border-radius: 4px; text-transform: uppercase;">
                    UN-DEPLETED
                </div>
            </div>
        </div>

        <table class="items">
            <thead>
                <tr>
                    <th style="width: 40px;">#</th>
                    <th>Description</th>
                    <th class="num" style="width: 10%;">Qty</th>
                    <th class="num" style="width: 15%;">Unit Price</th>
                    <th class="num" style="width: 15%;">Discount</th>
                    <th class="num" style="width: 20%;">Net Total</th>
                </tr>
            </thead>
            <tbody>
                <?php $rowNum = 1; foreach($data['items'] as $item): ?>
                <tr>
                    <td style="text-align:center; color:#888;"><?= $rowNum++ ?></td>
                    <td><?= htmlspecialchars($item->name) ?></td>
                    <td class="num"><?= number_format($item->qty, 0) ?></td>
                    <td class="num"><?= number_format($item->billing_price, 2) ?></td>
                    <td class="num">
                        <?php if($item->discount_value > 0): ?>
                            <?= $item->discount_type == '%' ? $item->discount_value . '%' : 'Rs: ' . number_format($item->discount_value, 2) ?>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td class="num">Rs: <?= number_format($item->total, 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="totals-section">
            <div class="totals-box">
                <div class="totals-row">
                    <span>Subtotal:</span>
                    <span>Rs: <?= number_format($data['order']->subtotal, 2) ?></span>
                </div>
                <?php if($data['order']->discount > 0): ?>
                    <div class="totals-row" style="color: #c62828;">
                        <span>Discount:</span>
                        <span>- Rs: <?= number_format($data['order']->discount, 2) ?></span>
                    </div>
                <?php endif; ?>
                <div class="totals-row grand-total">
                    <span>Sales Order Total:</span>
                    <span>Rs: <?= number_format($data['order']->grand_total, 2) ?></span>
                </div>
            </div>
        </div>
        
        <?php if(!empty($data['order']->notes)): ?>
        <div style="margin-top: 30px; font-size: 12px; border: 1px solid #ddd; padding: 10px; background: #fafafa;">
            <strong>Order Notes / Instructions:</strong><br>
            <?= nl2br(htmlspecialchars($data['order']->notes)) ?>
        </div>
        <?php endif; ?>
        
        <div class="footer-notes">
            Note: This is a Sales Order containing reserved pricing and items list. This document is not a tax invoice and does not deplete active physical stock or establish debt.
        </div>
    </div>
</body>
</html>
