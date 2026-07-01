<?php
class SupplierReturn {
    private $db;

    public function __construct() {
        $this->db = new Database();
        $this->checkAndMigrateSchema();
    }

    private function checkAndMigrateSchema() {
        try {
            // Check and create supplier_returns table
            $this->db->query("SHOW TABLES LIKE 'supplier_returns'");
            if (!$this->db->single()) {
                $this->db->query("
                    CREATE TABLE supplier_returns (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        return_number VARCHAR(100) NOT NULL UNIQUE,
                        vendor_id INT NOT NULL,
                        return_date DATE NOT NULL,
                        notes TEXT NULL,
                        total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                        created_by INT NOT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
                ");
                $this->db->execute();
            }

            // Check and create supplier_return_items table
            $this->db->query("SHOW TABLES LIKE 'supplier_return_items'");
            if (!$this->db->single()) {
                $this->db->query("
                    CREATE TABLE supplier_return_items (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        supplier_return_id INT NOT NULL,
                        item_id INT NOT NULL,
                        item_variation_option_id INT NULL,
                        description VARCHAR(255) NOT NULL,
                        quantity DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                        unit_cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                        total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                        grn_id INT NULL
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
                ");
                $this->db->execute();
            }
        } catch (Exception $e) {
            // Silently fail if table already exists or database is busy
        }
    }

    public function getReturnsPaginated($search = '', $limit = 10, $offset = 0, $filters = []) {
        $sql = "SELECT r.*, v.name as vendor_name, u.username as creator_name 
                FROM supplier_returns r 
                JOIN vendors v ON r.vendor_id = v.id 
                LEFT JOIN users u ON r.created_by = u.id
                WHERE (r.return_number LIKE :search OR v.name LIKE :search)";
        
        if (!empty($filters['vendor_id'])) { $sql .= " AND r.vendor_id = :vid"; }
        
        $sql .= " ORDER BY r.created_at DESC LIMIT :limit OFFSET :offset";
        
        $this->db->query($sql);
        $this->db->bind(':search', "%$search%");
        if (!empty($filters['vendor_id'])) { $this->db->bind(':vid', $filters['vendor_id']); }
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        $this->db->bind(':offset', $offset, PDO::PARAM_INT);
        return $this->db->resultSet();
    }

    public function getTotalReturns($search = '', $filters = []) {
        $sql = "SELECT COUNT(*) as total FROM supplier_returns r JOIN vendors v ON r.vendor_id = v.id WHERE (r.return_number LIKE :search OR v.name LIKE :search)";
        if (!empty($filters['vendor_id'])) { $sql .= " AND r.vendor_id = :vid"; }
        
        $this->db->query($sql);
        $this->db->bind(':search', "%$search%");
        if (!empty($filters['vendor_id'])) { $this->db->bind(':vid', $filters['vendor_id']); }
        $row = $this->db->single();
        return $row->total ?? 0;
    }

    public function getReturnById($id) {
        $this->db->query("SELECT r.*, v.name as vendor_name, v.email, v.phone, v.address, u.username as creator_name
                          FROM supplier_returns r 
                          JOIN vendors v ON r.vendor_id = v.id 
                          LEFT JOIN users u ON r.created_by = u.id
                          WHERE r.id = :id");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    public function getReturnItems($id) {
        $this->db->query("SELECT ri.*, g.grn_number 
                          FROM supplier_return_items ri 
                          LEFT JOIN goods_receipt_notes g ON ri.grn_id = g.id 
                          WHERE ri.supplier_return_id = :id");
        $this->db->bind(':id', $id);
        return $this->db->resultSet();
    }

    /**
     * Fetch products purchased from a specific vendor in history (from GRN items)
     */
    public function getProductsPurchasedFromVendor($vendorId) {
        $this->db->query("
            SELECT DISTINCT i.id as item_id, ivo.id as var_opt_id,
                   CASE 
                       WHEN ivo.id IS NOT NULL THEN CONCAT(i.name, ' - ', v.name, ': ', vv.value_name)
                       ELSE i.name 
                   END as product_name,
                   i.item_code as sku,
                   ivo.sku as var_sku,
                   i.sample_code as sample_code
            FROM grn_items gri
            JOIN goods_receipt_notes grn ON gri.grn_id = grn.id
            JOIN items i ON gri.item_id = i.id
            LEFT JOIN item_variation_options ivo ON gri.item_variation_option_id = ivo.id
            LEFT JOIN variations v ON ivo.variation_id = v.id
            LEFT JOIN variation_values vv ON ivo.variation_value_id = vv.id
            WHERE grn.vendor_id = :vid
            ORDER BY product_name ASC
        ");
        $this->db->bind(':vid', $vendorId);
        return $this->db->resultSet();
    }

    /**
     * Fetch historical purchases of a specific product from a specific vendor (GRN items history)
     */
    public function getProductPurchaseHistory($vendorId, $itemId, $varOptId = null) {
        $sql = "
            SELECT gri.quantity, gri.unit_cost, grn.grn_number, grn.grn_date, grn.id as grn_id
            FROM grn_items gri
            JOIN goods_receipt_notes grn ON gri.grn_id = grn.id
            WHERE grn.vendor_id = :vid AND gri.item_id = :iid
        ";
        if ($varOptId) {
            $sql .= " AND gri.item_variation_option_id = :voptid";
        } else {
            $sql .= " AND gri.item_variation_option_id IS NULL";
        }
        $sql .= " ORDER BY grn.grn_date DESC";

        $this->db->query($sql);
        $this->db->bind(':vid', $vendorId);
        $this->db->bind(':iid', $itemId);
        if ($varOptId) {
            $this->db->bind(':voptid', $varOptId);
        }
        return $this->db->resultSet();
    }

    /**
     * Create Supplier Return and update physical stock
     */
    public function createReturn($returnData, $items, $userId) {
        try {
            $this->db->beginTransaction();

            // 1. Insert Master Return
            $this->db->query("INSERT INTO supplier_returns (return_number, vendor_id, return_date, notes, total_amount, created_by) 
                              VALUES (:num, :vid, :rdate, :notes, :total, :uid)");
            $this->db->bind(':num', $returnData['return_number']);
            $this->db->bind(':vid', $returnData['vendor_id']);
            $this->db->bind(':rdate', $returnData['return_date']);
            $this->db->bind(':notes', $returnData['notes'] ?? null);
            $this->db->bind(':total', $returnData['total_amount']);
            $this->db->bind(':uid', $userId);
            $this->db->execute();
            $returnId = $this->db->lastInsertId();

            // 2. Insert items and decrement stock
            foreach ($items as $item) {
                $this->db->query("INSERT INTO supplier_return_items (supplier_return_id, item_id, item_variation_option_id, description, quantity, unit_cost, total, grn_id) 
                                  VALUES (:rid, :iid, :vid, :desc, :qty, :cost, :total, :gid)");
                $this->db->bind(':rid', $returnId);
                $this->db->bind(':iid', $item['item_id']);
                $this->db->bind(':vid', $item['var_opt_id'] ?: null);
                $this->db->bind(':desc', $item['desc']);
                $this->db->bind(':qty', $item['qty']);
                $this->db->bind(':cost', $item['price']);
                $this->db->bind(':total', ($item['qty'] * $item['price']));
                $this->db->bind(':gid', $item['grn_id'] ?: null);
                $this->db->execute();

                // Deduct stock in items table
                require_once '../app/Models/Item.php';
                $itemModel = new Item();
                $itemModel->updateStockDelta($item['item_id'], -$item['qty']);

                // Deduct stock in item_variation_options table (If applicable)
                if ($item['var_opt_id']) {
                    $this->db->query("UPDATE item_variation_options SET quantity_on_hand = quantity_on_hand - :qty WHERE id = :vid");
                    $this->db->bind(':qty', $item['qty']);
                    $this->db->bind(':vid', $item['var_opt_id']);
                    $this->db->execute();
                }

                // Log Stock Movement in Ledger
                require_once '../app/Models/StockLedger.php';
                $ledger = new StockLedger();
                $this->db->query("SELECT warehouse_id FROM items WHERE id = :id");
                $this->db->bind(':id', $item['item_id']);
                $itemRow = $this->db->single();
                $whId = $itemRow ? $itemRow->warehouse_id : null;
                $ledger->logMovement($item['item_id'], $item['var_opt_id'] ?: null, 0, $item['qty'], 'Purchase Return', $returnData['return_number'], $whId, $userId, 'Supplier Return Stock Deduction', $item['price']);
            }

            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            return false;
        }
    }
}
