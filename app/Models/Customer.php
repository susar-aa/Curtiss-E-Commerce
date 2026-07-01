<?php
class Customer {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function getAllCustomers() {
        // FIXED: Dynamically calculates the True Grand Total, adds opening_balance, and JOINS the mca_areas table for filtering
        $this->db->query("
            SELECT c.*, m.name as mca_name,
                   c.opening_balance + 
                   (SELECT COALESCE(SUM(total_amount - COALESCE(CASE WHEN global_discount_type = '%' THEN (total_amount * global_discount_val / 100) ELSE global_discount_val END, 0) + COALESCE(tax_amount, 0)), 0) FROM invoices WHERE customer_id = c.id AND status != 'Voided') 
                   - 
                   (SELECT COALESCE(SUM(amount), 0) FROM customer_payments WHERE customer_id = c.id AND status = 'Active') 
                   - 
                   (SELECT COALESCE(SUM(total_amount), 0) FROM credit_notes WHERE customer_id = c.id) 
                   AS outstanding_balance
            FROM customers c 
            LEFT JOIN mca_areas m ON c.mca_id = m.id
            ORDER BY c.name ASC
        ");
        return $this->db->resultSet();
    }

    public function getCustomerById($id) {
        $this->db->query("SELECT c.*, m.name as mca_name 
                          FROM customers c 
                          LEFT JOIN mca_areas m ON c.mca_id = m.id 
                          WHERE c.id = :id");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    public function getCustomerStats($id) {
        $this->db->query("SELECT opening_balance FROM customers WHERE id = :id");
        $this->db->bind(':id', $id);
        $custObj = $this->db->single();
        $opening = $custObj ? floatval($custObj->opening_balance) : 0.00;

        $this->db->query("
            SELECT 
                COUNT(id) as total_orders,
                COALESCE(SUM(total_amount - COALESCE(CASE WHEN global_discount_type = '%' THEN (total_amount * global_discount_val / 100) ELSE global_discount_val END, 0) + COALESCE(tax_amount, 0)), 0) as total_billed
            FROM invoices WHERE customer_id = :id AND status != 'Voided'
        ");
        $this->db->bind(':id', $id);
        $stats = $this->db->single();

        $this->db->query("SELECT COALESCE(SUM(amount), 0) as total_paid FROM customer_payments WHERE customer_id = :id AND status = 'Active'");
        $this->db->bind(':id', $id);
        $paid = $this->db->single();
        
        $this->db->query("SELECT COALESCE(SUM(total_amount), 0) as total_credited FROM credit_notes WHERE customer_id = :id");
        $this->db->bind(':id', $id);
        $credited = $this->db->single();

        $stats->total_paid = $paid->total_paid;
        $stats->total_credited = $credited->total_credited;
        $stats->opening_balance = $opening;
        $stats->outstanding = $opening + $stats->total_billed - $stats->total_paid - $stats->total_credited;
        
        return $stats;
    }

    public function getActivityLedger($id) {
        // Combines Invoices and Payments into a single chronological timeline using the fixed math
        $sql = "
            SELECT 'Invoice' as type, id, invoice_number as ref, invoice_date as date, 
            (total_amount - COALESCE(CASE WHEN global_discount_type = '%' THEN (total_amount * global_discount_val / 100) ELSE global_discount_val END, 0) + COALESCE(tax_amount, 0)) as debit, 
            0 as credit, created_at 
            FROM invoices WHERE customer_id = :c1 AND status != 'Voided'
            UNION ALL
            SELECT 'Payment' as type, id, CONCAT(payment_method, IF(reference != '', CONCAT(' (', reference, ')'), '')) as ref, payment_date as date, 0 as debit, amount as credit, created_at 
            FROM customer_payments WHERE customer_id = :c2
            UNION ALL
            SELECT 'Credit Note' as type, id, credit_note_number as ref, note_date as date, 0 as debit, total_amount as credit, created_at 
            FROM credit_notes WHERE customer_id = :c3
            ORDER BY date ASC, created_at ASC
        ";
        $this->db->query($sql);
        $this->db->bind(':c1', $id);
        $this->db->bind(':c2', $id);
        $this->db->bind(':c3', $id);
        $ledger = $this->db->resultSet();

        $balance = 0;
        foreach($ledger as $row) {
            $balance += $row->debit;
            $balance -= $row->credit;
            $row->balance = $balance;
        }
        
        return array_reverse($ledger);
    }

    public function getMcaAreas() {
        $this->db->query("SELECT * FROM mca_areas ORDER BY name ASC");
        return $this->db->resultSet();
    }

    public function addCustomer($data) {
        $this->db->query("INSERT INTO customers (name, email, phone, whatsapp, address, latitude, longitude, mca_id, territory, credit_limit, customer_type, notes, uuid, opening_balance) 
                          VALUES (:name, :email, :phone, :whatsapp, :address, :lat, :lng, :mca_id, :territory, :credit_limit, :customer_type, :notes, :uuid, :opening_balance)");
        $this->db->bind(':name', $data['name']);
        $this->db->bind(':email', $data['email'] ?: null);
        $this->db->bind(':phone', $data['phone'] ?: null);
        $this->db->bind(':whatsapp', $data['whatsapp'] ?: null);
        $this->db->bind(':address', $data['address'] ?: null);
        $this->db->bind(':lat', $data['lat'] ?: null);
        $this->db->bind(':lng', $data['lng'] ?: null);
        $this->db->bind(':mca_id', $data['mca_id'] ?: null);
        $this->db->bind(':territory', $data['territory'] ?: null);
        $this->db->bind(':credit_limit', $data['credit_limit'] ?? 0.00);
        $this->db->bind(':customer_type', $data['customer_type'] ?? 'Standard');
        $this->db->bind(':notes', $data['notes'] ?: null);
        $this->db->bind(':uuid', $data['uuid'] ?? null);
        $this->db->bind(':opening_balance', $data['opening_balance'] ?? 0.00);
        return $this->db->execute();
    }

    public function getLastInsertId() {
        return $this->db->lastInsertId();
    }

    public function updateCustomer($data) {
        $this->db->query("UPDATE customers SET name = :name, email = :email, phone = :phone, whatsapp = :whatsapp, address = :address, 
                          latitude = :lat, longitude = :lng, mca_id = :mca_id, territory = :territory, 
                          credit_limit = :credit_limit, customer_type = :customer_type, notes = :notes, uuid = :uuid, opening_balance = :opening_balance
                          WHERE id = :id");
        $this->db->bind(':id', $data['id']);
        $this->db->bind(':name', $data['name']);
        $this->db->bind(':email', $data['email'] ?: null);
        $this->db->bind(':phone', $data['phone'] ?: null);
        $this->db->bind(':whatsapp', $data['whatsapp'] ?: null);
        $this->db->bind(':address', $data['address'] ?: null);
        $this->db->bind(':lat', $data['lat'] ?: null);
        $this->db->bind(':lng', $data['lng'] ?: null);
        $this->db->bind(':mca_id', $data['mca_id'] ?: null);
        $this->db->bind(':territory', $data['territory'] ?: null);
        $this->db->bind(':credit_limit', $data['credit_limit'] ?? 0.00);
        $this->db->bind(':customer_type', $data['customer_type'] ?? 'Standard');
        $this->db->bind(':notes', $data['notes'] ?: null);
        $this->db->bind(':uuid', $data['uuid'] ?? null);
        $this->db->bind(':opening_balance', $data['opening_balance'] ?? 0.00);
        return $this->db->execute();
    }

    public function recordPayment($data, $userId) {
        try {
            $this->db->beginTransaction();

            $desc = "Payment Received: " . $data['method'] . " from Customer ID " . $data['customer_id'];
            $this->db->query("INSERT INTO journal_entries (entry_date, reference, description, created_by, status) VALUES (:date, :ref, :desc, :uid, 'Posted')");
            $this->db->bind(':date', $data['date']);
            $this->db->bind(':ref', $data['reference']);
            $this->db->bind(':desc', $desc);
            $this->db->bind(':uid', $userId);
            $this->db->execute();
            $journalId = $this->db->lastInsertId();

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

            $this->db->query("INSERT INTO customer_payments (customer_id, amount, payment_date, payment_method, reference, journal_entry_id, created_by) 
                              VALUES (:cid, :amt, :date, :method, :ref, :jid, :uid)");
            $this->db->bind(':cid', $data['customer_id']);
            $this->db->bind(':amt', $data['amount']);
            $this->db->bind(':date', $data['date']);
            $this->db->bind(':method', $data['method']);
            $this->db->bind(':ref', $data['reference']);
            $this->db->bind(':jid', $journalId);
            $this->db->bind(':uid', $userId);
            $this->db->execute();

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

            $this->db->query("SELECT * FROM invoices WHERE customer_id = :cid AND status IN ('Unpaid', 'Draft') ORDER BY invoice_date ASC");
            $this->db->bind(':cid', $data['customer_id']);
            $unpaid = $this->db->resultSet();
            
            $remaining = $data['amount'];
            foreach($unpaid as $inv) {
                // Accurately calculate the exact Grand Total for the invoice
                $trueGrandTotal = $inv->total_amount;
                if ($inv->global_discount_val > 0) {
                    if ($inv->global_discount_type == '%') {
                        $trueGrandTotal -= ($inv->total_amount * ($inv->global_discount_val / 100));
                    } else {
                        $trueGrandTotal -= $inv->global_discount_val;
                    }
                }
                $trueGrandTotal += $inv->tax_amount;

                if ($remaining >= $trueGrandTotal - 0.01) { 
                    $this->db->query("UPDATE invoices SET status = 'Paid' WHERE id = :id");
                    $this->db->bind(':id', $inv->id);
                    $this->db->execute();
                    $remaining -= $trueGrandTotal;
                }
            }

            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function getCustomerInvoices($customerId, $limit = 5) {
        $this->db->query("SELECT * FROM invoices WHERE customer_id = :cid ORDER BY invoice_date DESC LIMIT :limit");
        $this->db->bind(':cid', $customerId);
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        return $this->db->resultSet();
    }

    public function getCustomerCheques($customerId, $limit = 5) {
        $this->db->query("SELECT * FROM cheques WHERE customer_id = :cid ORDER BY banking_date DESC LIMIT :limit");
        $this->db->bind(':cid', $customerId);
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        return $this->db->resultSet();
    }

    public function deleteCustomer($id) {
        $this->db->query("DELETE FROM customers WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }
}