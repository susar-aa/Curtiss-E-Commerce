<div class="mac-container" style="padding: 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h1 style="margin: 0; font-size: 24px; font-weight: 600; color: #111;"><i class="ph ph-trash"></i> Deleted Invoices Audit Log</h1>
        <a href="<?= APP_URL ?>/sales/create" class="btn btn-outline" style="font-size:12px; border:1px solid #0066cc; color:#0066cc; background:transparent;">+ Create Invoice</a>
    </div>

    <?php if(!empty($data['success'])): ?>
        <div style="background: #e8f5e9; color: #2e7d32; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 13px;">
            <?= htmlspecialchars($data['success']) ?>
        </div>
    <?php endif; ?>

    <?php if(!empty($data['error'])): ?>
        <div style="background: #ffebee; color: #c62828; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 13px;">
            <?= htmlspecialchars($data['error']) ?>
        </div>
    <?php endif; ?>

    <div class="po-table-container" style="background: #fff; border: 1px solid var(--mac-border); border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
        <table class="po-table" style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead>
                <tr style="background: #f5f5f7; border-bottom: 1px solid var(--mac-border);">
                    <th style="padding: 12px 15px; font-size: 12px; font-weight: 600; color: #555; width: 12%;">Document No</th>
                    <th style="padding: 12px 15px; font-size: 12px; font-weight: 600; color: #555; width: 10%;">Type</th>
                    <th style="padding: 12px 15px; font-size: 12px; font-weight: 600; color: #555; width: 18%;">Customer Name</th>
                    <th style="padding: 12px 15px; font-size: 12px; font-weight: 600; color: #555; width: 12%; text-align: right;">Grand Total</th>
                    <th style="padding: 12px 15px; font-size: 12px; font-weight: 600; color: #555; width: 13%;">Deleted By</th>
                    <th style="padding: 12px 15px; font-size: 12px; font-weight: 600; color: #555; width: 15%;">Deleted At</th>
                    <th style="padding: 12px 15px; font-size: 12px; font-weight: 600; color: #555; width: 20%;">Reason for Deletion</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($data['invoices'])): ?>
                    <tr>
                        <td colspan="7" style="padding: 30px; text-align: center; color: #888; font-size: 14px;">
                            No deleted invoices found in the audit trail.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach($data['invoices'] as $invoice): ?>
                        <tr style="border-bottom: 1px solid #f0f0f2;">
                            <td style="padding: 12px 15px; font-size: 13px; font-weight: 600; color: #333; font-family: monospace;">
                                <?= htmlspecialchars($invoice->invoice_number) ?>
                            </td>
                            <td style="padding: 12px 15px; font-size: 12px;">
                                <span style="padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; <?= (isset($invoice->record_type) && $invoice->record_type === 'Sales Order') ? 'background: #e3f2fd; color: #0d47a1;' : 'background: #ede7f6; color: #4a148c;' ?>">
                                    <?= htmlspecialchars($invoice->record_type ?? 'Invoice') ?>
                                </span>
                            </td>
                            <td style="padding: 12px 15px; font-size: 13px; color: #444;">
                                <?= htmlspecialchars($invoice->customer_name) ?>
                            </td>
                            <td style="padding: 12px 15px; font-size: 13px; color: #c62828; font-weight: 600; text-align: right;">
                                Rs: <?= number_format($invoice->total_amount, 2) ?>
                            </td>
                            <td style="padding: 12px 15px; font-size: 13px; color: #333; font-weight: 500;">
                                <span style="background: #f0f0f5; padding: 3px 8px; border-radius: 12px; font-size: 11px; color: #555;">
                                    <i class="ph ph-user"></i> <?= htmlspecialchars($invoice->deleted_user_name ?? 'System Admin') ?>
                                </span>
                            </td>
                            <td style="padding: 12px 15px; font-size: 12px; color: #666;">
                                <?= date('Y-m-d H:i:s', strtotime($invoice->deleted_at)) ?>
                            </td>
                            <td style="padding: 12px 15px; font-size: 12px; color: #e65100; font-style: italic; background: #fffde7; font-weight: 500;">
                                <i class="ph ph-warning"></i> <?= htmlspecialchars($invoice->delete_reason) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
