<div class="portal-layout">
    <!-- Sidebar Menu navigation -->
    <div class="portal-menu-card">
        <h4 style="font-size: 11px; text-transform:uppercase; color:var(--text-muted); font-weight:700; margin-bottom: 15px; letter-spacing:0.5px;">Navigation Menu</h4>
        <ul class="portal-menu-list">
            <li><a href="<?= APP_URL ?>/portal" class="portal-menu-link"><i class="ph ph-squares-four"></i> Dashboard</a></li>
            <li><a href="<?= APP_URL ?>/portal/orders" class="portal-menu-link"><i class="ph ph-receipt"></i> Order History</a></li>
            <li><a href="<?= APP_URL ?>/portal/wishlist" class="portal-menu-link"><i class="ph ph-heart"></i> My Wishlist</a></li>
            <li><a href="<?= APP_URL ?>/portal/returns" class="portal-menu-link active"><i class="ph ph-arrow-counter-clockwise"></i> Return Requests</a></li>
            <li><a href="<?= APP_URL ?>/portal/profile" class="portal-menu-link"><i class="ph ph-user-gear"></i> Profile Settings</a></li>
        </ul>
    </div>

    <!-- Main Workspace Content -->
    <div style="display:flex; flex-direction:column; gap:25px;">
        
        <?php if(!empty($data['success'])): ?>
            <div class="alert-box pill-success" style="background: rgba(52,199,89,0.1); color: #34c759;">
                <i class="ph ph-check-circle"></i> <?= htmlspecialchars($data['success']) ?>
            </div>
        <?php endif; ?>
        <?php if(!empty($data['error'])): ?>
            <div class="alert-box pill-danger" style="background: rgba(255,59,48,0.1); color: #ff3b30;">
                <i class="ph ph-warning-circle"></i> <?= htmlspecialchars($data['error']) ?>
            </div>
        <?php endif; ?>

        <!-- Submit Return request -->
        <div class="card">
            <h3 style="font-size: 15px; font-weight:700; border-bottom:1px solid var(--mega-divider); padding-bottom:10px; margin-bottom:15px;">Submit Return Authorization Request</h3>
            
            <?php if(empty($data['eligible_orders'])): ?>
                <p style="font-size:13px; color:var(--text-muted); padding: 10px 0;">No delivered orders eligible for return requests are linked to your profile ledger. Orders must be marked as "Delivered" in the system to initiate returns.</p>
            <?php else: ?>
                <form action="<?= APP_URL ?>/portal/returns" method="POST">
                    <input type="hidden" name="action" value="request_return">
                    
                    <div class="form-box">
                        <label>Select Delivered Purchase Invoice *</label>
                        <select name="sales_order_id" class="form-control" required>
                            <option value="">-- Choose Order --</option>
                            <?php foreach($data['eligible_orders'] as $eo): ?>
                                <option value="<?= $eo->id ?>">Order <?= htmlspecialchars($eo->order_number) ?> (Purchased <?= date('M d, Y', strtotime($eo->order_date)) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-box">
                        <label>Reason for Return / Exchange *</label>
                        <input type="text" name="reason" class="form-control" required placeholder="e.g. Defective items, wrong color variety...">
                    </div>

                    <div class="form-box">
                        <label>Detailed RMA Descriptions</label>
                        <textarea name="details" class="form-control" rows="3" placeholder="Provide quantity details, item details, serial numbers..."></textarea>
                    </div>

                    <button type="submit" class="btn-primary" style="margin-top:10px;"><i class="ph ph-arrow-counter-clockwise"></i> Submit RMA Authorization</button>
                </form>
            <?php endif; ?>
        </div>

        <!-- History -->
        <div class="card">
            <h3 style="font-size: 15px; font-weight:700; border-bottom:1px solid var(--mega-divider); padding-bottom:10px; margin-bottom:15px;">Return Request Logs</h3>
            
            <?php if(empty($data['returns'])): ?>
                <p style="text-align:center; padding:30px; color:var(--text-muted); font-size:13px;">No return requests filed.</p>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table style="width:100%; border-collapse:collapse; font-size:13px; text-align:left;">
                        <thead>
                            <tr style="border-bottom:1px solid var(--card-border); color:var(--text-muted); font-weight:600;">
                                <th style="padding:10px;">RMA Ref</th>
                                <th style="padding:10px;">Order No.</th>
                                <th style="padding:10px;">Reason</th>
                                <th style="padding:10px;">Submission Date</th>
                                <th style="padding:10px; text-align:right;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($data['returns'] as $r): ?>
                                <tr style="border-bottom:1px solid var(--mega-divider);">
                                    <td style="padding:12px; font-weight:600;">#RMA-<?= $r->id ?></td>
                                    <td style="padding:12px; font-family:monospace;"><?= htmlspecialchars($r->order_number) ?></td>
                                    <td style="padding:12px;"><?= htmlspecialchars($r->reason) ?></td>
                                    <td style="padding:12px;"><?= date('M d, Y', strtotime($r->created_at)) ?></td>
                                    <td style="padding:12px; text-align:right;">
                                        <?php if($r->status === 'approved'): ?>
                                            <span class="pill-badge pill-success">Approved</span>
                                        <?php elseif($r->status === 'pending'): ?>
                                            <span class="pill-badge pill-warning">Pending</span>
                                        <?php else: ?>
                                            <span class="pill-badge pill-danger">Rejected</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
