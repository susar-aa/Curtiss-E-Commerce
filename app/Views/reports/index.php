<?php
$categories = $data['categories'];
$groupedReports = $data['grouped_reports'];

// Icons helper mapped to categories for rich visual look
$categoryIcons = [
    'inventory' => 'ph-package',
    'sales' => 'ph-shopping-cart',
    'procurement' => 'ph-truck',
    'customer' => 'ph-users',
    'supplier' => 'ph-storefront',
    'finance' => 'ph-bank',
    'collection' => 'ph-hand-coins',
    'route' => 'ph-map-trifold',
    'management' => 'ph-presentation-chart'
];
?>

<style>
    .reports-hub-wrapper {
        padding: 20px;
        max-width: 1400px;
        margin: 0 auto;
    }

    .hub-header {
        margin-bottom: 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .hub-title h1 {
        font-size: 26px;
        font-weight: 700;
        color: #1a1a1a;
        margin: 0 0 5px 0;
    }

    .hub-title p {
        color: #666;
        margin: 0;
        font-size: 14px;
    }

    .search-box-wrapper {
        position: relative;
        width: 320px;
    }

    .search-box-wrapper i {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #888;
        font-size: 16px;
    }

    .search-box-wrapper input {
        width: 100%;
        padding: 10px 10px 10px 38px;
        border: 1px solid #ddd;
        border-radius: 20px;
        font-size: 14px;
        background: #fff;
        transition: all 0.3s;
    }

    .search-box-wrapper input:focus {
        border-color: #0066cc;
        box-shadow: 0 0 0 3px rgba(0, 102, 204, 0.15);
        outline: none;
    }

    /* Categories grid layout */
    .categories-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 25px;
    }

    .category-section {
        background: #fff;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        overflow: hidden;
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .category-section:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
    }

    .category-header {
        background: #f8fafc;
        padding: 16px 20px;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .category-header i {
        font-size: 22px;
        color: #0066cc;
    }

    .category-header h3 {
        font-size: 16px;
        font-weight: 650;
        margin: 0;
        color: #1e293b;
    }

    .reports-list {
        padding: 10px 0;
    }

    .report-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 20px;
        color: #475569;
        text-decoration: none;
        transition: background 0.2s, color 0.2s;
        border-bottom: 1px solid #f1f5f9;
    }

    .report-item:last-child {
        border-bottom: none;
    }

    .report-item:hover {
        background: #f0f7ff;
        color: #0066cc;
    }

    .report-info {
        display: flex;
        flex-direction: column;
    }

    .report-name {
        font-size: 14px;
        font-weight: 600;
    }

    .report-item i.launch-icon {
        font-size: 16px;
        opacity: 0;
        transition: opacity 0.2s, transform 0.2s;
    }

    .report-item:hover i.launch-icon {
        opacity: 1;
        transform: translateX(3px);
    }

    @media (prefers-color-scheme: dark) {
        .category-section {
            background: #1e1e1e;
            border-color: #2e2e2e;
        }
        .category-header {
            background: #252525;
            border-color: #2e2e2e;
        }
        .category-header h3 {
            color: #e2e8f0;
        }
        .report-item {
            color: #cbd5e1;
            border-color: #2e2e2e;
        }
        .report-item:hover {
            background: rgba(0, 102, 204, 0.15);
        }
        .hub-title h1 {
            color: #ffffff;
        }
    }
</style>

<div class="reports-hub-wrapper">
    <div class="hub-header">
        <div class="hub-title">
            <h1>Centralized Reporting Hub</h1>
            <p>Unified enterprise reporting engine for real-time operations, analytics, and accounting views.</p>
        </div>
        <div class="search-box-wrapper">
            <i class="ph ph-magnifying-glass"></i>
            <input type="text" id="reportSearch" placeholder="Search reports..." onkeyup="filterReports()">
        </div>
    </div>

    <div class="categories-grid">
        <?php foreach ($categories as $catKey => $catTitle): ?>
            <?php if (!empty($groupedReports[$catKey])): ?>
                <div class="category-section" data-category="<?= $catKey ?>">
                    <div class="category-header">
                        <i class="ph <?= $categoryIcons[$catKey] ?? 'ph-file-text' ?>"></i>
                        <h3><?= htmlspecialchars($catTitle) ?></h3>
                    </div>
                    <div class="reports-list">
                        <?php foreach ($groupedReports[$catKey] as $key => $rep): ?>
                            <a href="<?= APP_URL ?>/report/viewer/<?= $key ?>" class="report-item" data-name="<?= strtolower($rep['title']) ?>">
                                <div class="report-info">
                                    <span class="report-name"><?= htmlspecialchars($rep['title']) ?></span>
                                </div>
                                <i class="ph ph-arrow-square-out launch-icon"></i>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</div>

<script>
    function filterReports() {
        const query = document.getElementById('reportSearch').value.toLowerCase();
        const cards = document.querySelectorAll('.category-section');
        
        cards.forEach(card => {
            const items = card.querySelectorAll('.report-item');
            let hasVisibleItem = false;
            
            items.forEach(item => {
                const name = item.getAttribute('data-name');
                if (name.includes(query)) {
                    item.style.display = 'flex';
                    hasVisibleItem = true;
                } else {
                    item.style.display = 'none';
                }
            });
            
            if (hasVisibleItem) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    }
</script>
