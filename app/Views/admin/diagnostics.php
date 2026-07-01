<?php
?>
<style>
    .diagnostics-container {
        padding: 24px;
    }
    .glass-card {
        background: var(--card-bg);
        border: 1px solid var(--card-border);
        box-shadow: var(--card-shadow);
        backdrop-filter: blur(24px);
        -webkit-backdrop-filter: blur(24px);
        border-radius: 20px;
        padding: 28px;
        margin-bottom: 24px;
    }
    .header-actions { 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        margin-bottom: 24px; 
    }
    .header-title-wrap h2 {
        font-size: 22px;
        font-weight: 700;
        color: var(--text-main);
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .header-title-wrap p {
        font-size: 13px;
        color: var(--text-muted);
        margin: 4px 0 0 0;
    }
    
    .grid-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 24px;
        margin-bottom: 24px;
    }
    .grid-3 {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 24px;
        margin-bottom: 24px;
    }
    @media (max-width: 992px) {
        .grid-3 {
            grid-template-columns: 1fr 1fr;
        }
    }
    @media (max-width: 768px) {
        .grid-2, .grid-3 {
            grid-template-columns: 1fr;
        }
    }

    .health-status-bar {
        height: 6px;
        background: rgba(0, 0, 0, 0.08);
        border-radius: 3px;
        overflow: hidden;
        margin-top: 10px;
        margin-bottom: 6px;
    }
    .health-status-fill {
        height: 100%;
        background: #10b981;
        border-radius: 3px;
        transition: width 0.4s ease;
    }
    .health-status-fill.warning {
        background: #f59e0b;
    }
    .health-status-fill.danger {
        background: #ef4444;
    }

    .info-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .info-list li {
        display: flex;
        justify-content: space-between;
        padding: 11px 0;
        border-bottom: 1px solid var(--glass-border);
        font-size: 13.5px;
    }
    .info-list li:last-child {
        border-bottom: none;
    }
    .info-list li span.label {
        color: var(--text-muted);
        font-weight: 500;
    }
    .info-list li span.value {
        color: var(--text-main);
        font-weight: 600;
    }

    .badge-status {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .badge-ok {
        background: rgba(16, 185, 129, 0.12);
        color: #10b981;
    }
    .badge-fail {
        background: rgba(239, 68, 68, 0.12);
        color: #ef4444;
    }

    .ext-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
        gap: 12px;
        margin-top: 16px;
    }
    .ext-item {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid var(--glass-border);
        border-radius: 12px;
        padding: 12px;
        text-align: center;
    }
    .ext-name {
        font-size: 12.5px;
        font-weight: 600;
        display: block;
        margin-bottom: 6px;
        color: var(--text-main);
    }
</style>

<div class="diagnostics-container">
    <div class="header-actions">
        <div class="header-title-wrap">
            <h2><i class="ph ph-heartbeat" style="color: var(--text-accent);"></i> System Health &amp; Diagnostics</h2>
            <p>Monitor critical performance indices, database state metrics, and hardware resource utilization.</p>
        </div>
    </div>

    <div class="grid-3">
        <!-- Database Health -->
        <div class="glass-card" style="margin-bottom: 0;">
            <h3 style="margin-top: 0; margin-bottom: 16px; font-size: 15px; font-weight: 700; color: var(--text-main); display: flex; align-items: center; gap: 8px;">
                <i class="ph ph-database" style="color: var(--text-accent);"></i> Database Metrics
            </h3>
            <ul class="info-list">
                <li>
                    <span class="label">MySQL Version</span>
                    <span class="value"><?= htmlspecialchars($data['server_info']['mysql_version']) ?></span>
                </li>
                <li>
                    <span class="label">DB Name</span>
                    <span class="value"><?= htmlspecialchars($data['server_info']['db_name']) ?></span>
                </li>
                <li>
                    <span class="label">DB Size</span>
                    <span class="value"><?= $data['server_info']['db_size'] ?></span>
                </li>
                <li>
                    <span class="label">Total Tables</span>
                    <span class="value"><?= $data['server_info']['table_count'] ?> Tables</span>
                </li>
            </ul>
        </div>

        <!-- Disk Allocation Space -->
        <div class="glass-card" style="margin-bottom: 0;">
            <h3 style="margin-top: 0; margin-bottom: 16px; font-size: 15px; font-weight: 700; color: var(--text-main); display: flex; align-items: center; gap: 8px;">
                <i class="ph ph-hard-drive" style="color: var(--text-accent);"></i> Disk Space Space
            </h3>
            <ul class="info-list">
                <li>
                    <span class="label">Free Disk Space</span>
                    <span class="value"><?= $data['disk_metrics']['free'] ?></span>
                </li>
                <li>
                    <span class="label">Used Disk Space</span>
                    <span class="value"><?= $data['disk_metrics']['used'] ?></span>
                </li>
                <li>
                    <span class="label">Total Disk Capacity</span>
                    <span class="value"><?= $data['disk_metrics']['total'] ?></span>
                </li>
            </ul>
            <div style="margin-top: 14px;">
                <div style="display: flex; justify-content: space-between; font-size: 11px; color: var(--text-muted); font-weight: 600;">
                    <span>DISK USAGE STATUS</span>
                    <span><?= $data['disk_metrics']['percentage'] ?>%</span>
                </div>
                <div class="health-status-bar">
                    <div class="health-status-fill <?= $data['disk_metrics']['percentage'] > 90 ? 'danger' : ($data['disk_metrics']['percentage'] > 75 ? 'warning' : '') ?>" style="width: <?= $data['disk_metrics']['percentage'] ?>%;"></div>
                </div>
            </div>
        </div>

        <!-- PHP Engine Limits -->
        <div class="glass-card" style="margin-bottom: 0;">
            <h3 style="margin-top: 0; margin-bottom: 16px; font-size: 15px; font-weight: 700; color: var(--text-main); display: flex; align-items: center; gap: 8px;">
                <i class="ph ph-cpu" style="color: var(--text-accent);"></i> PHP Engine Settings
            </h3>
            <ul class="info-list">
                <li>
                    <span class="label">Memory Limit</span>
                    <span class="value"><?= $data['php_settings']['memory_limit'] ?></span>
                </li>
                <li>
                    <span class="label">Max Execution Time</span>
                    <span class="value"><?= $data['php_settings']['max_execution_time'] ?></span>
                </li>
                <li>
                    <span class="label">Max Upload Filesize</span>
                    <span class="value"><?= $data['php_settings']['upload_max_filesize'] ?></span>
                </li>
                <li>
                    <span class="label">Max POST size</span>
                    <span class="value"><?= $data['php_settings']['post_max_size'] ?></span>
                </li>
            </ul>
        </div>
    </div>

    <div class="grid-2">
        <!-- Directory Permissions -->
        <div class="glass-card" style="margin-bottom: 0;">
            <h3 style="margin-top: 0; margin-bottom: 16px; font-size: 15px; font-weight: 700; color: var(--text-main); display: flex; align-items: center; gap: 8px;">
                <i class="ph ph-folder" style="color: var(--text-accent);"></i> Directory Permission Diagnostics
            </h3>
            <div style="overflow-x: auto;">
                <table class="data-table" style="margin-top: 0;">
                    <thead>
                        <tr>
                            <th>Directory Target</th>
                            <th>Status</th>
                            <th>Permission</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($data['folders'] as $name => $f): ?>
                        <tr>
                            <td>
                                <strong style="color: var(--text-main);"><?= $name ?></strong>
                                <div style="font-size: 11px; color: var(--text-muted); margin-top: 2px; font-family: monospace;"><?= htmlspecialchars($f['path']) ?></div>
                            </td>
                            <td>
                                <?php if($f['exists']): ?>
                                    <span class="badge-status badge-ok"><i class="ph ph-check"></i> Exists</span>
                                <?php else: ?>
                                    <span class="badge-status badge-fail"><i class="ph ph-x"></i> Missing</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($f['writable']): ?>
                                    <span class="badge-status badge-ok"><i class="ph ph-lock-key-open"></i> Writable</span>
                                <?php else: ?>
                                    <span class="badge-status badge-fail"><i class="ph ph-lock-key"></i> Read-Only</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- PHP Extension Registry -->
        <div class="glass-card" style="margin-bottom: 0;">
            <h3 style="margin-top: 0; margin-bottom: 4px; font-size: 15px; font-weight: 700; color: var(--text-main); display: flex; align-items: center; gap: 8px;">
                <i class="ph ph-puzzle-piece" style="color: var(--text-accent);"></i> Critical Extension Diagnostics
            </h3>
            <p style="font-size: 12px; color: var(--text-muted); margin: 0;">Verify critical third-party compilation modules and PDO handlers.</p>
            
            <div class="ext-grid">
                <?php foreach($data['extensions'] as $name => $status): ?>
                <div class="ext-item">
                    <span class="ext-name"><?= htmlspecialchars($name) ?></span>
                    <?php if($status): ?>
                        <span class="badge-status badge-ok" style="font-size: 10px; padding: 2px 8px;"><i class="ph ph-check"></i> Active</span>
                    <?php else: ?>
                        <span class="badge-status badge-fail" style="font-size: 10px; padding: 2px 8px;"><i class="ph ph-x"></i> Inactive</span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
