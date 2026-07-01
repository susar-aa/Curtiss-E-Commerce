<?php
/* STREAMING_CHUNK:Rendering the Budget Dashboard UI */
?>
<style>
    .header-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .data-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    .data-table th, .data-table td { padding: 12px; text-align: left; border-bottom: 1px solid var(--mac-border); vertical-align: middle;}
    .data-table th { background-color: rgba(0,0,0,0.02); font-weight: 600; font-size: 13px; }
    
    .form-control { padding: 6px 10px; border: 1px solid var(--mac-border); border-radius: 4px; background: transparent; color: var(--text-main); width: 120px; text-align:right;}
    .btn-small { padding: 6px 12px; background: #0066cc; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; }
    .btn-small:hover { background: #005bb5; }

    /* Progress Bar Styles */
    .progress-container { width: 100%; background-color: #e0e0e0; border-radius: 8px; height: 12px; overflow: hidden; margin-top: 5px; }
    .progress-bar { height: 100%; border-radius: 8px; transition: width 0.4s ease; }
    .bg-green { background-color: #2e7d32; }
    .bg-yellow { background-color: #f57c00; }
    .bg-red { background-color: #c62828; }
</style>

<div class="card">
    <div class="header-actions">
        <div>
            <h2 style="margin: 0 0 5px 0;">Budget vs. Actuals</h2>
            <p style="margin: 0; color: #666;">Fiscal Year: <strong><?= $data['year'] ?></strong></p>
        </div>
    </div>

    <?php if(!empty($data['error'])): ?>
        <div style="padding: 10px; background:#ffebee; color:#c62828; border-radius:4px; margin-bottom:15px;"><?= $data['error'] ?></div>
    <?php endif; ?>
    <?php if(!empty($data['success'])): ?>
        <div style="padding: 10px; background:#e8f5e9; color:#2e7d32; border-radius:4px; margin-bottom:15px;"><?= $data['success'] ?></div>
    <?php endif; ?>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 25%;">Expense Account</th>
                <th style="width: 15%; text-align: right;">Budget Limit (Rs:)</th>
                <th style="width: 10%;"></th>
                <th style="width: 15%; text-align: right;">Actual Spent (Rs:)</th>
                <th style="width: 35%;">Variance Progress</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($data['budgets'] as $b): ?>
                <?php 
                    // Calculate percentage
                    $percent = 0;
                    if ($b->budget_amount > 0) {
                        $percent = ($b->actual_spent / $b->budget_amount) * 100;
                    }
                    
                    // Determine color
                    $colorClass = 'bg-green';
                    if ($percent >= 80 && $percent <= 100) $colorClass = 'bg-yellow';
                    if ($percent > 100) $colorClass = 'bg-red';

                    // Cap progress bar width visually at 100%
                    $barWidth = $percent > 100 ? 100 : $percent;
                ?>
            <tr>
                <td><strong><?= htmlspecialchars($b->account_code) ?></strong> - <?= htmlspecialchars($b->account_name) ?></td>
                
                <td style="text-align: right;">
                    <!-- Inline Form to Update Budget -->
                    <form action="<?= APP_URL ?>/budget" method="POST" style="margin:0; display:flex; justify-content:flex-end; gap:5px;">
                        <input type="hidden" name="action" value="update_budget">
                        <input type="hidden" name="account_id" value="<?= $b->account_id ?>">
                        <input type="number" name="budget_amount" step="0.01" min="0" class="form-control" value="<?= $b->budget_amount ?>">
                </td>
                <td>
                        <button type="submit" class="btn-small">Save</button>
                    </form>
                </td>
                
                <td style="text-align: right; font-weight: bold; color: <?= $percent > 100 ? '#c62828' : '#333' ?>;">
                    <?= number_format($b->actual_spent, 2) ?>
                </td>
                
                <td>
                    <?php if($b->budget_amount > 0): ?>
                        <div style="font-size: 11px; display:flex; justify-content:space-between; margin-bottom:2px;">
                            <span>Usage: <?= number_format($percent, 1) ?>%</span>
                            <span>Remaining: Rs: <?= number_format($b->budget_amount - $b->actual_spent, 2) ?></span>
                        </div>
                        <div class="progress-container">
                            <div class="progress-bar <?= $colorClass ?>" style="width: <?= $barWidth ?>%;"></div>
                        </div>
                    <?php else: ?>
                        <span style="font-size: 11px; color:#888;">No budget set</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>