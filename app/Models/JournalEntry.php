<?php
class JournalEntry {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function getAllEntries() {
        $this->db->query("SELECT je.*, u.username 
                          FROM journal_entries je 
                          JOIN users u ON je.created_by = u.id 
                          ORDER BY je.entry_date DESC, je.id DESC");
        return $this->db->resultSet();
    }

    public function postEntry($date, $reference, $description, $lines, $userId) {
        try {
            // Check if period is closed/locked
            $this->db->query("SELECT COUNT(*) as cnt FROM financial_years WHERE end_date >= :entry_date");
            $this->db->bind(':entry_date', $date);
            $res = $this->db->single();
            if ($res && $res->cnt > 0) {
                return false;
            }

            $this->db->beginTransaction();

            $this->db->query("INSERT INTO journal_entries (entry_date, reference, description, created_by) 
                              VALUES (:entry_date, :reference, :description, :created_by)");
            $this->db->bind(':entry_date', $date);
            $this->db->bind(':reference', $reference);
            $this->db->bind(':description', $description);
            $this->db->bind(':created_by', $userId);
            $this->db->execute();
            
            $journalId = $this->db->lastInsertId();

            foreach ($lines as $line) {
                $this->db->query("INSERT INTO transactions (journal_entry_id, account_id, debit, credit) 
                                  VALUES (:journal_id, :account_id, :debit, :credit)");
                $this->db->bind(':journal_id', $journalId);
                $this->db->bind(':account_id', $line['account_id']);
                $this->db->bind(':debit', $line['debit']);
                $this->db->bind(':credit', $line['credit']);
                $this->db->execute();

                $this->db->query("SELECT account_type FROM chart_of_accounts WHERE id = :id");
                $this->db->bind(':id', $line['account_id']);
                $account = $this->db->single();

                $balanceUpdateSql = "UPDATE chart_of_accounts SET balance = balance ";
                if (in_array($account->account_type, ['Asset', 'Expense'])) {
                    $balanceUpdateSql .= "+ :debit - :credit ";
                } else {
                    $balanceUpdateSql .= "- :debit + :credit ";
                }
                $balanceUpdateSql .= "WHERE id = :id";

                $this->db->query($balanceUpdateSql);
                $this->db->bind(':debit', $line['debit']);
                $this->db->bind(':credit', $line['credit']);
                $this->db->bind(':id', $line['account_id']);
                $this->db->execute();
            }

            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function closeFinancialYear($endDate, $userId, $retainedEarningsAccId) {
        try {
            $this->db->beginTransaction();

            // 1. Calculate P&L balances for unclosed entries up to $endDate
            $this->db->query("SELECT t.account_id, c.account_type, SUM(t.debit) as total_debit, SUM(t.credit) as total_credit 
                              FROM transactions t
                              JOIN journal_entries je ON t.journal_entry_id = je.id
                              JOIN chart_of_accounts c ON t.account_id = c.id
                              WHERE je.is_closed = 0 AND je.entry_date <= :end_date 
                              AND c.account_type IN ('Revenue', 'Expense')
                              GROUP BY t.account_id");
            $this->db->bind(':end_date', $endDate);
            $plAccounts = $this->db->resultSet();

            if(empty($plAccounts)) {
                $this->db->rollBack();
                return "No unclosed P&L transactions found for this period.";
            }

            // 2. Create Closing Journal Entry Header
            $desc = "Financial Year Closing Entry as of " . $endDate;
            $ref = "YE-CLOSE-" . date('Y', strtotime($endDate));
            $this->db->query("INSERT INTO journal_entries (entry_date, reference, description, created_by, status, is_closed) 
                              VALUES (:date, :ref, :desc, :user, 'Posted', 1)"); // Closing entry is automatically locked
            $this->db->bind(':date', $endDate);
            $this->db->bind(':ref', $ref);
            $this->db->bind(':desc', $desc);
            $this->db->bind(':user', $userId);
            $this->db->execute();
            $journalId = $this->db->lastInsertId();

            $netIncome = 0;
            
            // 3. Post zeroing lines for Revenue & Expense
            foreach($plAccounts as $acc) {
                // Net balance logic: Normal Rev is positive credit, Normal Exp is positive debit
                $balance = $acc->total_credit - $acc->total_debit; 
                if (round($balance, 2) == 0) continue;

                $netIncome += $balance;

                // To zero out: if balance is positive (Credit), we must Debit it.
                $debit = $balance > 0 ? $balance : 0;
                $credit = $balance < 0 ? abs($balance) : 0;

                $this->db->query("INSERT INTO transactions (journal_entry_id, account_id, debit, credit) VALUES (:jid, :aid, :deb, :cred)");
                $this->db->bind(':jid', $journalId);
                $this->db->bind(':aid', $acc->account_id);
                $this->db->bind(':deb', $debit);
                $this->db->bind(':cred', $credit);
                $this->db->execute();

                // Zero out the COA balance
                $this->db->query("UPDATE chart_of_accounts SET balance = 0 WHERE id = :id");
                $this->db->bind(':id', $acc->account_id);
                $this->db->execute();
            }

            // 4. Post offsetting entry to Retained Earnings
            if (round($netIncome, 2) != 0) {
                $reDebit = $netIncome < 0 ? abs($netIncome) : 0;
                $reCredit = $netIncome > 0 ? $netIncome : 0;

                $this->db->query("INSERT INTO transactions (journal_entry_id, account_id, debit, credit) VALUES (:jid, :aid, :deb, :cred)");
                $this->db->bind(':jid', $journalId);
                $this->db->bind(':aid', $retainedEarningsAccId);
                $this->db->bind(':deb', $reDebit);
                $this->db->bind(':cred', $reCredit);
                $this->db->execute();

                // Update Retained Earnings balance
                $this->db->query("UPDATE chart_of_accounts SET balance = balance - :deb + :cred WHERE id = :id");
                $this->db->bind(':deb', $reDebit);
                $this->db->bind(':cred', $reCredit);
                $this->db->bind(':id', $retainedEarningsAccId);
                $this->db->execute();
            }

            // 5. Lock all old journal entries
            $this->db->query("UPDATE journal_entries SET is_closed = 1 WHERE entry_date <= :end_date AND is_closed = 0");
            $this->db->bind(':end_date', $endDate);
            $this->db->execute();

            // 6. Record financial year in tracking table
            $this->db->query("INSERT INTO financial_years (year_name, end_date, closed_by) VALUES (:name, :end_date, :uid)");
            $this->db->bind(':name', 'FY ending ' . $endDate);
            $this->db->bind(':end_date', $endDate);
            $this->db->bind(':uid', $userId);
            $this->db->execute();

            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            return "Database Error: " . $e->getMessage();
        }
    }

    public function voidEntry($id) {
        try {
            // 1. Fetch the journal entry
            $this->db->query("SELECT * FROM journal_entries WHERE id = :id");
            $this->db->bind(':id', $id);
            $entry = $this->db->single();
            if (!$entry) return false;
            if ($entry->status === 'Voided') return true; // Already voided
            if ($entry->is_closed) return false; // Cannot void a closed financial year entry

            $this->db->beginTransaction();

            // 2. Fetch all transaction lines for this journal entry
            $this->db->query("SELECT * FROM transactions WHERE journal_entry_id = :id");
            $this->db->bind(':id', $id);
            $lines = $this->db->resultSet();

            // 3. Revert impact on COA balances
            foreach ($lines as $line) {
                $this->db->query("SELECT account_type FROM chart_of_accounts WHERE id = :aid");
                $this->db->bind(':aid', $line->account_id);
                $account = $this->db->single();

                $balanceUpdateSql = "UPDATE chart_of_accounts SET balance = balance ";
                if (in_array($account->account_type, ['Asset', 'Expense'])) {
                    $balanceUpdateSql .= "- :debit + :credit ";
                } else {
                    $balanceUpdateSql .= "+ :debit - :credit ";
                }
                $balanceUpdateSql .= "WHERE id = :aid";

                $this->db->query($balanceUpdateSql);
                $this->db->bind(':debit', $line->debit);
                $this->db->bind(':credit', $line->credit);
                $this->db->bind(':aid', $line->account_id);
                $this->db->execute();
            }

            // 4. Update journal entry status to Voided
            $this->db->query("UPDATE journal_entries SET status = 'Voided' WHERE id = :id");
            $this->db->bind(':id', $id);
            $this->db->execute();

            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            return false;
        }
    }
}