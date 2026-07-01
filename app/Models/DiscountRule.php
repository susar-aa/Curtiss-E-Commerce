<?php

class DiscountRule {
    private $db;

    public function __construct() {
        $this->db = new Database();
        $this->migrateTables();
    }

    private function migrateTables() {
        try {
            $this->db->query("
                CREATE TABLE IF NOT EXISTS discount_rules (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    rule_type ENUM('item_wise', 'bill_wise') NOT NULL,
                    target_item_id INT NULL,
                    status ENUM('Active', 'Inactive') DEFAULT 'Active',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX (target_item_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
            $this->db->execute();

            $this->db->query("
                CREATE TABLE IF NOT EXISTS discount_rule_tiers (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    rule_id INT NOT NULL,
                    min_threshold DECIMAL(10,2) NOT NULL,
                    max_threshold DECIMAL(10,2) NULL,
                    reward_val DECIMAL(10,2) NOT NULL,
                    INDEX (rule_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
            $this->db->execute();
        } catch (Exception $e) {
            // Silence migration errors
        }
    }

    public function getAllRules() {
        $this->db->query("
            SELECT r.*, i.name as item_name, i.item_code as item_sku 
            FROM discount_rules r 
            LEFT JOIN items i ON r.target_item_id = i.id 
            ORDER BY r.id DESC
        ");
        $rules = $this->db->resultSet();
        foreach ($rules as &$rule) {
            $rule->tiers = $this->getTiersByRuleId($rule->id);
        }
        return $rules;
    }

    public function getActiveRules() {
        $this->db->query("
            SELECT r.*, i.name as item_name, i.item_code as item_sku 
            FROM discount_rules r 
            LEFT JOIN items i ON r.target_item_id = i.id 
            WHERE r.status = 'Active'
            ORDER BY r.id DESC
        ");
        $rules = $this->db->resultSet();
        foreach ($rules as &$rule) {
            $rule->tiers = $this->getTiersByRuleId($rule->id);
        }
        return $rules;
    }

    public function getTiersByRuleId($ruleId) {
        $this->db->query("SELECT * FROM discount_rule_tiers WHERE rule_id = :rule_id ORDER BY min_threshold ASC");
        $this->db->bind(':rule_id', $ruleId);
        return $this->db->resultSet();
    }

    public function getRuleById($id) {
        $this->db->query("
            SELECT r.*, i.name as item_name, i.item_code as item_sku 
            FROM discount_rules r 
            LEFT JOIN items i ON r.target_item_id = i.id 
            WHERE r.id = :id
        ");
        $this->db->bind(':id', $id);
        $rule = $this->db->single();
        if ($rule) {
            $rule->tiers = $this->getTiersByRuleId($rule->id);
        }
        return $rule;
    }

    public function addRule($data, $tiers) {
        try {
            $this->db->beginTransaction();

            $this->db->query("INSERT INTO discount_rules (name, rule_type, target_item_id, status) VALUES (:name, :rule_type, :target_item_id, :status)");
            $this->db->bind(':name', $data['name']);
            $this->db->bind(':rule_type', $data['rule_type']);
            $this->db->bind(':target_item_id', $data['target_item_id'] ?: null);
            $this->db->bind(':status', $data['status'] ?? 'Active');
            $this->db->execute();
            $ruleId = $this->db->lastInsertId();

            if (!empty($tiers) && is_array($tiers)) {
                foreach ($tiers as $tier) {
                    $this->db->query("INSERT INTO discount_rule_tiers (rule_id, min_threshold, max_threshold, reward_val) VALUES (:rule_id, :min_threshold, :max_threshold, :reward_val)");
                    $this->db->bind(':rule_id', $ruleId);
                    $this->db->bind(':min_threshold', floatval($tier['min_threshold']));
                    $this->db->bind(':max_threshold', isset($tier['max_threshold']) && $tier['max_threshold'] !== '' ? floatval($tier['max_threshold']) : null);
                    $this->db->bind(':reward_val', floatval($tier['reward_val']));
                    $this->db->execute();
                }
            }

            $this->db->commit();
            return $ruleId;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function deleteRule($id) {
        try {
            $this->db->beginTransaction();

            // Tiers will cascade delete if database index is configured, but let's delete them explicitly for extra safety
            $this->db->query("DELETE FROM discount_rule_tiers WHERE rule_id = :id");
            $this->db->bind(':id', $id);
            $this->db->execute();

            $this->db->query("DELETE FROM discount_rules WHERE id = :id");
            $this->db->bind(':id', $id);
            $this->db->execute();

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function toggleRuleStatus($id, $status) {
        $this->db->query("UPDATE discount_rules SET status = :status WHERE id = :id");
        $this->db->bind(':status', $status);
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }
}
