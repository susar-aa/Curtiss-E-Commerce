<?php
class AccountingController extends Controller {
    private $coaModel;
    private $journalModel;

    public function __construct() {
        if (!isset($_SESSION['user_id'])) { header('Location: ' . APP_URL . '/auth/login'); exit; }
        $this->coaModel = $this->model('ChartOfAccount');
        $this->journalModel = $this->model('JournalEntry');
    }

    public function coa() {
        $data = [
            'title' => 'Chart of Accounts',
            'content_view' => 'accounting/coa',
            'accounts' => [],
            'main_accounts' => [],
            'error' => '',
            'success' => ''
        ];

        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            
            if ($_POST['action'] == 'add_main') {
                $postData = [
                    'account_code' => trim($_POST['account_code']),
                    'account_name' => trim($_POST['account_name']),
                    'account_type' => trim($_POST['account_type']),
                    'parent_id' => null
                ];
                if (!empty($postData['account_code']) && !empty($postData['account_name'])) {
                    if ($this->coaModel->addAccount($postData)) { $data['success'] = 'Main Account added successfully.'; } 
                    else { $data['error'] = 'Failed to add account. Account Code may already exist.'; }
                } else { $data['error'] = 'Code and Name are required.'; }
            }
            elseif ($_POST['action'] == 'add_sub') {
                $parent = $this->coaModel->getAccountById($_POST['parent_id']);
                if ($parent) {
                    $postData = [
                        'account_code' => trim($_POST['account_code']),
                        'account_name' => trim($_POST['account_name']),
                        'account_type' => $parent->account_type, // Sub-accounts MUST inherit the parent's type
                        'parent_id' => $parent->id
                    ];
                    if ($this->coaModel->addAccount($postData)) { $data['success'] = 'Sub-Account added successfully.'; } 
                    else { $data['error'] = 'Failed to add account. Account Code may already exist.'; }
                } else {
                    $data['error'] = 'Invalid parent account selected.';
                }
            }
            elseif ($_POST['action'] == 'edit_account') {
                $postData = [
                    'id' => $_POST['account_id'],
                    'account_code' => trim($_POST['account_code']),
                    'account_name' => trim($_POST['account_name']),
                    'account_type' => trim($_POST['account_type']),
                    'parent_id' => !empty($_POST['parent_id']) ? $_POST['parent_id'] : null,
                    'is_active' => isset($_POST['is_active']) ? 1 : 0
                ];
                
                // If moving under a parent, enforce parent's type mathematically
                if ($postData['parent_id']) {
                    $parent = $this->coaModel->getAccountById($postData['parent_id']);
                    if ($parent) $postData['account_type'] = $parent->account_type;
                }

                if ($this->coaModel->updateAccount($postData)) { $data['success'] = 'Account updated successfully.'; } 
                else { $data['error'] = 'Failed to update account.'; }
            }
            elseif ($_POST['action'] == 'delete_account') {
                $data['error'] = 'Audit Trail Protection: General Ledger accounts cannot be deleted to preserve financial audit history.';
            }
        }

        $allAccounts = $this->coaModel->getAccounts();
        $data['accounts'] = $allAccounts;
        $data['main_accounts'] = array_filter($allAccounts, function($a) { return empty($a->parent_id); });
        
        $customerModel = $this->model('Customer');
        $data['customers'] = $customerModel->getAllCustomers() ?: [];
        
        $this->view('layouts/main', $data);
    }

    /**
     * Reusable, filterable Account Ledger History view
     */
    public function history($id = null) {
        $allAccounts = $this->coaModel->getAccounts();
        
        if (!$id && !empty($allAccounts)) {
            $id = $allAccounts[0]->id;
        }

        $selectedAccount = null;
        if ($id) {
            $selectedAccount = $this->coaModel->getAccountById($id);
        }

        $transactions = [];
        $priorBalance = 0.00;
        $startDate = $_GET['start_date'] ?? '';
        $endDate = $_GET['end_date'] ?? '';
        $quickRange = $_GET['quick_range'] ?? '';

        if ($selectedAccount) {
            // Handle Quick Date Ranges
            if (!empty($quickRange)) {
                $today = date('Y-m-d');
                if ($quickRange === 'today') {
                    $startDate = $today;
                    $endDate = $today;
                } elseif ($quickRange === 'last_week') {
                    $startDate = date('Y-m-d', strtotime('-7 days'));
                    $endDate = $today;
                } elseif ($quickRange === 'last_month') {
                    $startDate = date('Y-m-d', strtotime('-30 days'));
                    $endDate = $today;
                }
            }

            // Calculate Prior Balance (for starting running total in ledger)
            if (!empty($startDate)) {
                $priorSum = $this->coaModel->getPriorBalance($id, $startDate);
                $pDeb = floatval($priorSum->total_debit ?? 0);
                $pCred = floatval($priorSum->total_credit ?? 0);

                if (in_array($selectedAccount->account_type, ['Asset', 'Expense'])) {
                    $priorBalance = $pDeb - $pCred;
                } else {
                    $priorBalance = $pCred - $pDeb;
                }
            }

            // Fetch filtered ledger lines
            $filters = [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'search' => trim($_GET['search'] ?? ''),
                'tx_type' => $_GET['tx_type'] ?? 'all'
            ];
            $transactions = $this->coaModel->getAccountHistory($id, $filters);
        }

        $data = [
            'title' => 'Account Ledger History',
            'content_view' => 'accounting/history',
            'all_accounts' => $allAccounts,
            'selected_account' => $selectedAccount,
            'transactions' => $transactions,
            'prior_balance' => $priorBalance,
            'selected_id' => $id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'quick_range' => $quickRange,
            'search' => $_GET['search'] ?? '',
            'tx_type' => $_GET['tx_type'] ?? 'all'
        ];

        $this->view('layouts/main', $data);
    }

    public function journal() {
        $this->generateCsrfToken();
        $data = ['title' => 'General Journal', 'content_view' => 'accounting/journal', 'accounts' => $this->coaModel->getAccounts(), 'entries' => $this->journalModel->getAllEntries(), 'error' => '', 'success' => ''];

        if (isset($_SESSION['flash_success'])) {
            $data['success'] = $_SESSION['flash_success'];
            unset($_SESSION['flash_success']);
        }
        if (isset($_SESSION['flash_error'])) {
            $data['error'] = $_SESSION['flash_error'];
            unset($_SESSION['flash_error']);
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $date = trim($_POST['entry_date']);
            $reference = trim($_POST['reference']);
            $description = trim($_POST['description']);
            $accounts = $_POST['account_id'];
            $debits = $_POST['debit'];
            $credits = $_POST['credit'];

            $lines = [];
            $totalDebit = 0; $totalCredit = 0;

            for ($i = 0; $i < count($accounts); $i++) {
                $deb = empty($debits[$i]) ? 0 : floatval($debits[$i]);
                $cred = empty($credits[$i]) ? 0 : floatval($credits[$i]);

                if ($deb > 0 || $cred > 0) {
                    $lines[] = ['account_id' => $accounts[$i], 'debit' => $deb, 'credit' => $cred];
                    $totalDebit += $deb; $totalCredit += $cred;
                }
            }

            if (empty($lines)) { $data['error'] = 'You must enter at least one transaction line.'; } 
            elseif (round($totalDebit, 2) !== round($totalCredit, 2)) { $data['error'] = 'Accounting Error: Total Debits must equal Total Credits.'; } 
            else {
                $db = new Database();
                $isDuplicate = false;
                if (!empty($reference)) {
                    $db->query("SELECT COUNT(*) as cnt FROM journal_entries WHERE reference = :ref");
                    $db->bind(':ref', $reference);
                    $dupRes = $db->single();
                    if ($dupRes && $dupRes->cnt > 0) {
                        $isDuplicate = true;
                    }
                }

                if ($isDuplicate) {
                    $data['error'] = 'Accounting Error: Journal Entry Reference "' . htmlspecialchars($reference) . '" already exists.';
                } else {
                    // Check if date falls in a locked/closed financial year
                    $db->query("SELECT COUNT(*) as cnt FROM financial_years WHERE end_date >= :entry_date");
                    $db->bind(':entry_date', $date);
                    $res = $db->single();
                    if ($res && $res->cnt > 0) {
                        $data['error'] = 'Accounting Error: The period containing date ' . $date . ' is closed and locked.';
                    } else {
                        if ($this->journalModel->postEntry($date, $reference, $description, $lines, $_SESSION['user_id'])) {
                            $data['success'] = 'Journal Entry successfully posted.';
                            $data['entries'] = $this->journalModel->getAllEntries();
                        } else { $data['error'] = 'Database Error: Failed to post entry.'; }
                    }
                }
            }
        }
        $this->view('layouts/main', $data);
    }

    public function close_year() {
        if ($_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Accountant') {
            die("Access Denied: Only Administrators or Accountants can perform Year-End Closing.");
        }

        $this->generateCsrfToken();

        $allAccounts = $this->coaModel->getAccounts();
        $equityAccounts = array_filter($allAccounts, function($a) { return $a->account_type == 'Equity'; });

        $data = [
            'title' => 'Financial Year Close',
            'content_view' => 'accounting/close_year',
            'equity_accounts' => $equityAccounts,
            'error' => '',
            'success' => ''
        ];

        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'close_books') {
            $this->validateCsrfOrDie();

            $endDate = $_POST['end_date'];
            $retainedEarningsId = $_POST['retained_earnings_id'];
            $confirmPassword = $_POST['confirm_password'] ?? '';

            $userModel = $this->model('User');
            $user = $userModel->login($_SESSION['username'], $confirmPassword);

            if (!$user) {
                $data['error'] = "Accounting Security Error: Invalid password confirmation. Year-End closing aborted.";
            } else {
                $result = $this->journalModel->closeFinancialYear($endDate, $_SESSION['user_id'], $retainedEarningsId);

                if ($result === true) {
                    $data['success'] = "Financial Year closed successfully! Net Income transferred to Retained Earnings and past ledgers are now locked.";
                } else {
                    $data['error'] = $result; 
                }
            }
        }

        $this->view('layouts/main', $data);
    }

    public function void_journal() {
        if ($_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Accountant') {
            die("Access Denied: Only Administrators or Accountants can void journal entries.");
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->validateCsrfOrDie();

            $id = intval($_POST['entry_id']);
            if ($this->journalModel->voidEntry($id)) {
                $_SESSION['flash_success'] = 'Journal Entry has been successfully voided and account balances reversed.';
            } else {
                $_SESSION['flash_error'] = 'Failed to void journal entry. (Maybe it belongs to a closed/locked period).';
            }
        }
        header('Location: ' . APP_URL . '/accounting/journal');
        exit;
    }
}