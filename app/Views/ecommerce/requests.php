<?style
/* Premium style injections matching ERP macOS theme */
?>
<style>
    .status-badge {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        display: inline-block;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .status-pending { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
    .status-approved { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .status-declined { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

    .req-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
        font-size: 13px;
    }
    .req-table th {
        background: rgba(0,0,0,0.02);
        padding: 12px 15px;
        text-align: left;
        font-weight: 600;
        border-bottom: 2px solid var(--mac-border);
        color: var(--text-muted);
    }
    .req-table td {
        padding: 12px 15px;
        border-bottom: 1px solid var(--mac-border);
        vertical-align: top;
    }
    .req-table tr:hover {
        background: rgba(0,0,0,0.01);
    }
    
    /* Modal container */
    .modal-backdrop {
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0, 0, 0, 0.4);
        backdrop-filter: blur(4px);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 3000;
    }
    .modal-box {
        background: var(--mega-bg);
        border: 1px solid var(--mac-border);
        border-radius: 12px;
        width: 500px;
        box-shadow: 0 15px 50px rgba(0,0,0,0.15);
        padding: 25px;
        box-sizing: border-box;
        position: relative;
        animation: modalSlide 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    }
    @keyframes modalSlide {
        from { transform: translateY(20px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid var(--mac-border);
        padding-bottom: 12px;
        margin-bottom: 18px;
    }
    .modal-header h3 { margin: 0; font-size: 16px; font-weight: 700; }
    .close-btn { background: none; border: none; font-size: 20px; cursor: pointer; color: var(--text-muted); }
    .close-btn:hover { color: var(--text-main); }
    
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 6px; font-size: 12px; font-weight: 600; color: var(--text-muted); }
    .form-control {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid var(--mac-border);
        border-radius: 6px;
        background: transparent;
        color: var(--text-main);
        box-sizing: border-box;
        font-size: 13px;
    }
    .form-control:focus {
        border-color: #0066cc;
        outline: none;
    }
    
    .radio-group {
        display: flex;
        gap: 20px;
        margin-bottom: 15px;
        padding: 10px;
        background: rgba(0,0,0,0.02);
        border-radius: 6px;
        border: 1px solid var(--mac-border);
    }
    .radio-option {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 13px;
        cursor: pointer;
    }
    .btn-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 20px;
    }
    .btn-primary { background: #0066cc; color: #fff; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 13px; font-weight: 600; }
    .btn-primary:hover { background: #005bb5; }
    .btn-secondary { background: rgba(0,0,0,0.05); color: var(--text-main); border: 1px solid var(--mac-border); padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 13px; font-weight: 600; }
    .btn-secondary:hover { background: rgba(0,0,0,0.08); }
</style>

<div class="header-actions" style="margin-bottom: 20px;">
    <h2>Wholesaler Registration Requests</h2>
    <p style="color:#666; margin-top:0;">Manage portal access requests, check business credentials, and link verified accounts with your ERP customer ledger.</p>
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

<div class="card">
    <table class="req-table">
        <thead>
            <tr>
                <th>Business Info</th>
                <th>City & Address</th>
                <th>Contact details</th>
                <th>Requested Credentials</th>
                <th>Status</th>
                <th>Date Received</th>
                <th style="text-align: right;">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if(empty($data['requests'])): ?>
                <tr>
                    <td colspan="7" style="text-align: center; color: #aaa; padding: 30px;">No wholesaler requests received yet.</td>
                </tr>
            <?php else: ?>
                <?php foreach($data['requests'] as $req): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($req->business_name) ?></strong>
                            <?php if(!empty($req->notes)): ?>
                                <div style="font-size: 11px; color: #777; margin-top: 4px; font-style: italic;">
                                    "<?= htmlspecialchars($req->notes) ?>"
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?= htmlspecialchars($req->city) ?></strong>
                            <div style="font-size: 11px; color: #666; margin-top: 2px;">
                                <?= htmlspecialchars($req->address) ?>
                            </div>
                        </td>
                        <td>
                            <div><i class="ph ph-phone" style="vertical-align:middle;"></i> <?= htmlspecialchars($req->contact_number) ?></div>
                            <div style="font-size: 12px; color: #666; margin-top: 2px;"><i class="ph ph-envelope" style="vertical-align:middle;"></i> <?= htmlspecialchars($req->email_address) ?></div>
                        </td>
                        <td>
                            <div style="font-family: monospace; font-size: 11px; background: rgba(0,0,0,0.03); padding: 2px 6px; border-radius: 4px; display: inline-block;">
                                Username: <?= htmlspecialchars($req->username) ?>
                            </div>
                        </td>
                        <td>
                            <span class="status-badge status-<?= $req->status ?>">
                                <?= $req->status ?>
                            </span>
                        </td>
                        <td>
                            <?= date('M d, Y h:i A', strtotime($req->created_at)) ?>
                        </td>
                        <td style="text-align: right; white-space: nowrap;">
                            <?php if($req->status === 'pending'): ?>
                                <button type="button" class="btn-primary" onclick="openApproveModal(<?= htmlspecialchars(json_encode($req)) ?>)">
                                    <i class="ph ph-check" style="vertical-align: middle;"></i> Approve & Link
                                </button>
                                
                                <form action="<?= APP_URL ?>/ecommerce/requests" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to decline this request?');">
                                    <input type="hidden" name="action" value="decline">
                                    <input type="hidden" name="request_id" value="<?= $req->id ?>">
                                    <button type="submit" class="btn-secondary" style="border-color: #ff3b30; color: #ff3b30;">
                                        Decline
                                    </button>
                                </form>
                            <?php elseif($req->status === 'approved'): ?>
                                <span style="font-size: 12px; color: #2e7d32; font-weight: 500;">
                                    Linked &rarr; <a href="<?= APP_URL ?>/customer/index/<?= $req->linked_customer_id ?>" style="color: #0066cc; text-decoration: underline; font-weight: bold;"><?= htmlspecialchars($req->linked_customer_name ?? 'ERP Profile') ?></a>
                                </span>
                            <?php else: ?>
                                <span style="color: #888; font-size: 12px; font-style: italic;">Declined</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Approval Action Modal -->
<div class="modal-backdrop" id="approveModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3>Approve Wholesaler Access</h3>
            <button class="close-btn" onclick="closeModal()">&times;</button>
        </div>
        <form action="<?= APP_URL ?>/ecommerce/requests" method="POST" id="approvalForm" onsubmit="return validateApprovalForm(event)">
            <input type="hidden" name="action" value="approve">
            <input type="hidden" name="request_id" id="modalRequestId">
            
            <div class="form-group">
                <label>Verification Link Action</label>
                <div class="radio-group">
                    <label class="radio-option">
                        <input type="radio" name="link_action" value="create_new" checked onchange="toggleLinkViews()">
                        Create New ERP Customer Profile
                    </label>
                    <label class="radio-option">
                        <input type="radio" name="link_action" value="link_existing" onchange="toggleLinkViews()">
                        Link to Existing ERP Customer
                    </label>
                </div>
            </div>

            <!-- Existing Customer Selection (hidden by default) -->
            <div class="form-group" id="existingCustomerGroup" style="display: none;">
                <label>Select Existing ERP Customer Account</label>
                <input type="text" id="customerSearchInput" class="form-control" placeholder="🔍 Type name or phone to search..." style="margin-bottom: 8px; border-color: rgba(0,0,0,0.15);">
                <select name="existing_customer_id" id="existingCustomerSelect" class="form-control" size="5" style="height: 130px;">
                    <option value="">-- Choose Customer --</option>
                    <?php foreach($data['erp_customers'] as $cust): ?>
                        <option value="<?= $cust->id ?>" data-search="<?= strtolower(htmlspecialchars($cust->name . ' ' . ($cust->phone ?? ''))) ?>">
                            <?= htmlspecialchars($cust->name) ?> (<?= htmlspecialchars($cust->phone ?? 'No Phone') ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Username & Password Config -->
            <div class="form-group">
                <label>Storefront Login Username</label>
                <input type="text" name="approve_username" id="modalUsername" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label>Storefront Login Password</label>
                <div style="display: flex; gap: 8px;">
                    <input type="text" name="approve_password" id="modalPassword" class="form-control" required>
                    <button type="button" class="btn-secondary" onclick="generatePassword()" style="flex-shrink: 0; padding: 8px 12px;">Generate</button>
                </div>
            </div>

            <div class="btn-actions">
                <button type="button" class="btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn-primary">Approve & Issue Access</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openApproveModal(request) {
        document.getElementById('modalRequestId').value = request.id;
        document.getElementById('modalUsername').value = request.username;
        document.getElementById('modalPassword').value = request.password; // Pre-fill with requested password
        
        // Reset inputs
        document.querySelector('input[name="link_action"][value="create_new"]').checked = true;
        toggleLinkViews();

        document.getElementById('approveModal').style.display = 'flex';
    }

    function closeModal() {
        document.getElementById('approveModal').style.display = 'none';
    }

    function toggleLinkViews() {
        const linkAction = document.querySelector('input[name="link_action"]:checked').value;
        const selectGroup = document.getElementById('existingCustomerGroup');
        const selectElem = document.getElementById('existingCustomerSelect');
        
        if (linkAction === 'link_existing') {
            selectGroup.style.display = 'block';
            selectElem.required = true;
        } else {
            selectGroup.style.display = 'none';
            selectElem.required = false;
        }
    }

    function generatePassword() {
        const chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%&*";
        let pass = "";
        for (let i = 0; i < 10; i++) {
            pass += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        document.getElementById('modalPassword').value = pass;
    }

    function validateApprovalForm(e) {
        const linkAction = document.querySelector('input[name="link_action"]:checked').value;
        const username = document.getElementById('modalUsername').value.trim();
        const password = document.getElementById('modalPassword').value.trim();
        
        if (linkAction === 'link_existing') {
            const selectElem = document.getElementById('existingCustomerSelect');
            if (!selectElem.value) {
                alert('Please select an existing ERP customer to link.');
                e.preventDefault();
                e.stopImmediatePropagation();
                return false;
            }
        }
        
        if (username.length < 3) {
            alert('Username must be at least 3 characters long.');
            e.preventDefault();
            e.stopImmediatePropagation();
            return false;
        }
        
        if (password.length < 4) {
            alert('Password must be at least 4 characters long.');
            e.preventDefault();
            e.stopImmediatePropagation();
            return false;
        }
        
        console.log('Approval form validation passed. Submitting to: ' + document.getElementById('approvalForm').action);
        return true;
    }

    // Dynamic search filtering for existing ERP customers
    document.getElementById('customerSearchInput').addEventListener('input', function(e) {
        const query = e.target.value.toLowerCase().trim();
        const select = document.getElementById('existingCustomerSelect');
        const options = select.options;
        
        let firstMatch = null;
        for (let i = 0; i < options.length; i++) {
            const opt = options[i];
            if (opt.value === "") {
                opt.style.display = ""; // Keep placeholder visible
                continue;
            }
            const searchText = opt.getAttribute('data-search') || "";
            if (searchText.indexOf(query) !== -1) {
                opt.style.display = "";
                if (!firstMatch) firstMatch = opt;
            } else {
                opt.style.display = "none";
            }
        }
        
        // If query is empty, reset select option scroll, otherwise select first match
        if (query === "") {
            select.value = "";
        }
    });
</script>
