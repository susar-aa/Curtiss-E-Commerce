<?php
class BankingController extends Controller {
    private $coaModel;
    private $journalModel;

    public function __construct() {
        if (!isset($_SESSION['user_id'])) { header('Location: ' . APP_URL . '/auth/login'); exit; }
        $this->coaModel = $this->model('ChartOfAccount');
        $this->journalModel = $this->model('JournalEntry');
    }

    // Displays the list of all Bank/Cash accounts
    public function index() {
        $db = new Database();
        
        // Self-heal: Ensure 1600 parent exists
        $db->query("SELECT * FROM chart_of_accounts WHERE account_code = '1600'");
        $parent = $db->single();
        if (!$parent) {
            $db->query("INSERT INTO chart_of_accounts (account_code, account_name, account_type, parent_id, balance) 
                        VALUES ('1600', 'Bank Current Account', 'Asset', NULL, 0)");
            $db->execute();
            $parentId = $db->lastInsertId();
        } else {
            $parentId = $parent->id;
        }

        // Self-heal: Ensure 1605 temporary bank account exists under 1600 parent
        $db->query("SELECT * FROM chart_of_accounts WHERE account_code = '1605'");
        $tempAcc = $db->single();
        if (!$tempAcc) {
            $db->query("INSERT INTO chart_of_accounts (account_code, account_name, account_type, parent_id, balance) 
                        VALUES ('1605', 'Temporary Bank Account', 'Asset', :pid, 0)");
            $db->bind(':pid', $parentId);
            $db->execute();
            $tempAccId = $db->lastInsertId();
        } else {
            $tempAccId = $tempAcc->id;
        }

        $data = [
            'title' => 'Banking & Cash',
            'content_view' => 'banking/index',
            'bank_accounts' => [],
            'cash_accounts' => [],
            'error' => '',
            'success' => ''
        ];

        // Handle Bank Account Actions & Transfers
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
            $action = $_POST['action'];
            
            if ($action == 'add_bank') {
                $name = trim($_POST['account_name']);
                if (empty($name)) {
                    $data['error'] = 'Bank Account Name is required.';
                } else {
                    $db->query("SELECT MAX(CAST(account_code AS UNSIGNED)) as max_code FROM chart_of_accounts WHERE account_code LIKE '16%' AND account_code != '1600'");
                    $row = $db->single();
                    $nextCode = $row && $row->max_code ? intval($row->max_code) + 1 : 1601;
                    if ($nextCode == 1605) {
                        $nextCode = 1606; // skip reserved 1605
                    }
                    
                    $db->query("INSERT INTO chart_of_accounts (account_code, account_name, account_type, parent_id, balance, is_active) 
                                VALUES (:code, :name, 'Asset', :pid, 0, 1)");
                    $db->bind(':code', (string)$nextCode);
                    $db->bind(':name', $name);
                    $db->bind(':pid', $parentId);
                    
                    if ($db->execute()) {
                        header('Location: ' . APP_URL . '/banking?success=bank_added');
                        exit;
                    } else {
                        $data['error'] = 'Failed to add bank account.';
                    }
                }
            }
            elseif ($action == 'edit_bank') {
                $id = intval($_POST['bank_id']);
                $name = trim($_POST['account_name']);
                if (empty($name)) {
                    $data['error'] = 'Bank Account Name is required.';
                } else {
                    $db->query("UPDATE chart_of_accounts SET account_name = :name WHERE id = :id");
                    $db->bind(':name', $name);
                    $db->bind(':id', $id);
                    if ($db->execute()) {
                        header('Location: ' . APP_URL . '/banking?success=bank_edited');
                        exit;
                    } else {
                        $data['error'] = 'Failed to update bank account.';
                    }
                }
            }
            elseif ($action == 'delete_bank') {
                $id = intval($_POST['bank_id']);
                
                $db->query("SELECT COUNT(*) as tx_count FROM transactions WHERE account_id = :id");
                $txCheck = $db->single();
                
                if ($txCheck && $txCheck->tx_count > 0) {
                    $data['error'] = 'Audit Protection: Bank accounts with transaction history cannot be deleted to preserve financial audit history.';
                } else {
                    $db->query("DELETE FROM chart_of_accounts WHERE id = :id");
                    $db->bind(':id', $id);
                    if ($db->execute()) {
                        header('Location: ' . APP_URL . '/banking?success=bank_deleted');
                        exit;
                    } else {
                        $data['error'] = 'Failed to delete bank account.';
                    }
                }
            }
            elseif ($action == 'transfer_funds') {
                $fromAccId = intval($_POST['from_account_id']);
                $toAccId = intval($_POST['to_account_id']);
                $amount = floatval($_POST['amount']);
                $date = $_POST['entry_date'];
                $description = trim($_POST['description']);
                
                if ($fromAccId == $toAccId) {
                    $data['error'] = 'Source and Destination bank accounts must be different.';
                } elseif ($amount <= 0) {
                    $data['error'] = 'Transfer amount must be greater than zero.';
                } else {
                    $reference = 'BK-XFER-' . time();
                    $lines = [
                        ['account_id' => $toAccId, 'debit' => $amount, 'credit' => 0],
                        ['account_id' => $fromAccId, 'debit' => 0, 'credit' => $amount]
                    ];
                    
                    if ($this->journalModel->postEntry($date, $reference, $description, $lines, $_SESSION['user_id'])) {
                        header('Location: ' . APP_URL . '/banking?success=transfer_completed');
                        exit;
                    } else {
                        $data['error'] = 'Failed to record inter-bank transfer in ledger.';
                    }
                }
            }
            elseif ($action == 'quick_entry') {
                $type = $_POST['type']; // 'deposit' or 'withdrawal'
                $bankAccountId = $_POST['bank_account_id'];
                $offsetAccountId = $_POST['offset_account_id'];
                $amount = floatval($_POST['amount']);
                $date = $_POST['entry_date'];
                $description = trim($_POST['description']);

                if ($amount <= 0) {
                    $data['error'] = 'Amount must be greater than zero.';
                } else {
                    if ($type == 'deposit') {
                        $lines = [
                            ['account_id' => $bankAccountId, 'debit' => $amount, 'credit' => 0],
                            ['account_id' => $offsetAccountId, 'debit' => 0, 'credit' => $amount]
                        ];
                    } else {
                        $lines = [
                            ['account_id' => $offsetAccountId, 'debit' => $amount, 'credit' => 0],
                            ['account_id' => $bankAccountId, 'debit' => 0, 'credit' => $amount]
                        ];
                    }

                    $reference = strtoupper($type) . '-' . time();

                    if ($this->journalModel->postEntry($date, $reference, $description, $lines, $_SESSION['user_id'])) {
                        header('Location: ' . APP_URL . '/banking?success=quick_entry_completed');
                        exit;
                    } else {
                        $data['error'] = 'Failed to post transaction to ledger.';
                    }
                }
            }
        }

        // Fetch bank accounts (sub-accounts of parent 1600)
        $db->query("SELECT * FROM chart_of_accounts WHERE parent_id = :pid ORDER BY account_code ASC");
        $db->bind(':pid', $parentId);
        $data['bank_accounts'] = $db->resultSet() ?: [];

        // Fetch cash accounts (other assets)
        $db->query("SELECT * FROM chart_of_accounts WHERE account_type = 'Asset' AND (parent_id IS NULL OR parent_id != :pid) AND id != :pid ORDER BY account_code ASC");
        $db->bind(':pid', $parentId);
        $data['cash_accounts'] = $db->resultSet() ?: [];

        $this->view('layouts/main', $data);
    }

    // View detailed transaction history for a specific account
    public function ledger($accountId = null) {
        if (!$accountId) {
            header('Location: ' . APP_URL . '/banking');
            exit;
        }

        $account = $this->coaModel->getAccountById($accountId);
        if (!$account) { die("Account not found."); }

        $db = new Database();
        $db->query("SELECT t.*, je.entry_date, je.reference, je.description 
                    FROM transactions t 
                    JOIN journal_entries je ON t.journal_entry_id = je.id 
                    WHERE t.account_id = :aid 
                    ORDER BY je.entry_date DESC, je.id DESC");
        $db->bind(':aid', $accountId);
        $transactions = $db->resultSet();

        $data = [
            'title' => 'Account Ledger: ' . $account->account_name,
            'content_view' => 'banking/ledger',
            'account' => $account,
            'transactions' => $transactions
        ];

        $this->view('layouts/main', $data);
    }

    // --- NEW: Bank Reconciliation Screen ---
    public function reconcile($accountId = null) {
        if (!$accountId) {
            header('Location: ' . APP_URL . '/banking');
            exit;
        }

        $account = $this->coaModel->getAccountById($accountId);
        if (!$account || $account->account_type !== 'Asset') {
            die("Invalid account selected for reconciliation.");
        }

        $data = [
            'title' => 'Reconcile: ' . $account->account_name,
            'content_view' => 'banking/reconcile',
            'account' => $account,
            'uncleared' => $this->coaModel->getUnclearedTransactions($accountId),
            'statement_balance' => isset($_POST['statement_balance']) ? floatval($_POST['statement_balance']) : $account->balance,
            'error' => '',
            'success' => ''
        ];

        // Handle the submission of checked transactions
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'save_reconciliation') {
            $statementBalance = floatval($_POST['statement_balance'] ?? 0.0);
            $clearedTx = $_POST['cleared_tx'] ?? [];

            if (!empty($clearedTx)) {
                // Fetch current uncleared to calculate and validate
                $unclearedList = $this->coaModel->getUnclearedTransactions($accountId);
                $unclearedMap = [];
                $totalUnclearedImpact = 0.0;
                foreach ($unclearedList as $t) {
                    $unclearedMap[$t->id] = $t;
                    $totalUnclearedImpact += ($t->debit > 0) ? floatval($t->debit) : -floatval($t->credit);
                }

                $validCleared = [];
                $selectedDeposits = 0.0;
                $selectedPayments = 0.0;

                foreach ($clearedTx as $txId) {
                    $txId = intval($txId);
                    if (isset($unclearedMap[$txId])) {
                        $validCleared[] = $txId;
                        $t = $unclearedMap[$txId];
                        $impact = ($t->debit > 0) ? floatval($t->debit) : -floatval($t->credit);
                        if ($impact > 0) {
                            $selectedDeposits += $impact;
                        } else {
                            $selectedPayments += abs($impact);
                        }
                    }
                }

                if (count($validCleared) !== count($clearedTx)) {
                    $data['error'] = 'Data Integrity Error: One or more selected transactions are invalid or already cleared.';
                } else {
                    $startingClearedBalance = floatval($account->balance) - $totalUnclearedImpact;
                    $newClearedBalance = $startingClearedBalance + $selectedDeposits - $selectedPayments;
                    $difference = $statementBalance - $newClearedBalance;

                    if (abs($difference) >= 0.01) {
                        $data['error'] = 'Reconciliation Error: Selected transactions do not match the statement ending balance (Difference: Rs ' . number_format($difference, 2) . ').';
                    } else {
                        if ($this->coaModel->clearTransactions($validCleared)) {
                            $data['success'] = count($validCleared) . ' transaction(s) have been successfully reconciled and cleared!';
                            $data['statement_balance'] = $statementBalance;
                            $data['uncleared'] = $this->coaModel->getUnclearedTransactions($accountId);
                        } else {
                            $data['error'] = 'Database error: Failed to update transaction status.';
                        }
                    }
                }
            } else {
                 $data['error'] = 'No transactions were selected to clear.';
            }
        }

        $this->view('layouts/main', $data);
    }
}