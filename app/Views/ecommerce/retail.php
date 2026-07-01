<style>
    .retail-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
        font-size: 13px;
    }
    .retail-table th {
        background: rgba(0,0,0,0.02);
        padding: 12px 15px;
        text-align: left;
        font-weight: 600;
        border-bottom: 2px solid var(--mac-border);
        color: var(--text-muted);
    }
    .retail-table td {
        padding: 12px 15px;
        border-bottom: 1px solid var(--mac-border);
        vertical-align: middle;
    }
    .retail-table tr:hover {
        background: rgba(0,0,0,0.01);
    }
</style>

<div class="header-actions" style="margin-bottom: 20px;">
    <h2>Retail Customer Registry</h2>
    <p style="color:#666; margin-top:0;">View profiles of normal retail consumers registered directly via the storefront.</p>
</div>

<div class="card">
    <table class="retail-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Full Name</th>
                <th>Email Address</th>
                <th>Username</th>
                <th>Phone Number</th>
                <th>City & Address</th>
                <th>Registered At</th>
            </tr>
        </thead>
        <tbody>
            <?php if(empty($data['customers'])): ?>
                <tr>
                    <td colspan="7" style="text-align: center; color: #aaa; padding: 30px;">No retail customers registered yet.</td>
                </tr>
            <?php else: ?>
                <?php foreach($data['customers'] as $cust): ?>
                    <tr>
                        <td><strong>#<?= $cust->id ?></strong></td>
                        <td><strong><?= htmlspecialchars($cust->name) ?></strong></td>
                        <td><?= htmlspecialchars($cust->email) ?></td>
                        <td>
                            <?php if(!empty($cust->username)): ?>
                                <span style="font-family: monospace; background: rgba(0,0,0,0.04); padding: 2px 6px; border-radius: 4px; font-size: 11px;">
                                    <?= htmlspecialchars($cust->username) ?>
                                </span>
                            <?php else: ?>
                                <span style="color:#aaa; font-style:italic;">None</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($cust->phone ?? 'Not Provided') ?></td>
                        <td>
                            <?php if(!empty($cust->city) || !empty($cust->address)): ?>
                                <strong><?= htmlspecialchars($cust->city ?? '') ?></strong>
                                <span style="font-size:11px; color:#666; display:block;"><?= htmlspecialchars($cust->address ?? '') ?></span>
                            <?php else: ?>
                                <span style="color:#aaa; font-style:italic;">Not Provided</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= date('M d, Y h:i A', strtotime($cust->created_at)) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
