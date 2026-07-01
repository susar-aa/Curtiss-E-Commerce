<?php
// Filters values
$filterUser = $data['filters']['user_id'] ?? '';
$filterModule = $data['filters']['module'] ?? '';
$filterAction = $data['filters']['action'] ?? '';
$filterDateFrom = $data['filters']['date_from'] ?? '';
$filterDateTo = $data['filters']['date_to'] ?? '';
$filterSearch = $data['filters']['search'] ?? '';
?>

<style>
    /* Styling & Layout */
    .audit-container {
        max-width: 1400px;
        margin: 0 auto;
        padding-bottom: 40px;
    }

    .header-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        border-bottom: 1px solid var(--mac-border);
        padding-bottom: 15px;
    }

    /* Filter Panel styling */
    .filter-panel {
        background: var(--mac-bg);
        border: 1px solid var(--mac-border);
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 25px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        transition: all 0.3s ease;
    }

    .filter-form {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        align-items: flex-end;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .filter-group label {
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        color: var(--text-muted);
        letter-spacing: 0.5px;
    }

    .filter-input {
        background: rgba(0, 0, 0, 0.03);
        border: 1px solid var(--mac-border);
        border-radius: 6px;
        padding: 8px 12px;
        font-size: 13px;
        color: var(--text-main);
        outline: none;
        transition: border-color 0.2s, background-color 0.2s;
    }
    @media (prefers-color-scheme: dark) {
        .filter-input {
            background: rgba(255, 255, 255, 0.03);
        }
    }

    .filter-input:focus {
        border-color: #0066cc;
        background: rgba(0, 0, 0, 0.01);
    }

    .filter-buttons {
        display: flex;
        gap: 10px;
        grid-column: span 1;
        justify-content: flex-end;
    }

    .btn {
        padding: 9px 16px;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s ease;
        border: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        justify-content: center;
    }

    .btn-primary {
        background: #0066cc;
        color: #fff;
    }

    .btn-primary:hover {
        background: #0052a3;
        transform: translateY(-1px);
    }

    .btn-secondary {
        background: rgba(0, 0, 0, 0.05);
        color: var(--text-main);
        text-decoration: none;
    }
    @media (prefers-color-scheme: dark) {
        .btn-secondary {
            background: rgba(255, 255, 255, 0.08);
        }
    }

    .btn-secondary:hover {
        background: rgba(0, 0, 0, 0.1);
    }

    /* Logs Table styling */
    .data-table-container {
        background: #fff;
        border: 1px solid var(--mac-border);
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 30px rgba(0, 0, 0, 0.02);
    }
    @media (prefers-color-scheme: dark) {
        .data-table-container {
            background: #1e1e2d;
        }
    }

    .data-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
    }

    .data-table th, .data-table td {
        padding: 14px 18px;
        border-bottom: 1px solid var(--mac-border);
        font-size: 13px;
    }

    .data-table th {
        background-color: rgba(0, 0, 0, 0.02);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-size: 11px;
        color: var(--text-muted);
    }
    @media (prefers-color-scheme: dark) {
        .data-table th {
            background-color: rgba(255, 255, 255, 0.02);
        }
    }

    .log-row {
        transition: background-color 0.2s;
        cursor: pointer;
    }

    .log-row:hover {
        background-color: rgba(0, 102, 204, 0.03) !important;
    }

    .badge {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 10px;
        font-weight: bold;
        letter-spacing: 0.5px;
        display: inline-block;
    }

    /* Action badges coloring */
    .action-default { background: rgba(0,0,0,0.05); color: var(--text-main); }
    .action-create-invoice, .action-create-sales-order, .action-create-credit-note, .action-create, .action-user-created, .action-product-created, .action-grn-created { background: #e8f5e9; color: #2e7d32; }
    .action-edit-invoice, .action-update, .action-user-edited, .action-product-edited, .action-grn-edited { background: #fff3e0; color: #ef6c00; }
    .action-delete-invoice, .action-delete, .action-user-deleted, .action-product-deleted, .action-grn-deleted, .action-login-failed { background: #ffebee; color: #c62828; }
    .action-login, .action-logout { background: #e3f2fd; color: #1565c0; }

    .module-badge {
        font-weight: 600;
        color: var(--text-main);
    }

    /* Modal Backdrop */
    .modal-backdrop {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.4);
        backdrop-filter: blur(5px);
        -webkit-backdrop-filter: blur(5px);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 3000;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .modal-backdrop.show {
        display: flex;
        opacity: 1;
    }

    /* Modal box */
    .modal-box {
        background: #fff;
        border: 1px solid var(--mac-border);
        border-radius: 14px;
        width: 90%;
        max-width: 1100px;
        max-height: 85vh;
        display: flex;
        flex-direction: column;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
        animation: modalSlide 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        overflow: hidden;
    }
    @media (prefers-color-scheme: dark) {
        .modal-box {
            background: #1e1e2e;
            color: #eee;
        }
    }

    @keyframes modalSlide {
        from { transform: scale(0.95) translateY(10px); }
        to { transform: scale(1) translateY(0); }
    }

    .modal-header {
        padding: 18px 24px;
        border-bottom: 1px solid var(--mac-border);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .modal-title {
        font-size: 16px;
        font-weight: 600;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .modal-close {
        cursor: pointer;
        font-size: 20px;
        color: var(--text-muted);
        transition: color 0.2s;
        background: none;
        border: none;
        outline: none;
    }

    .modal-close:hover {
        color: #ff3b30;
    }

    .modal-body {
        padding: 24px;
        overflow-y: auto;
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    /* Metadata details inside modal */
    .meta-summary {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 15px;
        background: rgba(0, 0, 0, 0.02);
        border: 1px solid var(--mac-border);
        border-radius: 8px;
        padding: 15px;
    }
    @media (prefers-color-scheme: dark) {
        .meta-summary {
            background: rgba(255, 255, 255, 0.02);
        }
    }

    .meta-item {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .meta-label {
        font-size: 10px;
        text-transform: uppercase;
        color: var(--text-muted);
        font-weight: 600;
        letter-spacing: 0.5px;
    }

    .meta-value {
        font-size: 13px;
        font-weight: 500;
    }

    /* Diff View styling */
    .diff-header-row {
        display: grid;
        grid-template-columns: 200px 1fr 1fr;
        gap: 20px;
        padding: 8px 15px;
        border-bottom: 2px solid var(--mac-border);
        font-weight: 600;
        font-size: 12px;
        text-transform: uppercase;
        color: var(--text-muted);
    }

    .diff-grid {
        display: flex;
        flex-direction: column;
        border: 1px solid var(--mac-border);
        border-radius: 8px;
        overflow: hidden;
    }

    .diff-row {
        display: grid;
        grid-template-columns: 200px 1fr 1fr;
        gap: 20px;
        padding: 12px 15px;
        border-bottom: 1px solid var(--mac-border);
        font-size: 12px;
        transition: background-color 0.15s;
    }

    .diff-row:last-child {
        border-bottom: none;
    }

    .diff-key {
        font-family: monospace;
        font-weight: 600;
        color: var(--text-main);
        word-break: break-all;
    }

    .diff-val {
        overflow-x: auto;
        word-break: break-all;
    }

    .diff-val pre {
        margin: 0;
        font-family: monospace;
        font-size: 11px;
        white-space: pre-wrap;
    }

    /* Row highlight colors */
    .row-added { background-color: rgba(46, 125, 50, 0.04); }
    .row-removed { background-color: rgba(198, 40, 40, 0.04); }
    .row-modified { background-color: rgba(239, 108, 0, 0.04); }
    
    .value-added { color: #2e7d32; font-weight: 500; }
    .value-removed { color: #c62828; text-decoration: line-through; }
    .value-none { color: var(--text-muted); font-style: italic; }

    .btn-inspect {
        background: rgba(0, 102, 204, 0.1);
        color: #0066cc;
        padding: 6px 12px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        border: none;
        cursor: pointer;
        transition: all 0.15s ease;
    }

    .btn-inspect:hover {
        background: #0066cc;
        color: #fff;
    }
</style>

<div class="audit-container">
    <div class="header-actions">
        <div>
            <h2 style="margin: 0 0 5px 0; color: #c62828; display: flex; align-items: center; gap: 8px;">
                <i class="ph ph-shield-check"></i> System Audit Trail
            </h2>
            <p style="margin: 0; color: var(--text-muted); font-size: 14px;">Immutable, read-only ledger of all administrative & operational actions.</p>
        </div>
        <div style="font-size: 12px; color: var(--text-muted); font-weight: 500;">
            Showing last <?= count($data['logs']) ?> actions
        </div>
    </div>

    <!-- Search and Filters Panel -->
    <div class="filter-panel">
        <form class="filter-form" method="GET" action="<?= APP_URL ?>/audit">
            <div class="filter-group">
                <label for="search">Search Details</label>
                <input type="text" id="search" name="search" class="filter-input" placeholder="Description, Record ID..." value="<?= htmlspecialchars($filterSearch) ?>">
            </div>

            <div class="filter-group">
                <label for="user_id">User / Operator</label>
                <select id="user_id" name="user_id" class="filter-input">
                    <option value="">All Users</option>
                    <?php foreach($data['users'] as $user): ?>
                        <option value="<?= $user->id ?>" <?= $filterUser == $user->id ? 'selected' : '' ?>>
                            <?= htmlspecialchars($user->username) ?> (<?= htmlspecialchars($user->role) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label for="module">System Module</label>
                <select id="module" name="module" class="filter-input">
                    <option value="">All Modules</option>
                    <?php foreach($data['modules'] as $mod): ?>
                        <option value="<?= htmlspecialchars($mod->module) ?>" <?= $filterModule == $mod->module ? 'selected' : '' ?>>
                            <?= htmlspecialchars($mod->module) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label for="action">Action Type</label>
                <select id="action" name="action" class="filter-input">
                    <option value="">All Actions</option>
                    <?php foreach($data['actions'] as $act): ?>
                        <option value="<?= htmlspecialchars($act->action) ?>" <?= $filterAction == $act->action ? 'selected' : '' ?>>
                            <?= htmlspecialchars($act->action) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label for="date_from">Date From</label>
                <input type="date" id="date_from" name="date_from" class="filter-input" value="<?= htmlspecialchars($filterDateFrom) ?>">
            </div>

            <div class="filter-group">
                <label for="date_to">Date To</label>
                <input type="date" id="date_to" name="date_to" class="filter-input" value="<?= htmlspecialchars($filterDateTo) ?>">
            </div>

            <div class="filter-buttons">
                <button type="submit" class="btn btn-primary"><i class="ph ph-funnel"></i> Filter</button>
                <a href="<?= APP_URL ?>/audit" class="btn btn-secondary"><i class="ph ph-arrow-counter-clockwise"></i> Reset</a>
            </div>
        </form>
    </div>

    <!-- Data Logs Table -->
    <div class="data-table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 160px;">Timestamp</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Module</th>
                    <th style="width: 35%;">Description</th>
                    <th>IP Address</th>
                    <th style="width: 90px; text-align: center;">Details</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($data['logs'])): ?>
                    <tr><td colspan="7" style="text-align: center; color: var(--text-muted); padding: 40px;">No audit logs match current filters.</td></tr>
                <?php else: foreach($data['logs'] as $log): ?>
                    <?php
                    $cleanAct = strtolower(str_replace(' ', '-', $log->action));
                    $actionClass = 'action-' . $cleanAct;
                    // Check if has diff values
                    $hasDiff = (!empty($log->old_values) || !empty($log->new_values));
                    ?>
                    <tr class="log-row" 
                        data-old="<?= htmlspecialchars($log->old_values ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                        data-new="<?= htmlspecialchars($log->new_values ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        data-action="<?= htmlspecialchars($log->action) ?>"
                        data-module="<?= htmlspecialchars($log->module) ?>"
                        data-desc="<?= htmlspecialchars($log->description) ?>"
                        data-user="<?= htmlspecialchars($log->username ?? 'System') ?>"
                        data-time="<?= date('M d, Y H:i:s', strtotime($log->created_at)) ?>"
                        data-ip="<?= htmlspecialchars($log->ip_address) ?>"
                        data-device="<?= htmlspecialchars($log->browser_device ?? 'N/A') ?>"
                        data-record="<?= htmlspecialchars($log->record_id ?? 'N/A') ?>">
                        
                        <td style="color: var(--text-muted);"><?= date('M d, Y H:i:s', strtotime($log->created_at)) ?></td>
                        <td>
                            <strong><?= htmlspecialchars($log->username ?? 'System') ?></strong><br>
                            <span style="font-size: 10px; color: var(--text-muted);"><?= htmlspecialchars($log->role ?? 'N/A') ?></span>
                        </td>
                        <td><span class="badge <?= $actionClass ?>"><?= htmlspecialchars($log->action) ?></span></td>
                        <td class="module-badge"><?= htmlspecialchars($log->module) ?></td>
                        <td>
                            <?= htmlspecialchars($log->description) ?>
                            <?php if ($log->record_id): ?>
                                <span style="font-size: 11px; background: rgba(0,0,0,0.04); padding: 2px 4px; border-radius: 3px; font-family: monospace;">ID: <?= $log->record_id ?></span>
                            <?php endif; ?>
                        </td>
                        <td style="font-size: 11px; color: var(--text-muted); font-family: monospace;"><?= htmlspecialchars($log->ip_address) ?></td>
                        <td style="text-align: center;">
                            <?php if($hasDiff): ?>
                                <button type="button" class="btn-inspect" onclick="event.stopPropagation(); openInspectModal(this.closest('.log-row'))">
                                    <i class="ph ph-eye"></i> Diff
                                </button>
                            <?php else: ?>
                                <span style="color: var(--text-muted); font-size: 11px;">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Inspect Forensic Details Modal -->
<div class="modal-backdrop" id="inspectModal" onclick="closeInspectModal()">
    <div class="modal-box" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="ph ph-shield-check" style="color: #c62828;"></i> Forensic Audit Log Details
            </h3>
            <button class="modal-close" onclick="closeInspectModal()">&times;</button>
        </div>
        <div class="modal-body">
            <!-- Metadata Grid -->
            <div class="meta-summary">
                <div class="meta-item">
                    <span class="meta-label">User / Operator</span>
                    <span class="meta-value" id="meta-user"></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Action & Module</span>
                    <span class="meta-value" id="meta-action"></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Record Reference ID</span>
                    <span class="meta-value" id="meta-record"></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Timestamp</span>
                    <span class="meta-value" id="meta-time"></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">IP Address</span>
                    <span class="meta-value" id="meta-ip"></span>
                </div>
                <div class="meta-item" style="grid-column: span 2;">
                    <span class="meta-label">Browser & Device</span>
                    <span class="meta-value" id="meta-device" style="font-family: monospace; font-size: 11px;"></span>
                </div>
            </div>

            <!-- Description -->
            <div>
                <h4 style="margin: 0 0 8px 0; font-size: 14px;">Event Description</h4>
                <p id="meta-desc" style="margin: 0; padding: 12px; background: rgba(0,0,0,0.02); border-left: 4px solid #0066cc; border-radius: 4px; font-size: 13px;"></p>
            </div>

            <!-- Diff Content View -->
            <div>
                <h4 style="margin: 0 0 10px 0; font-size: 14px;">Historical Ledger Diff (Old State vs New State)</h4>
                
                <div class="diff-header-row">
                    <div>Field / Key</div>
                    <div>Before (Old Value)</div>
                    <div>After (New Value)</div>
                </div>
                <div id="diff-view-target"></div>
            </div>
        </div>
    </div>
</div>

<script>
    // Row click navigation
    document.querySelectorAll('.log-row').forEach(row => {
        row.addEventListener('click', () => {
            if (row.getAttribute('data-old') || row.getAttribute('data-new')) {
                openInspectModal(row);
            }
        });
    });

    function escapeHtml(text) {
        if (!text) return '';
        return text
            .toString()
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function formatDiffValue(val, isNew, status) {
        if (val === undefined) {
            return isNew ? '<span class="value-none">N/A</span>' : '<span class="value-none">Deleted</span>';
        }
        if (val === null) {
            return '<span class="value-none">null</span>';
        }
        
        const isObject = typeof val === 'object';
        const str = isObject ? JSON.stringify(val, null, 2) : String(val);
        const escaped = escapeHtml(str);
        
        let spanClass = '';
        if (status === 'added') spanClass = 'value-added';
        else if (status === 'removed') spanClass = 'value-removed';
        
        if (isObject) {
            return `<pre class="${spanClass}">${escaped}</pre>`;
        } else {
            return `<span class="${spanClass}">${escaped}</span>`;
        }
    }

    function generateDiffHtml(oldVal, newVal) {
        if (!oldVal && !newVal) {
            return '<p class="text-muted" style="padding: 15px; text-align: center;">No structured state snapshots captured for this event.</p>';
        }

        let oldObj = null;
        let newObj = null;

        try {
            oldObj = oldVal ? (typeof oldVal === 'string' ? JSON.parse(oldVal) : oldVal) : null;
        } catch(e) { oldObj = oldVal; }

        try {
            newObj = newVal ? (typeof newVal === 'string' ? JSON.parse(newVal) : newVal) : null;
        } catch(e) { newObj = newVal; }

        // If both are not objects, show flat diff
        if ((oldObj && typeof oldObj !== 'object') || (newObj && typeof newObj !== 'object')) {
            return `
                <div class="diff-grid">
                    <div class="diff-row row-modified">
                        <div class="diff-key">Value</div>
                        <div class="diff-val diff-old">${formatDiffValue(oldObj, false, 'removed')}</div>
                        <div class="diff-val diff-new">${formatDiffValue(newObj, true, 'added')}</div>
                    </div>
                </div>
            `;
        }

        // Standardize as objects
        oldObj = oldObj || {};
        newObj = newObj || {};

        const allKeys = new Set([...Object.keys(oldObj), ...Object.keys(newObj)]);
        let html = '<div class="diff-grid">';

        allKeys.forEach(key => {
            const valOld = oldObj[key];
            const valNew = newObj[key];

            const isOldDefined = valOld !== undefined;
            const isNewDefined = valNew !== undefined;

            let rowClass = '';
            let oldHtml = '';
            let newHtml = '';

            if (!isOldDefined) {
                rowClass = 'row-added';
                oldHtml = formatDiffValue(undefined, false, 'added');
                newHtml = formatDiffValue(valNew, true, 'added');
            } else if (!isNewDefined) {
                rowClass = 'row-removed';
                oldHtml = formatDiffValue(valOld, false, 'removed');
                newHtml = formatDiffValue(undefined, true, 'removed');
            } else if (JSON.stringify(valOld) !== JSON.stringify(valNew)) {
                rowClass = 'row-modified';
                oldHtml = formatDiffValue(valOld, false, 'removed');
                newHtml = formatDiffValue(valNew, true, 'added');
            } else {
                rowClass = 'row-unchanged';
                oldHtml = formatDiffValue(valOld, false, 'unchanged');
                newHtml = formatDiffValue(valNew, true, 'unchanged');
            }

            html += `
                <div class="diff-row ${rowClass}">
                    <div class="diff-key">${escapeHtml(key)}</div>
                    <div class="diff-val diff-old">${oldHtml}</div>
                    <div class="diff-val diff-new">${newHtml}</div>
                </div>
            `;
        });

        html += '</div>';
        return html;
    }

    function openInspectModal(row) {
        const oldVal = row.getAttribute('data-old');
        const newVal = row.getAttribute('data-new');
        
        document.getElementById('meta-user').innerText = row.getAttribute('data-user');
        document.getElementById('meta-action').innerText = row.getAttribute('data-action') + ' (' + row.getAttribute('data-module') + ')';
        document.getElementById('meta-record').innerText = row.getAttribute('data-record') || 'N/A';
        document.getElementById('meta-time').innerText = row.getAttribute('data-time');
        document.getElementById('meta-ip').innerText = row.getAttribute('data-ip');
        document.getElementById('meta-device').innerText = row.getAttribute('data-device');
        document.getElementById('meta-desc').innerText = row.getAttribute('data-desc');

        const diffContainer = document.getElementById('diff-view-target');
        diffContainer.innerHTML = generateDiffHtml(oldVal, newVal);

        document.getElementById('inspectModal').classList.add('show');
    }

    function closeInspectModal() {
        document.getElementById('inspectModal').classList.remove('show');
    }

    // Close on Escape key press
    window.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeInspectModal();
        }
    });
</script>