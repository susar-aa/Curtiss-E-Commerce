<?php
class Payroll {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function getAllPayrollRuns() {
        $this->db->query("SELECT p.*, u.username 
                          FROM payroll_runs p 
                          JOIN users u ON p.created_by = u.id 
                          ORDER BY p.run_date DESC");
        return $this->db->resultSet();
    }

    public function processPayroll($periodStart, $periodEnd, $runDate, $totalGross, $wageExpenseAccId, $bankAccId, $userId) {
        try {
            $this->db->beginTransaction();

            // 1. Post Journal Entry Header
            $desc = "Payroll Run: " . date('M d', strtotime($periodStart)) . " to " . date('M d, Y', strtotime($periodEnd));
            $reference = "PR-" . time();
            
            $this->db->query("INSERT INTO journal_entries (entry_date, reference, description, created_by, status) 
                              VALUES (:date, :ref, :desc, :user, 'Posted')");
            $this->db->bind(':date', $runDate);
            $this->db->bind(':ref', $reference);
            $this->db->bind(':desc', $desc);
            $this->db->bind(':user', $userId);
            $this->db->execute();
            $journalId = $this->db->lastInsertId();

            // 2. Post Journal Lines (Debit Wage Expense, Credit Bank)
            $lines = [
                ['account_id' => $wageExpenseAccId, 'debit' => $totalGross, 'credit' => 0],
                ['account_id' => $bankAccId, 'debit' => 0, 'credit' => $totalGross]
            ];

            foreach ($lines as $line) {
                $this->db->query("INSERT INTO transactions (journal_entry_id, account_id, debit, credit) 
                                  VALUES (:jid, :aid, :deb, :cred)");
                $this->db->bind(':jid', $journalId);
                $this->db->bind(':aid', $line['account_id']);
                $this->db->bind(':deb', $line['debit']);
                $this->db->bind(':cred', $line['credit']);
                $this->db->execute();

                // Update COA Balances
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

            // 3. Create Payroll Header record
            $this->db->query("INSERT INTO payroll_runs (run_date, period_start, period_end, total_gross, journal_entry_id, created_by) 
                              VALUES (:rdate, :pstart, :pend, :gross, :jid, :uid)");
            $this->db->bind(':rdate', $runDate);
            $this->db->bind(':pstart', $periodStart);
            $this->db->bind(':pend', $periodEnd);
            $this->db->bind(':gross', $totalGross);
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