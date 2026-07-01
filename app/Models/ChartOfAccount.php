<?php
class ChartOfAccount {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function getAccounts() {
        $this->db->query("SELECT c.*, p.account_name as parent_name 
                          FROM chart_of_accounts c 
                          LEFT JOIN chart_of_accounts p ON c.parent_id = p.id 
                          ORDER BY FIELD(c.account_type, 'Asset', 'Liability', 'Equity', 'Revenue', 'Expense'), c.account_code ASC");
        return $this->db->resultSet();
    }

    public function getAccountById($id) {
        $this->db->query("SELECT * FROM chart_of_accounts WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    public function addAccount($data) {
        $this->db->query("INSERT INTO chart_of_accounts (account_code, account_name, account_type, parent_id) 
                          VALUES (:account_code, :account_name, :account_type, :parent_id)");
        
        $this->db->bind(':account_code', $data['account_code']);
        $this->db->bind(':account_name', $data['account_name']);
        $this->db->bind(':account_type', $data['account_type']);
        $this->db->bind(':parent_id', !empty($data['parent_id']) ? $data['parent_id'] : null);

        try { return $this->db->execute(); } catch (PDOException $e) { return false; }
    }

    public function updateAccount($data) {
        $this->db->query("UPDATE chart_of_accounts 
                          SET account_code = :code, account_name = :name, account_type = :type, parent_id = :pid, is_active = :status 
                          WHERE id = :id");
        $this->db->bind(':id', $data['id']);
        $this->db->bind(':code', $data['account_code']);
        $this->db->bind(':name', $data['account_name']);
        $this->db->bind(':type', $data['account_type']);
        $this->db->bind(':pid', !empty($data['parent_id']) ? $data['parent_id'] : null);
        $this->db->bind(':status', $data['is_active']);
        
        try {
            $res = $this->db->execute();
            if ($res) {
                $this->cascadeAccountType($data['id'], $data['account_type']);
            }
            return $res;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function cascadeAccountType($parentId, $type) {
        $tempDb = new Database();
        $tempDb->query("SELECT id FROM chart_of_accounts WHERE parent_id = :parent_id");
        $tempDb->bind(':parent_id', $parentId);
        $children = $tempDb->resultSet();

        if (!empty($children)) {
            $tempDb->query("UPDATE chart_of_accounts SET account_type = :type WHERE parent_id = :parent_id");
            $tempDb->bind(':type', $type);
            $tempDb->bind(':parent_id', $parentId);
            $tempDb->execute();

            foreach ($children as $child) {
                $this->cascadeAccountType($child->id, $type);
            }
        }
    }

    public function deleteAccount($id) {
        $this->db->query("DELETE FROM chart_of_accounts WHERE id = :id");
        $this->db->bind(':id', $id);
        try { return $this->db->execute(); } catch (PDOException $e) { return false; }
    }

    /**
     * Compute cumulative prior transaction sums before a starting date
     */
    public function getPriorBalance($accountId, $startDate) {
        $this->db->query("SELECT SUM(t.debit) as total_debit, SUM(t.credit) as total_credit 
                          FROM transactions t 
                          JOIN journal_entries je ON t.journal_entry_id = je.id 
                          WHERE t.account_id = :account_id AND je.entry_date < :start_date AND je.status = 'Posted'");
        $this->db->bind(':account_id', $accountId);
        $this->db->bind(':start_date', $startDate);
        $row = $this->db->single();
        return $row ? $row : (object)['total_debit' => 0, 'total_credit' => 0];
    }

    /**
     * Fetch filtered chronological transaction entries for account ledger
     */
    public function getAccountHistory($accountId, $filters = []) {
        $sql = "SELECT t.*, je.entry_date, je.reference, je.description, inv.id as invoice_id, inv.invoice_number 
                FROM transactions t 
                JOIN journal_entries je ON t.journal_entry_id = je.id 
                LEFT JOIN invoices inv ON je.id = inv.journal_entry_id 
                WHERE t.account_id = :account_id AND je.status = 'Posted'";
        
        $params = [':account_id' => $accountId];

        if (!empty($filters['start_date'])) {
            $sql .= " AND je.entry_date >= :start_date";
            $params[':start_date'] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $sql .= " AND je.entry_date <= :end_date";
            $params[':end_date'] = $filters['end_date'];
        }
        if (!empty($filters['search'])) {
            $sql .= " AND (je.reference LIKE :search OR je.description LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        if (isset($filters['tx_type'])) {
            if ($filters['tx_type'] === 'debit') {
                $sql .= " AND t.debit > 0";
            } elseif ($filters['tx_type'] === 'credit') {
                $sql .= " AND t.credit > 0";
            }
        }

        $sql .= " ORDER BY je.entry_date ASC, t.id ASC";

        $this->db->query($sql);
        foreach ($params as $param => $val) {
            $this->db->bind($param, $val);
        }
        return $this->db->resultSet() ?: [];
    }

    // --- Bank Reconciliation Methods ---
    public function getUnclearedTransactions($accountId) {
        $this->db->query("SELECT t.*, je.entry_date, je.reference, je.description 
                          FROM transactions t 
                          JOIN journal_entries je ON t.journal_entry_id = je.id 
                          WHERE t.account_id = :aid AND t.is_cleared = 0 
                          ORDER BY je.entry_date ASC");
        $this->db->bind(':aid', $accountId);
        return $this->db->resultSet();
    }

    public function clearTransactions($transactionIds) {
        if (empty($transactionIds)) return true;
        $placeholders = [];
        foreach($transactionIds as $key => $val) { $placeholders[] = ":id" . $key; }
        $placeholderString = implode(',', $placeholders);
        
        $this->db->query("UPDATE transactions SET is_cleared = 1 WHERE id IN ($placeholderString)");
        foreach($transactionIds as $key => $val) { $this->db->bind(":id" . $key, $val); }
        
        try { return $this->db->execute(); } catch (PDOException $e) { return false; }
    }
}