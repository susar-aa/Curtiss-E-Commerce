<?php
class Asset {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function getAllAssets() {
        $this->db->query("SELECT * FROM fixed_assets ORDER BY status ASC, purchase_date DESC");
        return $this->db->resultSet();
    }

    public function getAssetById($id) {
        $this->db->query("SELECT * FROM fixed_assets WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    public function getDepreciationHistory($assetId) {
        $this->db->query("SELECT * FROM depreciation_runs WHERE asset_id = :aid ORDER BY run_date DESC");
        $this->db->bind(':aid', $assetId);
        return $this->db->resultSet();
    }

    public function addAsset($data) {
        $this->db->query("INSERT INTO fixed_assets (asset_name, purchase_date, purchase_price, salvage_value, useful_life_years, asset_account_id, accum_dep_account_id, dep_expense_account_id) 
                          VALUES (:name, :pdate, :price, :salvage, :years, :aid, :acc_id, :exp_id)");
        
        $this->db->bind(':name', $data['asset_name']);
        $this->db->bind(':pdate', $data['purchase_date']);
        $this->db->bind(':price', $data['purchase_price']);
        $this->db->bind(':salvage', $data['salvage_value']);
        $this->db->bind(':years', $data['useful_life_years']);
        $this->db->bind(':aid', $data['asset_account_id']);
        $this->db->bind(':acc_id', $data['accum_dep_account_id']);
        $this->db->bind(':exp_id', $data['dep_expense_account_id']);
        
        return $this->db->execute();
    }

    public function postDepreciation($assetId, $amount, $date, $userId) {
        $asset = $this->getAssetById($assetId);
        if (!$asset) return false;

        try {
            $this->db->beginTransaction();

            // 1. Post Journal Entry Header
            $desc = "Depreciation Run for Asset: " . $asset->asset_name;
            $ref = "DEP-" . $assetId . "-" . time();
            $this->db->query("INSERT INTO journal_entries (entry_date, reference, description, created_by, status) 
                              VALUES (:date, :ref, :desc, :user, 'Posted')");
            $this->db->bind(':date', $date);
            $this->db->bind(':ref', $ref);
            $this->db->bind(':desc', $desc);
            $this->db->bind(':user', $userId);
            $this->db->execute();
            $journalId = $this->db->lastInsertId();

            // 2. Post Journal Lines (Debit Expense, Credit Accumulated Depreciation)
            $lines = [
                ['account_id' => $asset->dep_expense_account_id, 'debit' => $amount, 'credit' => 0],
                ['account_id' => $asset->accum_dep_account_id, 'debit' => 0, 'credit' => $amount]
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

            // 3. Log Depreciation Run
            $this->db->query("INSERT INTO depreciation_runs (asset_id, run_date, amount, journal_entry_id) 
                              VALUES (:aid, :rdate, :amt, :jid)");
            $this->db->bind(':aid', $assetId);
            $this->db->bind(':rdate', $date);
            $this->db->bind(':amt', $amount);
            $this->db->bind(':jid', $journalId);
            $this->db->execute();

            $this->db->commit();
            return true;

        } catch (PDOException $e) {
            $this->db->rollBack();
            return false;
        }
    }
}