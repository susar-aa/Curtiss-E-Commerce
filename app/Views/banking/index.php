<?php
// Custom Alert Message Resolver
$successMsg = '';
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'bank_added': $successMsg = '🏦 New Bank Account successfully registered!'; break;
        case 'bank_edited': $successMsg = '✏️ Bank Account updated successfully!'; break;
        case 'bank_deleted': $successMsg = '🗑️ Bank Account deleted successfully!'; break;
        case 'transfer_completed': $successMsg = '💸 Inter-bank fund transfer recorded successfully!'; break;
        case 'quick_entry_completed': $successMsg = '⚡ Transaction successfully posted to the ledger!'; break;
        default: $successMsg = 'Success!'; break;
    }
}
?>
<style>
    /* Styling & Theme Variables */
    .banking-container {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        color: var(--text-dark, #333);
    }
    .header-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        flex-wrap: wrap;
        gap: 15px;
    }
    .btn-group { display: flex; gap: 10px; }
    .btn {
        padding: 10px 18px;
        font-size: 13px;
        font-weight: 600;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.2s ease;
        border: 1px solid transparent;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        text-decoration: none;
    }
    
    .btn-primary { background: #0066cc; color: #fff; }
    .btn-primary:hover { background: #0056b3; }
    
    .btn-success { background: #2e7d32; color: #fff; }
    .btn-success:hover { background: #235f26; }
    
    .btn-danger { background: #c62828; color: #fff; }
    .btn-danger:hover { background: #9a1f1f; }
    
    .btn-secondary { background: #fff; color: #475569; border: 1px solid #cbd5e1; }
    .btn-secondary:hover { background: #f8fafc; border-color: #94a3b8; }
    @media (prefers-color-scheme: dark) {
        .btn-secondary { background: #1e1e2d; color: #e2e8f0; border-color: #3f3f46; }
        .btn-secondary:hover { background: #27273a; border-color: #52525b; }
    }

    /* Section Headings */
    .section-title {
        font-size: 15px;
        font-weight: bold;
        text-transform: uppercase;
        color: #64748b;
        letter-spacing: 0.5px;
        margin: 30px 0 15px 0;
        border-bottom: 2px solid #e2e8f0;
        padding-bottom: 8px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    @media (prefers-color-scheme: dark) {
        .section-title { border-color: #27272a; color: #a1a1aa; }
    }

    /* Bank Account Cards Grid */
    .bank-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 20px;
        margin-bottom: 10px;
    }
    
    .bank-card {
        background: #fff;
        border: 1px solid var(--mac-border, #e2e8f0);
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        display: flex;
        flex-direction: column;
        position: relative;
        overflow: hidden;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .bank-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
    }
    .bank-card.temp-bank {
        border-left: 5px solid #ef6c00;
        background: rgba(239, 108, 0, 0.01);
    }
    .bank-card.real-bank {
        border-left: 5px solid #0066cc;
    }

    @media (prefers-color-scheme: dark) {
        .bank-card { background: #1e1e2d; border-color: #2d2d3d; }
    }
    
    .bank-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 10px;
    }
    
    .bank-title { font-size: 16px; font-weight: 700; color: var(--text-dark, #1e293b); }
    @media (prefers-color-scheme: dark) { .bank-title { color: #f1f5f9; } }
    
    .bank-tag {
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        padding: 3px 8px;
        border-radius: 20px;
    }
    .tag-temp { background: #fff3e0; color: #ef6c00; }
    .tag-real { background: #e0f2fe; color: #0369a1; }
    @media (prefers-color-scheme: dark) {
        .tag-temp { background: rgba(239, 108, 0, 0.15); }
        .tag-real { background: rgba(3, 105, 161, 0.15); }
    }
    
    .bank-code { font-size: 11px; color: #94a3b8; margin-bottom: 15px; }
    
    .bank-balance-wrapper {
        margin-bottom: 20px;
    }
    .balance-label { font-size: 10px; text-transform: uppercase; font-weight: bold; color: #94a3b8; }
    .balance-amount { font-size: 26px; font-weight: 800; font-family: monospace; color: #0066cc; margin-top: 2px;}
    .temp-bank .balance-amount { color: #ef6c00; }
    
    .bank-actions {
        margin-top: auto;
        display: flex;
        gap: 8px;
        border-top: 1px solid var(--mac-border, #f1f5f9);
        padding-top: 15px;
    }
    @media (prefers-color-scheme: dark) { .bank-actions { border-color: #27272a; } }
    .bank-actions .btn { flex: 1; justify-content: center; }

    /* Cash / Subsidiary Accounts Table */
    .cash-table-wrapper {
        background: #fff;
        border: 1px solid var(--mac-border, #e2e8f0);
        border-radius: 12px;
        overflow: hidden;
        margin-bottom: 30px;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
    }
    @media (prefers-color-scheme: dark) { .cash-table-wrapper { background: #1e1e2d; border-color: #2d2d3d; } }
    
    .cash-table { width: 100%; border-collapse: collapse; text-align: left; }
    .cash-table th, .cash-table td { padding: 12px 20px; border-bottom: 1px solid var(--mac-border, #e2e8f0); }
    @media (prefers-color-scheme: dark) { .cash-table th, .cash-table td { border-color: #27272a; } }
    .cash-table th { background: rgba(0,0,0,0.01); font-size: 11px; font-weight: bold; color: #64748b; text-transform: uppercase; }
    @media (prefers-color-scheme: dark) { .cash-table th { background: rgba(255,255,255,0.01); } }
    .cash-table tr:last-child td { border-bottom: none; }
    .cash-table tr:hover td { background: rgba(0,0,0,0.01); }
    @media (prefers-color-scheme: dark) { .cash-table tr:hover td { background: rgba(255,255,255,0.01); } }

    /* Modals CSS */
    .modal-backdrop {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.5);
        backdrop-filter: blur(4px);
        z-index: 2000;
        align-items: center;
        justify-content: center;
    }
    .modal-panel {
        background: #fff;
        width: 100%;
        max-width: 480px;
        border-radius: 12px;
        box-shadow: 0 20px 25px -5px rgba(0,0,0,0.15);
        border: 1px solid #e2e8f0;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        animation: modalFadeIn 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    }
    @media (prefers-color-scheme: dark) {
        .modal-panel { background: #1e1e2d; border-color: #2d2d3d; }
    }
    @keyframes modalFadeIn {
        from { transform: scale(0.95); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
    }
    .modal-header {
        padding: 15px 20px;
        background: #0066cc;
        color: #fff;
        font-weight: 700;
        font-size: 15px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .modal-body { padding: 20px; display: flex; flex-direction: column; gap: 15px; }
    .modal-body label { font-weight: 700; font-size: 11px; text-transform: uppercase; color: #64748b; margin-bottom: 2px; display: block;}
    .modal-body input, .modal-body select {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        box-sizing: border-box;
        font-size: 13px;
        background: transparent;
        color: inherit;
    }
    .modal-body input:focus, .modal-body select:focus { border-color: #0066cc; outline: none; }
    @media (prefers-color-scheme: dark) {
        .modal-body input, .modal-body select { border-color: #3f3f46; }
    }
    .modal-footer {
        padding: 15px 20px;
        background: rgba(0,0,0,0.02);
        border-top: 1px solid var(--mac-border, #e2e8f0);
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }
    @media (prefers-color-scheme: dark) {
        .modal-footer { background: rgba(255,255,255,0.01); border-color: #27272a; }
    }

    .edit-btn {
        position: absolute;
        top: 15px;
        right: 15px;
        background: transparent;
        border: none;
        cursor: pointer;
        font-size: 16px;
        color: #94a3b8;
        transition: color 0.15s;
    }
    .edit-btn:hover { color: #0066cc; }
</style>

<div class="banking-container">
    <div class="header-actions">
        <div>
            <h2 style="margin: 0 0 5px 0;">Bank & Cash Accounts Management</h2>
            <p style="margin: 0; color: #64748b; font-size: 14px;">Manage commercial bank sub-ledgers, view transaction registers, and record inter-bank transfers.</p>
        </div>
        <div class="btn-group">
            <button class="btn btn-secondary" onclick="openTransferModal()">💸 Inter-Bank Transfer</button>
            <button class="btn btn-success" onclick="openTransactionModal('deposit')">💵 Record Deposit</button>
            <button class="btn btn-danger" onclick="openTransactionModal('withdrawal')">💰 Record Withdrawal</button>
        </div>
    </div>

    <!-- Feedback Alerts -->
    <?php if(!empty($successMsg)): ?>
        <div style="padding: 12px 16px; background:#e8f5e9; color:#2e7d32; border:1px solid #c8e6c9; border-radius:8px; margin-bottom:20px; font-weight:600; font-size:14px; display:flex; align-items:center; gap:8px;">
            <span>✅</span> <?= htmlspecialchars($successMsg) ?>
        </div>
    <?php endif; ?>
    <?php if(!empty($data['error'])): ?>
        <div style="padding: 12px 16px; background:#ffebee; color:#c62828; border:1px solid #ffcdd2; border-radius:8px; margin-bottom:20px; font-weight:600; font-size:14px; display:flex; align-items:center; gap:8px;">
            <span>⚠️</span> <?= htmlspecialchars($data['error']) ?>
        </div>
    <?php endif; ?>

    <!-- Section 1: Bank Accounts -->
    <div class="section-title">
        <span>🏦 Registered Bank Accounts</span>
        <button class="btn btn-primary" onclick="openAddBankModal()" style="padding: 4px 10px; font-size: 11px;">+ Add Bank</button>
    </div>
    
    <div class="bank-grid">
        <?php if(empty($data['bank_accounts'])): ?>
            <div style="grid-column: 1/-1; text-align: center; padding: 40px; color: #94a3b8; border: 2px dashed var(--mac-border, #cbd5e1); border-radius: 12px;">
                <span style="font-size: 40px; display: block; margin-bottom: 10px;">🏦</span>
                No bank accounts registered. Click "+ Add Bank" to register a new account.
            </div>
        <?php else: foreach($data['bank_accounts'] as $acc): ?>
            <?php 
                $isTemp = $acc->account_code === '1605';
                $cardClass = $isTemp ? 'temp-bank' : 'real-bank';
                $tagText = $isTemp ? 'Clearing / Temp' : 'Bank Sub-ledger';
                $tagClass = $isTemp ? 'tag-temp' : 'tag-real';
            ?>
            <div class="bank-card <?= $cardClass ?>">
                <!-- Edit & Delete Trigger for Real Banks -->
                <?php if (!$isTemp): ?>
                    <button class="edit-btn" onclick="openEditBankModal(<?= $acc->id ?>, '<?= htmlspecialchars($acc->account_name, ENT_QUOTES) ?>')" title="Edit Bank Account">✏️</button>
                <?php endif; ?>
                
                <div class="bank-header">
                    <div class="bank-title"><?= htmlspecialchars($acc->account_name) ?></div>
                    <span class="bank-tag <?= $tagClass ?>"><?= $tagText ?></span>
                </div>
                <div class="bank-code">GL Account Code: <strong><?= htmlspecialchars($acc->account_code) ?></strong></div>
                
                <div class="bank-balance-wrapper">
                    <div class="balance-label">Current Ledger Balance</div>
                    <div class="balance-amount">Rs <?= number_format($acc->balance, 2) ?></div>
                </div>
                
                <div class="bank-actions">
                    <a href="<?= APP_URL ?>/banking/ledger/<?= $acc->id ?>" class="btn btn-secondary">🔍 Register</a>
                    <a href="<?= APP_URL ?>/banking/reconcile/<?= $acc->id ?>" class="btn btn-primary" style="background: #2e7d32; border-color: #2e7d32;">⚖️ Reconcile</a>
                </div>
            </div>
        <?php endforeach; endif; ?>
    </div>

    <!-- Section 2: Cash & Subsidiaries -->
    <div class="section-title">
        <span>💵 Cash & Clearing Asset Accounts</span>
    </div>
    
    <div class="cash-table-wrapper">
        <table class="cash-table">
            <thead>
                <tr>
                    <th>Account Code</th>
                    <th>Account Name</th>
                    <th>Account Type</th>
                    <th style="text-align: right;">Current Balance</th>
                    <th style="text-align: center; width: 150px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($data['cash_accounts'])): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; color: #94a3b8; padding: 30px;">No cash asset accounts found.</td>
                    </tr>
                <?php else: foreach($data['cash_accounts'] as $acc): ?>
                    <tr>
                        <td style="font-weight: 700; color: #0066cc;"># <?= htmlspecialchars($acc->account_code) ?></td>
                        <td style="font-weight: 600;"><?= htmlspecialchars($acc->account_name) ?></td>
                        <td><span style="font-size: 11px; text-transform: uppercase; font-weight: bold; color: #64748b;"><?= htmlspecialchars($acc->account_type) ?></span></td>
                        <td style="text-align: right; font-weight: bold; font-family: monospace; font-size: 14px;">Rs <?= number_format($acc->balance, 2) ?></td>
                        <td style="text-align: center;">
                            <a href="<?= APP_URL ?>/banking/ledger/<?= $acc->id ?>" class="btn btn-secondary" style="padding: 4px 10px; font-size: 11px;">🔍 Register</a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL 1: Add Bank Account -->
<div class="modal-backdrop" id="addBankModal">
    <div class="modal-panel">
        <div class="modal-header">
            <span>🏦 Add Bank Account</span>
            <button onclick="closeModal('addBankModal')" style="background:transparent; border:none; color:#fff; font-size:18px; cursor:pointer; font-weight:bold;">✕</button>
        </div>
        <form action="<?= APP_URL ?>/banking" method="POST">
            <input type="hidden" name="action" value="add_bank">
            <div class="modal-body">
                <div>
                    <label>Bank Name / Account Title *</label>
                    <input type="text" name="account_name" placeholder="e.g. Commercial Bank - Main Account" required>
                </div>
                <div style="font-size: 12px; color: #64748b; line-height: 1.4; padding: 10px; background: rgba(0,0,0,0.02); border-radius: 6px;">
                    ℹ️ <strong>System Note:</strong> Creating this bank account will automatically create a new general ledger asset sub-account under parent <strong>1600 - Bank Current Account</strong>.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addBankModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Bank Account</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL 2: Edit Bank Account -->
<div class="modal-backdrop" id="editBankModal">
    <div class="modal-panel">
        <div class="modal-header" style="background: #0066cc;">
            <span>✏️ Edit Bank Account</span>
            <button onclick="closeModal('editBankModal')" style="background:transparent; border:none; color:#fff; font-size:18px; cursor:pointer; font-weight:bold;">✕</button>
        </div>
        <form action="<?= APP_URL ?>/banking" method="POST">
            <input type="hidden" name="action" value="edit_bank">
            <input type="hidden" name="bank_id" id="editBankId">
            <div class="modal-body">
                <div>
                    <label>Bank Name / Account Title *</label>
                    <input type="text" name="account_name" id="editBankName" required>
                </div>
            </div>
            <div class="modal-footer" style="justify-content: space-between;">
                <!-- Option to delete bank -->
                <button type="button" class="btn btn-danger" onclick="confirmDeleteBank()" style="background: #9a1f1f;">🗑️ Delete Bank</button>
                <div style="display: flex; gap: 8px;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editBankModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </div>
        </form>
        
        <!-- Hidden delete form to bypass double submit issues -->
        <form id="deleteBankForm" action="<?= APP_URL ?>/banking" method="POST" style="display: none;">
            <input type="hidden" name="action" value="delete_bank">
            <input type="hidden" name="bank_id" id="deleteBankId">
        </form>
    </div>
</div>

<!-- MODAL 3: Inter-bank Fund Transfer -->
<div class="modal-backdrop" id="transferModal">
    <div class="modal-panel">
        <div class="modal-header" style="background: #0066cc;">
            <span>💸 Inter-Bank Fund Transfer</span>
            <button onclick="closeModal('transferModal')" style="background:transparent; border:none; color:#fff; font-size:18px; cursor:pointer; font-weight:bold;">✕</button>
        </div>
        <form action="<?= APP_URL ?>/banking" method="POST">
            <input type="hidden" name="action" value="transfer_funds">
            <div class="modal-body">
                <div>
                    <label>Transfer Date *</label>
                    <input type="date" name="entry_date" value="<?= date('Y-m-d') ?>" required>
                </div>
                
                <div>
                    <label>From Bank Account (Source - Credits account) *</label>
                    <select name="from_account_id" required>
                        <option value="">-- Select Source Bank --</option>
                        <?php foreach($data['bank_accounts'] as $acc): ?>
                            <option value="<?= $acc->id ?>"><?= htmlspecialchars($acc->account_name) ?> (Rs: <?= number_format($acc->balance, 2) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label>To Bank Account (Destination - Debits account) *</label>
                    <select name="to_account_id" required>
                        <option value="">-- Select Destination Bank --</option>
                        <?php foreach($data['bank_accounts'] as $acc): ?>
                            <option value="<?= $acc->id ?>"><?= htmlspecialchars($acc->account_name) ?> (Rs: <?= number_format($acc->balance, 2) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label>Transfer Amount (Rs:) *</label>
                    <input type="number" name="amount" step="0.01" min="0.01" placeholder="0.00" required style="font-size: 16px; font-weight:bold;">
                </div>
                
                <div>
                    <label>Memo / Reference *</label>
                    <input type="text" name="description" placeholder="e.g. Weekly Cash Liquidity Sweep" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('transferModal')">Cancel</button>
                <button type="submit" class="btn btn-primary" style="background:#0066cc;">Record Transfer</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL 4: Record Deposit / Withdrawal (Quick Entries) -->
<div class="modal-backdrop" id="transactionModal">
    <div class="modal-panel">
        <div class="modal-header" id="transModalHeader">
            <span id="transModalTitle">Record Transaction</span>
            <button onclick="closeModal('transactionModal')" style="background:transparent; border:none; color:#fff; font-size:18px; cursor:pointer; font-weight:bold;">✕</button>
        </div>
        <form action="<?= APP_URL ?>/banking" method="POST">
            <input type="hidden" name="action" value="quick_entry">
            <input type="hidden" name="type" id="transType" value="deposit">
            
            <div class="modal-body">
                <div>
                    <label>Transaction Date *</label>
                    <input type="date" name="entry_date" value="<?= date('Y-m-d') ?>" required>
                </div>

                <div>
                    <label id="transBankLabel">Bank Account *</label>
                    <select name="bank_account_id" required>
                        <?php foreach($data['bank_accounts'] as $acc): ?>
                            <option value="<?= $acc->id ?>"><?= htmlspecialchars($acc->account_name) ?> (Rs: <?= number_format($acc->balance, 2) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label id="transOffsetLabel">Ledger Double-Entry Offset Account *</label>
                    <select name="offset_account_id" required>
                        <option value="">-- Select Offset Account --</option>
                        <?php 
                        $db = new Database();
                        $db->query("SELECT * FROM chart_of_accounts ORDER BY account_type, account_name");
                        $allAccs = $db->resultSet();
                        foreach($allAccs as $acc): 
                        ?>
                            <option value="<?= $acc->id ?>"><?= $acc->account_code ?> - <?= htmlspecialchars($acc->account_name) ?> (<?= $acc->account_type ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label>Amount (Rs:) *</label>
                    <input type="number" name="amount" step="0.01" min="0.01" placeholder="0.00" required style="font-size: 16px; font-weight:bold;">
                </div>

                <div>
                    <label>Memo / Reference *</label>
                    <input type="text" name="description" placeholder="e.g. Sales Collection Offset" required>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('transactionModal')">Cancel</button>
                <button type="submit" class="btn btn-primary" id="transSubmitBtn">Save Transaction</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openModal(id) {
        document.getElementById(id).style.display = 'flex';
    }
    
    function closeModal(id) {
        document.getElementById(id).style.display = 'none';
    }
    
    function openAddBankModal() {
        openModal('addBankModal');
    }
    
    function openEditBankModal(id, name) {
        document.getElementById('editBankId').value = id;
        document.getElementById('deleteBankId').value = id;
        document.getElementById('editBankName').value = name;
        openModal('editBankModal');
    }
    
    function confirmDeleteBank() {
        if (confirm("⚠️ Are you sure you want to delete this bank account? This action will permanently remove it from your Chart of Accounts and cannot be undone.")) {
            document.getElementById('deleteBankForm').submit();
        }
    }
    
    function openTransferModal() {
        openModal('transferModal');
    }
    
    function openTransactionModal(type) {
        document.getElementById('transType').value = type;
        const header = document.getElementById('transModalHeader');
        const title = document.getElementById('transModalTitle');
        const bankLabel = document.getElementById('transBankLabel');
        const offsetLabel = document.getElementById('transOffsetLabel');
        const submitBtn = document.getElementById('transSubmitBtn');
        
        if (type === 'deposit') {
            header.style.background = '#2e7d32';
            title.innerText = 'Record Deposit';
            bankLabel.innerText = 'Bank Account (Receiving Funds) *';
            offsetLabel.innerText = 'From Account (Source of Funds / Revenue Offset) *';
            submitBtn.style.background = '#2e7d32';
            submitBtn.style.borderColor = '#2e7d32';
            submitBtn.innerText = 'Record Deposit';
        } else {
            header.style.background = '#c62828';
            title.innerText = 'Record Withdrawal';
            bankLabel.innerText = 'Bank Account (Withdrawing From) *';
            offsetLabel.innerText = 'To Account (Expense / Offset Paid) *';
            submitBtn.style.background = '#c62828';
            submitBtn.style.borderColor = '#c62828';
            submitBtn.innerText = 'Record Withdrawal';
        }
        
        openModal('transactionModal');
    }
</script>