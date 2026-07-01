<div style="margin-bottom: 20px;">
    <a href="<?= APP_URL ?>/shop/blog" style="text-decoration:none; color:var(--text-muted); font-size:13.5px; font-weight:600;"><i class="ph ph-arrow-left"></i> Back to Blog roll</a>
</div>

<div class="card" style="max-width: 800px; margin: 0 auto 50px auto; padding: 40px 30px;">
    <span class="pill-badge" style="background: rgba(0,118,255,0.08); color:var(--text-accent); font-size:11px; margin-bottom: 15px;"><?= htmlspecialchars($data['post']->category) ?></span>
    <h1 style="font-size: 32px; font-weight: 800; color:var(--text-main); line-height: 1.2; margin-bottom: 15px;"><?= htmlspecialchars($data['post']->title) ?></h1>
    
    <div style="display:flex; gap:15px; font-size: 12.5px; color:var(--text-muted); margin-bottom: 30px; border-bottom:1px solid var(--mega-divider); padding-bottom: 15px;">
        <span>Published: <strong><?= date('F d, Y', strtotime($data['post']->created_at)) ?></strong></span>
        <span>Written by: <strong><?= htmlspecialchars($data['post']->author) ?></strong></span>
    </div>

    <?php if(!empty($data['post']->image_path)): ?>
        <div style="border-radius: var(--rounded); overflow:hidden; border:1px solid var(--card-border); margin-bottom: 30px; text-align:center;">
            <img src="<?= APP_URL ?>/uploads/blog/<?= htmlspecialchars($data['post']->image_path) ?>" alt="Blog banner" style="max-width:100%; height:auto; object-fit:cover;">
        </div>
    <?php endif; ?>

    <div style="font-size:15px; line-height:1.7; color:var(--text-main);">
        <?= nl2br(htmlspecialchars($data['post']->content)) ?>
    </div>
</div>
