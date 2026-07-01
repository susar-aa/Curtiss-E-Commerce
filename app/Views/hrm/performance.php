<?php
?>
<style>
    .hrm-container {
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
    @media (prefers-color-scheme: dark) {
        .btn-outline:hover {
            background: rgba(255, 255, 255, 0.04);
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
    
    .rating-star {
        color: #f59e0b;
        font-size: 14px;
    }
    .rating-star-empty {
        color: var(--text-muted);
        opacity: 0.3;
        font-size: 14px;
    }

    /* modal styling */
    .modal { 
        display: none; 
        position: fixed; 
        top: 0; 
        left: 0; 
        width: 100%; 
        height: 100%; 
        background: rgba(8, 8, 16, 0.65); 
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        z-index: 9999; 
        align-items: center; 
        justify-content: center; 
    }
    .modal-content { 
        background: var(--glass-bg); 
        border: 1px solid var(--glass-border);
        box-shadow: var(--glass-shadow);
        backdrop-filter: blur(32px); 
        -webkit-backdrop-filter: blur(32px);
        padding: 30px; 
        border-radius: 20px; 
        width: 100%;
        max-width: 600px; 
        color: var(--text-main);
        box-sizing: border-box;
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
        transition: border-color 0.2s, box-shadow 0.2s;
    }
    @media (prefers-color-scheme: dark) {
        .form-control {
            background: rgba(0, 0, 0, 0.2);
        }
    }
    .form-control:focus {
        border-color: var(--text-accent);
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15);
    }
    select.form-control option {
        background: var(--bg-color);
        color: var(--text-main);
    }
</style>

<div class="hrm-container">
    <div class="glass-card">
        <div class="header-actions">
            <div class="header-title-wrap">
                <h2><i class="ph ph-trend-up" style="color: var(--text-accent);"></i> Performance Reviews</h2>
                <p>Track employee ratings, evaluations, progress, and manager feedback.</p>
            </div>
            <div style="display: flex; gap: 10px;">
                <a href="<?= APP_URL ?>/user" class="btn btn-outline"><i class="ph ph-arrow-left"></i> Employee Directory</a>
                <button class="btn" onclick="document.getElementById('reviewModal').style.display='flex'"><i class="ph ph-plus-circle"></i> Create Review</button>
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

        <div style="overflow-x: auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Review Date</th>
                        <th>Rating</th>
                        <th>Feedback</th>
                        <th>Evaluated By</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($data['reviews'])): ?>
                    <tr><td colspan="6" style="text-align: center; color: var(--text-muted); padding: 40px;">No performance reviews found.</td></tr>
                    <?php else: foreach($data['reviews'] as $rev): ?>
                    <tr>
                        <td>
                            <strong style="color: var(--text-main);"><?= htmlspecialchars($rev->first_name . ' ' . $rev->last_name) ?></strong>
                            <div style="font-size:11px; color: var(--text-muted); margin-top: 2px;"><?= htmlspecialchars($rev->job_title) ?> (<?= htmlspecialchars($rev->department ?: 'N/A') ?>)</div>
                        </td>
                        <td><strong><?= date('d M Y', strtotime($rev->review_date)) ?></strong></td>
                        <td>
                            <div style="display: flex; gap: 2px;">
                                <?php for($i = 1; $i <= 5; $i++): ?>
                                    <?php if($i <= $rev->rating): ?>
                                        <i class="ph-fill ph-star rating-star"></i>
                                    <?php else: ?>
                                        <i class="ph ph-star rating-star-empty"></i>
                                    <?php endif; ?>
                                <?php endfor; ?>
                            </div>
                            <div style="font-size:11px; color: var(--text-muted); margin-top: 2px; font-weight:600;"><?= $rev->rating ?> / 5</div>
                        </td>
                        <td>
                            <span style="font-size: 13px; opacity: 0.95;"><?= htmlspecialchars($rev->feedback ?: '—') ?></span>
                        </td>
                        <td>
                            <span style="font-weight: 600; color: var(--text-accent);"><i class="ph ph-shield-check"></i> <?= htmlspecialchars($rev->reviewer_name) ?></span>
                        </td>
                        <td style="text-align: right;">
                            <a href="<?= APP_URL ?>/performance/delete/<?= $rev->id ?>" class="btn btn-outline" style="padding: 6px 12px; font-size: 11.5px; border-radius: 8px; border-color: rgba(239, 68, 68, 0.25); color: #ef4444 !important;" onclick="return confirm('Are you sure you want to delete this performance review?')">
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

<div class="modal" id="reviewModal">
    <div class="modal-content">
        <h3 style="margin-top:0; margin-bottom: 20px; font-size: 18px; font-weight: 700; display: flex; align-items: center; gap: 8px; color: var(--text-main);">
            <i class="ph ph-file-plus" style="color: var(--text-accent);"></i> Create Performance Review
        </h3>
        <form action="<?= APP_URL ?>/performance/create" method="POST">
            <div class="form-group">
                <label>Employee *</label>
                <select name="employee_id" class="form-control" required>
                    <option value="">Select Employee...</option>
                    <?php foreach($data['employees'] as $emp): ?>
                        <option value="<?= $emp->id ?>"><?= htmlspecialchars($emp->first_name . ' ' . $emp->last_name) ?> (<?= htmlspecialchars($emp->job_title) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Review Date *</label>
                <input type="date" name="review_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>

            <div class="form-group">
                <label>Overall Rating *</label>
                <select name="rating" class="form-control" required>
                    <option value="5">5 - Excellent (Outstanding performance)</option>
                    <option value="4">4 - Very Good (Exceeds expectations)</option>
                    <option value="3" selected>3 - Good (Meets all expectations)</option>
                    <option value="2">2 - Needs Improvement (Below average)</option>
                    <option value="1">1 - Unsatisfactory (Poor performance)</option>
                </select>
            </div>

            <div class="form-group">
                <label>Evaluation Feedback &amp; Comments</label>
                <textarea name="feedback" rows="4" class="form-control" placeholder="Provide detailed feedback on goals, strengths, and areas of growth..."></textarea>
            </div>
            
            <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--glass-border);">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('reviewModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn">Submit Review</button>
            </div>
        </form>
    </div>
</div>
