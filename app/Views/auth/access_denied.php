<?php
// Premium Glassmorphic Access Denied View
?>
<div style="display: flex; align-items: center; justify-content: center; min-height: 60vh; padding: 20px;">
    <div style="background: rgba(255, 255, 255, 0.75); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border: 1px solid var(--mac-border); border-radius: 16px; padding: 40px; text-align: center; max-width: 500px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); width: 100%;">
        <div style="width: 72px; height: 72px; background: #ffebee; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px; border: 1px solid rgba(198, 40, 40, 0.2);">
            <i class="ph-bold ph-shield-warning" style="font-size: 36px; color: #c62828;"></i>
        </div>
        <h2 style="font-size: 22px; font-weight: 700; color: var(--text-main); margin: 0 0 12px 0;">Security Access Control</h2>
        <p style="font-size: 14px; color: var(--text-muted); line-height: 1.6; margin: 0 0 24px 0;">
            <?= htmlspecialchars($data['error_message'] ?? 'You do not have permission to view this page.') ?>
        </p>
        <div style="display: flex; gap: 12px; justify-content: center;">
            <a href="<?= APP_URL ?>/dashboard" class="btn" style="display: flex; align-items: center; gap: 8px; justify-content: center; font-weight: 600;">
                <i class="ph-bold ph-house-line" style="font-size: 16px;"></i> Return to Dashboard
            </a>
        </div>
    </div>
</div>
