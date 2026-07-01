<?php
$filterAction = $data['filter_action'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($data['title']) ?></title>
    <style>
        body { background: #f0f2f5; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; padding: 24px; color: #1a1a1a; margin: 0; }
        .report-wrap { max-width: 1100px; margin: 0 auto; }
        .report-paper { background: #fff; padding: 40px 48px; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); }
        .text-center { text-align: center; }
        .company-name { font-size: 22px; font-weight: 700; }
        .report-title { font-size: 20px; margin: 8px 0 4px; color: #0066cc; }
        .report-meta { color: #666; font-size: 13px; margin-bottom: 20px; }
        .filter-bar { background: #e8f0fe; border: 1px solid #c5d9f7; border-radius: 8px; padding: 14px 18px; margin-bottom: 24px; display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end; }
        .filter-bar label { font-size: 11px; font-weight: 700; text-transform: uppercase; color: #555; display: block; margin-bottom: 4px; }
        .filter-bar input { padding: 8px 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 13px; }
        .filter-bar button { padding: 9px 18px; background: #0066cc; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; }
        .kpi-row { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 12px; margin-bottom: 24px; }
        .kpi { background: #f8f9fa; border: 1px solid #e0e0e0; border-radius: 8px; padding: 14px; text-align: center; }
        .kpi span { font-size: 10px; text-transform: uppercase; color: #888; font-weight: 700; display: block; margin-bottom: 4px; }
        .kpi strong { font-size: 17px; color: #0066cc; }
        table.rpt { width: 100%; border-collapse: collapse; font-size: 13px; }
        table.rpt th { background: #f0f4f8; padding: 10px 12px; text-align: left; border-bottom: 2px solid #ccc; font-size: 11px; text-transform: uppercase; color: #555; }
        table.rpt td { padding: 9px 12px; border-bottom: 1px solid #eee; }
        table.rpt td.num, table.rpt th.num { text-align: right; }
        table.rpt tr.total-row td { font-weight: 700; border-top: 2px solid #333; background: #fafafa; }
        .section-h { font-size: 14px; font-weight: 700; margin: 24px 0 8px; color: #333; }
        .empty-msg { text-align: center; padding: 32px; color: #888; }
        .text-success { color: #2e7d32; } .text-danger { color: #c62828; }
        .toolbar { text-align: center; margin-top: 20px; }
        .toolbar a, .toolbar button { padding: 9px 18px; margin: 0 4px; border-radius: 4px; font-weight: 600; font-size: 13px; cursor: pointer; border: none; text-decoration: none; display: inline-block; }
        .btn-hub { background: #666; color: #fff; } .btn-print { background: #0066cc; color: #fff; }
        @media print { body { background: #fff; padding: 0; } .filter-bar, .toolbar { display: none !important; } .report-paper { box-shadow: none; padding: 0; } }
    </style>
</head>
<body>
<div class="report-wrap">
    <div class="report-paper">
        <div class="text-center">
            <div class="company-name"><?= htmlspecialchars($data['company']->company_name ?? '') ?></div>
            <h1 class="report-title"><?= htmlspecialchars($data['title']) ?></h1>
            <?php if (!empty($data['subtitle'])): ?><p class="report-meta"><?= htmlspecialchars($data['subtitle']) ?></p><?php endif; ?>
            <p class="report-meta">
                <?php if (!empty($data['start_date']) && !empty($data['end_date']) && !empty($data['dated'])): ?>
                    Period: <?= date('M j, Y', strtotime($data['start_date'])) ?> — <?= date('M j, Y', strtotime($data['end_date'])) ?>
                <?php else: ?>
                    As of <?= date('F j, Y') ?>
                <?php endif; ?>
            </p>
        </div>
        <?php if (!empty($data['dated']) && $filterAction): ?>
        <form class="filter-bar" method="GET" action="<?= APP_URL ?>/report/<?= htmlspecialchars($filterAction) ?>">
            <div><label>From</label><input type="date" name="start_date" value="<?= htmlspecialchars($data['start_date']) ?>" required></div>
            <div><label>To</label><input type="date" name="end_date" value="<?= htmlspecialchars($data['end_date']) ?>" required></div>
            <button type="submit">Apply</button>
        </form>
        <?php endif; ?>
