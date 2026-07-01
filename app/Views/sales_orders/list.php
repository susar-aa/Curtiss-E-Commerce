<!-- Sales Orders Management -->
<div class="mac-container" style="padding: 20px;">
    
    <!-- Top Action Bar -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; gap: 15px; flex-wrap: wrap;">
        <div>
            <h1 style="margin: 0; font-size: 24px; font-weight: 700; color: #111; display: flex; align-items: center; gap: 8px;">
                <i class="ph-fill ph-book-bookmark" style="color: #0066cc;"></i> Sales Order Center
            </h1>
            <p style="margin: 5px 0 0 0; font-size: 13px; color: #666;">Manage sales bookings, convert approved orders to finalized invoices, and view route bookings.</p>
        </div>
        
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <a href="<?= APP_URL ?>/sales/deleted_list" class="btn btn-outline" style="border-color: #ef4444; color: #ef4444; background: transparent; display: inline-flex; align-items: center; gap: 6px; font-size: 13px;">
                <i class="ph-bold ph-trash"></i> Deleted Invoices Log
            </a>
            <a href="<?= APP_URL ?>/sales" class="btn btn-outline" style="display: inline-flex; align-items: center; gap: 6px; font-size: 13px;">
                <i class="ph-bold ph-receipt"></i> Go to Invoices List
            </a>
            <a href="<?= APP_URL ?>/sales/create?type=sales_order" class="btn" style="background: #0066cc; color: #fff; display: inline-flex; align-items: center; gap: 6px; font-size: 13px;">
                <i class="ph-bold ph-plus-circle"></i> + Create Sales Order
            </a>
        </div>
    </div>

    <!-- Alert Notifications -->
    <?php if (isset($_SESSION['flash_success'])): ?>
        <div style="padding: 12px 16px; background:#e8f5e9; color:#2e7d32; border-radius:8px; margin-bottom:20px; font-weight:500; display:flex; align-items:center; gap:8px;">
            <i class="ph-bold ph-check-circle" style="font-size: 18px;"></i>
            <?= $_SESSION['flash_success']; unset($_SESSION['flash_success']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['flash_error'])): ?>
        <div style="padding: 12px 16px; background:#ffebee; color:#c62828; border-radius:8px; margin-bottom:20px; font-weight:500; display:flex; align-items:center; gap:8px;">
            <i class="ph-bold ph-warning-circle" style="font-size: 18px;"></i>
            <?= $_SESSION['flash_error']; unset($_SESSION['flash_error']); ?>
        </div>
    <?php endif; ?>

    <!-- Search & Filters Card -->
    <div style="background: #fff; border: 1px solid var(--mac-border, #e0e0e0); border-radius: 12px; padding: 20px; margin-bottom: 25px; box-shadow: 0 4px 12px rgba(0,0,0,0.02);">
        <form method="GET" action="<?= APP_URL ?>/salesorder" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; align-items: end;">
            
            <!-- Search Text -->
            <div>
                <label style="display: block; font-size: 12px; font-weight: 600; color: #555; margin-bottom: 6px;">Search Order / Customer</label>
                <div style="position: relative;">
                    <i class="ph ph-magnifying-glass" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #888;"></i>
                    <input type="text" name="search" value="<?= htmlspecialchars($data['search'] ?? '') ?>" placeholder="SO No, customer name..." style="width: 100%; padding: 8px 10px 8px 32px; border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box; font-size: 13px;">
                </div>
            </div>

            <!-- Customer Filter -->
            <div>
                <label style="display: block; font-size: 12px; font-weight: 600; color: #555; margin-bottom: 6px;">Customer</label>
                <select name="customer_id" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box; font-size: 13px; background-color: #fff;">
                    <option value="0">-- All Customers --</option>
                    <?php foreach ($data['customers'] as $c): ?>
                        <option value="<?= $c->id ?>" <?= (isset($data['customer_id']) && $data['customer_id'] == $c->id) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c->name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Date Range: Start -->
            <div>
                <label style="display: block; font-size: 12px; font-weight: 600; color: #555; margin-bottom: 6px;">Start Date</label>
                <input type="date" name="start_date" value="<?= htmlspecialchars($data['start_date'] ?? '') ?>" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box; font-size: 13px;">
            </div>

            <!-- Date Range: End -->
            <div>
                <label style="display: block; font-size: 12px; font-weight: 600; color: #555; margin-bottom: 6px;">End Date</label>
                <input type="date" name="end_date" value="<?= htmlspecialchars($data['end_date'] ?? '') ?>" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box; font-size: 13px;">
            </div>

            <!-- Status -->
            <div>
                <label style="display: block; font-size: 12px; font-weight: 600; color: #555; margin-bottom: 6px;">Order Status</label>
                <select name="status" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box; font-size: 13px; background-color: #fff;">
                    <option value="">-- All Statuses --</option>
                    <option value="Pending" <?= (isset($data['status']) && $data['status'] === 'Pending') ? 'selected' : '' ?>>Pending</option>
                    <option value="Transferred" <?= (isset($data['status']) && $data['status'] === 'Transferred') ? 'selected' : '' ?>>Transferred (Invoiced)</option>
                    <option value="Voided" <?= (isset($data['status']) && $data['status'] === 'Voided') ? 'selected' : '' ?>>Voided</option>
                </select>
            </div>

            <!-- Source Type -->
            <div>
                <label style="display: block; font-size: 12px; font-weight: 600; color: #555; margin-bottom: 6px;">Channel / Source</label>
                <select name="source_type" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box; font-size: 13px; background-color: #fff;">
                    <option value="">-- All Channels --</option>
                    <option value="standard" <?= (isset($data['source_type']) && $data['source_type'] === 'standard') ? 'selected' : '' ?>>Standard Sales Orders</option>
                    <option value="route" <?= (isset($data['source_type']) && $data['source_type'] === 'route') ? 'selected' : '' ?>>Route Rep Bookings</option>
                </select>
            </div>

            <!-- Sales Rep Filter -->
            <div>
                <label style="display: block; font-size: 12px; font-weight: 600; color: #555; margin-bottom: 6px;">Sales Rep</label>
                <select name="rep_name" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box; font-size: 13px; background-color: #fff;">
                    <option value="">-- All Reps --</option>
                    <?php foreach ($data['sales_reps'] as $rep): ?>
                        <option value="<?= htmlspecialchars($rep->name) ?>" <?= (isset($data['rep_name']) && $data['rep_name'] === $rep->name) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($rep->name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Action Buttons -->
            <div style="display: flex; gap: 8px;">
                <button type="submit" class="btn" style="flex: 1; justify-content: center; height: 35px; font-size: 13px; background: #0066cc; color:#fff; border:none; border-radius:6px; cursor:pointer;">
                    <i class="ph ph-funnel"></i> Filter
                </button>
                <a href="<?= APP_URL ?>/salesorder" class="btn btn-outline" style="flex: 1; text-align: center; height: 35px; line-height: 33px; font-size: 13px; border:1px solid #ccc; color:#555; box-sizing:border-box; text-decoration:none;">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <form id="bulkForm" method="POST" action="<?= APP_URL ?>/salesorder/bulk_action" onsubmit="return handleBulkSubmit(event);">
        <!-- Bulk Action Bar -->
        <div id="bulkActionBar" style="display: none; background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); border: 1px solid rgba(0, 102, 204, 0.2); border-radius: 12px; padding: 15px 20px; margin-bottom: 20px; box-shadow: 0 8px 32px rgba(0,0,0,0.05); align-items: center; justify-content: space-between; gap: 15px; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <span style="background: #0066cc; color: #fff; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;" id="selectedCountBadge">0 selected</span>
                <span style="font-size: 13px; font-weight: 500; color: #333;">Bulk Actions:</span>
            </div>
            
            <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap; flex-grow: 1; justify-content: flex-end;">
                <!-- Action Dropdown -->
                <select name="bulk_action" id="bulkActionSelect" onchange="toggleBulkActionInputs()" style="padding: 8px 12px; border: 1px solid #ccc; border-radius: 6px; font-size: 13px; background-color: #fff; min-width: 180px;" required>
                    <option value="">-- Select Action --</option>
                    <option value="change_date">Change Transaction Date</option>
                    <option value="change_rep">Change Assigned Representative</option>
                    <option value="delete">Delete Selected</option>
                </select>

                <!-- Date Input -->
                <div id="bulkDateInputContainer" style="display: none;">
                    <input type="date" name="bulk_date" style="padding: 8px 10px; border: 1px solid #ccc; border-radius: 6px; font-size: 13px;">
                </div>

                <!-- Rep Dropdown -->
                <div id="bulkRepInputContainer" style="display: none;">
                    <select name="bulk_rep" style="padding: 8px 12px; border: 1px solid #ccc; border-radius: 6px; font-size: 13px; background-color: #fff;">
                        <option value="">-- Select Representative --</option>
                        <?php if (!empty($data['sales_reps'])): ?>
                            <?php foreach ($data['sales_reps'] as $rep): ?>
                                <option value="<?= htmlspecialchars($rep->name) ?>"><?= htmlspecialchars($rep->name) ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <!-- Password Input (for Delete validation) -->
                <div id="bulkPasswordInputContainer" style="display: none; align-items: center; gap: 8px;">
                    <input type="password" name="admin_password" placeholder="Enter Admin Password" style="padding: 8px 10px; border: 1px solid #ef4444; border-radius: 6px; font-size: 13px;" autocomplete="new-password">
                </div>

                <!-- Apply Button -->
                <button type="submit" class="btn" style="background: #0066cc; color: #fff; padding: 8px 16px; font-size: 13px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="ph ph-check-square"></i> Apply
                </button>
            </div>
        </div>

        <!-- Data Table Container -->
        <div style="background: #fff; border: 1px solid var(--mac-border, #e0e0e0); border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.03); margin-bottom: 20px;">
            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead>
                    <tr style="background: #f5f5f7; border-bottom: 1px solid var(--mac-border, #e0e0e0);">
                        <th style="padding: 14px 18px; font-size: 12px; font-weight: 600; color: #555; width: 4%; text-align: center;">
                            <input type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll(this)">
                        </th>
                        <th style="padding: 14px 18px; font-size: 12px; font-weight: 600; color: #555; width: 14%;">Order No</th>
                        <th style="padding: 14px 18px; font-size: 12px; font-weight: 600; color: #555; width: 11%;">Date</th>
                        <th style="padding: 14px 18px; font-size: 12px; font-weight: 600; color: #555;">Customer Name</th>
                        <th style="padding: 14px 18px; font-size: 12px; font-weight: 600; color: #555; width: 12%;">Source / Channel</th>
                        <th style="padding: 14px 18px; font-size: 12px; font-weight: 600; color: #555; width: 13%; text-align: right;">Total Amount</th>
                        <th style="padding: 14px 18px; font-size: 12px; font-weight: 600; color: #555; width: 11%; text-align: center;">Status</th>
                        <th style="padding: 14px 18px; font-size: 12px; font-weight: 600; color: #555; width: 25%; text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data['orders'])): ?>
                        <tr>
                            <td colspan="8" style="padding: 40px; text-align: center; color: #888; font-size: 14px;">
                                <i class="ph ph-folder-open" style="font-size: 32px; display: block; margin: 0 auto 10px; color: #ccc;"></i>
                                No sales orders matching the current criteria found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($data['orders'] as $so): ?>
                            <tr style="border-bottom: 1px solid #f0f0f2; transition: background 0.15s;" onmouseover="this.style.background='#fbfbfd'" onmouseout="this.style.background='transparent'">
                                <td style="padding: 14px 18px; text-align: center;">
                                    <input type="checkbox" name="ids[]" value="<?= htmlspecialchars($so->source_type) ?>:<?= $so->id ?>" class="row-checkbox" onchange="updateSelectedCount()">
                                </td>
                                <td style="padding: 14px 18px; font-size: 13px; font-weight: 600; color: #0066cc; font-family: monospace;">
                                    <?php if ($so->source_type === 'standard'): ?>
                                        <a href="<?= APP_URL ?>/salesorder/show/<?= $so->id ?>" target="_blank" style="text-decoration: none; color: inherit;">
                                            <?= htmlspecialchars($so->document_number) ?>
                                        </a>
                                    <?php else: ?>
                                        <a href="<?= APP_URL ?>/sales/show/<?= $so->id ?>" target="_blank" style="text-decoration: none; color: inherit;">
                                            <?= htmlspecialchars($so->document_number) ?>
                                        </a>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 14px 18px; font-size: 13px; color: #555;">
                                    <?= date('Y-m-d', strtotime($so->document_date)) ?>
                                </td>
                                <td style="padding: 14px 18px; font-size: 13px; color: #333; font-weight: 500;">
                                    <?= htmlspecialchars($so->customer_name) ?>
                                    <?php if (!empty($so->rep_name)): ?>
                                        <div style="font-size: 11px; color: #777; margin-top: 3px; font-weight: 400;">
                                            <i class="ph ph-user-circle" style="vertical-align: middle;"></i> Rep: <?= htmlspecialchars($so->rep_name) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 14px 18px; font-size: 13px;">
                                    <?php if ($so->source_type === 'standard'): ?>
                                        <span style="background: #e3f2fd; color: #0d47a1; padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: 600;">
                                            📋 Standard SO
                                        </span>
                                    <?php else: ?>
                                        <span style="background: #f3e5f5; color: #4a148c; padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: 600;">
                                            🚚 Route Rep <?= !empty($so->route_name) ? '(' . htmlspecialchars($so->route_name) . ')' : '' ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 14px 18px; font-size: 13px; color: #111; font-weight: 600; text-align: right; font-family: monospace;">
                                    Rs: <?= number_format($so->grand_total, 2) ?>
                                </td>
                                <td style="padding: 14px 18px; text-align: center;">
                                    <?php 
                                        $status = $so->status ?? 'Pending';
                                        $badgeBg = '#fff9c4'; $badgeColor = '#f57f17';
                                        if ($status === 'Transferred' || $status === 'Completed') { $badgeBg = '#e8f5e9'; $badgeColor = '#2e7d32'; }
                                        elseif ($status === 'Pending') { $badgeBg = '#fff3e0'; $badgeColor = '#e65100'; }
                                        elseif ($status === 'Voided') { $badgeBg = '#eceff1'; $badgeColor = '#37474f'; }
                                    ?>
                                    <span style="background: <?= $badgeBg ?>; color: <?= $badgeColor ?>; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: uppercase;">
                                        <?= $status === 'Transferred' ? 'Invoiced' : $status ?>
                                    </span>
                                </td>
                                <td style="padding: 14px 18px; text-align: right;">
                                    <div style="display: flex; gap: 6px; justify-content: flex-end; align-items: center;">
                                        
                                        <!-- Print -->
                                        <?php if ($so->source_type === 'standard'): ?>
                                            <a href="<?= APP_URL ?>/salesorder/show/<?= $so->id ?>" target="_blank" class="btn btn-outline" style="padding: 5px 8px; font-size: 12px; display: inline-flex; align-items: center; gap: 4px;">
                                                <i class="ph ph-printer"></i> Print
                                            </a>
                                        <?php else: ?>
                                            <a href="<?= APP_URL ?>/sales/show/<?= $so->id ?>" target="_blank" class="btn btn-outline" style="padding: 5px 8px; font-size: 12px; display: inline-flex; align-items: center; gap: 4px;">
                                                <i class="ph ph-printer"></i> Print
                                            </a>
                                        <?php endif; ?>
 
                                        <!-- Edit -->
                                        <a href="<?= APP_URL ?>/sales/edit/<?= $so->id ?>?type=sales_order" target="_blank" class="btn btn-outline" style="padding: 5px 8px; font-size: 12px; display: inline-flex; align-items: center; gap: 4px; border-color: #0066cc; color: #0066cc;">
                                            <i class="ph ph-pencil"></i> Edit
                                        </a>
 
                                        <!-- Convert / Transfer to Invoice -->
                                        <?php if ($status !== 'Transferred' && $status !== 'Completed'): ?>
                                            <?php if ($so->source_type === 'standard'): ?>
                                                <a href="<?= APP_URL ?>/sales/create?from_so=<?= $so->id ?>" class="btn btn-outline" style="padding: 5px 8px; font-size: 12px; display: inline-flex; align-items: center; gap: 4px; border-color: #2e7d32; color: #2e7d32; background: #e8f5e9;">
                                                    <i class="ph ph-arrow-square-out"></i> Convert
                                                </a>
                                            <?php else: ?>
                                                <a href="<?= APP_URL ?>/sales/create?from_so_route=<?= $so->id ?>" class="btn btn-outline" style="padding: 5px 8px; font-size: 12px; display: inline-flex; align-items: center; gap: 4px; border-color: #2e7d32; color: #2e7d32; background: #e8f5e9;">
                                                    <i class="ph ph-arrow-square-out"></i> Convert
                                                </a>
                                            <?php endif; ?>
                                        <?php endif; ?>
 
                                        <!-- Delete -->
                                        <button type="button" class="btn" style="padding: 5px 8px; font-size: 12px; background: #ef4444; color: #fff; border:none; border-radius:4px; cursor:pointer; display: inline-flex; align-items: center; gap: 4px;" onclick="openDeleteModal(<?= $so->id ?>, '<?= $so->document_number ?>', '<?= $so->source_type ?>')">
                                            <i class="ph ph-trash"></i> Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </form>

    <!-- Pagination Footer -->
    <?php if ($data['total_pages'] > 1): ?>
        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 13px; color: #666; padding: 10px 5px;">
            <div>
                Showing page <strong><?= $data['page'] ?></strong> of <strong><?= $data['total_pages'] ?></strong> (Total <strong><?= $data['total_records'] ?></strong> records)
            </div>
            <div style="display: flex; gap: 8px;">
                <?php if ($data['page'] > 1): ?>
                    <a href="?page=<?= $data['page'] - 1 ?>&search=<?= urlencode($data['search']) ?>&customer_id=<?= $data['customer_id'] ?>&start_date=<?= urlencode($data['start_date']) ?>&end_date=<?= urlencode($data['end_date']) ?>&status=<?= urlencode($data['status']) ?>&source_type=<?= urlencode($data['source_type'] ?? '') ?>&rep_name=<?= urlencode($data['rep_name'] ?? '') ?>" class="btn btn-outline" style="padding: 5px 12px; text-decoration: none; font-size: 12px;">&laquo; Previous</a>
                <?php else: ?>
                    <span class="btn btn-outline" style="padding: 5px 12px; opacity: 0.5; cursor: not-allowed; font-size: 12px;">&laquo; Previous</span>
                <?php endif; ?>

                <?php if ($data['page'] < $data['total_pages']): ?>
                    <a href="?page=<?= $data['page'] + 1 ?>&search=<?= urlencode($data['search']) ?>&customer_id=<?= $data['customer_id'] ?>&start_date=<?= urlencode($data['start_date']) ?>&end_date=<?= urlencode($data['end_date']) ?>&status=<?= urlencode($data['status']) ?>&source_type=<?= urlencode($data['source_type'] ?? '') ?>&rep_name=<?= urlencode($data['rep_name'] ?? '') ?>" class="btn btn-outline" style="padding: 5px 12px; text-decoration: none; font-size: 12px;">Next &raquo;</a>
                <?php else: ?>
                    <span class="btn btn-outline" style="padding: 5px 12px; opacity: 0.5; cursor: not-allowed; font-size: 12px;">Next &raquo;</span>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

</div>

<!-- Delete Authentication Modal -->
<div id="deleteAuthModal" class="modal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; overflow:auto; background-color:rgba(0,0,0,0.5); justify-content:center; align-items:center;">
    <div class="modal-content" style="background-color:#fff; padding:25px; border:1px solid #888; width:90%; max-width:400px; border-radius:12px; box-shadow:0 8px 30px rgba(0,0,0,0.25);">
        <h3 style="margin-top:0; color:#ef4444; font-size: 18px; font-weight: 700; display:flex; align-items:center; gap:8px;">
            <i class="ph-fill ph-warning" style="font-size:22px;"></i> Confirm Deletion
        </h3>
        <p style="font-size: 13px; color: #444; line-height: 1.5; margin-bottom: 20px;">
            You are about to delete <strong id="deleteRecordNum" style="color:#ef4444;"></strong>. This action requires administrative authentication. The deletion will be permanently logged in the audit trail.
        </p>
        
        <form id="deleteForm" method="POST" action="">
            <div style="margin-bottom:15px;">
                <label style="display:block; font-size: 12px; font-weight:600; color:#555; margin-bottom:6px;">Reason for Deletion *</label>
                <textarea name="delete_reason" required style="width:100%; height:70px; padding:8px; border:1px solid #ccc; border-radius:6px; box-sizing:border-box; resize:none; font-size:13px;" placeholder="Why are you deleting this record?"></textarea>
            </div>
            
            <div style="margin-bottom:20px;">
                <label style="display:block; font-size: 12px; font-weight:600; color:#555; margin-bottom:6px;">Your Admin Password *</label>
                <input type="password" name="password" required style="width:100%; padding:8px; border:1px solid #ccc; border-radius:6px; box-sizing:border-box; font-size:13px;" placeholder="Enter password to verify">
            </div>
            
            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" class="btn btn-outline" onclick="closeDeleteModal()" style="padding:7px 14px; font-size:13px;">Cancel</button>
                <button type="submit" class="btn" style="padding:7px 14px; background:#ef4444; color:#fff; border:none; font-weight:600; font-size:13px; cursor:pointer;">Verify & Delete</button>
            </div>
        </form>
    </div>
</div>

<script>
function openDeleteModal(id, number, sourceType) {
    const modal = document.getElementById('deleteAuthModal');
    const form = document.getElementById('deleteForm');
    const recordNumSpan = document.getElementById('deleteRecordNum');
    
    recordNumSpan.textContent = `Sales Order ${number}`;
    
    // Capture current search/filter parameters
    const queryParams = window.location.search || '';
    
    // Set form action based on the source table
    if (sourceType === 'standard') {
        form.action = `<?= APP_URL ?>/salesorder/delete/${id}${queryParams}`;
    } else {
        form.action = `<?= APP_URL ?>/sales/delete/${id}${queryParams}`;
    }
    
    form.reset();
    modal.style.display = 'flex';
}

function closeDeleteModal() {
    document.getElementById('deleteAuthModal').style.display = 'none';
}

window.onclick = function(event) {
    const modal = document.getElementById('deleteAuthModal');
    if (event.target == modal) {
        closeDeleteModal();
    }
}

// Bulk Actions Logic
function toggleSelectAll(master) {
    const checkboxes = document.querySelectorAll('.row-checkbox');
    checkboxes.forEach(cb => cb.checked = master.checked);
    updateSelectedCount();
}

function updateSelectedCount() {
    const checkboxes = document.querySelectorAll('.row-checkbox:checked');
    const panel = document.getElementById('bulkActionBar');
    const badge = document.getElementById('selectedCountBadge');
    
    if (checkboxes.length > 0) {
        panel.style.display = 'flex';
        badge.textContent = checkboxes.length + ' selected';
    } else {
        panel.style.display = 'none';
        const selectAll = document.getElementById('selectAllCheckbox');
        if (selectAll) selectAll.checked = false;
    }
}

function toggleBulkActionInputs() {
    const action = document.getElementById('bulkActionSelect').value;
    
    document.getElementById('bulkDateInputContainer').style.display = action === 'change_date' ? 'block' : 'none';
    document.getElementById('bulkRepInputContainer').style.display = action === 'change_rep' ? 'block' : 'none';
    document.getElementById('bulkPasswordInputContainer').style.display = action === 'delete' ? 'block' : 'none';
    
    // Add required attributes dynamically
    document.querySelector('input[name="bulk_date"]').required = action === 'change_date';
    document.querySelector('select[name="bulk_rep"]').required = action === 'change_rep';
    document.querySelector('input[name="admin_password"]').required = action === 'delete';
}

function handleBulkSubmit(e) {
    const action = document.getElementById('bulkActionSelect').value;
    if (!action) {
        alert('Please select a bulk action.');
        e.preventDefault();
        return false;
    }
    
    const checkboxes = document.querySelectorAll('.row-checkbox:checked');
    if (checkboxes.length === 0) {
        alert('No records selected.');
        e.preventDefault();
        return false;
    }
    
    if (action === 'delete') {
        const password = document.querySelector('input[name="admin_password"]').value;
        if (!password) {
            alert('Admin password is required for bulk deletion.');
            e.preventDefault();
            return false;
        }
        if (!confirm('Are you sure you want to permanently delete the ' + checkboxes.length + ' selected records? This action requires administrator validation and cannot be undone.')) {
            e.preventDefault();
            return false;
        }
    }
    return true;
}
</script>
