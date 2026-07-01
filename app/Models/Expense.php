<?php
class Expense {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function getAllExpenses() {
        $this->db->query("SELECT e.*, v.name as vendor_name 
                          FROM expenses e 
                          LEFT JOIN vendors v ON e.vendor_id = v.id 
                          ORDER BY e.expense_date DESC, e.id DESC");
        return $this->db->resultSet();
    }

    public function createExpenseWithAccounting($data, $expenseAccountId, $paymentAccountId, $userId) {
        try {
            $this->db->beginTransaction();

            // 1. Post Journal Entry Header
            $desc = "Expense recorded: " . $data['description'];
            $this->db->query("INSERT INTO journal_entries (entry_date, reference, description, created_by, status) 
                              VALUES (:date, :ref, :desc, :user, 'Posted')");
            $this->db->bind(':date', $data['expense_date']);
            $this->db->bind(':ref', $data['reference']);
            $this->db->bind(':desc', $desc);
            $this->db->bind(':user', $userId);
            $this->db->execute();
            
            // Fixed: Using the new public method
            $journalId = $this->db->lastInsertId();

            // 2. Post Journal Lines (Debit Expense, Credit Asset/Bank/Liability)
            $lines = [
                ['account_id' => $expenseAccountId, 'debit' => $data['amount'], 'credit' => 0],
                ['account_id' => $paymentAccountId, 'debit' => 0, 'credit' => $data['amount']]
            ];

            foreach ($lines as $line) {
                $this->db->query("INSERT INTO transactions (journal_entry_id, account_id, debit, credit) 
                                  VALUES (:jid, :aid, :deb, :cred)");
                $this->db->bind(':jid', $journalId);
                $this->db->bind(':aid', $line['account_id']);
                $this->db->bind(':deb', $line['debit']);
                $this->db->bind(':cred', $line['credit']);
                $this->db->execute();

                // 3. Update Chart of Accounts Balance Instantly
                $this->db->query("SELECT account_type FROM chart_of_accounts WHERE id = :id");
                $this->db->bind(':id', $line['account_id']);
                $acc = $this->db->single();

                $sql = "UPDATE chart_of_accounts SET balance = balance ";
                if (in_array($acc->account_type, ['Asset', 'Expense'])) {
                    $sql .= "+ :debit - :credit ";
                } else {
                    $sql .= "- :debit + :credit ";
                }
                $sql .= "WHERE id = :id";
                
                $this->db->query($sql);
                $this->db->bind(':debit', $line['debit']);
                $this->db->bind(':credit', $line['credit']);
                $this->db->bind(':id', $line['account_id']);
                $this->db->execute();
            }

            // 4. Create Expense Header
            $this->db->query("INSERT INTO expenses (reference, vendor_id, expense_date, amount, description, journal_entry_id, created_by) 
                              VALUES (:ref, :vid, :edate, :amt, :desc, :jid, :uid)");
            $this->db->bind(':ref', $data['reference']);
            $this->db->bind(':vid', $data['vendor_id'] ?: null);
            $this->db->bind(':edate', $data['expense_date']);
            $this->db->bind(':amt', $data['amount']);
            $this->db->bind(':desc', $data['description']);
            $this->db->bind(':jid', $journalId);
            $this->db->bind(':uid', $userId);
            $this->db->execute();

            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            return false;
        }
    }
}