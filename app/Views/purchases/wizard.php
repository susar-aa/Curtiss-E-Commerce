<?php
?>
<style>
    .wizard-card { max-width: 600px; margin: 40px auto; background: #fff; padding: 40px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); border-top: 6px solid #0066cc;}
    @media (prefers-color-scheme: dark) { .wizard-card { background: #1e1e2d; } }
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; margin-bottom: 8px; font-size: 14px; font-weight: 600; }
    .form-control { width: 100%; padding: 12px; border: 1px solid var(--mac-border); border-radius: 6px; background: transparent; color: var(--text-main); font-size: 15px; box-sizing: border-box;}
    
    .radio-group { display: flex; gap: 15px; margin-bottom: 25px; }
    .radio-card { flex: 1; border: 2px solid var(--mac-border); border-radius: 8px; padding: 20px; cursor: pointer; transition: 0.2s; position: relative;}
    .radio-card:hover { border-color: #0066cc; background: rgba(0,102,204,0.02); }
    .radio-card input[type="radio"] { position: absolute; opacity: 0; }
    .radio-card h4 { margin: 0 0 5px 0; color: #0066cc; }
    .radio-card p { margin: 0; font-size: 12px; color: #666; }
    
    .radio-card.selected { border-color: #0066cc; background: rgba(0,102,204,0.05); box-shadow: 0 4px 10px rgba(0,102,204,0.1);}
    
    .ai-settings { display: none; background: rgba(0,0,0,0.02); padding: 20px; border-radius: 8px; border: 1px dashed #aaa; margin-bottom: 25px;}
    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
    .btn { padding: 12px 24px; background: #0066cc; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-size: 16px; font-weight:bold; width: 100%;}
</style>

<div class="wizard-card">
    <h2 style="margin-top:0; text-align:center;">Purchase Order Wizard</h2>
    <p style="text-align:center; color:#666; margin-bottom:30px;">How would you like to generate this order?</p>

    <form action="<?= APP_URL ?>/purchase/wizard_process" method="POST">
        
        <div class="form-group">
            <label>Select Supplier / Vendor *</label>
            <select name="vendor_id" class="form-control" required>
                <option value="">Select Vendor...</option>
                <?php foreach($data['vendors'] as $ven): ?>
                    <option value="<?= $ven->id ?>"><?= htmlspecialchars($ven->name) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <label style="font-size: 14px; font-weight: 600; display:block; margin-bottom:10px;">Generation Mode *</label>
        <div class="radio-group">
            <label class="radio-card selected" id="card_manual">
                <input type="radio" name="po_mode" value="manual" checked onchange="toggleMode()">
                <h4>✏️ Manual Entry</h4>
                <p>Start with a blank Purchase Order and add items yourself.</p>
            </label>
            <label class="radio-card" id="card_ai">
                <input type="radio" name="po_mode" value="ai" onchange="toggleMode()">
                <h4>🤖 Smart Suggestion</h4>
                <p>Analyze past sales and stock levels to calculate exactly what to order.</p>
            </label>
        </div>

        <div id="aiSettings" class="ai-settings">
            <div class="form-group">
                <label>Analyze Sales From</label>
                <select name="date_range" id="dateRange" class="form-control" onchange="toggleCustomDates()">
                    <option value="week">Past 7 Days</option>
                    <option value="month">Past 30 Days</option>
                    <option value="custom">Custom Date Range...</option>
                </select>
            </div>
            
            <div class="grid-2" id="customDates" style="display:none; margin-bottom:15px;">
                <div><label>Start Date</label><input type="date" name="start_date" class="form-control"></div>
                <div><label>End Date</label><input type="date" name="end_date" class="form-control"></div>
            </div>

            <div class="form-group" style="margin-bottom:0;">
                <label>Safety Buffer Percentage (%)</label>
                <input type="number" name="buffer_percent" class="form-control" value="10" min="0" step="1">
                <span style="font-size:11px; color:#888;">If you sold 100 units, a 10% buffer will suggest ordering 110 units (minus current stock).</span>
            </div>
        </div>

        <button type="submit" class="btn" id="submitBtn">Continue to Order &rarr;</button>
    </form>
</div>

<script>
    function toggleMode() {
        const mode = document.querySelector('input[name="po_mode"]:checked').value;
        const cardManual = document.getElementById('card_manual');
        const cardAi = document.getElementById('card_ai');
        const aiSettings = document.getElementById('aiSettings');
        const submitBtn = document.getElementById('submitBtn');

        if (mode === 'manual') {
            cardManual.classList.add('selected');
            cardAi.classList.remove('selected');
            aiSettings.style.display = 'none';
            submitBtn.innerText = 'Start Manual PO \u2192';
        } else {
            cardAi.classList.add('selected');
            cardManual.classList.remove('selected');
            aiSettings.style.display = 'block';
            submitBtn.innerText = 'Analyze & Suggest \u2192';
        }
    }

    function toggleCustomDates() {
        const range = document.getElementById('dateRange').value;
        document.getElementById('customDates').style.display = (range === 'custom') ? 'grid' : 'none';
    }
</script>