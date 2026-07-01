<style>
    .blog-layout-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 30px;
        margin-top: 15px;
    }
</style>

<div class="header-actions" style="margin-bottom: 25px;">
    <h2>Guides &amp; Stationery Insights</h2>
    <p style="color: var(--text-muted); margin-top: 4px;">Learn how to pick supplies, organize offices, and maximize productivity with the right gear.</p>
</div>

<div class="blog-layout-grid">
    <?php if(empty($data['posts'])): ?>
        <div class="card" style="grid-column: 1/-1; text-align:center; padding: 60px 20px; color: var(--text-muted);">
            <i class="ph ph-article-ny times" style="font-size:48px; opacity:0.5; margin-bottom:10px;"></i>
            <p>No blog posts published yet.</p>
        </div>
    <?php else: ?>
        <?php foreach($data['posts'] as $post): ?>
            <a href="<?= APP_URL ?>/shop/blog_post/<?= htmlspecialchars($post->seo_url) ?>" class="blog-showcase-card" style="background:var(--card-bg); height: 100%;">
                <?php if(!empty($post->image_path)): ?>
                    <div class="blog-showcase-img" style="height: 180px; background-image: url('<?= APP_URL ?>/uploads/blog/<?= htmlspecialchars($post->image_path) ?>');"></div>
                <?php else: ?>
                    <div class="blog-showcase-img" style="height: 180px; display:flex; align-items:center; justify-content:center; color:#ccc;"><i class="ph ph-article" style="font-size:48px;"></i></div>
                <?php endif; ?>
                
                <div class="blog-showcase-body" style="padding: 20px;">
                    <span class="pill-badge" style="background: rgba(0,0,0,0.05); color:var(--text-muted); width:fit-content; font-size:9px; margin-bottom: 10px;"><?= htmlspecialchars($post->category) ?></span>
                    <h3 style="font-size: 16px; font-weight:700; color:var(--text-main); line-height: 1.3; margin-bottom:10px;"><?= htmlspecialchars($post->title) ?></h3>
                    
                    <!-- Truncated body helper -->
                    <p style="font-size: 13px; color:var(--text-muted); line-height:1.5; margin-bottom: 15px;">
                        <?= htmlspecialchars(substr(strip_tags($post->content), 0, 120)) ?>...
                    </p>
                    
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-top: auto; font-size: 11px; color:var(--text-muted);">
                        <span>By <?= htmlspecialchars($post->author) ?></span>
                        <span><?= date('M d, Y', strtotime($post->created_at)) ?></span>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
