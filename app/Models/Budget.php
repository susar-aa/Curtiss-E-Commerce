<?php
class Budget {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    // Get all expense accounts and their current year budget
    public function getExpenseBudgets($year) {
        $this->db->query("SELECT c.id as account_id, c.account_code, c.account_name, c.balance as actual_spent, 
                                 COALESCE(b.budget_amount, 0) as budget_amount
                          FROM chart_of_accounts c
                          LEFT JOIN budgets b ON c.id = b.account_id AND b.fiscal_year = :year
                          WHERE c.account_type = 'Expense'
                          ORDER BY c.account_code ASC");
        $this->db->bind(':year', $year);
        return $this->db->resultSet();
    }

    // Insert or Update the budget for a specific account and year
    public function setBudget($accountId, $year, $amount) {
        // Uses MySQL ON DUPLICATE KEY UPDATE to either insert a new budget or update an existing one
        $this->db->query("INSERT INTO budgets (account_id, fiscal_year, budget_amount) 
                          VALUES (:aid, :year, :amount) 
                          ON DUPLICATE KEY UPDATE budget_amount = :amount2");
        $this->db->bind(':aid', $accountId);
        $this->db->bind(':year', $year);
        $this->db->bind(':amount', $amount);
        $this->db->bind(':amount2', $amount);
        
        return $this->db->execute();
    }
}