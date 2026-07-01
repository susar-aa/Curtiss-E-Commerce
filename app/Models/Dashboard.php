<?php
class Dashboard {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    // Get Total Revenue from the Ledger
    public function getTotalRevenue() {
        $this->db->query("SELECT SUM(balance) as total FROM chart_of_accounts WHERE account_type = 'Revenue'");
        $row = $this->db->single();
        return $row->total ?? 0;
    }

    // Get Total Expenses from the Ledger
    public function getTotalExpenses() {
        $this->db->query("SELECT SUM(balance) as total FROM chart_of_accounts WHERE account_type = 'Expense'");
        $row = $this->db->single();
        return $row->total ?? 0;
    }

    // Get Total Accounts Receivable (Money owed to you)
    public function getTotalAR() {
        $this->db->query("SELECT SUM(balance) as total FROM chart_of_accounts WHERE account_type = 'Asset' AND account_name LIKE '%Receivable%'");
        $row = $this->db->single();
        return $row->total ?? 0;
    }

    // Get Recent Transactions (Journal Entries)
    public function getRecentActivity($limit = 5) {
        $this->db->query("SELECT je.*, u.username 
                          FROM journal_entries je 
                          JOIN users u ON je.created_by = u.id 
                          ORDER BY je.entry_date DESC, je.id DESC 
                          LIMIT :limit");
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        return $this->db->resultSet();
    }
}