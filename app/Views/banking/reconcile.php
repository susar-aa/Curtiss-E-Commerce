<?php
// Bank Reconciliation View
?>
<style>
    .header-actions { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 20px; }
    .btn { padding: 8px 16px; background: #0066cc; color: #fff; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; font-size: 14px;}
    .btn:disabled { background: #ccc; cursor: not-allowed; }
    .form-control { padding: 8px 12px; border: 1px solid var(--mac-border); border-radius: 4px; background: transparent; color: var(--text-main); font-size: 16px; width: 200px;}
    
    .reconciliation-dashboard { display: grid; grid-template-columns: 1fr 300px; gap: 20px; }
    
    .recon-table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    @media (prefers-color-scheme: dark) { .recon-table { background: #1e1e2d; } }
    .recon-table th, .recon-table td { padding: 12px; text-align: left; border-bottom: 1px solid var(--mac-border); font-size: 14px; }
    .recon-table th { background-color: rgba(0,0,0,0.02); font-weight: 600; font-size: 13px; }
    .num-col { text-align: right !important; }
    .debit { color: #2e7d32; font-weight: 500; }
    .credit { color: #c62828; font-weight: 500; }
    
    .summary-box { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); position: sticky; top: 20px;}
    @media (prefers-color-scheme: dark) { .summary-box { background: #1e1e2d; } }
    .summary-row { display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 15px; }
    .summary-row.total { font-size: 18px; font-weight: bold; border-top: 1px solid var(--mac-border); padding-top: 15px; margin-top: 15px;}
    
    .difference-box { padding: 15px; border-radius: 8px; text-align: center; margin-bottom: 20px; font-weight: bold; font-size: 18px; transition: 0.3s;}
    .diff-zero { background: #e8f5e9; color: #2e7d32; border: 2px solid #a5d6a7; }
    .diff-alert { background: #ffebee; color: #c62828; border: 2px solid #ef9a9a; }
</style>

<div class="header-actions">
    <div>
        <a href="<?= APP_URL ?>/banking" style="color: #666; text-decoration:none; font-size:13px;">&larr; Back to Banking</a>
        <h2 style="margin: 10px 0 0 0;">Reconcile Account</h2>
        <p style="margin: 0; color: #888; font-size: 14px;"><?= htmlspecialchars($data['account']->account_code) ?> - <?= htmlspecialchars($data['account']->account_name) ?></p>
    </div>
</div>

<?php if(!empty($data['error'])): ?>
    <div style="padding: 10px; background:#ffebee; color:#c62828; border-radius:4px; margin-bottom:15px;"><?= $data['error'] ?></div>
<?php endif; ?>
<?php if(!empty($data['success'])): ?>
    <div style="padding: 10px; background:#e8f5e9; color:#2e7d32; border-radius:4px; margin-bottom:15px;"><?= $data['success'] ?></div>
<?php endif; ?>

<form action="<?= APP_URL ?>/banking/reconcile/<?= $data['account']->id ?>" method="POST" id="reconForm">
    <input type="hidden" name="action" value="save_reconciliation">

    <div class="reconciliation-dashboard">
        <!-- Left Column: Transactions List -->
        <div>
            <div style="margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; background: rgba(0,0,0,0.02); padding: 15px; border-radius: 8px; border: 1px solid var(--mac-border); flex-wrap: wrap; gap: 15px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <label style="font-weight: bold; font-size: 13px;">Ending Balance from Statement:</label>
                    <div>Rs: <input type="number" name="statement_balance" id="statementBalance" step="0.01" class="form-control" value="<?= $data['statement_balance'] ?>" oninput="calculateDifference()"></div>
                </div>
                <div style="display: flex; gap: 10px;">
                    <input type="file" id="csvFileInput" accept=".csv" style="display: none;" onchange="handleCSVImport(this)">
                    <button type="button" class="btn" style="background:#0f172a; color:#fff;" onclick="document.getElementById('csvFileInput').click()">📥 Import CSV Statement</button>
                    <button type="button" class="btn" style="background:#475569; color:#fff;" onclick="exportToCSV()">📤 Export Unreconciled CSV</button>
                </div>
            </div>

            <table class="recon-table">
                <thead>
                    <tr>
                        <th style="width: 40px; text-align: center;">✓</th>
                        <th style="width: 100px;">Date</th>
                        <th>Description / Reference</th>
                        <th class="num-col" style="width: 120px;">Deposit (+)</th>
                        <th class="num-col" style="width: 120px;">Payment (-)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($data['uncleared'])): ?>
                        <tr><td colspan="5" style="text-align: center; color: #888; padding: 30px;">All transactions are currently cleared!</td></tr>
                    <?php else: foreach($data['uncleared'] as $t): ?>
                        <tr>
                            <td style="text-align: center;">
                                <!-- Store the impact value directly in a data attribute for easy JS calculation -->
                                <?php $impact = ($t->debit > 0) ? $t->debit : -$t->credit; ?>
                                <input type="checkbox" name="cleared_tx[]" value="<?= $t->id ?>" class="tx-checkbox" data-impact="<?= $impact ?>" onchange="calculateDifference()" style="cursor:pointer; width: 16px; height: 16px;">
                            </td>
                            <td><?= date('M d, y', strtotime($t->entry_date)) ?></td>
                            <td>
                                <?= htmlspecialchars($t->description) ?><br>
                                <span style="font-size:11px; color:#888;"><?= htmlspecialchars($t->reference) ?></span>
                            </td>
                            <td class="num-col debit"><?= $t->debit > 0 ? number_format($t->debit, 2) : '' ?></td>
                            <td class="num-col credit"><?= $t->credit > 0 ? number_format($t->credit, 2) : '' ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Right Column: Reconciliation Summary -->
        <div>
            <div class="summary-box">
                <h3 style="margin-top:0; border-bottom: 1px solid var(--mac-border); padding-bottom: 10px;">Summary</h3>
                
                <div class="summary-row">
                    <span style="color:#888;">Cleared Balance (System):</span>
                    <!-- The starting point is the system balance MINUS all UNCLEARED transactions -->
                    <?php 
                        $totalUncleared = 0;
                        foreach($data['uncleared'] as $t) {
                            $totalUncleared += ($t->debit > 0) ? $t->debit : -$t->credit;
                        }
                        $startingClearedBalance = $data['account']->balance - $totalUncleared;
                    ?>
                    <span id="clearedSystemBal" data-base="<?= $startingClearedBalance ?>">Rs: <?= number_format($startingClearedBalance, 2) ?></span>
                </div>

                <div class="summary-row">
                    <span style="color:#888;">Selected Deposits:</span>
                    <span id="selectedDeposits" style="color: #2e7d32;">Rs: 0.00</span>
                </div>
                <div class="summary-row">
                    <span style="color:#888;">Selected Payments:</span>
                    <span id="selectedPayments" style="color: #c62828;">Rs: 0.00</span>
                </div>

                <div class="summary-row total">
                    <span>New Cleared Balance:</span>
                    <span id="newClearedBal">Rs: <?= number_format($startingClearedBalance, 2) ?></span>
                </div>

                <div id="diffBox" class="difference-box diff-alert">
                    Difference: Rs: <span id="diffAmount">0.00</span>
                </div>

                <button type="submit" id="saveReconBtn" class="btn" style="width: 100%; font-size: 16px; padding: 12px;">Save Reconciliation</button>
            </div>
        </div>
    </div>
</form>

<script>
    function calculateDifference() {
        const checkboxes = document.querySelectorAll('.tx-checkbox');
        const statementBalInput = document.getElementById('statementBalance');
        const baseClearedStr = document.getElementById('clearedSystemBal').getAttribute('data-base');
        
        let baseCleared = parseFloat(baseClearedStr) || 0;
        let statementBal = parseFloat(statementBalInput.value) || 0;
        
        let sumDeposits = 0;
        let sumPayments = 0;

        checkboxes.forEach(cb => {
            if(cb.checked) {
                let impact = parseFloat(cb.getAttribute('data-impact'));
                if (impact > 0) { sumDeposits += impact; } 
                else { sumPayments += Math.abs(impact); }
            }
        });

        document.getElementById('selectedDeposits').innerText = 'Rs: ' + sumDeposits.toFixed(2);
        document.getElementById('selectedPayments').innerText = 'Rs: ' + sumPayments.toFixed(2);

        // Calculate new cleared balance
        let newCleared = baseCleared + sumDeposits - sumPayments;
        document.getElementById('newClearedBal').innerText = 'Rs: ' + newCleared.toFixed(2);

        // Calculate Difference (Statement vs New Cleared)
        let difference = statementBal - newCleared;
        const diffBox = document.getElementById('diffBox');
        const diffAmount = document.getElementById('diffAmount');
        const saveBtn = document.getElementById('saveReconBtn');

        diffAmount.innerText = Math.abs(difference).toFixed(2);

        // The goal of reconciliation is a difference of exactly 0
        if (Math.abs(difference) < 0.01) { // Floating point safety
            diffBox.className = 'difference-box diff-zero';
            diffBox.innerHTML = 'Perfectly Balanced! (Diff: 0.00)';
            saveBtn.disabled = false;
            saveBtn.innerText = 'Reconcile Account';
        } else {
            diffBox.className = 'difference-box diff-alert';
            diffBox.innerHTML = 'Difference: Rs: ' + difference.toFixed(2);
            saveBtn.disabled = true; 
            saveBtn.innerText = 'Check transactions to balance';
        }
    }

    function exportToCSV() {
        const rows = [
            ["ID", "Date", "Description", "Reference", "Amount"]
        ];
        
        const tbodyRows = document.querySelectorAll('.recon-table tbody tr');
        tbodyRows.forEach(tr => {
            const cb = tr.querySelector('.tx-checkbox');
            if (!cb) return;

            const id = cb.value;
            const date = tr.cells[1].innerText.trim();
            const descAndRef = tr.cells[2].innerText.split('\n');
            const desc = descAndRef[0].trim();
            
            const deposit = tr.cells[3].innerText.trim().replace(/,/g, '');
            const payment = tr.cells[4].innerText.trim().replace(/,/g, '');
            
            const amount = deposit ? parseFloat(deposit) : -parseFloat(payment);
            
            rows.push([id, date, desc, descAndRef[1] ? descAndRef[1].trim() : "", amount]);
        });
        
        let csvContent = "data:text/csv;charset=utf-8,";
        rows.forEach(rowArray => {
            let row = rowArray.map(val => `"${String(val).replace(/"/g, '""')}"`).join(",");
            csvContent += row + "\r\n";
        });
        
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "unreconciled_transactions.csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    function handleCSVImport(input) {
        const file = input.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = function(e) {
            const text = e.target.result;
            const lines = text.split(/\r?\n/);
            if (lines.length < 2) {
                alert("CSV file is empty or invalid.");
                return;
            }

            const headers = lines[0].split(',').map(h => h.trim().replace(/^["']|["']$/g, '').toLowerCase());
            
            let dateIdx = headers.findIndex(h => h.includes('date'));
            let amountIdx = headers.findIndex(h => h.includes('amount'));
            let depositIdx = headers.findIndex(h => h.includes('deposit'));
            let paymentIdx = headers.findIndex(h => h.includes('payment'));
            let descIdx = headers.findIndex(h => h.includes('desc') || h.includes('memo') || h.includes('ref'));

            if (dateIdx === -1) {
                dateIdx = 0;
                descIdx = 1;
                amountIdx = 2;
            }

            const statementTxs = [];
            for (let i = 1; i < lines.length; i++) {
                const line = lines[i].trim();
                if (!line) continue;
                
                const cells = [];
                let current = '';
                let inQuotes = false;
                for (let c = 0; c < line.length; c++) {
                    const char = line[c];
                    if (char === '"') {
                        inQuotes = !inQuotes;
                    } else if (char === ',' && !inQuotes) {
                        cells.push(current.trim());
                        current = '';
                    } else {
                        current += char;
                    }
                }
                cells.push(current.trim());

                if (cells.length <= Math.max(dateIdx, amountIdx, depositIdx, paymentIdx)) continue;

                const dateStr = cells[dateIdx];
                const parsedDate = new Date(dateStr);
                if (isNaN(parsedDate.getTime())) continue;

                let amt = 0;
                if (amountIdx !== -1 && cells[amountIdx]) {
                    amt = parseFloat(cells[amountIdx].replace(/[^\d.-]/g, '')) || 0;
                } else {
                    const dep = depositIdx !== -1 && cells[depositIdx] ? parseFloat(cells[depositIdx].replace(/[^\d.-]/g, '')) : 0;
                    const pay = paymentIdx !== -1 && cells[paymentIdx] ? parseFloat(cells[paymentIdx].replace(/[^\d.-]/g, '')) : 0;
                    amt = dep > 0 ? dep : -pay;
                }

                statementTxs.push({
                    date: parsedDate,
                    amount: amt,
                    desc: descIdx !== -1 ? cells[descIdx] : ''
                });
            }

            if (statementTxs.length === 0) {
                alert("No valid transactions found in CSV.");
                return;
            }

            autoMatchTransactions(statementTxs);
        };
        reader.readAsText(file);
        input.value = '';
    }

    function autoMatchTransactions(statementTxs) {
        const checkboxes = Array.from(document.querySelectorAll('.tx-checkbox'));
        
        checkboxes.forEach(cb => {
            cb.checked = false;
            const tr = cb.closest('tr');
            tr.style.background = '';
            const badge = tr.querySelector('.match-badge');
            if (badge) badge.remove();
        });

        let matchCount = 0;
        
        const systemCandidates = checkboxes.map(cb => {
            const tr = cb.closest('tr');
            const dateText = tr.cells[1].innerText.trim();
            const sysDate = new Date(dateText);
            const impact = parseFloat(cb.getAttribute('data-impact'));
            return {
                cb: cb,
                tr: tr,
                date: sysDate,
                impact: impact,
                matched: false
            };
        });

        statementTxs.forEach(st => {
            const threeDaysMs = 3 * 24 * 60 * 60 * 1000;
            
            const match = systemCandidates.find(sys => {
                if (sys.matched) return false;
                
                const amtDiff = Math.abs(sys.impact - st.amount);
                const timeDiff = Math.abs(sys.date.getTime() - st.date.getTime());
                
                return (amtDiff < 0.01 && timeDiff <= threeDaysMs);
            });

            if (match) {
                match.matched = true;
                match.cb.checked = true;
                matchCount++;

                match.tr.style.background = 'rgba(46, 125, 50, 0.08)';
                
                const descCell = match.tr.cells[2];
                const badge = document.createElement('span');
                badge.className = 'match-badge';
                badge.style.display = 'inline-block';
                badge.style.padding = '2px 6px';
                badge.style.background = '#2e7d32';
                badge.style.color = '#fff';
                badge.style.borderRadius = '4px';
                badge.style.fontSize = '10px';
                badge.style.fontWeight = 'bold';
                badge.style.marginLeft = '8px';
                badge.innerText = 'Auto-Matched ✓';
                descCell.appendChild(badge);
            }
        });

        calculateDifference();
        alert(`Successfully imported statement! Auto-matched ${matchCount} out of ${statementTxs.length} statement transactions.`);
    }

    // Run on page load to set initial state
    document.addEventListener("DOMContentLoaded", calculateDifference);
</script>