<?php
class AssetController extends Controller {
    private $assetModel;
    private $coaModel;

    public function __construct() {
        if (!isset($_SESSION['user_id'])) { header('Location: ' . APP_URL . '/auth/login'); exit; }
        $this->assetModel = $this->model('Asset');
        $this->coaModel = $this->model('ChartOfAccount');
    }

    public function index() {
        $accounts = $this->coaModel->getAccounts();
        $assets = array_filter($accounts, function($a) { return $a->account_type == 'Asset'; });
        $expenses = array_filter($accounts, function($a) { return $a->account_type == 'Expense'; });
        
        // Liability/Equity/Contra-Asset accounts can be used for Accum Dep depending on accountant preference, 
        // but normally it's a Contra-Asset. We'll provide all accounts for flexibility.

        $data = [
            'title' => 'Fixed Assets',
            'content_view' => 'assets/index',
            'fixed_assets' => $this->assetModel->getAllAssets(),
            'assets_accounts' => $assets,
            'expense_accounts' => $expenses,
            'all_accounts' => $accounts,
            'error' => '',
            'success' => ''
        ];

        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
            if ($_POST['action'] == 'add_asset') {
                $assetData = [
                    'asset_name' => trim($_POST['asset_name']),
                    'purchase_date' => $_POST['purchase_date'],
                    'purchase_price' => floatval($_POST['purchase_price']),
                    'salvage_value' => floatval($_POST['salvage_value']),
                    'useful_life_years' => intval($_POST['useful_life_years']),
                    'asset_account_id' => $_POST['asset_account_id'],
                    'accum_dep_account_id' => $_POST['accum_dep_account_id'],
                    'dep_expense_account_id' => $_POST['dep_expense_account_id']
                ];

                if ($this->assetModel->addAsset($assetData)) {
                    $data['success'] = 'Fixed Asset registered successfully.';
                    $data['fixed_assets'] = $this->assetModel->getAllAssets();
                } else {
                    $data['error'] = 'Failed to register asset.';
                }
            } 
            elseif ($_POST['action'] == 'run_depreciation') {
                $assetId = $_POST['asset_id'];
                $amount = floatval($_POST['amount']);
                $date = $_POST['run_date'];

                if ($amount <= 0) {
                    $data['error'] = 'Depreciation amount must be greater than zero.';
                } else {
                    if ($this->assetModel->postDepreciation($assetId, $amount, $date, $_SESSION['user_id'])) {
                        $data['success'] = 'Depreciation successfully posted to ledger.';
                    } else {
                        $data['error'] = 'Failed to post depreciation.';
                    }
                }
            }
        }

        // Calculate current accumulated depreciation for display
        foreach ($data['fixed_assets'] as $asset) {
            $runs = $this->assetModel->getDepreciationHistory($asset->id);
            $accum = 0;
            foreach($runs as $r) { $accum += $r->amount; }
            $asset->accumulated = $accum;
            $asset->book_value = $asset->purchase_price - $accum;
            
            // Straight-line annual calc
            $asset->annual_dep = ($asset->purchase_price - $asset->salvage_value) / ($asset->useful_life_years > 0 ? $asset->useful_life_years : 1);
        }

        $this->view('layouts/main', $data);
    }
}