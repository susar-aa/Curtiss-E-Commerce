<style>
    .reviews-list {
        display: flex;
        flex-direction: column;
        gap: 20px;
        margin-top: 15px;
    }
    .review-card {
        background: var(--card-bg);
        border: 1px solid var(--card-border);
        border-radius: 16px;
        padding: 20px;
        box-shadow: var(--card-shadow);
        backdrop-filter: blur(var(--glass-blur));
        display: flex;
        flex-direction: column;
        gap: 12px;
        transition: transform 0.2s;
    }
    .review-card:hover {
        transform: translateY(-2px);
    }
    .review-top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        flex-wrap: wrap;
        gap: 10px;
        border-bottom: 1px solid var(--mega-divider);
        padding-bottom: 12px;
    }
    .reviewer-info {
        display: flex;
        flex-direction: column;
        gap: 3px;
    }
    .reviewer-name {
        font-weight: 700;
        font-size: 14.5px;
        color: var(--text-main);
    }
    .reviewer-email {
        font-size: 11px;
        color: var(--text-muted);
    }
    .product-linked {
        font-size: 12.5px;
        font-weight: 600;
        color: var(--text-accent);
        text-decoration: none;
    }
    .rating-stars {
        color: #ffcc00;
        font-size: 16px;
        display: flex;
        gap: 2px;
        margin-top: 4px;
    }

    .review-comment {
        font-size: 13.5px;
        line-height: 1.5;
        color: var(--text-main);
        font-style: italic;
        padding-left: 12px;
        border-left: 3px solid var(--card-border);
        margin: 8px 0;
    }
    .review-bottom {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 5px;
    }
    .review-date {
        font-size: 11.5px;
        color: var(--text-muted);
    }
    .review-actions {
        display: flex;
        gap: 8px;
    }

    .status-badge-outline {
        padding: 3px 8px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        border: 1px solid;
    }
    .status-badge-pending { background: rgba(255,149,0,0.05); color: #ff9500; border-color: rgba(255,149,0,0.3); }
    .status-badge-approved { background: rgba(52,199,89,0.05); color: #34c759; border-color: rgba(52,199,89,0.3); }
    .status-badge-rejected { background: rgba(255,59,48,0.05); color: #ff3b30; border-color: rgba(255,59,48,0.3); }
    .status-badge-hidden { background: rgba(142,142,147,0.05); color: #8e8e93; border-color: rgba(142,142,147,0.3); }
</style>

<div class="header-actions" style="margin-bottom: 25px;">
    <h2>Storefront Customer Reviews</h2>
    <p style="color: var(--text-muted); margin-top: 4px;">Moderate product reviews submitted by clients, filter by star ratings, and toggle visibility on the product page.</p>
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

<!-- Star Filter Controls -->
<div class="card" style="padding: 15px; margin-bottom: 20px; display:flex; gap:15px; align-items:center; flex-wrap:wrap;">
    <span style="font-size: 13px; font-weight:600; color:var(--text-muted);">Quick Filter by Rating:</span>
    <select id="reviewRatingFilter" class="form-control" style="max-width: 160px;" onchange="filterReviewsByRating(this.value)">
        <option value="all">All Star Ratings</option>
        <option value="5">⭐⭐⭐⭐⭐ (5 Stars)</option>
        <option value="4">⭐⭐⭐⭐ (4 Stars)</option>
        <option value="3">⭐⭐⭐ (3 Stars)</option>
        <option value="2">⭐⭐ (2 Stars)</option>
        <option value="1">⭐ (1 Star)</option>
    </select>
    <select id="reviewStatusFilter" class="form-control" style="max-width: 160px;" onchange="filterReviewsByStatus(this.value)">
        <option value="all">All Moderation Status</option>
        <option value="pending">Pending Approval</option>
        <option value="approved">Approved & Live</option>
        <option value="rejected">Rejected</option>
        <option value="hidden">Hidden</option>
    </select>
</div>

<div class="reviews-list">
    <?php if(empty($data['reviews'])): ?>
        <div class="card" style="text-align: center; color: var(--text-muted); padding: 50px;">
            <i class="ph ph-chat-centered-dots" style="font-size: 48px; opacity: 0.5; margin-bottom: 10px;"></i>
            <p>No customer reviews submitted yet.</p>
        </div>
    <?php else: ?>
        <?php foreach($data['reviews'] as $rev): ?>
            <div class="review-card" data-rating="<?= $rev->rating ?>" data-status="<?= $rev->status ?>">
                <div class="review-top">
                    <div class="reviewer-info">
                        <span class="reviewer-name"><?= htmlspecialchars($rev->reviewer_name) ?></span>
                        <span class="reviewer-email"><i class="ph ph-envelope"></i> <?= htmlspecialchars($rev->reviewer_email) ?></span>
                        <div class="rating-stars">
                            <?php for($i=1; $i<=5; $i++): ?>
                                <i class="ph-fill ph-star" style="color: <?= ($i <= $rev->rating) ? '#ffcc00' : 'rgba(0,0,0,0.1)' ?>;"></i>
                            <?php endfor; ?>
                        </div>
                    </div>
                    
                    <div style="text-align: right; display:flex; flex-direction:column; align-items:flex-end; gap:6px;">
                        <span class="status-badge-outline status-badge-<?= $rev->status ?>">
                            <?= $rev->status ?>
                        </span>
                        <span style="font-size:11px; color:var(--text-muted);">Product: <strong><?= htmlspecialchars($rev->item_name) ?></strong></span>
                    </div>
                </div>

                <div class="review-comment">
                    "<?= htmlspecialchars($rev->comment) ?>"
                </div>

                <div class="review-bottom">
                    <span class="review-date">Submitted on: <?= date('M d, Y h:i A', strtotime($rev->created_at)) ?></span>
                    
                    <div class="review-actions">
                        <?php if($rev->status !== 'approved'): ?>
                            <form action="<?= APP_URL ?>/ecommerce/reviews" method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="approve">
                                <input type="hidden" name="review_id" value="<?= $rev->id ?>">
                                <button type="submit" class="btn-primary" style="padding: 6px 12px; font-size:11.5px;">
                                    <i class="ph ph-check"></i> Approve & Live
                                </button>
                            </form>
                        <?php endif; ?>
                        
                        <?php if($rev->status !== 'rejected'): ?>
                            <form action="<?= APP_URL ?>/ecommerce/reviews" method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="reject">
                                <input type="hidden" name="review_id" value="<?= $rev->id ?>">
                                <button type="submit" class="btn-secondary" style="border-color: #ff3b30; color: #ff3b30; padding: 6px 12px; font-size:11.5px;">
                                    <i class="ph ph-x"></i> Reject
                                </button>
                            </form>
                        <?php endif; ?>

                        <?php if($rev->status === 'approved'): ?>
                            <form action="<?= APP_URL ?>/ecommerce/reviews" method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="hide">
                                <input type="hidden" name="review_id" value="<?= $rev->id ?>">
                                <button type="submit" class="btn-secondary" style="padding: 6px 12px; font-size:11.5px;">
                                    <i class="ph ph-eye-slash"></i> Hide Review
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
    function filterReviewsByRating(val) {
        const cards = document.querySelectorAll('.review-card');
        const statusVal = document.getElementById('reviewStatusFilter').value;

        cards.forEach(card => {
            const cardRating = card.getAttribute('data-rating');
            const cardStatus = card.getAttribute('data-status');
            
            const matchRating = (val === 'all' || cardRating === val);
            const matchStatus = (statusVal === 'all' || cardStatus === statusVal);

            if (matchRating && matchStatus) {
                card.style.display = 'flex';
            } else {
                card.style.display = 'none';
            }
        });
    }

    function filterReviewsByStatus(val) {
        const cards = document.querySelectorAll('.review-card');
        const ratingVal = document.getElementById('reviewRatingFilter').value;

        cards.forEach(card => {
            const cardRating = card.getAttribute('data-rating');
            const cardStatus = card.getAttribute('data-status');
            
            const matchRating = (ratingVal === 'all' || cardRating === ratingVal);
            const matchStatus = (val === 'all' || cardStatus === val);

            if (matchRating && matchStatus) {
                card.style.display = 'flex';
            } else {
                card.style.display = 'none';
            }
        });
    }
</script>
