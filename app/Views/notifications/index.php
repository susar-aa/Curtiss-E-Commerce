<?php
?>
<style>
    .header-actions { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 20px; }
    .btn-clear { padding: 8px 16px; background: transparent; color: #666; border: 1px solid var(--mac-border); border-radius: 4px; cursor: pointer; text-decoration: none; font-size: 13px;}
    .btn-clear:hover { background: rgba(0,0,0,0.05); }
    
    .notif-list { display: flex; flex-direction: column; gap: 10px; }
    .notif-card { background: #fff; padding: 15px 20px; border-radius: 8px; border: 1px solid var(--mac-border); display: flex; justify-content: space-between; align-items: center; transition: 0.2s;}
    @media (prefers-color-scheme: dark) { .notif-card { background: #1e1e2d; } }
    .notif-card:hover { border-color: #0066cc; }
    .notif-card.unread { border-left: 4px solid #0066cc; background: rgba(0, 102, 204, 0.02); }
    
    .notif-content h4 { margin: 0 0 5px 0; font-size: 15px; color: var(--text-main); }
    .notif-content p { margin: 0; font-size: 13px; color: #666; }
    .notif-meta { font-size: 11px; color: #aaa; margin-top: 5px; }
    
    .btn-action { padding: 6px 12px; background: #0066cc; color: #fff; border-radius: 4px; text-decoration: none; font-size: 12px; }
</style>

<div class="card">
    <div class="header-actions">
        <div>
            <h2 style="margin: 0 0 5px 0;">Alerts & Notifications</h2>
            <p style="margin: 0; color: #666; font-size: 14px;">Stay updated on crucial ERP events.</p>
        </div>
        <a href="<?= APP_URL ?>/notification/read_all" class="btn-clear">✓ Mark All as Read</a>
    </div>

    <div class="notif-list">
        <?php if(empty($data['notifications'])): ?>
            <div style="text-align: center; color: #888; padding: 40px;">No notifications yet. You're all caught up!</div>
        <?php else: foreach($data['notifications'] as $notif): ?>
            <div class="notif-card <?= $notif->is_read ? '' : 'unread' ?>">
                <div class="notif-content">
                    <h4><?= htmlspecialchars($notif->title) ?></h4>
                    <p><?= htmlspecialchars($notif->message) ?></p>
                    <div class="notif-meta"><?= date('F j, Y - g:i A', strtotime($notif->created_at)) ?></div>
                </div>
                <div>
                    <?php if(!empty($notif->link_url)): ?>
                        <a href="<?= APP_URL ?>/notification/read/<?= $notif->id ?>" class="btn-action">View &rarr;</a>
                    <?php elseif(!$notif->is_read): ?>
                        <a href="<?= APP_URL ?>/notification/read/<?= $notif->id ?>" class="btn-clear" style="border:none;">Mark Read</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; endif; ?>
    </div>
</div>