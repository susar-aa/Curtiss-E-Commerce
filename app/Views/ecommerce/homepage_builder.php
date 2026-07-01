<style>
    /* Homepage Builder drag and drop styling */
    .builder-list {
        display: flex;
        flex-direction: column;
        gap: 15px;
        margin-top: 15px;
    }
    .section-card {
        background: var(--card-bg);
        border: 1px solid var(--card-border);
        border-radius: 12px;
        padding: 16px 20px;
        box-shadow: var(--card-shadow);
        backdrop-filter: blur(var(--glass-blur));
        cursor: grab;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
        transition: transform 0.2s, box-shadow 0.2s;
        position: relative;
    }
    .section-card.dragging {
        opacity: 0.5;
        border: 2px dashed var(--text-accent);
        cursor: grabbing;
    }
    .section-card:hover {
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    }
    .section-left {
        display: flex;
        align-items: center;
        gap: 15px;
    }
    .drag-handle {
        font-size: 20px;
        color: var(--text-muted);
        cursor: grab;
        display: flex;
        align-items: center;
    }
    .section-info {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    .section-title {
        font-size: 14.5px;
        font-weight: 700;
        color: var(--text-main);
    }
    .section-desc {
        font-size: 11.5px;
        color: var(--text-muted);
    }
    .section-config-btn {
        background: rgba(0,0,0,0.03);
        border: 1px solid var(--card-border);
        border-radius: 6px;
        padding: 6px 12px;
        font-size: 12px;
        font-weight: 600;
        color: var(--text-muted);
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 5px;
        transition: background 0.2s;
    }
    .section-config-btn:hover {
        background: rgba(0,0,0,0.06);
        color: var(--text-main);
    }
    @media (prefers-color-scheme: dark) {
        .section-config-btn { background: rgba(255,255,255,0.05); }
        .section-config-btn:hover { background: rgba(255,255,255,0.1); }
    }

    /* Switch toggle button */
    .switch {
        position: relative;
        display: inline-block;
        width: 44px;
        height: 24px;
    }
    .switch input { 
        opacity: 0;
        width: 0;
        height: 0;
    }
    .slider {
        position: absolute;
        cursor: pointer;
        top: 0; left: 0; right: 0; bottom: 0;
        background-color: #ccc;
        transition: .3s;
        border-radius: 24px;
    }
    .slider:before {
        position: absolute;
        content: "";
        height: 18px;
        width: 18px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: .3s;
        border-radius: 50%;
    }
    input:checked + .slider {
        background-color: var(--text-accent);
    }
    input:checked + .slider:before {
        transform: translateX(20px);
    }

    .config-drawer {
        display: none;
        border-top: 1px solid var(--mega-divider);
        margin-top: 15px;
        padding-top: 15px;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        width: 100%;
    }
    .config-drawer.open {
        display: grid;
    }

    .builder-wrapper {
        display: flex;
        flex-direction: column;
        width: 100%;
    }
</style>

<div class="header-actions" style="margin-bottom: 25px;">
    <h2>Homepage Layout Builder</h2>
    <p style="color: var(--text-muted); margin-top: 4px;">Drag and drop layout blocks to rearrange the public storefront home layout, toggle display, and fine-tune limits.</p>
</div>

<?php if(!empty($data['success'])): ?>
    <div class="alert alert-success" style="padding: 12px; background: #e8f5e9; color: #2e7d32; border: 1px solid #a5d6a7; border-radius: 6px; margin-bottom: 15px; font-size: 13px;">
        <i class="ph ph-check-circle" style="vertical-align: middle; font-size: 16px; margin-right: 5px;"></i> <?= $data['success'] ?>
    </div>
<?php endif; ?>
<?php if(!empty($data['error'])): ?>
    <div class="alert alert-error" style="padding: 12px; background: #ffebee; color: #c62828; border: 1px solid #ef9a9a; border-radius: 6px; margin-bottom: 15px; font-size: 13px;">
        <i class="ph ph-warning-circle" style="vertical-align: middle; font-size: 16px; margin-right: 5px;"></i> <?= $data['error'] ?>
    </div>
<?php endif; ?>

<form action="<?= APP_URL ?>/ecommerce/homepage_builder" method="POST">
    <input type="hidden" name="action" value="update_sections">
    <div class="card builder-wrapper">
        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--mega-divider); padding-bottom:12px;">
            <span style="font-size: 13px; font-weight:600; color:var(--text-muted);"><i class="ph ph-hand-grabbing"></i> Grab the grid icon to drag and sort sections</span>
            <button type="submit" class="btn-primary" style="padding: 8px 18px; border-radius: 6px; font-size:13px;">
                <i class="ph ph-floppy-disk" style="vertical-align: middle;"></i> Save Layout Settings
            </button>
        </div>

        <div class="builder-list" id="sortableList">
            <?php foreach($data['sections'] as $sec): 
                $config = json_decode($sec->config, true) ?: [];
            ?>
                <div class="section-card" draggable="true" data-id="<?= $sec->id ?>">
                    <!-- Hidden inputs for sort order -->
                    <input type="hidden" name="sections[<?= $sec->id ?>][sort_order]" class="sort-order-input" value="<?= $sec->sort_order ?>">
                    
                    <div style="display: flex; flex-direction: column; width: 100%;">
                        <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                            <div class="section-left">
                                <div class="drag-handle">
                                    <i class="ph ph-dots-six-vertical"></i>
                                </div>
                                <div class="section-info">
                                    <span class="section-title"><?= htmlspecialchars($sec->section_name) ?></span>
                                    <span class="section-desc">Key: <code><?= htmlspecialchars($sec->section_key) ?></code></span>
                                </div>
                            </div>
                            
                            <div style="display: flex; align-items: center; gap: 15px;">
                                <button type="button" class="section-config-btn" onclick="toggleConfigDrawer(this)">
                                    <i class="ph ph-sliders"></i> Config
                                </button>
                                
                                <label class="switch" title="Toggle Section Visibility">
                                    <input type="checkbox" name="sections[<?= $sec->id ?>][is_enabled]" value="1" <?= $sec->is_enabled ? 'checked' : '' ?>>
                                    <span class="slider"></span>
                                </label>
                            </div>
                        </div>

                        <!-- Expandable Configuration drawer -->
                        <div class="config-drawer">
                            <div class="form-box">
                                <label>Header Display Title</label>
                                <input type="text" name="sections[<?= $sec->id ?>][config][title]" class="form-control" value="<?= htmlspecialchars($config['title'] ?? '') ?>" placeholder="e.g. Best Sellers">
                            </div>
                            <div class="form-box">
                                <label>Item/Product Limit</label>
                                <input type="number" name="sections[<?= $sec->id ?>][config][limit]" class="form-control" value="<?= htmlspecialchars($config['limit'] ?? '8') ?>" min="1" max="24">
                            </div>
                            <div class="form-box">
                                <label>Background Style Profile</label>
                                <select name="sections[<?= $sec->id ?>][config][theme]" class="form-control">
                                    <option value="default" <?= ($config['theme'] ?? '') === 'default' ? 'selected' : '' ?>>Default Clean</option>
                                    <option value="light" <?= ($config['theme'] ?? '') === 'light' ? 'selected' : '' ?>>Light Gray</option>
                                    <option value="dark" <?= ($config['theme'] ?? '') === 'dark' ? 'selected' : '' ?>>Premium Dark</option>
                                    <option value="accent" <?= ($config['theme'] ?? '') === 'accent' ? 'selected' : '' ?>>Accent Highlight</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</form>

<script>
    const sortableList = document.getElementById('sortableList');
    let dragEl = null;

    sortableList.addEventListener('dragstart', (e) => {
        if(e.target.classList.contains('section-card')) {
            dragEl = e.target;
            dragEl.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
        }
    });

    sortableList.addEventListener('dragend', () => {
        if (dragEl) {
            dragEl.classList.remove('dragging');
            dragEl = null;
            recalculateOrders();
        }
    });

    sortableList.addEventListener('dragover', (e) => {
        e.preventDefault();
        const afterElement = getDragAfterElement(sortableList, e.clientY);
        if (afterElement == null) {
            sortableList.appendChild(dragEl);
        } else {
            sortableList.insertBefore(dragEl, afterElement);
        }
    });

    function getDragAfterElement(container, y) {
        const draggableElements = [...container.querySelectorAll('.section-card:not(.dragging)')];
        
        return draggableElements.reduce((closest, child) => {
            const box = child.getBoundingClientRect();
            const offset = y - box.top - box.height / 2;
            if (offset < 0 && offset > closest.offset) {
                return { offset: offset, element: child };
            } else {
                return closest;
            }
        }, { offset: Number.NEGATIVE_INFINITY }).element;
    }

    function recalculateOrders() {
        const cards = sortableList.querySelectorAll('.section-card');
        cards.forEach((card, index) => {
            const input = card.querySelector('.sort-order-input');
            if (input) {
                input.value = index + 1;
            }
        });
    }

    function toggleConfigDrawer(btn) {
        const card = btn.closest('.section-card');
        const drawer = card.querySelector('.config-drawer');
        drawer.classList.toggle('open');
    }
</script>
