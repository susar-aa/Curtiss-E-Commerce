<?php

class WarehouseTransfer {
    private $db;

    public function __construct() {
        $this->db = new Database();
        $this->ensureTransferTableExists();
    }

    /**
     * Self-healing database migration for Warehouse Stock Transfers
     */
    private function ensureTransferTableExists() {
        try {
            $this->db->query("CREATE TABLE IF NOT EXISTS warehouse_transfers (
                id INT AUTO_INCREMENT PRIMARY KEY,
                transfer_number VARCHAR(50) NOT NULL UNIQUE,
                item_id INT NOT NULL,
                qty INT NOT NULL,
                from_warehouse_id INT NOT NULL,
                to_warehouse_id INT NOT NULL,
                transfer_date DATE NOT NULL,
                notes TEXT NULL,
                created_by INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
                FOREIGN KEY (from_warehouse_id) REFERENCES warehouses(id) ON DELETE CASCADE,
                FOREIGN KEY (to_warehouse_id) REFERENCES warehouses(id) ON DELETE CASCADE
            )");
            $this->db->execute();
        } catch (Exception $e) {
            // Suppress failsafe errors
        }
    }

    /**
     * Retrieve all stock transfer records
     */
    public function getAllTransfers() {
        $this->db->query("
            SELECT wt.*, 
                   i.name AS item_name, 
                   i.item_code AS item_code,
                   w_from.name AS from_warehouse_name,
                   w_to.name AS to_warehouse_name,
                   u.username AS creator_name
            FROM warehouse_transfers wt
            JOIN items i ON wt.item_id = i.id
            JOIN warehouses w_from ON wt.from_warehouse_id = w_from.id
            JOIN warehouses w_to ON wt.to_warehouse_id = w_to.id
            LEFT JOIN users u ON wt.created_by = u.id
            ORDER BY wt.transfer_date DESC, wt.id DESC
        ");
        return $this->db->resultSet() ?: [];
    }

    /**
     * Execute stock transfer transactionally with partial quantity support & auto item splitting
     */
    public function createTransfer($data) {
        try {
            $this->db->beginTransaction();

            // 1. Log the transfer record
            $this->db->query("
                INSERT INTO warehouse_transfers (transfer_number, item_id, qty, from_warehouse_id, to_warehouse_id, transfer_date, notes, created_by)
                VALUES (:transfer_num, :item_id, :qty, :from_wh, :to_wh, :t_date, :notes, :created_by)
            ");
            $this->db->bind(':transfer_num', $data['transfer_number']);
            $this->db->bind(':item_id', $data['item_id']);
            $this->db->bind(':qty', $data['qty']);
            $this->db->bind(':from_wh', $data['from_warehouse_id']);
            $this->db->bind(':to_wh', $data['to_warehouse_id']);
            $this->db->bind(':t_date', $data['transfer_date']);
            $this->db->bind(':notes', $data['notes']);
            $this->db->bind(':created_by', $data['created_by']);
            $this->db->execute();

            // 2. Fetch the source item details
            $this->db->query("SELECT * FROM items WHERE id = :item_id");
            $this->db->bind(':item_id', $data['item_id']);
            $item = $this->db->single();

            if (!$item) {
                throw new Exception("Source item not found.");
            }

            // 3. Deduct stock from the source item
            $this->db->query("
                UPDATE items 
                SET qty = qty - :qty, quantity_on_hand = GREATEST(0, CAST(quantity_on_hand AS SIGNED) - :qty)
                WHERE id = :item_id
            ");
            $this->db->bind(':qty', $data['qty']);
            $this->db->bind(':item_id', $data['item_id']);
            $this->db->execute();

            // 4. Check if the SKU already exists at the destination warehouse
            $this->db->query("
                SELECT * FROM items 
                WHERE item_code = :code AND warehouse_id = :to_wh 
                LIMIT 1
            ");
            $this->db->bind(':code', $item->item_code);
            $this->db->bind(':to_wh', $data['to_warehouse_id']);
            $destItem = $this->db->single();

            if ($destItem) {
                // SKU exists in destination, update stock
                $this->db->query("
                    UPDATE items 
                    SET qty = qty + :qty, quantity_on_hand = quantity_on_hand + :qty
                    WHERE id = :dest_id
                ");
                $this->db->bind(':qty', $data['qty']);
                $this->db->bind(':dest_id', $destItem->id);
                $this->db->execute();
            } else {
                // SKU does not exist in destination, clone item record
                $this->db->query("
                    INSERT INTO items (
                        item_code, name, price, wholesale_price, qty, quantity_on_hand, description,
                        barcode, category_id, brand, warehouse_id, vendor_id, cost_price, alert_qty, unit, status, weight, sync_woo, variations_json, image_path
                    ) VALUES (
                        :item_code, :name, :price, :wholesale_price, :qty, :qty, :description,
                        :barcode, :category_id, :brand, :warehouse_id, :vendor_id, :cost_price, :alert_qty, :unit, :status, :weight, :sync_woo, :variations_json, :image_path
                    )
                ");
                $this->db->bind(':item_code', $item->item_code);
                $this->db->bind(':name', $item->name);
                $this->db->bind(':price', $item->price);
                $this->db->bind(':wholesale_price', $item->wholesale_price);
                $this->db->bind(':qty', $data['qty']);
                $this->db->bind(':description', $item->description);
                $this->db->bind(':barcode', $item->barcode);
                $this->db->bind(':category_id', $item->category_id);
                $this->db->bind(':brand', $item->brand);
                $this->db->bind(':warehouse_id', $data['to_warehouse_id']);
                $this->db->bind(':vendor_id', $item->vendor_id);
                $this->db->bind(':cost_price', $item->cost_price);
                $this->db->bind(':alert_qty', $item->alert_qty);
                $this->db->bind(':unit', $item->unit);
                $this->db->bind(':status', $item->status);
                $this->db->bind(':weight', $item->weight);
                $this->db->bind(':sync_woo', $item->sync_woo);
                $this->db->bind(':variations_json', $item->variations_json);
                $this->db->bind(':image_path', $item->image_path);
                $this->db->execute();
            }

            $transferId = $this->db->lastInsertId();
            $this->db->commit();
            return $transferId;
        } catch (Exception $e) {
            error_log("Stock Transfer SQL Exception: " . $e->getMessage());
            $this->db->rollBack();
            return false;
        }
    }
}
