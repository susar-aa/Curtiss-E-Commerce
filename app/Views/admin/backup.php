<?php
?>
<style>
    .admin-container {
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
        gap: 16px;
        flex-wrap: wrap;
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
    
    .btn { 
        padding: 10px 20px; 
        background: var(--text-accent); 
        color: #fff !important; 
        border: none; 
        border-radius: 12px; 
        cursor: pointer; 
        text-decoration: none; 
        font-size: 13.5px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: background 0.2s, transform 0.15s;
    }
    .btn:hover { 
        background: var(--text-accent-light); 
        transform: translateY(-1px);
    }
    .btn-outline { 
        background: transparent; 
        border: 1px solid var(--glass-border); 
        color: var(--text-main) !important; 
    }
    .btn-outline:hover {
        background: rgba(255, 255, 255, 0.08);
    }
    .btn-danger {
        background: #ef4444 !important;
        color: #fff !important;
    }
    .btn-danger:hover {
        background: #f87171 !important;
    }
    .btn-success {
        background: #10b981 !important;
        color: #fff !important;
    }
    .btn-success:hover {
        background: #34d399 !important;
    }

    .grid-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 24px;
    }
    @media (max-width: 768px) {
        .grid-2 {
            grid-template-columns: 1fr;
        }
    }

    .data-table { 
        width: 100%; 
        border-collapse: collapse; 
        margin-top: 10px; 
    }
    .data-table th, .data-table td { 
        padding: 14px 16px; 
        text-align: left; 
        border-bottom: 1px solid var(--glass-border); 
    }
    .data-table th { 
        background-color: rgba(0, 0, 0, 0.03); 
        font-weight: 600; 
        font-size: 12px; 
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--text-muted);
    }
    @media (prefers-color-scheme: dark) {
        .data-table th {
            background-color: rgba(255, 255, 255, 0.02);
        }
    }
    .data-table td {
        color: var(--text-main);
        font-size: 13.5px;
    }
    .data-table tr:hover td {
        background: rgba(255, 255, 255, 0.03);
    }
    
    .form-group { margin-bottom: 16px; }
    .form-group label { 
        display: block; 
        margin-bottom: 6px; 
        font-size: 12.5px; 
        font-weight: 600; 
        color: var(--text-main);
        opacity: 0.85;
    }
    .form-control { 
        width: 100%; 
        padding: 10px 14px; 
        border: 1px solid var(--glass-border); 
        border-radius: 10px; 
        background: rgba(255, 255, 255, 0.08); 
        color: var(--text-main); 
        box-sizing: border-box;
        font-family: inherit;
        font-size: 13.5px;
        outline: none;
        transition: border-color 0.2s;
    }
    @media (prefers-color-scheme: dark) {
        .form-control {
            background: rgba(0, 0, 0, 0.2);
        }
    }
</style>

<div class="admin-container">
    <div class="header-actions">
        <div class="header-title-wrap">
            <h2><i class="ph ph-database" style="color: var(--text-accent);"></i> Backup &amp; Restore</h2>
            <p>Export database dumps, download offline backups, and restore database system states.</p>
        </div>
    </div>

    <?php if(!empty($data['error'])): ?>
        <div style="padding: 12px 16px; background: rgba(239, 68, 68, 0.12); color: #ef4444; border-radius: 12px; margin-bottom: 20px; font-size: 13px; font-weight: 500; display: flex; align-items: center; gap: 8px; border: 1px solid rgba(239, 68, 68, 0.25);">
            <i class="ph ph-warning-circle" style="font-size: 16px;"></i>
            <?= $data['error'] ?>
        </div>
    <?php endif; ?>
    <?php if(!empty($data['success'])): ?>
        <div style="padding: 12px 16px; background: rgba(16, 185, 129, 0.12); color: #10b981; border-radius: 12px; margin-bottom: 20px; font-size: 13px; font-weight: 500; display: flex; align-items: center; gap: 8px; border: 1px solid rgba(16, 185, 129, 0.25);">
            <i class="ph ph-check-circle" style="font-size: 16px;"></i>
            <?= $data['success'] ?>
        </div>
    <?php endif; ?>

    <div class="grid-2">
        <!-- Backup Section -->
        <div class="glass-card">
            <h3 style="margin-top: 0; margin-bottom: 12px; font-size: 16px; font-weight: 700; color: var(--text-main); display: flex; align-items: center; gap: 8px;">
                <i class="ph ph-file-arrow-down" style="color: var(--text-accent);"></i> Create Database Backup
            </h3>
            <p style="font-size: 12.5px; color: var(--text-muted); margin-bottom: 20px; line-height: 1.5;">
                Generates a native SQL dump of the system database (`<?= DB_NAME ?>`), including all definitions and data. Files are securely archived in the server environment.
            </p>
            <a href="<?= APP_URL ?>/backup/generate" class="btn"><i class="ph ph-rocket-launch"></i> Run Full Backup Now</a>
        </div>

        <!-- Restore Section -->
        <div class="glass-card">
            <h3 style="margin-top: 0; margin-bottom: 12px; font-size: 16px; font-weight: 700; color: var(--text-main); display: flex; align-items: center; gap: 8px;">
                <i class="ph ph-file-arrow-up" style="color: #ef4444;"></i> Upload &amp; Restore SQL
            </h3>
            <p style="font-size: 12.5px; color: var(--text-muted); margin-bottom: 16px; line-height: 1.5;">
                Select a local `.sql` backup file to upload and execute on the database server.
            </p>
            <form action="<?= APP_URL ?>/backup/restore" method="POST" enctype="multipart/form-data">
                <div class="form-group" style="margin-bottom: 12px;">
                    <input type="file" name="backup_file" class="form-control" accept=".sql" required>
                </div>
                <button type="submit" class="btn btn-danger" onclick="return confirm('WARNING: Restoring the database will overwrite all current tables and transactional entries. Are you sure you want to continue?')">
                    <i class="ph ph-warning-circle"></i> Upload &amp; Restore Database
                </button>
            </form>
        </div>
    </div>

    <!-- Backup Archives List -->
    <div class="glass-card">
        <h3 style="margin-top: 0; margin-bottom: 20px; font-size: 16px; font-weight: 700; color: var(--text-main); display: flex; align-items: center; gap: 8px;">
            <i class="ph ph-archive" style="color: var(--text-accent);"></i> Backup History Archives
        </h3>
        
        <div style="overflow-x: auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Backup Filename</th>
                        <th>Created Date</th>
                        <th>File Size</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($data['files'])): ?>
                    <tr><td colspan="4" style="text-align: center; color: var(--text-muted); padding: 40px;">No database backups generated yet.</td></tr>
                    <?php else: foreach($data['files'] as $f): ?>
                    <tr>
                        <td>
                            <strong style="color: var(--text-main); font-family: monospace; font-size: 13px;"><?= htmlspecialchars($f['filename']) ?></strong>
                        </td>
                        <td>
                            <span style="font-size: 13px; font-weight: 500; color: var(--text-main);"><?= date('d M Y - h:i A', $f['date']) ?></span>
                        </td>
                        <td>
                            <strong style="font-size: 12.5px;"><?= round($f['size'] / 1024, 2) ?> KB</strong>
                        </td>
                        <td style="text-align: right; display: flex; justify-content: flex-end; gap: 8px;">
                            <!-- Restore Server File -->
                            <form action="<?= APP_URL ?>/backup/restore" method="POST" style="margin: 0; display: inline;">
                                <input type="hidden" name="server_file" value="<?= htmlspecialchars($f['filename']) ?>">
                                <button type="submit" class="btn btn-success" style="padding: 6px 12px; font-size: 11.5px; border-radius: 8px;" onclick="return confirm('Are you sure you want to restore the system state using <?= htmlspecialchars($f['filename']) ?>?')">
                                    <i class="ph ph-arrow-counter-clockwise"></i> Restore
                                </button>
                            </form>
                            <!-- Download File -->
                            <a href="<?= APP_URL ?>/backup/download/<?= urlencode($f['filename']) ?>" class="btn btn-outline" style="padding: 6px 12px; font-size: 11.5px; border-radius: 8px;">
                                <i class="ph ph-download-simple"></i> Download
                            </a>
                            <!-- Delete File -->
                            <a href="<?= APP_URL ?>/backup/delete/<?= urlencode($f['filename']) ?>" class="btn btn-outline" style="padding: 6px 12px; font-size: 11.5px; border-radius: 8px; border-color: rgba(239, 68, 68, 0.25); color: #ef4444 !important;" onclick="return confirm('Are you sure you want to delete this backup archive from the server?')">
                                <i class="ph ph-trash"></i> Delete
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
