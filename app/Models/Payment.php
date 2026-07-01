<?php
class Payment {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    /**
     * Get list of customers with their outstanding receivables
     */
    public function getCustomerOutstandingList() {
        $this->db->query("
            SELECT c.id, c.name, c.phone, c.email,
                   (SELECT COALESCE(SUM(total_amount - COALESCE(CASE WHEN global_discount_type = '%' THEN (total_amount * global_discount_val / 100) ELSE global_discount_val END, 0) + COALESCE(tax_amount, 0)), 0) FROM invoices WHERE customer_id = c.id AND status != 'Voided') 
                   - 
                   (SELECT COALESCE(SUM(amount), 0) FROM customer_payments WHERE customer_id = c.id AND status = 'Active') 
                   - 
                   (SELECT COALESCE(SUM(total_amount), 0) FROM credit_notes WHERE customer_id = c.id) 
                   AS outstanding_balance,
                   (SELECT MAX(payment_date) FROM customer_payments WHERE customer_id = c.id AND status = 'Active') as last_payment_date
            FROM customers c
            ORDER BY outstanding_balance DESC, c.name ASC
        ");
        return $this->db->resultSet();
    }

    /**
     * Get list of suppliers with their outstanding payables
     */
    public function getSupplierOutstandingList() {
        $this->db->query("
            SELECT v.id, v.name, v.phone, v.email,
                   (SELECT COALESCE(SUM(gri.total), 0) 
                    FROM grn_items gri 
                    JOIN goods_receipt_notes grn ON gri.grn_id = grn.id 
                    WHERE grn.vendor_id = v.id) as total_billed,
                   (SELECT COALESCE(SUM(amount), 0) 
                    FROM expenses 
                    WHERE vendor_id = v.id) 
                   + 
                   (SELECT COALESCE(SUM(amount), 0) 
                    FROM supplier_payments 
                    WHERE vendor_id = v.id AND status = 'Active') as total_paid,
                   (SELECT COALESCE(SUM(total_amount), 0) 
                    FROM supplier_returns 
                    WHERE vendor_id = v.id) as total_returned,
                   (SELECT MAX(payment_date) FROM supplier_payments WHERE vendor_id = v.id AND status = 'Active') as last_payment_date
            FROM vendors v
            ORDER BY v.name ASC
        ");
        $suppliers = $this->db->resultSet();
        foreach ($suppliers as $s) {
            $s->outstanding_balance = $s->total_billed - $s->total_paid - $s->total_returned;
        }
        
        // Sort by outstanding balance desc
        usort($suppliers, function($a, $b) {
            return $b->outstanding_balance <=> $a->outstanding_balance;
        });

        return $suppliers;
    }

    /**
     * Get unpaid or partially paid invoices for a customer
     */
    public function getCustomerUnpaidInvoices($customerId) {
        $this->db->query("
            SELECT i.id, i.invoice_number, i.invoice_date, i.due_date, i.status,
                   (i.total_amount - COALESCE(CASE WHEN i.global_discount_type = '%' THEN (i.total_amount * i.global_discount_val / 100) ELSE i.global_discount_val END, 0) + COALESCE(i.tax_amount, 0)) as total_amount,
                   COALESCE((SELECT SUM(amount) FROM customer_payment_allocations WHERE invoice_id = i.id AND is_reversed = 0), 0) as amount_paid
            FROM invoices i
            WHERE i.customer_id = :cid AND i.status != 'Voided' AND i.status != 'Paid'
            ORDER BY i.invoice_date ASC
        ");
        $this->db->bind(':cid', $customerId);
        $invoices = $this->db->resultSet();
        
        foreach ($invoices as $inv) {
            $inv->balance_due = $inv->total_amount - $inv->amount_paid;
        }
        
        // Return only invoices that have a remaining balance > 0
        return array_filter($invoices, function($inv) {
            return $inv->balance_due > 0.01;
        });
    }

    /**
     * Get unpaid or partially paid GRNs for a supplier
     */
    public function getSupplierUnpaidGRNs($vendorId) {
        $this->db->query("
            SELECT g.id, g.grn_number, g.receipt_number, g.grn_date,
                   COALESCE((SELECT SUM(total) FROM grn_items WHERE grn_id = g.id), 0) as total_amount,
                   COALESCE((SELECT SUM(amount) FROM supplier_payment_allocations WHERE grn_id = g.id AND is_reversed = 0), 0) as amount_paid
            FROM goods_receipt_notes g
            WHERE g.vendor_id = :vid AND g.is_approved = 1
            ORDER BY g.grn_date ASC
        ");
        $this->db->bind(':vid', $vendorId);
        $grns = $this->db->resultSet();
        
        foreach ($grns as $g) {
            $g->balance_due = $g->total_amount - $g->amount_paid;
        }
        
        // Return only GRNs that have a remaining balance > 0
        return array_filter($grns, function($g) {
            return $g->balance_due > 0.01;
        });
    }

    /**
     * Record a Customer Payment (AR Collection)
     */
    public function recordCustomerPayment($data, $userId) {
        try {
            $this->db->beginTransaction();

            // 1. Post Journal Entry
            $desc = "Customer Payment: " . $data['method'] . " from Customer ID " . $data['customer_id'];
            $this->db->query("INSERT INTO journal_entries (entry_date, reference, description, created_by, status) VALUES (:date, :ref, :desc, :uid, 'Posted')");
            $this->db->bind(':date', $data['date']);
            $this->db->bind(':ref', $data['reference']);
            $this->db->bind(':desc', $desc);
            $this->db->bind(':uid', $userId);
            $this->db->execute();
            $journalId = $this->db->lastInsertId();

            // 2. Post Transactions (Debit Asset Account, Credit Accounts Receivable)
            $lines = [
                ['account_id' => $data['asset_account_id'], 'debit' => $data['amount'], 'credit' => 0],
                ['account_id' => $data['ar_account_id'], 'debit' => 0, 'credit' => $data['amount']]
            ];

            foreach ($lines as $line) {
                $this->db->query("INSERT INTO transactions (journal_entry_id, account_id, debit, credit) VALUES (:jid, :aid, :deb, :cred)");
                $this->db->bind(':jid', $journalId);
                $this->db->bind(':aid', $line['account_id']);
                $this->db->bind(':deb', $line['debit']);
                $this->db->bind(':cred', $line['credit']);
                $this->db->execute();

                // Update Chart of Account Balance
                $this->db->query("SELECT account_type FROM chart_of_accounts WHERE id = :id");
                $this->db->bind(':id', $line['account_id']);
                $acc = $this->db->single();
                $sql = "UPDATE chart_of_accounts SET balance = balance ";
                $sql .= in_array($acc->account_type, ['Asset', 'Expense']) ? "+ :debit - :credit " : "- :debit + :credit ";
                $sql .= "WHERE id = :id";
                $this->db->query($sql);
                $this->db->bind(':debit', $line['debit']);
                $this->db->bind(':credit', $line['credit']);
                $this->db->bind(':id', $line['account_id']);
                $this->db->execute();
            }

            // 3. Save to customer_payments
            $this->db->query("INSERT INTO customer_payments (customer_id, amount, unallocated_amount, payment_date, payment_method, reference, notes, journal_entry_id, created_by, status) 
                              VALUES (:cid, :amt, :uamt, :date, :method, :ref, :notes, :jid, :uid, 'Active')");
            $this->db->bind(':cid', $data['customer_id']);
            $this->db->bind(':amt', $data['amount']);
            $this->db->bind(':uamt', $data['amount']); // initially all is unallocated, updated below
            $this->db->bind(':date', $data['date']);
            $this->db->bind(':method', $data['method']);
            $this->db->bind(':ref', $data['reference']);
            $this->db->bind(':notes', $data['notes'] ?? null);
            $this->db->bind(':jid', $journalId);
            $this->db->bind(':uid', $userId);
            $this->db->execute();
            $paymentId = $this->db->lastInsertId();

            // 4. Save Cheque details if applicable
            if ($data['method'] === 'Cheque') {
                $this->db->query("INSERT INTO cheques (customer_id, bank_name, cheque_number, amount, banking_date, status, created_by) 
                                  VALUES (:cid, :bn, :cn, :amt, :bdate, 'Pending', :uid)");
                $this->db->bind(':cid', $data['customer_id']);
                $this->db->bind(':bn', $data['cheque_bank']);
                $this->db->bind(':cn', $data['cheque_number']);
                $this->db->bind(':amt', $data['amount']);
                $this->db->bind(':bdate', $data['cheque_date']);
                $this->db->bind(':uid', $userId);
                $this->db->execute();
            }

            // 5. Handle Allocations
            $allocatedAmount = 0;
            if ($data['allocation_type'] === 'auto') {
                // Auto FIFO Clear
                $unpaid = $this->getCustomerUnpaidInvoices($data['customer_id']);
                $remaining = $data['amount'];
                foreach ($unpaid as $inv) {
                    if ($remaining <= 0.01) break;
                    
                    $allocAmt = min($remaining, $inv->balance_due);
                    
                    // Save allocation
                    $this->db->query("INSERT INTO customer_payment_allocations (customer_payment_id, invoice_id, amount) 
                                      VALUES (:pid, :inv_id, :amt)");
                    $this->db->bind(':pid', $paymentId);
                    $this->db->bind(':inv_id', $inv->id);
                    $this->db->bind(':amt', $allocAmt);
                    $this->db->execute();

                    // Check if invoice is fully paid
                    $newPaidSum = $inv->amount_paid + $allocAmt;
                    if ($newPaidSum >= $inv->total_amount - 0.01) {
                        $this->db->query("UPDATE invoices SET status = 'Paid' WHERE id = :id");
                        $this->db->bind(':id', $inv->id);
                        $this->db->execute();
                    }

                    $allocatedAmount += $allocAmt;
                    $remaining -= $allocAmt;
                }
            } elseif ($data['allocation_type'] === 'manual' && !empty($data['allocations'])) {
                // Manual Allocation
                $remaining = $data['amount'];
                foreach ($data['allocations'] as $invoiceId => $allocAmt) {
                    $allocAmt = floatval($allocAmt);
                    if ($allocAmt <= 0) continue;
                    if ($remaining <= 0.01) break;

                    $allocAmt = min($remaining, $allocAmt);

                    $this->db->query("INSERT INTO customer_payment_allocations (customer_payment_id, invoice_id, amount) 
                                      VALUES (:pid, :inv_id, :amt)");
                    $this->db->bind(':pid', $paymentId);
                    $this->db->bind(':inv_id', $invoiceId);
                    $this->db->bind(':amt', $allocAmt);
                    $this->db->execute();

                    // Retrieve invoice detail to check pay status
                    $this->db->query("
                        SELECT 
                            (total_amount - COALESCE(CASE WHEN global_discount_type = '%' THEN (total_amount * global_discount_val / 100) ELSE global_discount_val END, 0) + COALESCE(tax_amount, 0)) as total,
                            COALESCE((SELECT SUM(amount) FROM customer_payment_allocations WHERE invoice_id = :iid AND is_reversed = 0), 0) as paid
                        FROM invoices WHERE id = :iid2
                    ");
                    $this->db->bind(':iid', $invoiceId);
                    $this->db->bind(':iid2', $invoiceId);
                    $invCheck = $this->db->single();

                    if ($invCheck && $invCheck->paid >= $invCheck->total - 0.01) {
                        $this->db->query("UPDATE invoices SET status = 'Paid' WHERE id = :id");
                        $this->db->bind(':id', $invoiceId);
                        $this->db->execute();
                    }

                    $allocatedAmount += $allocAmt;
                    $remaining -= $allocAmt;
                }
            }

            // Update unallocated amount
            $unallocated = $data['amount'] - $allocatedAmount;
            $this->db->query("UPDATE customer_payments SET unallocated_amount = :uamt WHERE id = :pid");
            $this->db->bind(':uamt', $unallocated);
            $this->db->bind(':pid', $paymentId);
            $this->db->execute();

            $this->db->commit();
            return $paymentId;
        } catch (Throwable $e) {
            $this->db->rollBack();
            error_log("recordCustomerPayment Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Record a Supplier Payment (AP Payout)
     */
    public function recordSupplierPayment($data, $userId) {
        try {
            $this->db->beginTransaction();

            // 1. Post Journal Entry
            $desc = "Supplier Payment: " . $data['method'] . " to Supplier ID " . $data['supplier_id'];
            $this->db->query("INSERT INTO journal_entries (entry_date, reference, description, created_by, status) VALUES (:date, :ref, :desc, :uid, 'Posted')");
            $this->db->bind(':date', $data['date']);
            $this->db->bind(':ref', $data['reference']);
            $this->db->bind(':desc', $desc);
            $this->db->bind(':uid', $userId);
            $this->db->execute();
            $journalId = $this->db->lastInsertId();

            // 2. Post Transactions (Debit Accounts Payable, Credit Asset Account)
            $lines = [
                ['account_id' => $data['ap_account_id'], 'debit' => $data['amount'], 'credit' => 0],
                ['account_id' => $data['asset_account_id'], 'debit' => 0, 'credit' => $data['amount']]
            ];

            foreach ($lines as $line) {
                $this->db->query("INSERT INTO transactions (journal_entry_id, account_id, debit, credit) VALUES (:jid, :aid, :deb, :cred)");
                $this->db->bind(':jid', $journalId);
                $this->db->bind(':aid', $line['account_id']);
                $this->db->bind(':deb', $line['debit']);
                $this->db->bind(':cred', $line['credit']);
                $this->db->execute();

                // Update Chart of Account Balance
                $this->db->query("SELECT account_type FROM chart_of_accounts WHERE id = :id");
                $this->db->bind(':id', $line['account_id']);
                $acc = $this->db->single();
                $sql = "UPDATE chart_of_accounts SET balance = balance ";
                $sql .= in_array($acc->account_type, ['Asset', 'Expense']) ? "+ :debit - :credit " : "- :debit + :credit ";
                $sql .= "WHERE id = :id";
                $this->db->query($sql);
                $this->db->bind(':debit', $line['debit']);
                $this->db->bind(':credit', $line['credit']);
                $this->db->bind(':id', $line['account_id']);
                $this->db->execute();
            }

            // 3. Save to supplier_payments
            $this->db->query("INSERT INTO supplier_payments (vendor_id, amount, unallocated_amount, payment_date, payment_method, reference, notes, journal_entry_id, created_by, status) 
                              VALUES (:vid, :amt, :uamt, :date, :method, :ref, :notes, :jid, :uid, 'Active')");
            $this->db->bind(':vid', $data['supplier_id']);
            $this->db->bind(':amt', $data['amount']);
            $this->db->bind(':uamt', $data['amount']);
            $this->db->bind(':date', $data['date']);
            $this->db->bind(':method', $data['method']);
            $this->db->bind(':ref', $data['reference']);
            $this->db->bind(':notes', $data['notes'] ?? null);
            $this->db->bind(':jid', $journalId);
            $this->db->bind(':uid', $userId);
            $this->db->execute();
            $paymentId = $this->db->lastInsertId();

            // 4. Save Cheque details if applicable
            if ($data['method'] === 'Cheque') {
                $this->db->query("INSERT INTO cheques (vendor_id, customer_id, bank_name, cheque_number, amount, banking_date, status, created_by) 
                                  VALUES (:vid, NULL, :bn, :cn, :amt, :bdate, 'Pending', :uid)");
                $this->db->bind(':vid', $data['supplier_id']);
                $this->db->bind(':bn', $data['cheque_bank']);
                $this->db->bind(':cn', $data['cheque_number']);
                $this->db->bind(':amt', $data['amount']);
                $this->db->bind(':bdate', $data['cheque_date']);
                $this->db->bind(':uid', $userId);
                $this->db->execute();
            }

            // 5. Handle Allocations
            $allocatedAmount = 0;
            if ($data['allocation_type'] === 'auto') {
                $unpaid = $this->getSupplierUnpaidGRNs($data['supplier_id']);
                $remaining = $data['amount'];
                foreach ($unpaid as $g) {
                    if ($remaining <= 0.01) break;
                    
                    $allocAmt = min($remaining, $g->balance_due);
                    
                    // Save allocation
                    $this->db->query("INSERT INTO supplier_payment_allocations (supplier_payment_id, grn_id, amount) 
                                      VALUES (:pid, :grn_id, :amt)");
                    $this->db->bind(':pid', $paymentId);
                    $this->db->bind(':grn_id', $g->id);
                    $this->db->bind(':amt', $allocAmt);
                    $this->db->execute();

                    $allocatedAmount += $allocAmt;
                    $remaining -= $allocAmt;
                }
            } elseif ($data['allocation_type'] === 'manual' && !empty($data['allocations'])) {
                $remaining = $data['amount'];
                foreach ($data['allocations'] as $grnId => $allocAmt) {
                    $allocAmt = floatval($allocAmt);
                    if ($allocAmt <= 0) continue;
                    if ($remaining <= 0.01) break;

                    $allocAmt = min($remaining, $allocAmt);

                    $this->db->query("INSERT INTO supplier_payment_allocations (supplier_payment_id, grn_id, amount) 
                                      VALUES (:pid, :grn_id, :amt)");
                    $this->db->bind(':pid', $paymentId);
                    $this->db->bind(':grn_id', $grnId);
                    $this->db->bind(':amt', $allocAmt);
                    $this->db->execute();

                    $allocatedAmount += $allocAmt;
                    $remaining -= $allocAmt;
                }
            }

            // Update unallocated amount
            $unallocated = $data['amount'] - $allocatedAmount;
            $this->db->query("UPDATE supplier_payments SET unallocated_amount = :uamt WHERE id = :pid");
            $this->db->bind(':uamt', $unallocated);
            $this->db->bind(':pid', $paymentId);
            $this->db->execute();

            $this->db->commit();
            return $paymentId;
        } catch (Throwable $e) {
            $this->db->rollBack();
            error_log("recordSupplierPayment Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Reverse Customer Payment
     */
    public function reverseCustomerPayment($id, $userId) {
        try {
            $this->db->beginTransaction();

            // 1. Get Payment details
            $this->db->query("SELECT * FROM customer_payments WHERE id = :id");
            $this->db->bind(':id', $id);
            $payment = $this->db->single();

            if (!$payment || $payment->status === 'Reversed') {
                throw new Exception("Payment not found or already reversed.");
            }

            // 2. Fetch original journal entry to reverse debits/credits
            $this->db->query("SELECT * FROM transactions WHERE journal_entry_id = :jid");
            $this->db->bind(':jid', $payment->journal_entry_id);
            $txs = $this->db->resultSet();

            if (empty($txs)) {
                throw new Exception("Original transactions not found.");
            }

            // 3. Post Reversing Journal Entry
            $desc = "Reversal: Customer Payment ID " . $payment->id;
            $this->db->query("INSERT INTO journal_entries (entry_date, reference, description, created_by, status) VALUES (CURRENT_DATE(), :ref, :desc, :uid, 'Posted')");
            $this->db->bind(':ref', 'REV-' . $payment->reference);
            $this->db->bind(':desc', $desc);
            $this->db->bind(':uid', $userId);
            $this->db->execute();
            $revJournalId = $this->db->lastInsertId();

            // Reversing allocations: Debit AR Account (original Credit), Credit Asset Account (original Debit)
            foreach ($txs as $tx) {
                $revDebit = $tx->credit;
                $revCredit = $tx->debit;

                $this->db->query("INSERT INTO transactions (journal_entry_id, account_id, debit, credit) VALUES (:jid, :aid, :deb, :cred)");
                $this->db->bind(':jid', $revJournalId);
                $this->db->bind(':aid', $tx->account_id);
                $this->db->bind(':deb', $revDebit);
                $this->db->bind(':cred', $revCredit);
                $this->db->execute();

                // Update Chart of Account balance in reverse
                $this->db->query("SELECT account_type FROM chart_of_accounts WHERE id = :id");
                $this->db->bind(':id', $tx->account_id);
                $acc = $this->db->single();
                $sql = "UPDATE chart_of_accounts SET balance = balance ";
                $sql .= in_array($acc->account_type, ['Asset', 'Expense']) ? "+ :debit - :credit " : "- :debit + :credit ";
                $sql .= "WHERE id = :id";
                $this->db->query($sql);
                $this->db->bind(':debit', $revDebit);
                $this->db->bind(':credit', $revCredit);
                $this->db->bind(':id', $tx->account_id);
                $this->db->execute();
            }

            // 4. Mark payment as Reversed
            $this->db->query("UPDATE customer_payments SET status = 'Reversed', reversed_by = :uid, reversed_at = CURRENT_TIMESTAMP() WHERE id = :id");
            $this->db->bind(':uid', $userId);
            $this->db->bind(':id', $id);
            $this->db->execute();

            // 5. If it was a cheque payment, find and mark cheque as Bounced / Voided
            if ($payment->payment_method === 'Cheque') {
                $this->db->query("UPDATE cheques SET status = 'Bounced' WHERE customer_id = :cid AND amount = :amt AND cheque_number = (
                    SELECT cheque_number FROM (
                        SELECT cheque_number FROM cheques WHERE customer_id = :cid2 AND amount = :amt2 ORDER BY id DESC LIMIT 1
                    ) as tmp
                )");
                $this->db->bind(':cid', $payment->customer_id);
                $this->db->bind(':amt', $payment->amount);
                $this->db->bind(':cid2', $payment->customer_id);
                $this->db->bind(':amt2', $payment->amount);
                $this->db->execute();
            }

            // 6. Reverse Allocations
            $this->db->query("SELECT invoice_id FROM customer_payment_allocations WHERE customer_payment_id = :pid AND is_reversed = 0");
            $this->db->bind(':pid', $id);
            $allocs = $this->db->resultSet();

            $this->db->query("UPDATE customer_payment_allocations SET is_reversed = 1, reversed_by = :uid, reversed_at = CURRENT_TIMESTAMP() WHERE customer_payment_id = :pid");
            $this->db->bind(':uid', $userId);
            $this->db->bind(':pid', $id);
            $this->db->execute();

            // 7. For each previously allocated invoice, set status back to Unpaid
            foreach ($allocs as $a) {
                $this->db->query("UPDATE invoices SET status = 'Unpaid' WHERE id = :id");
                $this->db->bind(':id', $a->invoice_id);
                $this->db->execute();
            }

            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            $this->db->rollBack();
            error_log("reverseCustomerPayment Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Reverse Supplier Payment
     */
    public function reverseSupplierPayment($id, $userId) {
        try {
            $this->db->beginTransaction();

            // 1. Get Payment details
            $this->db->query("SELECT * FROM supplier_payments WHERE id = :id");
            $this->db->bind(':id', $id);
            $payment = $this->db->single();

            if (!$payment || $payment->status === 'Reversed') {
                throw new Exception("Payment not found or already reversed.");
            }

            // 2. Fetch original journal entry to reverse debits/credits
            $this->db->query("SELECT * FROM transactions WHERE journal_entry_id = :jid");
            $this->db->bind(':jid', $payment->journal_entry_id);
            $txs = $this->db->resultSet();

            if (empty($txs)) {
                throw new Exception("Original transactions not found.");
            }

            // 3. Post Reversing Journal Entry
            $desc = "Reversal: Supplier Payment ID " . $payment->id;
            $this->db->query("INSERT INTO journal_entries (entry_date, reference, description, created_by, status) VALUES (CURRENT_DATE(), :ref, :desc, :uid, 'Posted')");
            $this->db->bind(':ref', 'REV-' . $payment->reference);
            $this->db->bind(':desc', $desc);
            $this->db->bind(':uid', $userId);
            $this->db->execute();
            $revJournalId = $this->db->lastInsertId();

            // Reversing: Debit Asset Account (original Credit), Credit AP Account (original Debit)
            foreach ($txs as $tx) {
                $revDebit = $tx->credit;
                $revCredit = $tx->debit;

                $this->db->query("INSERT INTO transactions (journal_entry_id, account_id, debit, credit) VALUES (:jid, :aid, :deb, :cred)");
                $this->db->bind(':jid', $revJournalId);
                $this->db->bind(':aid', $tx->account_id);
                $this->db->bind(':deb', $revDebit);
                $this->db->bind(':cred', $revCredit);
                $this->db->execute();

                // Update Chart of Account balance in reverse
                $this->db->query("SELECT account_type FROM chart_of_accounts WHERE id = :id");
                $this->db->bind(':id', $tx->account_id);
                $acc = $this->db->single();
                $sql = "UPDATE chart_of_accounts SET balance = balance ";
                $sql .= in_array($acc->account_type, ['Asset', 'Expense']) ? "+ :debit - :credit " : "- :debit + :credit ";
                $sql .= "WHERE id = :id";
                $this->db->query($sql);
                $this->db->bind(':debit', $revDebit);
                $this->db->bind(':credit', $revCredit);
                $this->db->bind(':id', $tx->account_id);
                $this->db->execute();
            }

            // 4. Mark payment as Reversed
            $this->db->query("UPDATE supplier_payments SET status = 'Reversed', reversed_by = :uid, reversed_at = CURRENT_TIMESTAMP() WHERE id = :id");
            $this->db->bind(':uid', $userId);
            $this->db->bind(':id', $id);
            $this->db->execute();

            // 5. If it was a cheque payment, find and mark cheque as Bounced / Voided
            if ($payment->payment_method === 'Cheque') {
                $this->db->query("UPDATE cheques SET status = 'Bounced' WHERE vendor_id = :vid AND amount = :amt AND cheque_number = (
                    SELECT cheque_number FROM (
                        SELECT cheque_number FROM cheques WHERE vendor_id = :vid2 AND amount = :amt2 ORDER BY id DESC LIMIT 1
                    ) as tmp
                )");
                $this->db->bind(':vid', $payment->vendor_id);
                $this->db->bind(':amt', $payment->amount);
                $this->db->bind(':vid2', $payment->vendor_id);
                $this->db->bind(':amt2', $payment->amount);
                $this->db->execute();
            }

            // 6. Reverse Allocations
            $this->db->query("UPDATE supplier_payment_allocations SET is_reversed = 1, reversed_by = :uid, reversed_at = CURRENT_TIMESTAMP() WHERE supplier_payment_id = :pid");
            $this->db->bind(':uid', $userId);
            $this->db->bind(':pid', $id);
            $this->db->execute();

            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            $this->db->rollBack();
            error_log("reverseSupplierPayment Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get Customer Payment Details by ID
     */
    public function getCustomerPaymentById($id) {
        $this->db->query("
            SELECT p.*, c.name as customer_name, c.email as customer_email, c.phone as customer_phone, c.address as customer_address, u.username as creator_name, ur.username as reverser_name
            FROM customer_payments p
            JOIN customers c ON p.customer_id = c.id
            LEFT JOIN users u ON p.created_by = u.id
            LEFT JOIN users ur ON p.reversed_by = ur.id
            WHERE p.id = :id
        ");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    /**
     * Get Supplier Payment Details by ID
     */
    public function getSupplierPaymentById($id) {
        $this->db->query("
            SELECT p.*, v.name as supplier_name, v.email as supplier_email, v.phone as supplier_phone, v.address as supplier_address, u.username as creator_name, ur.username as reverser_name
            FROM supplier_payments p
            JOIN vendors v ON p.vendor_id = v.id
            LEFT JOIN users u ON p.created_by = u.id
            LEFT JOIN users ur ON p.reversed_by = ur.id
            WHERE p.id = :id
        ");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    /**
     * Get allocations for customer payment
     */
    public function getCustomerPaymentAllocations($paymentId) {
        $this->db->query("
            SELECT a.*, i.invoice_number, i.invoice_date
            FROM customer_payment_allocations a
            JOIN invoices i ON a.invoice_id = i.id
            WHERE a.customer_payment_id = :pid
        ");
        $this->db->bind(':pid', $paymentId);
        return $this->db->resultSet();
    }

    /**
     * Get allocations for supplier payment
     */
    public function getSupplierPaymentAllocations($paymentId) {
        $this->db->query("
            SELECT a.*, g.grn_number, g.grn_date
            FROM supplier_payment_allocations a
            JOIN goods_receipt_notes g ON a.grn_id = g.id
            WHERE a.supplier_payment_id = :pid
        ");
        $this->db->bind(':pid', $paymentId);
        return $this->db->resultSet();
    }

    /**
     * Get Customer Statement
     */
    public function getCustomerStatement($customerId, $startDate = '', $endDate = '') {
        $sql = "
            SELECT 'Invoice' as type, id, invoice_number as ref, invoice_date as date, 
            (total_amount - COALESCE(CASE WHEN global_discount_type = '%' THEN (total_amount * global_discount_val / 100) ELSE global_discount_val END, 0) + COALESCE(tax_amount, 0)) as debit, 
            0 as credit, created_at 
            FROM invoices WHERE customer_id = :c1 AND status != 'Voided'
            UNION ALL
            SELECT 'Payment' as type, id, CONCAT(payment_method, IF(reference != '', CONCAT(' (', reference, ')'), '')) as ref, payment_date as date, 0 as debit, amount as credit, created_at 
            FROM customer_payments WHERE customer_id = :c2 AND status = 'Active'
            UNION ALL
            SELECT 'Credit Note' as type, id, credit_note_number as ref, note_date as date, 0 as debit, total_amount as credit, created_at 
            FROM credit_notes WHERE customer_id = :c3
            ORDER BY date ASC, created_at ASC
        ";
        $this->db->query($sql);
        $this->db->bind(':c1', $customerId);
        $this->db->bind(':c2', $customerId);
        $this->db->bind(':c3', $customerId);
        $ledger = $this->db->resultSet();

        $balance = 0;
        $filteredLedger = [];
        
        foreach ($ledger as $row) {
            $balance += $row->debit;
            $balance -= $row->credit;
            $row->balance = $balance;

            // Apply date filters after computing running balance to keep running balance accurate
            $rowDate = $row->date;
            if (!empty($startDate) && $rowDate < $startDate) continue;
            if (!empty($endDate) && $rowDate > $endDate) continue;

            $filteredLedger[] = $row;
        }
        
        return array_reverse($filteredLedger);
    }

    /**
     * Get Supplier Statement
     */
    public function getSupplierStatement($vendorId, $startDate = '', $endDate = '') {
        $sql = "
            SELECT 'GRN' as type, grn.id, grn.grn_number as ref, grn.grn_date as date,
                   0 as debit, 
                   (SELECT COALESCE(SUM(total), 0) FROM grn_items WHERE grn_id = grn.id) as credit, 
                   grn.created_at
            FROM goods_receipt_notes grn 
            WHERE grn.vendor_id = :vid1 AND grn.is_approved = 1
            UNION ALL
            SELECT 'Expense' as type, id, CONCAT('Exp: ', IF(reference != '', reference, 'No Ref')) as ref, expense_date as date,
                   amount as debit, 0 as credit, created_at
            FROM expenses 
            WHERE vendor_id = :vid2
            UNION ALL
            SELECT 'Payment' as type, id, CONCAT('Pay: ', payment_method, IF(reference != '', CONCAT(' (', reference, ')'), '')) as ref, payment_date as date,
                   amount as debit, 0 as credit, created_at
            FROM supplier_payments 
            WHERE vendor_id = :vid3 AND status = 'Active'
            UNION ALL
            SELECT 'Supplier Return' as type, id, credit_note_number as ref, note_date as date,
                   total_amount as debit, 0 as credit, created_at
            FROM supplier_returns 
            WHERE vendor_id = :vid4
            ORDER BY date ASC, created_at ASC
        ";
        $this->db->query($sql);
        $this->db->bind(':vid1', $vendorId);
        $this->db->bind(':vid2', $vendorId);
        $this->db->bind(':vid3', $vendorId);
        $this->db->bind(':vid4', $vendorId);
        $ledger = $this->db->resultSet();

        $balance = 0;
        $filteredLedger = [];

        foreach ($ledger as $row) {
            $balance += $row->credit;
            $balance -= $row->debit;
            $row->balance = $balance;

            // Apply date filters after computing running balance to keep running balance accurate
            $rowDate = $row->date;
            if (!empty($startDate) && $rowDate < $startDate) continue;
            if (!empty($endDate) && $rowDate > $endDate) continue;

            $filteredLedger[] = $row;
        }

        return array_reverse($filteredLedger);
    }

    /**
     * Get Unified Payment History
     */
    public function getUnifiedPaymentHistory($filters = []) {
        // Customer payments query
        $sqlCustomer = "
            SELECT 'Customer' as type, p.id, p.payment_date, p.payment_method, p.reference, p.amount, p.status, c.name as counterparty_name, u.username as creator_name
            FROM customer_payments p
            JOIN customers c ON p.customer_id = c.id
            LEFT JOIN users u ON p.created_by = u.id
            WHERE 1=1
        ";
        
        // Supplier payments query
        $sqlSupplier = "
            SELECT 'Supplier' as type, p.id, p.payment_date, p.payment_method, p.reference, p.amount, p.status, v.name as counterparty_name, u.username as creator_name
            FROM supplier_payments p
            JOIN vendors v ON p.vendor_id = v.id
            LEFT JOIN users u ON p.created_by = u.id
            WHERE 1=1
        ";

        if (!empty($filters['start_date'])) {
            $sqlCustomer .= " AND p.payment_date >= :start_customer";
            $sqlSupplier .= " AND p.payment_date >= :start_supplier";
        }
        if (!empty($filters['end_date'])) {
            $sqlCustomer .= " AND p.payment_date <= :end_customer";
            $sqlSupplier .= " AND p.payment_date <= :end_supplier";
        }
        if (!empty($filters['method'])) {
            $sqlCustomer .= " AND p.payment_method = :method_customer";
            $sqlSupplier .= " AND p.payment_method = :method_supplier";
        }

        $sql = "($sqlCustomer) UNION ALL ($sqlSupplier) ORDER BY payment_date DESC, id DESC LIMIT :limit OFFSET :offset";

        $this->db->query($sql);
        
        if (!empty($filters['start_date'])) {
            $this->db->bind(':start_customer', $filters['start_date']);
            $this->db->bind(':start_supplier', $filters['start_date']);
        }
        if (!empty($filters['end_date'])) {
            $this->db->bind(':end_customer', $filters['end_date']);
            $this->db->bind(':end_supplier', $filters['end_date']);
        }
        if (!empty($filters['method'])) {
            $this->db->bind(':method_customer', $filters['method']);
            $this->db->bind(':method_supplier', $filters['method']);
        }

        $limit = $filters['limit'] ?? 50;
        $offset = $filters['offset'] ?? 0;
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        $this->db->bind(':offset', $offset, PDO::PARAM_INT);

        return $this->db->resultSet();
    }

    /**
     * Get Customer-only Payment History
     */
    public function getCustomerPaymentHistory($filters = []) {
        $sql = "
            SELECT 'Customer' as type, p.id, p.payment_date, p.payment_method, p.reference, p.amount, p.status, c.name as counterparty_name, u.username as creator_name
            FROM customer_payments p
            JOIN customers c ON p.customer_id = c.id
            LEFT JOIN users u ON p.created_by = u.id
            WHERE 1=1
        ";

        if (!empty($filters['start_date'])) {
            $sql .= " AND p.payment_date >= :start_date";
        }
        if (!empty($filters['end_date'])) {
            $sql .= " AND p.payment_date <= :end_date";
        }
        if (!empty($filters['method'])) {
            $sql .= " AND p.payment_method = :method";
        }

        $sql .= " ORDER BY payment_date DESC, id DESC LIMIT :limit OFFSET :offset";
        $this->db->query($sql);

        if (!empty($filters['start_date'])) {
            $this->db->bind(':start_date', $filters['start_date']);
        }
        if (!empty($filters['end_date'])) {
            $this->db->bind(':end_date', $filters['end_date']);
        }
        if (!empty($filters['method'])) {
            $this->db->bind(':method', $filters['method']);
        }

        $limit = $filters['limit'] ?? 50;
        $offset = $filters['offset'] ?? 0;
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        $this->db->bind(':offset', $offset, PDO::PARAM_INT);

        return $this->db->resultSet();
    }

    /**
     * Get Supplier-only Payment History
     */
    public function getSupplierPaymentHistory($filters = []) {
        $sql = "
            SELECT 'Supplier' as type, p.id, p.payment_date, p.payment_method, p.reference, p.amount, p.status, v.name as counterparty_name, u.username as creator_name
            FROM supplier_payments p
            JOIN vendors v ON p.vendor_id = v.id
            LEFT JOIN users u ON p.created_by = u.id
            WHERE 1=1
        ";

        if (!empty($filters['start_date'])) {
            $sql .= " AND p.payment_date >= :start_date";
        }
        if (!empty($filters['end_date'])) {
            $sql .= " AND p.payment_date <= :end_date";
        }
        if (!empty($filters['method'])) {
            $sql .= " AND p.payment_method = :method";
        }

        $sql .= " ORDER BY payment_date DESC, id DESC LIMIT :limit OFFSET :offset";
        $this->db->query($sql);

        if (!empty($filters['start_date'])) {
            $this->db->bind(':start_date', $filters['start_date']);
        }
        if (!empty($filters['end_date'])) {
            $this->db->bind(':end_date', $filters['end_date']);
        }
        if (!empty($filters['method'])) {
            $this->db->bind(':method', $filters['method']);
        }

        $limit = $filters['limit'] ?? 50;
        $offset = $filters['offset'] ?? 0;
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        $this->db->bind(':offset', $offset, PDO::PARAM_INT);

        return $this->db->resultSet();
    }

    /**
     * Settle Customer unpaid invoices using their available advance credit balance
     */
    public function settleCustomerInvoicesWithCredit($customerId, $userId) {
        try {
            $this->db->beginTransaction();

            // 1. Get available active customer payments with unallocated_amount > 0
            $this->db->query("SELECT * FROM customer_payments WHERE customer_id = :cid AND status = 'Active' AND unallocated_amount > 0 ORDER BY payment_date ASC");
            $this->db->bind(':cid', $customerId);
            $credits = $this->db->resultSet();

            if (empty($credits)) {
                throw new Exception("No available customer credit found.");
            }

            // 2. Get unpaid invoices
            $unpaid = $this->getCustomerUnpaidInvoices($customerId);
            if (empty($unpaid)) {
                throw new Exception("No unpaid invoices found for this customer.");
            }

            foreach ($credits as $cred) {
                $remainingCredit = $cred->unallocated_amount;
                
                foreach ($unpaid as &$inv) {
                    if ($remainingCredit <= 0.01) break;
                    if ($inv->balance_due <= 0.01) continue;

                    $allocAmt = min($remainingCredit, $inv->balance_due);

                    // Create allocation record
                    $this->db->query("INSERT INTO customer_payment_allocations (customer_payment_id, invoice_id, amount) 
                                      VALUES (:pid, :inv_id, :amt)");
                    $this->db->bind(':pid', $cred->id);
                    $this->db->bind(':inv_id', $inv->id);
                    $this->db->bind(':amt', $allocAmt);
                    $this->db->execute();

                    $inv->amount_paid += $allocAmt;
                    $inv->balance_due -= $allocAmt;
                    $remainingCredit -= $allocAmt;

                    // Update invoice status if fully paid
                    if ($inv->amount_paid >= $inv->total_amount - 0.01) {
                        $this->db->query("UPDATE invoices SET status = 'Paid' WHERE id = :id");
                        $this->db->bind(':id', $inv->id);
                        $this->db->execute();
                    }
                }

                // Update unallocated amount on the payment
                $this->db->query("UPDATE customer_payments SET unallocated_amount = :uamt WHERE id = :pid");
                $this->db->bind(':uamt', $remainingCredit);
                $this->db->bind(':pid', $cred->id);
                $this->db->execute();
            }

            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            $this->db->rollBack();
            error_log("settleCustomerInvoicesWithCredit Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Settle Supplier unpaid GRNs using their available advance credit balance
     */
    public function settleSupplierGRNsWithCredit($vendorId, $userId) {
        try {
            $this->db->beginTransaction();

            // 1. Get available active supplier payments with unallocated_amount > 0
            $this->db->query("SELECT * FROM supplier_payments WHERE vendor_id = :vid AND status = 'Active' AND unallocated_amount > 0 ORDER BY payment_date ASC");
            $this->db->bind(':vid', $vendorId);
            $credits = $this->db->resultSet();

            if (empty($credits)) {
                throw new Exception("No available supplier credit found.");
            }

            // 2. Get unpaid GRNs
            $unpaid = $this->getSupplierUnpaidGRNs($vendorId);
            if (empty($unpaid)) {
                throw new Exception("No unpaid GRNs found for this supplier.");
            }

            foreach ($credits as $cred) {
                $remainingCredit = $cred->unallocated_amount;

                foreach ($unpaid as &$g) {
                    if ($remainingCredit <= 0.01) break;
                    if ($g->balance_due <= 0.01) continue;

                    $allocAmt = min($remainingCredit, $g->balance_due);

                    // Create allocation record
                    $this->db->query("INSERT INTO supplier_payment_allocations (supplier_payment_id, grn_id, amount) 
                                      VALUES (:pid, :grn_id, :amt)");
                    $this->db->bind(':pid', $cred->id);
                    $this->db->bind(':grn_id', $g->id);
                    $this->db->bind(':amt', $allocAmt);
                    $this->db->execute();

                    $g->amount_paid += $allocAmt;
                    $g->balance_due -= $allocAmt;
                    $remainingCredit -= $allocAmt;
                }

                // Update unallocated amount on the payment
                $this->db->query("UPDATE supplier_payments SET unallocated_amount = :uamt WHERE id = :pid");
                $this->db->bind(':uamt', $remainingCredit);
                $this->db->bind(':pid', $cred->id);
                $this->db->execute();
            }

            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            $this->db->rollBack();
            error_log("settleSupplierGRNsWithCredit Error: " . $e->getMessage());
            return false;
        }
    }
}
