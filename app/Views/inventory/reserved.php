<div style="max-width: 1400px; margin: 0 auto; padding: 20px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">

    <!-- Premium Dashboard Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); padding: 24px; border-radius: 16px; border: 1px solid rgba(255,255,255,0.08); box-shadow: 0 10px 30px rgba(0,0,0,0.2);">
        <div>
            <h1 style="margin: 0; font-size: 26px; font-weight: 700; color: #fff; display: flex; align-items: center; gap: 12px;">
                <i class="ph ph-shield-check" style="color: #3b82f6; font-size: 32px;"></i>
                Stock Reservation Center
            </h1>
            <p style="margin: 6px 0 0 0; font-size: 14px; color: #94a3b8;">
                Real-time inventory commitments across active field routes and pending dispatches.
            </p>
        </div>
        <div style="background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.2); padding: 8px 16px; border-radius: 9999px;">
            <span style="font-size: 13px; font-weight: 600; color: #60a5fa; display: flex; align-items: center; gap: 6px;">
                <i class="ph ph-circle-notch ph-spin"></i> Live Synchronized
            </span>
        </div>
    </div>

    <!-- Quick Stats Cards Row -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 30px;">
        <!-- Card 1: Total Reserved Items -->
        <div style="background: #1e293b; border: 1px solid rgba(255,255,255,0.05); border-radius: 16px; padding: 24px; display: flex; align-items: center; justify-content: justify-content; gap: 20px;">
            <div style="background: rgba(59, 130, 246, 0.15); width: 56px; height: 56px; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #3b82f6; font-size: 28px;">
                <i class="ph ph-package"></i>
            </div>
            <div>
                <span style="display: block; font-size: 13px; color: #94a3b8; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px;">Reserved Items</span>
                <span style="display: block; font-size: 32px; font-weight: 700; color: #fff; margin-top: 4px;">
                    <?= count($data['reserved_items'] ?? []) ?>
                </span>
            </div>
        </div>

        <!-- Card 2: Total Reserved Variations -->
        <div style="background: #1e293b; border: 1px solid rgba(255,255,255,0.05); border-radius: 16px; padding: 24px; display: flex; align-items: center; justify-content: justify-content; gap: 20px;">
            <div style="background: rgba(168, 85, 247, 0.15); width: 56px; height: 56px; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #a855f7; font-size: 28px;">
                <i class="ph ph-sparkle"></i>
            </div>
            <div>
                <span style="display: block; font-size: 13px; color: #94a3b8; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px;">Reserved Variations</span>
                <span style="display: block; font-size: 32px; font-weight: 700; color: #fff; margin-top: 4px;">
                    <?= count($data['reserved_variations'] ?? []) ?>
                </span>
            </div>
        </div>

        <!-- Card 3: Active Orders Committed -->
        <div style="background: #1e293b; border: 1px solid rgba(255,255,255,0.05); border-radius: 16px; padding: 24px; display: flex; align-items: center; justify-content: justify-content; gap: 20px;">
            <div style="background: rgba(16, 185, 129, 0.15); width: 56px; height: 56px; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #10b981; font-size: 28px;">
                <i class="ph ph-receipt"></i>
            </div>
            <div>
                <span style="display: block; font-size: 13px; color: #94a3b8; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px;">Active Holds</span>
                <span style="display: block; font-size: 32px; font-weight: 700; color: #fff; margin-top: 4px;">
                    <?= count($data['details'] ?? []) ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Main Workspace Layout -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
        
        <!-- Left Side: Main Tables -->
        <div style="display: flex; flex-direction: column; gap: 24px;">
            
            <!-- Panel 1: Simple Products Reservation -->
            <div style="background: #1e293b; border: 1px solid rgba(255,255,255,0.05); border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
                <div style="padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.05); display: flex; justify-content: space-between; align-items: center;">
                    <h2 style="margin: 0; font-size: 18px; font-weight: 600; color: #fff; display: flex; align-items: center; gap: 8px;">
                        <i class="ph ph-package" style="color: #3b82f6;"></i>
                        Reserved Stock Catalog
                    </h2>
                    <input type="text" id="catalogSearch" placeholder="🔍 Search reserved items..." 
                           style="background: #0f172a; border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; padding: 6px 12px; color: #fff; font-size: 13px; outline: none; width: 220px;">
                </div>
                
                <div style="overflow-x: auto;">
                    <table id="catalogTable" style="width: 100%; border-collapse: collapse; text-align: left; font-size: 14px;">
                        <thead>
                            <tr style="background: rgba(255,255,255,0.02); border-bottom: 1px solid rgba(255,255,255,0.05);">
                                <th style="padding: 14px 20px; color: #94a3b8; font-weight: 600;">SKU / Code</th>
                                <th style="padding: 14px 20px; color: #94a3b8; font-weight: 600;">Product Name</th>
                                <th style="padding: 14px 20px; color: #94a3b8; font-weight: 600;">Category</th>
                                <th style="padding: 14px 20px; color: #94a3b8; font-weight: 600; text-align: center;">On Hand</th>
                                <th style="padding: 14px 20px; color: #f43f5e; font-weight: 600; text-align: center;">Reserved</th>
                                <th style="padding: 14px 20px; color: #10b981; font-weight: 600; text-align: center;">Net Available</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($data['reserved_items'])): ?>
                                <tr>
                                    <td colspan="6" style="padding: 40px; text-align: center; color: #64748b;">
                                        <i class="ph ph-package" style="font-size: 40px; display: block; margin: 0 auto 10px auto;"></i>
                                        No active catalog reservations found.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($data['reserved_items'] as $item): ?>
                                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.03); transition: background 0.2s;" class="searchable-row">
                                        <td style="padding: 14px 20px; font-weight: 600; color: #3b82f6; font-family: monospace;"><?= htmlspecialchars($item->item_code) ?></td>
                                        <td style="padding: 14px 20px; color: #fff; font-weight: 500;"><?= htmlspecialchars($item->item_name) ?></td>
                                        <td style="padding: 14px 20px; color: #94a3b8;"><span style="background: rgba(255,255,255,0.05); padding: 4px 8px; border-radius: 6px; font-size: 12px;"><?= htmlspecialchars($item->category_name) ?></span></td>
                                        <td style="padding: 14px 20px; text-align: center; color: #fff;"><?= $item->quantity_on_hand ?></td>
                                        <td style="padding: 14px 20px; text-align: center; font-weight: 700; color: #f43f5e;"><?= $item->quantity_reserved ?></td>
                                        <td style="padding: 14px 20px; text-align: center; font-weight: 600; color: #10b981;"><?= $item->quantity_available ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Panel 2: Variations Reservations -->
            <?php if (!empty($data['reserved_variations'])): ?>
                <div style="background: #1e293b; border: 1px solid rgba(255,255,255,0.05); border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
                    <div style="padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.05); display: flex; justify-content: space-between; align-items: center;">
                        <h2 style="margin: 0; font-size: 18px; font-weight: 600; color: #fff; display: flex; align-items: center; gap: 8px;">
                            <i class="ph ph-sparkle" style="color: #a855f7;"></i>
                            Reserved Variations
                        </h2>
                        <input type="text" id="variationSearch" placeholder="🔍 Search variations..." 
                               style="background: #0f172a; border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; padding: 6px 12px; color: #fff; font-size: 13px; outline: none; width: 220px;">
                    </div>
                    
                    <div style="overflow-x: auto;">
                        <table id="variationTable" style="width: 100%; border-collapse: collapse; text-align: left; font-size: 14px;">
                            <thead>
                                <tr style="background: rgba(255,255,255,0.02); border-bottom: 1px solid rgba(255,255,255,0.05);">
                                    <th style="padding: 14px 20px; color: #94a3b8; font-weight: 600;">SKU</th>
                                    <th style="padding: 14px 20px; color: #94a3b8; font-weight: 600;">Parent Item</th>
                                    <th style="padding: 14px 20px; color: #94a3b8; font-weight: 600;">Variation Details</th>
                                    <th style="padding: 14px 20px; color: #94a3b8; font-weight: 600; text-align: center;">On Hand</th>
                                    <th style="padding: 14px 20px; color: #f43f5e; font-weight: 600; text-align: center;">Reserved</th>
                                    <th style="padding: 14px 20px; color: #10b981; font-weight: 600; text-align: center;">Available</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['reserved_variations'] as $v): ?>
                                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.03); transition: background 0.2s;" class="searchable-v-row">
                                        <td style="padding: 14px 20px; font-weight: 600; color: #a855f7; font-family: monospace;"><?= htmlspecialchars($v->sku) ?></td>
                                        <td style="padding: 14px 20px; color: #fff; font-weight: 500;"><?= htmlspecialchars($v->item_name) ?> <span style="font-size:12px; color:#64748b;">(<?= htmlspecialchars($v->parent_code) ?>)</span></td>
                                        <td style="padding: 14px 20px; color: #e2e8f0;"><span style="background: rgba(168, 85, 247, 0.1); border: 1px solid rgba(168, 85, 247, 0.2); padding: 4px 8px; border-radius: 6px; font-size: 12px; color: #c084fc;"><?= htmlspecialchars($v->variation_display) ?></span></td>
                                        <td style="padding: 14px 20px; text-align: center; color: #fff;"><?= $v->quantity_on_hand ?></td>
                                        <td style="padding: 14px 20px; text-align: center; font-weight: 700; color: #f43f5e;"><?= $v->quantity_reserved ?></td>
                                        <td style="padding: 14px 20px; text-align: center; font-weight: 600; color: #10b981;"><?= $v->quantity_available ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Right Side: Order Allocation Breakdowns -->
        <div>
            <div style="background: #1e293b; border: 1px solid rgba(255,255,255,0.05); border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); position: sticky; top: 20px;">
                <div style="padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.05);">
                    <h2 style="margin: 0; font-size: 18px; font-weight: 600; color: #fff; display: flex; align-items: center; gap: 8px;">
                        <i class="ph ph-map-pin" style="color: #10b981;"></i>
                        Active Commitments
                    </h2>
                    <p style="margin: 4px 0 0 0; font-size: 12px; color: #94a3b8;">Detailed breakdown of products committed to sales routes</p>
                </div>
                
                <div style="max-height: 600px; overflow-y: auto; padding: 20px; display: flex; flex-direction: column; gap: 16px;">
                    <?php if (empty($data['details'])): ?>
                        <div style="padding: 40px; text-align: center; color: #64748b;">
                            <i class="ph ph-clock" style="font-size: 32px; display: block; margin: 0 auto 10px auto;"></i>
                            No active route commitments at this time.
                        </div>
                    <?php else: ?>
                        <?php foreach ($data['details'] as $det): ?>
                            <div style="background: #0f172a; border: 1px solid rgba(255,255,255,0.05); border-radius: 12px; padding: 16px; transition: transform 0.2s; border-left: 4px solid #10b981;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                    <span style="font-weight: 700; color: #60a5fa; font-family: monospace; font-size: 13px;">
                                        <?= htmlspecialchars($det->invoice_number) ?>
                                    </span>
                                    <span style="background: rgba(16, 185, 129, 0.1); color: #34d399; font-size: 11px; padding: 2px 6px; border-radius: 4px; font-weight: 600; text-transform: uppercase;">
                                        <?= htmlspecialchars($det->stock_status) ?>
                                    </span>
                                </div>
                                <div style="font-size: 14px; font-weight: 600; color: #fff; margin-bottom: 4px; display: flex; justify-content: space-between;">
                                    <span><?= htmlspecialchars($det->item_name) ?></span>
                                    <span style="color: #f43f5e; font-weight: 700;">Qty: <?= $det->reserved_qty ?></span>
                                </div>
                                <div style="font-size: 12px; color: #94a3b8; display: flex; flex-direction: column; gap: 2px; margin-top: 8px;">
                                    <span><strong>Customer:</strong> <?= htmlspecialchars($det->customer_name ?? 'N/A') ?></span>
                                    <span><strong>Route:</strong> <?= htmlspecialchars($det->route_name ?? 'Offline Route') ?></span>
                                    <span style="color: #64748b; margin-top: 4px; font-size: 11px;">
                                        <i class="ph ph-calendar"></i> <?= date('Y-m-d H:i', strtotime($det->invoice_date)) ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>

</div>

<!-- Interactive UI Script for Real-Time Search Filtering -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const catalogSearch = document.getElementById('catalogSearch');
    const variationSearch = document.getElementById('variationSearch');

    if (catalogSearch) {
        catalogSearch.addEventListener('input', function(e) {
            const term = e.target.value.toLowerCase().trim();
            const rows = document.querySelectorAll('.searchable-row');
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(term) ? '' : 'none';
            });
        });
    }

    if (variationSearch) {
        variationSearch.addEventListener('input', function(e) {
            const term = e.target.value.toLowerCase().trim();
            const rows = document.querySelectorAll('.searchable-v-row');
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(term) ? '' : 'none';
            });
        });
    }
});
</script>
