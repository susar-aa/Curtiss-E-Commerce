<?php
// Capture flash/session messages safely
$successMsg = $data['success'] ?? '';
$errorMsg = $_SESSION['discount_error'] ?? $data['error'] ?? '';
if (isset($_SESSION['discount_error'])) {
    unset($_SESSION['discount_error']);
}

$rules = $data['rules'] ?? [];
$items = $data['items'] ?? [];
?>

<!-- Inline Status Alerts -->
<div id="alert-container" class="mb-6">
    <?php if ($successMsg): ?>
        <div id="success-alert" class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 flex items-start gap-4 shadow-sm">
            <div class="bg-emerald-100 text-emerald-600 p-2 rounded-full mt-0.5 shrink-0">
                <i class="fa-solid fa-check"></i>
            </div>
            <div>
                <h4 class="text-emerald-800 font-semibold text-sm">Action Successful</h4>
                <p class="text-emerald-600 text-xs mt-0.5"><?php echo htmlspecialchars($successMsg); ?></p>
            </div>
            <button onclick="document.getElementById('success-alert').style.display='none'" class="ml-auto text-emerald-400 hover:text-emerald-600 cursor-pointer">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>
    <?php endif; ?>

    <?php if ($errorMsg): ?>
        <div id="error-alert" class="bg-rose-50 border border-rose-200 rounded-xl p-4 flex items-start gap-4 shadow-sm">
            <div class="bg-rose-100 text-rose-600 p-2 rounded-full mt-0.5 shrink-0">
                <i class="fa-solid fa-circle-exclamation"></i>
            </div>
            <div>
                <h4 class="text-rose-800 font-semibold text-sm">Action Failed</h4>
                <p class="text-rose-600 text-xs mt-0.5"><?php echo htmlspecialchars($errorMsg); ?></p>
            </div>
            <button onclick="document.getElementById('error-alert').style.display='none'" class="ml-auto text-rose-400 hover:text-rose-600 cursor-pointer">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>
    <?php endif; ?>
</div>

<!-- Header -->
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-800">Customizable Discount Feed</h1>
        <p class="text-xs text-slate-500 mt-1">Configure item-wise free issue promotions and bill-wise percentage thresholds.</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Creation Panel -->
    <div class="lg:col-span-1 bg-white border border-slate-200 rounded-xl p-6 shadow-sm">
        <h3 class="font-bold text-slate-800 text-base mb-4 flex items-center gap-2">
            <i class="fa-solid fa-plus-circle text-indigo-600"></i> Configure New Discount Rule
        </h3>
        
        <form action="<?php echo APP_URL; ?>/discount/add" method="POST" class="space-y-4">
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Rule Name / Description *</label>
                <input type="text" name="name" required placeholder="e.g., Buy 10 Get 1 Free on X" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-xs font-medium focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Rule Type *</label>
                <select name="rule_type" id="ruleTypeSelect" onchange="toggleRuleFields()" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-xs font-medium focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 focus:outline-none">
                    <option value="item_wise">Item-Wise (Free Issue)</option>
                    <option value="bill_wise">Bill Total-Wise (Discount %)</option>
                </select>
            </div>

            <div id="targetProductWrapper">
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Target Product *</label>
                <select name="target_item_id" id="targetItemSelect" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-xs font-medium focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 focus:outline-none">
                    <option value="">Select Product...</option>
                    <?php foreach ($items as $item): ?>
                        <option value="<?php echo $item->id; ?>">
                            <?php echo htmlspecialchars($item->name . ' [' . ($item->item_code ?? 'N/A') . ']'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Tiers Builder -->
            <div class="border-t border-slate-100 pt-4">
                <div class="flex justify-between items-center mb-3">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Discount Tiers / Thresholds</label>
                    <button type="button" onclick="addTierRow()" class="px-2 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded text-[10px] font-bold transition flex items-center gap-1 cursor-pointer">
                        <i class="fa-solid fa-plus"></i> Add Tier
                    </button>
                </div>
                
                <div class="space-y-2" id="tiersContainer">
                    <!-- Dynamic tiers rows are loaded here -->
                </div>
            </div>

            <button type="submit" class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-lg shadow transition cursor-pointer">
                Save Discount Rule
            </button>
        </form>
    </div>

    <!-- Active Rules Registry -->
    <div class="lg:col-span-2 bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">
        <div class="bg-slate-50 border-b border-slate-200 px-6 py-4 flex justify-between items-center">
            <h3 class="font-bold text-slate-800 text-sm">Configured Rules Registry</h3>
        </div>
        
        <table class="w-full text-sm border-collapse">
            <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 text-xs font-semibold uppercase tracking-wider">
                <tr>
                    <th class="py-3 px-6 text-left w-[30%]">Rule Details</th>
                    <th class="py-3 px-6 text-left w-[20%]">Rule Type</th>
                    <th class="py-3 px-6 text-left w-[35%]">Configured Tiers</th>
                    <th class="py-3 px-6 text-center w-[15%]">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-xs">
                <?php if (empty($rules)): ?>
                    <tr>
                        <td colspan="4" class="py-12 text-center text-slate-400 italic">
                            <div class="flex flex-col items-center gap-2">
                                <i class="fa-solid fa-tags text-2xl text-slate-300"></i>
                                <span>No customizable discount rules found.</span>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rules as $r): ?>
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="py-4 px-6">
                                <div class="font-semibold text-slate-800 text-sm"><?php echo htmlspecialchars($r->name); ?></div>
                                <?php if ($r->rule_type === 'item_wise' && !empty($r->item_name)): ?>
                                    <div class="text-[10px] text-slate-500 mt-1">
                                        Target SKU: <span class="font-mono bg-slate-100 px-1 py-0.5 rounded"><?php echo htmlspecialchars($r->item_sku); ?></span>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="py-4 px-6">
                                <?php if ($r->rule_type === 'item_wise'): ?>
                                    <span class="inline-flex items-center gap-1 bg-emerald-50 text-emerald-700 px-2 py-1 rounded-md font-medium text-[10px]">
                                        <i class="fa-solid fa-gift"></i> Item-Wise Free
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1 bg-indigo-50 text-indigo-700 px-2 py-1 rounded-md font-medium text-[10px]">
                                        <i class="fa-solid fa-percent"></i> Bill-Wise Discount
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="py-4 px-6">
                                <ul class="space-y-1">
                                    <?php foreach ($r->tiers as $t): ?>
                                        <li class="flex items-center gap-2">
                                            <span class="font-semibold text-slate-700">
                                                <?php if ($r->rule_type === 'item_wise'): ?>
                                                    Buy &ge; <?php echo intval($t->min_threshold); ?> QTY
                                                <?php else: ?>
                                                    Rs <?php echo number_format($t->min_threshold); ?>
                                                    <?php if ($t->max_threshold): ?>
                                                        - Rs <?php echo number_format($t->max_threshold); ?>
                                                    <?php else: ?>
                                                        +
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </span>
                                            <span class="text-slate-400">&rarr;</span>
                                            <span class="text-indigo-600 font-bold bg-indigo-50/50 px-1.5 py-0.5 rounded">
                                                <?php if ($r->rule_type === 'item_wise'): ?>
                                                    Get <?php echo intval($t->reward_val); ?> Free
                                                <?php else: ?>
                                                    <?php echo floatval($t->reward_val); ?>% Off
                                                <?php endif; ?>
                                            </span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </td>
                            <td class="py-4 px-6 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <!-- Toggle Status Button -->
                                    <a href="<?php echo APP_URL; ?>/discount/toggle/<?php echo $r->id; ?>" 
                                       class="px-2 py-1 rounded text-[10px] font-semibold transition cursor-pointer <?php echo $r->status === 'Active' ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200' : 'bg-slate-100 text-slate-800 hover:bg-slate-205'; ?>">
                                        <?php echo $r->status; ?>
                                    </a>
                                    <!-- Delete Button -->
                                    <a href="<?php echo APP_URL; ?>/discount/delete/<?php echo $r->id; ?>" 
                                       onclick="return confirm('Are you sure you want to delete this discount rule?');"
                                       class="p-1 text-rose-600 hover:text-rose-800 hover:bg-rose-50 rounded transition cursor-pointer">
                                        <i class="fa-solid fa-trash-can text-sm"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    function toggleRuleFields() {
        const type = document.getElementById('ruleTypeSelect').value;
        const productWrapper = document.getElementById('targetProductWrapper');
        const productSelect = document.getElementById('targetItemSelect');
        const container = document.getElementById('tiersContainer');
        
        if (type === 'item_wise') {
            productWrapper.style.display = 'block';
            productSelect.setAttribute('required', 'required');
        } else {
            productWrapper.style.display = 'none';
            productSelect.removeAttribute('required');
        }

        // Clear existing tiers and populate a default first row
        container.innerHTML = '';
        addTierRow();
    }

    function addTierRow() {
        const type = document.getElementById('ruleTypeSelect').value;
        const container = document.getElementById('tiersContainer');
        const rowDiv = document.createElement('div');
        rowDiv.className = 'flex items-center gap-2 bg-slate-50 p-2 rounded-lg border border-slate-100 animate-fade-in';
        
        if (type === 'item_wise') {
            rowDiv.innerHTML = `
                <div class="flex-1">
                    <input type="number" min="1" step="1" name="min_threshold[]" required placeholder="Min Qty (e.g. 10)" class="w-full px-2 py-1 bg-white border border-slate-200 rounded text-xs focus:outline-none">
                </div>
                <div class="flex-1">
                    <input type="number" min="1" step="1" name="reward_val[]" required placeholder="Free Qty (e.g. 1)" class="w-full px-2 py-1 bg-white border border-slate-200 rounded text-xs focus:outline-none">
                </div>
                <button type="button" onclick="this.closest('div').remove()" class="text-rose-500 hover:text-rose-700 cursor-pointer">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            `;
        } else {
            rowDiv.innerHTML = `
                <div style="flex: 2;">
                    <input type="number" min="0.01" step="0.01" name="min_threshold[]" required placeholder="Min Amt (e.g. 50000)" class="w-full px-2 py-1 bg-white border border-slate-200 rounded text-xs focus:outline-none">
                </div>
                <div style="flex: 2;">
                    <input type="number" min="0.01" step="0.01" name="max_threshold[]" placeholder="Max Amt (e.g. 99999)" class="w-full px-2 py-1 bg-white border border-slate-200 rounded text-xs focus:outline-none">
                </div>
                <div style="flex: 1;">
                    <input type="number" min="0.01" max="100" step="0.01" name="reward_val[]" required placeholder="%" class="w-full px-2 py-1 bg-white border border-slate-200 rounded text-xs focus:outline-none">
                </div>
                <button type="button" onclick="this.closest('div').remove()" class="text-rose-500 hover:text-rose-700 cursor-pointer">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            `;
        }
        
        container.appendChild(rowDiv);
    }

    // Initialize fields
    document.addEventListener("DOMContentLoaded", function() {
        toggleRuleFields();
    });
</script>
