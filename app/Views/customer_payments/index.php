<style>
    /* Design Tokens & Theme integration */
    :root {
        --primary-gradient: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%);
        --success-gradient: linear-gradient(135deg, #10b981 0%, #065f46 100%);
        --warning-gradient: linear-gradient(135deg, #f59e0b 0%, #b45309 100%);
        --danger-gradient: linear-gradient(135deg, #ef4444 0%, #991b1b 100%);
        --card-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        --hover-shadow: 0 20px 40px rgba(79, 70, 229, 0.15);
    }

    /* Tabs Layout */
    .payment-center-tabs {
        display: flex;
        border-bottom: 2px solid var(--mac-border);
        margin-bottom: 25px;
        gap: 5px;
    }
    .payment-tab-btn {
        background: transparent;
        border: none;
        border-bottom: 3px solid transparent;
        padding: 12px 24px;
        font-size: 14px;
        font-weight: 600;
        color: var(--text-muted);
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .payment-tab-btn:hover {
        color: var(--text-main);
        background: rgba(0,0,0,0.02);
    }
    .payment-tab-btn.active {
        color: #4f46e5;
        border-bottom-color: #4f46e5;
    }
    @media (prefers-color-scheme: dark) {
        .payment-tab-btn:hover {
            background: rgba(255, 255, 255, 0.02);
        }
        .payment-tab-btn.active {
            color: #818cf8;
            border-bottom-color: #818cf8;
        }
    }

    /* KPI Row */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 20px;
        margin-bottom: 25px;
    }
    .stat-card {
        background: var(--mac-bg);
        border: 1px solid var(--mac-border);
        border-radius: 16px;
        padding: 20px;
        box-shadow: var(--card-shadow);
        position: relative;
        overflow: hidden;
        transition: all 0.3s;
    }
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 15px 35px rgba(0,0,0,0.08);
    }
    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 4px;
        height: 100%;
    }
    .stat-card.receivable::before { background: #4f46e5; }
    .stat-card.general::before { background: #10b981; }

    .stat-title {
        font-size: 11px;
        text-transform: uppercase;
        font-weight: 700;
        color: var(--text-muted);
        letter-spacing: 1px;
        margin-bottom: 8px;
    }
    .stat-val {
        font-size: 26px;
        font-weight: 800;
        color: var(--text-main);
        font-family: monospace;
    }

    /* Split Panes */
    .pane-row {
        display: grid;
        grid-template-columns: 1fr;
        gap: 25px;
    }
    @media (min-width: 1024px) {
        .pane-row {
            grid-template-columns: 350px 1fr;
        }
    }

    .pane-sidebar {
        background: var(--mac-bg);
        border: 1px solid var(--mac-border);
        border-radius: 16px;
        padding: 20px;
        box-shadow: var(--card-shadow);
        display: flex;
        flex-direction: column;
        gap: 15px;
        max-height: 75vh;
        overflow-y: auto;
    }
    .pane-main {
        background: var(--mac-bg);
        border: 1px solid var(--mac-border);
        border-radius: 16px;
        padding: 24px;
        box-shadow: var(--card-shadow);
    }

    /* Entity selector item */
    .entity-item {
        padding: 12px 16px;
        border-radius: 10px;
        border: 1px solid var(--mac-border);
        cursor: pointer;
        transition: all 0.2s;
        background: rgba(0,0,0,0.01);
    }
    .entity-item:hover {
        background: rgba(79, 70, 229, 0.05);
        border-color: rgba(79, 70, 229, 0.2);
    }
    .entity-item.active {
        background: rgba(79, 70, 229, 0.1);
        border-color: #4f46e5;
    }
    .entity-title {
        font-weight: 600;
        font-size: 13px;
        color: var(--text-main);
        margin-bottom: 4px;
    }
    .entity-desc {
        font-size: 11px;
        color: var(--text-muted);
        display: flex;
        justify-content: space-between;
    }

    /* Forms and allocation grid */
    .allocation-box {
        border: 1px solid var(--mac-border);
        border-radius: 12px;
        padding: 20px;
        background: rgba(0,0,0,0.01);
        margin-top: 15px;
    }
    .allocations-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
        font-size: 12px;
    }
    .allocations-table th, .allocations-table td {
        padding: 8px 10px;
        border-bottom: 1px solid var(--mac-border);
        text-align: left;
    }
    .allocations-table th {
        font-weight: 600;
        color: var(--text-muted);
        text-transform: uppercase;
        font-size: 10px;
    }

    /* Reversal badge */
    .status-badge {
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
    }
    .status-Active { background: #d1fae5; color: #065f46; }
    .status-Reversed { background: #fee2e2; color: #991b1b; }

    /* Action Buttons */
    .btn-action-small {
        padding: 5px 10px;
        font-size: 11px;
        font-weight: 600;
        border-radius: 6px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        border: 1px solid var(--mac-border);
        background: var(--mac-bg);
        color: var(--text-main);
        transition: all 0.2s;
        text-decoration: none;
    }
    .btn-action-small:hover {
        background: rgba(0,0,0,0.05);
    }
    .btn-action-small.btn-danger {
        border-color: #f87171;
        color: #ef4444;
    }
    .btn-action-small.btn-danger:hover {
        background: #fef2f2;
    }

    /* Hide helper */
    .hidden {
        display: none !important;
    }
</style>

<div style="margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h2 style="margin: 0 0 5px 0; font-weight: 800; color: var(--text-main);">Customer Payments Center</h2>
        <p style="margin: 0; color: var(--text-muted); font-size: 14px;">Audit-ready accounts receivable (AR) billing collections, credit applications, and ledger adjustments.</p>
    </div>
</div>

<!-- Notifications -->
<?php if (!empty($data['error'])): ?>
    <div style="padding: 12px 15px; background: #fee2e2; color: #991b1b; border-radius: 8px; border: 1px solid #fca5a5; margin-bottom: 20px; font-size: 13px; font-weight: 500;">
        ⚠ <?= $data['error'] ?>
    </div>
<?php endif; ?>
<?php if (!empty($data['success'])): ?>
    <div style="padding: 12px 15px; background: #d1fae5; color: #065f46; border-radius: 8px; border: 1px solid #6ee7b7; margin-bottom: 20px; font-size: 13px; font-weight: 500;">
        ✓ <?= $data['success'] ?>
    </div>
<?php endif; ?>

<!-- Stats Row -->
<div class="stats-grid">
    <div class="stat-card receivable">
        <div class="stat-title">Total Accounts Receivable (Outstanding)</div>
        <div class="stat-val">
            Rs <?= number_format(array_sum(array_column($data['customers'], 'outstanding_balance')), 2) ?>
        </div>
    </div>
    <div class="stat-card general">
        <div class="stat-title">GL Cash & Bank Accounts</div>
        <div class="stat-val" style="font-size: 13px; font-family: inherit; font-weight: 600; line-height: 1.5; margin-top: 10px;">
            <?php foreach (array_slice($data['assets'], 0, 3) as $asset): ?>
                • <?= htmlspecialchars($asset->account_name) ?>: <strong>Rs <?= number_format($asset->balance, 2) ?></strong><br>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Tabs -->
<div class="payment-center-tabs">
    <button class="payment-tab-btn active" id="tab-ar-btn" onclick="switchMainTab('ar')">
        💵 Record Customer Payment
    </button>
    <button class="payment-tab-btn" id="tab-history-btn" onclick="switchMainTab('history')">
        📜 Collections History & Reversals
    </button>
</div>

<!-- ========================================== -->
<!-- TAB PANEL: RECORD CUSTOMER PAYMENT         -->
<!-- ========================================== -->
<div id="tab-ar" class="payment-tab-panel">
    <div class="pane-row">
        <!-- Sidebar: Customers List -->
        <div class="pane-sidebar">
            <h3 style="margin: 0 0 10px 0; font-size: 14px; font-weight: 700;">Select Customer</h3>
            <input type="text" id="cust-search" class="form-control" placeholder="Search customer..." onkeyup="filterEntities('cust')">
            <div id="cust-list" style="display: flex; flex-direction: column; gap: 8px; margin-top: 8px;">
                <?php foreach ($data['customers'] as $cust): ?>
                    <div class="entity-item cust-item-el" data-id="<?= $cust->id ?>" data-name="<?= htmlspecialchars($cust->name) ?>" onclick="selectCustomer(<?= $cust->id ?>, '<?= htmlspecialchars($cust->name) ?>', <?= $cust->outstanding_balance ?>)">
                        <div class="entity-title"><?= htmlspecialchars($cust->name) ?></div>
                        <div class="entity-desc">
                            <span>Outstanding: <strong>Rs <?= number_format($cust->outstanding_balance, 2) ?></strong></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Main Workspace: Payment allocation -->
        <div class="pane-main">
            <div id="ar-welcome" style="text-align: center; padding: 60px 20px; color: var(--text-muted);">
                <i class="ph ph-hand-coins" style="font-size: 64px; color: #4f46e5; opacity: 0.6; margin-bottom: 15px;"></i>
                <h3>Select a customer from the left sidebar to record a payment.</h3>
                <p style="font-size: 13px; max-width: 450px; margin: 10px auto 0 auto;">Dedicated, audit-ready AR module allows auto-clearing invoices (FIFO), manual line-by-line allocation, and advance payments.</p>
            </div>

            <div id="ar-workspace" class="hidden">
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--mac-border); padding-bottom: 15px; margin-bottom: 20px;">
                    <div>
                        <h3 id="selected-cust-name" style="margin: 0; font-size: 18px; font-weight: 700; color: var(--text-main);">Customer</h3>
                        <p style="margin: 5px 0 0 0; font-size: 13px; color: var(--text-muted);">
                            Outstanding Balance: <strong id="selected-cust-bal" style="color:#ef4444; font-family:monospace;">Rs 0.00</strong>
                        </p>
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <a href="#" id="selected-cust-statement-btn" target="_blank" class="btn-action-small">
                            📄 Print Statement
                        </a>
                        <a href="#" id="selected-cust-apply-credit" class="btn-action-small" style="border-color:#10b981; color:#059669; display: none;">
                            💵 Apply Credit Balance
                        </a>
                    </div>
                </div>

                <form action="<?= APP_URL ?>/customerpayment/recordCustomerPayment" method="POST" id="customerPayForm">
                    <input type="hidden" name="customer_id" id="form-cust-id">
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 15px;">
                        <div class="form-group">
                            <label>Payment Amount (Rs:) *</label>
                            <input type="number" step="0.01" name="amount" id="cust-amount-input" class="form-control" style="font-size: 16px; font-weight: 700; color: #10b981;" required min="0.01" oninput="updateAllocationTotals()">
                        </div>
                        <div class="form-group">
                            <label>Payment Date *</label>
                            <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 15px;">
                        <div class="form-group">
                            <label>Payment Method *</label>
                            <select name="payment_method" id="cust-method-select" class="form-control" onchange="toggleChequeFields('cust')" required>
                                <option value="Cash">Cash</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="Cheque">Cheque (PDC)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Receipt / Reference #</label>
                            <input type="text" name="reference" class="form-control" placeholder="Optional reference">
                        </div>
                    </div>

                    <!-- Cheque Details Container -->
                    <div id="cust-cheque-details" class="cheque-details-box" style="margin-bottom: 15px;">
                        <h4 style="margin: 0 0 10px 0; font-size: 11px; text-transform: uppercase; color: #b45309; font-weight: 700;">Cheque & PDC Information</h4>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px;">
                            <div class="form-group">
                                <label>Bank Name *</label>
                                <input type="text" name="cheque_bank" id="cust-chk-bank-input" class="form-control" placeholder="e.g. Commercial Bank">
                            </div>
                            <div class="form-group">
                                <label>Cheque Number *</label>
                                <input type="text" name="cheque_number" id="cust-chk-num-input" class="form-control" placeholder="e.g. 123456">
                            </div>
                            <div class="form-group">
                                <label>Banking Date *</label>
                                <input type="date" name="cheque_date" id="cust-chk-date-input" class="form-control">
                            </div>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 15px;">
                        <div class="form-group">
                            <label>Debit Ledger Account (Cash/Bank) *</label>
                            <select name="asset_account_id" id="cust-asset-account" class="form-control" required style="background: var(--mac-bg);">
                                <?php foreach ($data['assets'] as $asset): ?>
                                    <option value="<?= $asset->id ?>" data-code="<?= $asset->account_code ?>" <?= $asset->account_code === '1000' ? 'selected' : '' ?>>
                                        <?= $asset->account_code ?> - <?= htmlspecialchars($asset->account_name) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Credit Ledger Account (Accounts Receivable) *</label>
                            <select name="ar_account_id" class="form-control" required style="background: var(--mac-bg);">
                                <option value="<?= $data['ar_account'] ? $data['ar_account']->id : 11 ?>">
                                    <?= $data['ar_account'] ? $data['ar_account']->account_code . ' - ' . htmlspecialchars($data['ar_account']->account_name) : '1200 - Accounts Receivable' ?>
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Allocation Selection -->
                    <div class="allocation-box">
                        <h4 style="margin: 0 0 10px 0; font-size: 13px; font-weight: 700; color: var(--text-main);">Invoice Allocation Workflow</h4>
                        <div class="form-group">
                            <label>Allocation Strategy</label>
                            <select name="allocation_type" id="cust-allocation-type" class="form-control" onchange="toggleAllocationGrid('cust')" style="background: var(--mac-bg);">
                                <option value="auto">Auto-Clear Oldest Document (FIFO)</option>
                                <option value="manual">Manual Invoice Allocation (Line-by-line)</option>
                                <option value="advance">Advance / Unallocated Payment</option>
                            </select>
                        </div>

                        <!-- Manual Grid -->
                        <div id="cust-manual-grid" class="hidden" style="margin-top: 15px;">
                            <div style="font-size: 11px; color: var(--text-muted); margin-bottom: 10px; font-weight: 600; text-transform: uppercase;">
                                Unpaid & Partially Paid Invoices
                            </div>
                            <div style="max-height: 250px; overflow-y: auto; border: 1px solid var(--mac-border); border-radius: 8px;">
                                <table class="allocations-table">
                                    <thead>
                                        <tr>
                                            <th>Invoice #</th>
                                            <th>Date</th>
                                            <th style="text-align: right;">Total Amount</th>
                                            <th style="text-align: right;">Balance Due</th>
                                            <th style="width: 120px; text-align: right;">Allocation</th>
                                        </tr>
                                    </thead>
                                    <tbody id="cust-invoices-tbody">
                                        <!-- Injected via JS -->
                                    </tbody>
                                </table>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 12px; font-size: 13px; font-weight: 600;">
                                <span>Unallocated Amount: <span id="cust-unallocated-lbl" style="color: #ef4444; font-family: monospace;">Rs 0.00</span></span>
                                <span>Total Allocated: <span id="cust-allocated-lbl" style="color: #10b981; font-family: monospace;">Rs 0.00</span></span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group" style="margin-top: 15px;">
                        <label>Notes / Memo</label>
                        <textarea name="notes" rows="2" class="form-control" placeholder="Internal audit logs memo..."></textarea>
                    </div>

                    <div style="margin-top: 20px;">
                        <button type="submit" class="btn-submit" style="background: var(--primary-gradient); font-size: 14px; font-weight: 700; height: 44px; border:none; border-radius:8px; color:white; width:100%; cursor:pointer;">
                            <i class="ph ph-shield-check"></i> Post & Allocate Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- TAB PANEL: COLLECTIONS HISTORY & REVERSALS -->
<!-- ========================================== -->
<div id="tab-history" class="payment-tab-panel hidden">
    <div class="pane-main" style="width: 100%; box-sizing: border-box;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0; font-size: 16px; font-weight: 700;">GL Audit-Trail Collections History</h3>
            <div style="display: flex; gap: 10px;">
                <input type="text" id="history-search" class="form-control" style="width: 250px;" placeholder="Search history..." onkeyup="filterHistory()">
            </div>
        </div>

        <div style="overflow-x: auto;">
            <table class="data-table" id="history-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Customer</th>
                        <th>Method</th>
                        <th>Reference</th>
                        <th style="text-align: right;">Amount</th>
                        <th>Status</th>
                        <th>Logged By</th>
                        <th style="text-align: center; width: 180px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data['payments_history'])): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; color: var(--text-muted); padding: 30px;">No collection logs found in current audit timeline.</td>
                        </tr>
                    <?php else: foreach ($data['payments_history'] as $ph): ?>
                        <tr class="history-row-el">
                            <td style="white-space: nowrap; font-weight: 500;"><?= date('Y-m-d', strtotime($ph->payment_date)) ?></td>
                            <td class="history-name-col" style="font-weight: 600;"><?= htmlspecialchars($ph->counterparty_name) ?></td>
                            <td><span class="badge-method method-<?= str_replace(' ', '', $ph->payment_method) ?>"><?= $ph->payment_method ?></span></td>
                            <td class="history-ref-col"><?= htmlspecialchars($ph->reference ?: '-') ?></td>
                            <td style="text-align: right; font-weight: 700; font-family: monospace; color: <?= $ph->status === 'Reversed' ? '#991b1b' : 'inherit' ?>">
                                Rs <?= number_format($ph->amount, 2) ?>
                            </td>
                            <td>
                                <span class="status-badge status-<?= $ph->status ?>"><?= $ph->status ?></span>
                            </td>
                            <td style="font-size: 11px; color: var(--text-muted);"><?= htmlspecialchars($ph->creator_name ?: 'System') ?></td>
                            <td style="text-align: center; white-space: nowrap;">
                                <a href="<?= APP_URL ?>/customerpayment/receipt/<?= $ph->id ?>" target="_blank" class="btn-action-small">
                                    🖨 Receipt
                                </a>
                                <?php if ($ph->status === 'Active'): ?>
                                    <button onclick="triggerReversal(<?= $ph->id ?>, <?= $ph->amount ?>)" class="btn-action-small btn-danger" style="background:transparent; border:1px solid #f87171; color:#ef4444; border-radius:6px; padding:4px 8px; cursor:pointer;">
                                        ↩ Reverse
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    // Global states
    let activeCustomerInvoices = [];

    // Switch main tabs
    function switchMainTab(tab) {
        document.querySelectorAll('.payment-tab-panel').forEach(panel => panel.classList.add('hidden'));
        document.querySelectorAll('.payment-tab-btn').forEach(btn => btn.classList.remove('active'));

        document.getElementById('tab-' + tab).classList.remove('hidden');
        document.getElementById('tab-' + tab + '-btn').classList.add('active');
    }

    // Filter sidebar list helper
    function filterEntities(prefix) {
        const query = document.getElementById(prefix + '-search').value.toLowerCase();
        const items = document.querySelectorAll('.' + prefix + '-item-el');
        items.forEach(item => {
            const name = item.getAttribute('data-name').toLowerCase();
            if (name.includes(query)) {
                item.classList.remove('hidden');
            } else {
                item.classList.add('hidden');
            }
        });
    }

    // Filter history
    function filterHistory() {
        const query = document.getElementById('history-search').value.toLowerCase();
        const rows = document.querySelectorAll('.history-row-el');
        rows.forEach(row => {
            const name = row.querySelector('.history-name-col').textContent.toLowerCase();
            const ref = row.querySelector('.history-ref-col').textContent.toLowerCase();
            if (name.includes(query) || ref.includes(query)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    // Toggle cheque details
    function toggleChequeFields(prefix) {
        const method = document.getElementById(prefix + '-method-select').value;
        const cqBox = document.getElementById(prefix + '-cheque-details');
        const bankInput = document.getElementById(prefix + '-chk-bank-input');
        const numInput = document.getElementById(prefix + '-chk-num-input');
        const dateInput = document.getElementById(prefix + '-chk-date-input');

        if (method === 'Cheque') {
            cqBox.classList.remove('hidden');
            bankInput.required = true;
            numInput.required = true;
            dateInput.required = true;
        } else {
            cqBox.classList.add('hidden');
            bankInput.required = false;
            numInput.required = false;
            dateInput.required = false;
        }

        // Automatic Account Selection based on Method
        const assetSelect = document.getElementById(prefix + '-asset-account');
        if (assetSelect) {
            let targetCode = '1000'; // Default Cash
            if (method === 'Bank Transfer') {
                targetCode = '1600'; // Bank account
            } else if (method === 'Cheque') {
                targetCode = '1010'; // Cheque in Hand account
            }

            for (let i = 0; i < assetSelect.options.length; i++) {
                if (assetSelect.options[i].getAttribute('data-code') === targetCode) {
                    assetSelect.selectedIndex = i;
                    break;
                }
            }
        }
    }

    // Select Customer
    function selectCustomer(id, name, balance) {
        // Toggle Active highlight
        document.querySelectorAll('.cust-item-el').forEach(el => el.classList.remove('active'));
        const activeItem = document.querySelector(`.cust-item-el[data-id="${id}"]`);
        if (activeItem) activeItem.classList.add('active');

        // Setup form fields
        document.getElementById('form-cust-id').value = id;
        document.getElementById('selected-cust-name').innerText = name;
        document.getElementById('selected-cust-bal').innerText = 'Rs ' + parseFloat(balance).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        
        // Setup statement link
        document.getElementById('selected-cust-statement-btn').href = '<?= APP_URL ?>/customerpayment/statement/' + id;
        
        // Settle Invoices with credit link
        const creditBtn = document.getElementById('selected-cust-apply-credit');
        creditBtn.href = '<?= APP_URL ?>/customerpayment/applyCredit/' + id;
        creditBtn.style.display = balance > 0 ? 'inline-flex' : 'none';

        // Load unpaid invoices via AJAX
        document.getElementById('cust-invoices-tbody').innerHTML = '<tr><td colspan="5" style="text-align:center;">Loading unpaid invoices...</td></tr>';
        
        fetch('<?= APP_URL ?>/customerpayment/getCustomerInvoicesJson/' + id)
            .then(res => res.json())
            .then(data => {
                activeCustomerInvoices = data;
                renderCustomerInvoicesTable();
                
                // Show Workspace
                document.getElementById('ar-welcome').classList.add('hidden');
                document.getElementById('ar-workspace').classList.remove('hidden');
                
                // Reset Form
                document.getElementById('cust-amount-input').value = '';
                document.getElementById('cust-allocation-type').value = 'auto';
                toggleAllocationGrid('cust');
                updateAllocationTotals();
            })
            .catch(err => {
                console.error(err);
                document.getElementById('cust-invoices-tbody').innerHTML = '<tr><td colspan="5" style="text-align:center; color:red;">Failed to load invoices.</td></tr>';
            });
    }

    // Render customer invoices grid
    function renderCustomerInvoicesTable() {
        const tbody = document.getElementById('cust-invoices-tbody');
        tbody.innerHTML = '';
        if (activeCustomerInvoices.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; color:var(--text-muted);">No unpaid invoices for this customer.</td></tr>';
            return;
        }

        activeCustomerInvoices.forEach(inv => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td><strong>${inv.invoice_number}</strong></td>
                <td>${inv.invoice_date}</td>
                <td style="text-align:right;">Rs ${parseFloat(inv.total_amount).toFixed(2)}</td>
                <td style="text-align:right; font-weight:600; color:#ef4444;">Rs ${parseFloat(inv.balance_due).toFixed(2)}</td>
                <td style="text-align:right;">
                    <input type="number" step="0.01" class="form-control cust-alloc-input" 
                           style="width: 100px; text-align: right; padding: 4px 8px; border: 1px solid var(--mac-border); border-radius: 6px; background: transparent; color: var(--text-main);" 
                           name="allocations[${inv.id}]" 
                           data-max="${inv.balance_due}" 
                           placeholder="0.00" 
                           oninput="validateAndAllocate('cust', this)">
                </td>
            `;
            tbody.appendChild(tr);
        });
    }

    // Toggle allocation strategy grid visibility
    function toggleAllocationGrid(prefix) {
        const type = document.getElementById(prefix + '-allocation-type').value;
        const grid = document.getElementById(prefix + '-manual-grid');
        
        if (type === 'manual') {
            grid.classList.remove('hidden');
        } else {
            grid.classList.add('hidden');
        }
        updateAllocationTotals();
    }

    // Validate allocation input and recalculate
    function validateAndAllocate(prefix, input) {
        const max = parseFloat(input.getAttribute('data-max'));
        let val = parseFloat(input.value) || 0;
        
        if (val < 0) {
            input.value = '0.00';
            val = 0;
        }
        if (val > max) {
            input.value = max.toFixed(2);
            val = max;
        }

        updateAllocationTotals();
    }

    // Update running allocated / unallocated totals
    function updateAllocationTotals() {
        const custTotalPay = parseFloat(document.getElementById('cust-amount-input').value) || 0;
        const custAllocType = document.getElementById('cust-allocation-type').value;
        let custAllocated = 0;

        if (custAllocType === 'manual') {
            document.querySelectorAll('.cust-alloc-input').forEach(input => {
                custAllocated += parseFloat(input.value) || 0;
            });
        }

        const custUnallocated = Math.max(0, custTotalPay - custAllocated);
        document.getElementById('cust-allocated-lbl').innerText = 'Rs ' + custAllocated.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2});
        document.getElementById('cust-unallocated-lbl').innerText = 'Rs ' + custUnallocated.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2});
    }

    // Trigger reversal action
    function triggerReversal(id, amount) {
        const msg = `Are you absolutely sure you want to REVERSE this customer payment of Rs ${parseFloat(amount).toLocaleString()}?\n\nThis will restore the unpaid balance of the allocated invoices, bounce associated cheques, and create reversing journal entries in the General Ledger.`;
        if (confirm(msg)) {
            window.location.href = `<?= APP_URL ?>/customerpayment/reverseCustomerPayment/${id}`;
        }
    }

    // Initial page load bindings
    document.addEventListener('DOMContentLoaded', () => {
        toggleChequeFields('cust');
    });
</script>
