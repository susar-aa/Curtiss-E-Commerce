<?php

class StockLedger {
    private $db;

    public function __construct() {
        $this->db = new Database();
        $this->initDatabase();
    }

    /**
     * Auto-migrate schema for Stock Ledger
     */
    private function initDatabase() {
        // Skip DDL migrations if a transaction is active to prevent implicit commits
        if ($this->db->inTransaction()) {
            return;
        }

        try {
            $this->db->query("SELECT 1 FROM stock_ledger LIMIT 1");
            $this->db->execute();
            return; // Table exists
        } catch (Throwable $t) {
            // Table does not exist, run migrations
        }

        try {
            $this->db->query("CREATE TABLE IF NOT EXISTS stock_ledger (
                id INT AUTO_INCREMENT PRIMARY KEY,
                item_id INT NOT NULL,
                variation_option_id INT NULL DEFAULT NULL,
                transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                transaction_type VARCHAR(50) NOT NULL,
                reference_number VARCHAR(100) NOT NULL,
                warehouse_id INT NULL DEFAULT NULL,
                quantity_in DECIMAL(15,2) DEFAULT 0.00,
                quantity_out DECIMAL(15,2) DEFAULT 0.00,
                running_balance DECIMAL(15,2) DEFAULT 0.00,
                unit_cost DECIMAL(15,2) DEFAULT 0.00,
                total_value DECIMAL(15,2) DEFAULT 0.00,
                user_id INT NOT NULL,
                remarks TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX (item_id),
                INDEX (variation_option_id),
                INDEX (transaction_date),
                INDEX (transaction_type),
                INDEX (reference_number)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $this->db->execute();

            // Seed empty ledger with existing physical stock as "Opening Stock"
            $this->db->query("SELECT COUNT(*) as cnt FROM stock_ledger");
            $count = $this->db->single();
            if ($count && $count->cnt == 0) {
                $this->db->query("SELECT id, quantity_on_hand, cost, cost_price, warehouse_id FROM items WHERE quantity_on_hand > 0");
                $items = $this->db->resultSet() ?: [];
                foreach ($items as $item) {
                    $cost = floatval($item->cost > 0 ? $item->cost : ($item->cost_price > 0 ? $item->cost_price : 0.00));
                    $this->db->query("INSERT INTO stock_ledger (item_id, transaction_type, reference_number, warehouse_id, quantity_in, quantity_out, running_balance, unit_cost, total_value, user_id, remarks)
                                      VALUES (:iid, 'Opening Stock', 'INIT-STOCK', :whid, :qty, 0, :qty, :cost, :val, :uid, 'Initial system stock import')");
                    $this->db->bind(':iid', $item->id);
                    $this->db->bind(':whid', $item->warehouse_id ? $item->warehouse_id : null);
                    $this->db->bind(':qty', $item->quantity_on_hand);
                    $this->db->bind(':cost', $cost);
                    $this->db->bind(':val', $item->quantity_on_hand * $cost);
                    $this->db->bind(':uid', 1);
                    $this->db->execute();
                }
            }
        } catch (Throwable $e) {
            // Silently ignore or log migration error
        }
    }

    /**
     * Record a new stock movement
     */
    public function logMovement($itemId, $varOptId, $qtyIn, $qtyOut, $type, $ref, $warehouseId, $userId, $remarks, $unitCost = 0.00) {
        try {
            // Find current stock/running balance for this item
            $this->db->query("SELECT running_balance FROM stock_ledger 
                              WHERE item_id = :iid AND (variation_option_id = :vid OR (variation_option_id IS NULL AND :vid IS NULL))
                              ORDER BY transaction_date DESC, id DESC LIMIT 1");
            $this->db->bind(':iid', $itemId);
            $this->db->bind(':vid', $varOptId ? $varOptId : null);
            $row = $this->db->single();
            $prevBalance = $row ? floatval($row->running_balance) : 0.00;

            $qtyIn = floatval($qtyIn);
            $qtyOut = floatval($qtyOut);
            $newBalance = $prevBalance + $qtyIn - $qtyOut;
            $unitCost = floatval($unitCost);
            $totalVal = ($qtyIn > 0 ? $qtyIn : $qtyOut) * $unitCost;

            $this->db->query("INSERT INTO stock_ledger (item_id, variation_option_id, transaction_type, reference_number, warehouse_id, quantity_in, quantity_out, running_balance, unit_cost, total_value, user_id, remarks)
                              VALUES (:iid, :vid, :type, :ref, :whid, :qin, :qout, :bal, :cost, :val, :uid, :remarks)");
            $this->db->bind(':iid', $itemId);
            $this->db->bind(':vid', $varOptId ? $varOptId : null);
            $this->db->bind(':type', $type);
            $this->db->bind(':ref', $ref);
            $this->db->bind(':whid', $warehouseId ? $warehouseId : null);
            $this->db->bind(':qin', $qtyIn);
            $this->db->bind(':qout', $qtyOut);
            $this->db->bind(':bal', $newBalance);
            $this->db->bind(':cost', $unitCost);
            $this->db->bind(':val', $totalVal);
            $this->db->bind(':uid', $userId);
            $this->db->bind(':remarks', $remarks);
            $this->db->execute();
        } catch (Throwable $e) {
            // Prevent ledger failures from crashing main transactions
            error_log("StockLedger Log Fail: " . $e->getMessage());
        }
    }

    /**
     * Fetch all movements paginated and filtered
     */
    public function getMovements($filters = [], $limit = 50, $offset = 0) {
        $params = [];
        $where = $this->buildWhereClause($filters, $params);

        $sql = "SELECT sl.*, i.name as item_name, i.item_code as sku, i.barcode, sl.unit_cost,
                       w.name as warehouse_name, u.username as user_name,
                       CONCAT(v.name, ': ', vv.value_name) as variation_name
                FROM stock_ledger sl
                JOIN items i ON sl.item_id = i.id
                LEFT JOIN warehouses w ON sl.warehouse_id = w.id
                LEFT JOIN users u ON sl.user_id = u.id
                LEFT JOIN item_variation_options ivo ON sl.variation_option_id = ivo.id
                LEFT JOIN variations v ON ivo.variation_id = v.id
                LEFT JOIN variation_values vv ON ivo.variation_value_id = vv.id
                $where
                ORDER BY sl.transaction_date DESC, sl.id DESC
                LIMIT :limit OFFSET :offset";

        $this->db->query($sql);
        foreach ($params as $param => $val) {
            $this->db->bind($param, $val);
        }
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        $this->db->bind(':offset', $offset, PDO::PARAM_INT);

        return $this->db->resultSet() ?: [];
    }

    /**
     * Get total count for pagination
     */
    public function getMovementsCount($filters = []) {
        $params = [];
        $where = $this->buildWhereClause($filters, $params);

        $sql = "SELECT COUNT(*) as total 
                FROM stock_ledger sl
                JOIN items i ON sl.item_id = i.id
                LEFT JOIN warehouses w ON sl.warehouse_id = w.id
                LEFT JOIN users u ON sl.user_id = u.id
                LEFT JOIN item_variation_options ivo ON sl.variation_option_id = ivo.id
                LEFT JOIN variations v ON ivo.variation_id = v.id
                LEFT JOIN variation_values vv ON ivo.variation_value_id = vv.id
                $where";

        $this->db->query($sql);
        foreach ($params as $param => $val) {
            $this->db->bind($param, $val);
        }
        $row = $this->db->single();
        return $row ? intval($row->total) : 0;
    }

    /**
     * Get summary metrics for filtered range
     */
    public function getSummaryMetrics($filters = []) {
        $params = [];
        $where = $this->buildWhereClause($filters, $params);

        $sql = "SELECT 
                    COALESCE(SUM(sl.quantity_in), 0) as total_in,
                    COALESCE(SUM(sl.quantity_out), 0) as total_out,
                    COALESCE(SUM(sl.quantity_in - sl.quantity_out), 0) as net_movement,
                    COALESCE(SUM(sl.total_value), 0) as total_value_impact
                FROM stock_ledger sl
                JOIN items i ON sl.item_id = i.id
                $where";

        $this->db->query($sql);
        foreach ($params as $param => $val) {
            $this->db->bind($param, $val);
        }
        return $this->db->single();
    }

    /**
     * Build WHERE conditions dynamically
     */
    private function buildWhereClause($filters, &$params) {
        $conditions = [];

        if (!empty($filters['search'])) {
            $conditions[] = "(i.name LIKE :search OR i.item_code LIKE :search OR i.barcode LIKE :search OR sl.reference_number LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['item_id'])) {
            $conditions[] = "sl.item_id = :item_id";
            $params[':item_id'] = intval($filters['item_id']);
        }

        if (!empty($filters['category_id'])) {
            $conditions[] = "i.category_id = :category_id";
            $params[':category_id'] = intval($filters['category_id']);
        }

        if (!empty($filters['brand'])) {
            $conditions[] = "i.brand = :brand";
            $params[':brand'] = $filters['brand'];
        }

        if (!empty($filters['warehouse_id'])) {
            $conditions[] = "sl.warehouse_id = :warehouse_id";
            $params[':warehouse_id'] = intval($filters['warehouse_id']);
        }

        if (!empty($filters['transaction_type'])) {
            $conditions[] = "sl.transaction_type = :transaction_type";
            $params[':transaction_type'] = $filters['transaction_type'];
        }

        if (!empty($filters['reference_number'])) {
            $conditions[] = "sl.reference_number = :reference_number";
            $params[':reference_number'] = $filters['reference_number'];
        }

        if (!empty($filters['user_id'])) {
            $conditions[] = "sl.user_id = :user_id";
            $params[':user_id'] = intval($filters['user_id']);
        }

        if (!empty($filters['start_date'])) {
            $conditions[] = "sl.transaction_date >= :start_date";
            $params[':start_date'] = $filters['start_date'] . ' 00:00:00';
        }

        if (!empty($filters['end_date'])) {
            $conditions[] = "sl.transaction_date <= :end_date";
            $params[':end_date'] = $filters['end_date'] . ' 23:59:59';
        }

        return !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
    }

    /**
     * Get stock card details (opening, closing, and movements) for a specific product
     */
    public function getStockCardForProduct($itemId, $varOptId = null, $startDate = null, $endDate = null) {
        $itemId = intval($itemId);
        $varOptId = $varOptId ? intval($varOptId) : null;

        // 1. Calculate Opening Balance before $startDate
        $opParams = [':item_id' => $itemId];
        $opSql = "SELECT COALESCE(SUM(quantity_in - quantity_out), 0) as opening_bal 
                  FROM stock_ledger 
                  WHERE item_id = :item_id";

        if ($varOptId) {
            $opSql .= " AND variation_option_id = :var_opt_id";
            $opParams[':var_opt_id'] = $varOptId;
        } else {
            $opSql .= " AND (variation_option_id IS NULL OR variation_option_id = 0)";
        }

        if ($startDate) {
            $opSql .= " AND transaction_date < :start_date";
            $opParams[':start_date'] = $startDate . ' 00:00:00';
        }

        $this->db->query($opSql);
        foreach ($opParams as $p => $v) {
            $this->db->bind($p, $v);
        }
        $opRow = $this->db->single();
        $openingBalance = $opRow ? floatval($opRow->opening_bal) : 0.00;

        // 2. Fetch all movements within range
        $mvParams = [':item_id' => $itemId];
        $mvSql = "SELECT sl.*, u.username as user_name, w.name as warehouse_name
                  FROM stock_ledger sl
                  LEFT JOIN users u ON sl.user_id = u.id
                  LEFT JOIN warehouses w ON sl.warehouse_id = w.id
                  WHERE sl.item_id = :item_id";

        if ($varOptId) {
            $mvSql .= " AND sl.variation_option_id = :var_opt_id";
            $mvParams[':var_opt_id'] = $varOptId;
        } else {
            $mvSql .= " AND (sl.variation_option_id IS NULL OR sl.variation_option_id = 0)";
        }

        if ($startDate) {
            $mvSql .= " AND sl.transaction_date >= :start_date";
            $mvParams[':start_date'] = $startDate . ' 00:00:00';
        }
        if ($endDate) {
            $mvSql .= " AND sl.transaction_date <= :end_date";
            $mvParams[':end_date'] = $endDate . ' 23:59:59';
        }

        $mvSql .= " ORDER BY sl.transaction_date ASC, sl.id ASC";

        $this->db->query($mvSql);
        foreach ($mvParams as $p => $v) {
            $this->db->bind($p, $v);
        }
        $movements = $this->db->resultSet() ?: [];

        // 3. Compute running balance for each movement starting from Opening Balance
        $running = $openingBalance;
        foreach ($movements as $mv) {
            $running = $running + floatval($mv->quantity_in) - floatval($mv->quantity_out);
            $mv->computed_running_balance = $running;
        }

        $closingBalance = $running;

        return [
            'opening_balance' => $openingBalance,
            'movements' => $movements,
            'closing_balance' => $closingBalance
        ];
    }
}
