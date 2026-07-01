<style>
    .blog-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 25px;
        margin-top: 20px;
    }
    .blog-card {
        background: var(--card-bg);
        border: 1px solid var(--card-border);
        border-radius: 16px;
        overflow: hidden;
        box-shadow: var(--card-shadow);
        backdrop-filter: blur(var(--glass-blur));
        display: flex;
        flex-direction: column;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .blog-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 30px rgba(0,0,0,0.12);
    }
    .blog-img-preview {
        height: 150px;
        width: 100%;
        object-fit: cover;
        background: #f0f2f6;
        border-bottom: 1px solid var(--mega-divider);
    }
    .blog-body {
        padding: 18px;
        display: flex;
        flex-direction: column;
        flex: 1;
        gap: 8px;
    }
    .blog-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 11px;
        color: var(--text-muted);
    }
    .blog-cat-badge {
        background: rgba(0,102,204,0.1);
        color: #0066cc;
        padding: 2px 6px;
        border-radius: 4px;
        font-weight: 600;
        text-transform: uppercase;
    }
    .blog-title {
        font-size: 14.5px;
        font-weight: 700;
        color: var(--text-main);
        margin: 4px 0;
        line-height: 1.3;
    }
    .blog-excerpt {
        font-size: 12.5px;
        color: var(--text-muted);
        line-height: 1.4;
        flex: 1;
    }
    .blog-footer {
        display: flex;
        justify-content: flex-end;
        gap: 8px;
        border-top: 1px solid var(--mega-divider);
        padding-top: 12px;
        margin-top: 10px;
    }
</style>

<div class="header-actions" style="margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h2>Blog &amp; Content Management</h2>
        <p style="color: var(--text-muted); margin-top: 4px;">Publish promotional articles, news feeds, and stationery guides to engage storefront visitors.</p>
    </div>
    <button type="button" class="btn-primary" onclick="openAddModal()" style="padding: 10px 20px; border-radius: 8px; font-size: 13px;">
        <i class="ph ph-plus-circle" style="vertical-align: middle; margin-right: 5px;"></i> Write Blog Post
    </button>
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

<!-- Blog Articles Grid -->
<div class="blog-grid">
    <?php if(empty($data['posts'])): ?>
        <div class="card" style="grid-column: 1/-1; text-align: center; color: var(--text-muted); padding: 50px;">
            <i class="ph ph-article" style="font-size: 48px; opacity: 0.5; margin-bottom: 10px;"></i>
            <p>No blog posts published yet. Start writing articles to populate your storefront.</p>
        </div>
    <?php else: ?>
        <?php foreach($data['posts'] as $post): ?>
            <div class="blog-card">
                <?php if(!empty($post->image_path)): ?>
                    <img src="<?= APP_URL ?>/uploads/blog/<?= htmlspecialchars($post->image_path) ?>" class="blog-img-preview" alt="Blog Image">
                <?php else: ?>
                    <div class="blog-img-preview" style="display:flex; align-items:center; justify-content:center; color:#ccc;"><i class="ph ph-image" style="font-size:32px;"></i></div>
                <?php endif; ?>
                
                <div class="blog-body">
                    <div class="blog-meta">
                        <span class="blog-cat-badge"><?= htmlspecialchars($post->category ?: 'General') ?></span>
                        <span><?= date('M d, Y', strtotime($post->created_at)) ?></span>
                    </div>
                    <h3 class="blog-title">
                        <?= htmlspecialchars($post->title) ?>
                        <?php if($post->is_featured): ?><span class="pill-badge pill-success" style="font-size: 9px; vertical-align: middle; margin-left: 5px;">Featured</span><?php endif; ?>
                    </h3>
                    <p class="blog-excerpt">
                        <?= htmlspecialchars(substr(strip_tags($post->content), 0, 110)) ?>...
                    </p>
                    <div style="font-size: 11px; color: var(--text-muted); margin-top:5px;">
                        Slug: <code>/blog/<?= htmlspecialchars($post->seo_url) ?></code> | Author: <?= htmlspecialchars($post->author) ?>
                    </div>

                    <div class="blog-footer">
                        <button type="button" class="btn-secondary" style="padding: 6px 12px; font-size:12px;" onclick="openEditModal(<?= htmlspecialchars(json_encode($post)) ?>)">
                            <i class="ph ph-pencil"></i> Edit
                        </button>
                        
                        <form action="<?= APP_URL ?>/ecommerce/blog" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this blog post?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="post_id" value="<?= $post->id ?>">
                            <button type="submit" class="btn-secondary" style="border-color: #ff3b30; color: #ff3b30; padding: 6px 12px; font-size:12px;">
                                <i class="ph ph-trash"></i> Delete
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Modal: Add / Edit Blog Post -->
<div class="modal-backdrop" id="blogModal">
    <div class="modal-box" style="width: 700px;">
        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom: 1px solid var(--mega-divider); padding-bottom:12px; margin-bottom: 18px;">
            <h3 id="modalTitle" style="font-size: 16px; font-weight:700;">Write Blog Post</h3>
            <button class="close-btn" onclick="closeModal()">&times;</button>
        </div>

        <form action="<?= APP_URL ?>/ecommerce/blog" method="POST" enctype="multipart/form-data" id="blogForm">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="post_id" id="formPostId">

            <div class="form-box">
                <label>Article Title</label>
                <input type="text" name="title" id="formTitle" class="form-control" required placeholder="e.g. 5 Best Fountain Pens for Calligraphy Beginners" oninput="generateSlug(this.value)">
            </div>

            <div class="form-box">
                <label>SEO Path Slug</label>
                <input type="text" name="seo_url" id="formSeoUrl" class="form-control" placeholder="e.g. best-fountain-pens-for-beginners">
            </div>

            <div class="settings-grid">
                <div class="form-box">
                    <label>Article Category</label>
                    <input type="text" name="category" id="formCategory" class="form-control" placeholder="e.g. Buying Guides">
                </div>
                <div class="form-box">
                    <label>Author Display Name</label>
                    <input type="text" name="author" id="formAuthor" class="form-control" value="Admin">
                </div>
            </div>

            <div class="settings-grid">
                <div class="form-box">
                    <label>Cover Thumbnail Image</label>
                    <input type="file" name="blog_image" class="form-control" accept="image/*">
                </div>
                <div class="form-box" style="display:flex; align-items:center;">
                    <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                        <input type="checkbox" name="is_featured" id="formFeatured" value="1" style="width: 16px; height:16px;">
                        Mark as Featured Post
                    </label>
                </div>
            </div>

            <div class="form-box">
                <label>Article Body Content</label>
                <textarea name="content" id="formContent" class="form-control" rows="10" required placeholder="Write article content here... (HTML tags supported)"></textarea>
            </div>

            <div class="btn-actions">
                <button type="button" class="btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn-primary" id="submitBtn">Publish Post</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openAddModal() {
        document.getElementById('modalTitle').innerText = "Write Blog Post";
        document.getElementById('formAction').value = "add";
        document.getElementById('formPostId').value = "";
        
        document.getElementById('formTitle').value = "";
        document.getElementById('formSeoUrl').value = "";
        document.getElementById('formCategory').value = "Guides";
        document.getElementById('formAuthor').value = "Admin";
        document.getElementById('formFeatured').checked = false;
        document.getElementById('formContent').value = "";
        
        document.getElementById('submitBtn').innerText = "Publish Post";
        document.getElementById('blogModal').style.display = "flex";
    }

    function openEditModal(post) {
        document.getElementById('modalTitle').innerText = "Edit Blog Post";
        document.getElementById('formAction').value = "edit";
        document.getElementById('formPostId').value = post.id;
        
        document.getElementById('formTitle').value = post.title;
        document.getElementById('formSeoUrl').value = post.seo_url;
        document.getElementById('formCategory').value = post.category || "";
        document.getElementById('formAuthor').value = post.author || "Admin";
        document.getElementById('formFeatured').checked = parseInt(post.is_featured) === 1;
        document.getElementById('formContent').value = post.content || "";
        
        document.getElementById('submitBtn').innerText = "Save Changes";
        document.getElementById('blogModal').style.display = "flex";
    }

    function closeModal() {
        document.getElementById('blogModal').style.display = "none";
    }

    function generateSlug(text) {
        const action = document.getElementById('formAction').value;
        // Only auto-generate slug on 'add' action
        if (action === 'add') {
            const slug = text.toLowerCase()
                .trim()
                .replace(/[^\w\s-]/g, '')
                .replace(/[\s_-]+/g, '-')
                .replace(/^-+|-+$/g, '');
            document.getElementById('formSeoUrl').value = slug;
        }
    }
</script>
