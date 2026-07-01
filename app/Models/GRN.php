<?php
class GRN {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function getGRNsPaginated($search = '', $limit = 10, $offset = 0, $filters = []) {
        $sql = "SELECT g.*, v.name as vendor_name, u.username as creator_name, p.po_number,
                       u2.username as approver_name,
                       COALESCE((SELECT SUM(total) FROM grn_items WHERE grn_id = g.id), 0) as total_amount
                FROM goods_receipt_notes g 
                JOIN vendors v ON g.vendor_id = v.id 
                LEFT JOIN users u ON g.created_by = u.id
                LEFT JOIN users u2 ON g.approved_by = u2.id
                LEFT JOIN purchase_orders p ON g.po_id = p.id
                WHERE (g.grn_number LIKE :search OR g.receipt_number LIKE :search OR v.name LIKE :search OR p.po_number LIKE :search)";
        
        if (!empty($filters['vendor_id'])) { $sql .= " AND g.vendor_id = :vid"; }
        
        $sql .= " ORDER BY g.created_at DESC LIMIT :limit OFFSET :offset";
        
        $this->db->query($sql);
        $this->db->bind(':search', "%$search%");
        if (!empty($filters['vendor_id'])) { $this->db->bind(':vid', $filters['vendor_id']); }
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        $this->db->bind(':offset', $offset, PDO::PARAM_INT);
        return $this->db->resultSet();
    }

    public function getTotalGRNs($search = '', $filters = []) {
        $sql = "SELECT COUNT(*) as total FROM goods_receipt_notes g JOIN vendors v ON g.vendor_id = v.id LEFT JOIN purchase_orders p ON g.po_id = p.id WHERE (g.grn_number LIKE :search OR g.receipt_number LIKE :search OR v.name LIKE :search OR p.po_number LIKE :search)";
        if (!empty($filters['vendor_id'])) { $sql .= " AND g.vendor_id = :vid"; }
        
        $this->db->query($sql);
        $this->db->bind(':search', "%$search%");
        if (!empty($filters['vendor_id'])) { $this->db->bind(':vid', $filters['vendor_id']); }
        $row = $this->db->single();
        return $row->total ?? 0;
    }

    public function getGRNById($id) {
        $this->db->query("SELECT g.*, v.name as vendor_name, v.email, v.phone, v.address, 
                                 u.username as creator_name, u.signature_path as creator_signature,
                                 p.po_number, u2.username as approver_name,
                                 COALESCE((SELECT SUM(total) FROM grn_items WHERE grn_id = g.id), 0) as total_amount
                          FROM goods_receipt_notes g 
                          JOIN vendors v ON g.vendor_id = v.id 
                          LEFT JOIN users u ON g.created_by = u.id
                          LEFT JOIN users u2 ON g.approved_by = u2.id
                          LEFT JOIN purchase_orders p ON g.po_id = p.id
                          WHERE g.id = :id");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    public function getGRNItems($id) {
        $this->db->query("SELECT * FROM grn_items WHERE grn_id = :id");
        $this->db->bind(':id', $id);
        return $this->db->resultSet();
    }

    public function createGRN($grnData, $items, $userId) {
        try {
            $this->db->beginTransaction();

            $this->db->query("INSERT INTO goods_receipt_notes (grn_number, receipt_number, po_id, vendor_id, grn_date, notes, created_by, is_approved) 
                              VALUES (:num, :receipt, :pid, :vid, :gdate, :notes, :uid, 0)");
            $this->db->bind(':num', $grnData['grn_number']);
            $this->db->bind(':receipt', $grnData['receipt_number'] ?? null);
            $this->db->bind(':pid', $grnData['po_id']);
            $this->db->bind(':vid', $grnData['vendor_id']);
            $this->db->bind(':gdate', $grnData['grn_date']);
            $this->db->bind(':notes', $grnData['notes']);
            $this->db->bind(':uid', $userId);
            $this->db->execute();
            $grnId = $this->db->lastInsertId();

            foreach ($items as $item) {
                // Insert Line Item
                $this->db->query("INSERT INTO grn_items (grn_id, item_id, item_variation_option_id, description, quantity, unit_cost, total, selling_price, wholesale_price, retail_margin, wholesale_margin) 
                                  VALUES (:gid, :iid, :vid, :desc, :qty, :cost, :total, :sprice, :wprice, :rmargin, :wmargin)");
                $this->db->bind(':gid', $grnId);
                $this->db->bind(':iid', $item['item_id']);
                $this->db->bind(':vid', $item['var_opt_id']);
                $this->db->bind(':desc', $item['desc']);
                $this->db->bind(':qty', $item['qty']);
                $this->db->bind(':cost', $item['price']);
                $this->db->bind(':total', ($item['qty'] * $item['price']));
                $this->db->bind(':sprice', $item['selling_price']);
                $this->db->bind(':wprice', $item['wholesale_price']);
                $this->db->bind(':rmargin', $item['retail_margin']);
                $this->db->bind(':wmargin', $item['wholesale_margin']);
                $this->db->execute();
            }

            $this->db->commit();
            return $grnId;
        } catch (PDOException $e) { $this->db->rollBack(); throw $e; }
    }

    public function deleteGRN($id) {
        try {
            $grn = $this->getGRNById($id);
            if (!$grn) { throw new Exception("GRN not found."); }
            if ($grn->is_approved) {
                throw new Exception("Approved Goods Receipt Notes cannot be deleted.");
            }

            $this->db->beginTransaction();

            $this->db->query("DELETE FROM grn_items WHERE grn_id = :id");
            $this->db->bind(':id', $id);
            $this->db->execute();

            $this->db->query("DELETE FROM goods_receipt_notes WHERE id = :id");
            $this->db->bind(':id', $id);
            $this->db->execute();

            $this->db->commit();
            return true;
        } catch (PDOException $e) { $this->db->rollBack(); throw $e; }
    }

    public function updateGRN($grnId, $grnData, $items, $userId) {
        try {
            $grn = $this->getGRNById($grnId);
            if (!$grn) { throw new Exception("GRN not found."); }
            if ($grn->is_approved) { throw new Exception("Approved GRNs cannot be edited."); }

            $this->db->beginTransaction();

            // Delete old grn_items
            $this->db->query("DELETE FROM grn_items WHERE grn_id = :id");
            $this->db->bind(':id', $grnId);
            $this->db->execute();

            // Update master GRN record
            $this->db->query("UPDATE goods_receipt_notes 
                              SET vendor_id = :vid, receipt_number = :receipt, grn_date = :gdate, notes = :notes 
                              WHERE id = :id");
            $this->db->bind(':vid', $grnData['vendor_id']);
            $this->db->bind(':receipt', $grnData['receipt_number'] ?? null);
            $this->db->bind(':gdate', $grnData['grn_date']);
            $this->db->bind(':notes', $grnData['notes']);
            $this->db->bind(':id', $grnId);
            $this->db->execute();

            // Insert new items
            foreach ($items as $item) {
                // Insert new line item
                $this->db->query("INSERT INTO grn_items (grn_id, item_id, item_variation_option_id, description, quantity, unit_cost, total, selling_price, wholesale_price, retail_margin, wholesale_margin) 
                                  VALUES (:gid, :iid, :vid, :desc, :qty, :cost, :total, :sprice, :wprice, :rmargin, :wmargin)");
                $this->db->bind(':gid', $grnId);
                $this->db->bind(':iid', $item['item_id']);
                $this->db->bind(':vid', $item['var_opt_id']);
                $this->db->bind(':desc', $item['desc']);
                $this->db->bind(':qty', $item['qty']);
                $this->db->bind(':cost', $item['price']);
                $this->db->bind(':total', ($item['qty'] * $item['price']));
                $this->db->bind(':sprice', $item['selling_price']);
                $this->db->bind(':wprice', $item['wholesale_price']);
                $this->db->bind(':rmargin', $item['retail_margin']);
                $this->db->bind(':wmargin', $item['wholesale_margin']);
                $this->db->execute();
            }

            $this->db->commit();
            return true;
        } catch (PDOException $e) { $this->db->rollBack(); throw $e; }
    }

    public function approveGRN($grnId, $userId) {
        try {
            $grn = $this->getGRNById($grnId);
            if (!$grn) { throw new Exception("GRN not found."); }
            if ($grn->is_approved) { throw new Exception("GRN is already approved."); }

            $this->db->beginTransaction();

            $items = $this->getGRNItems($grnId);

            require_once '../app/Models/FIFO.php';
            $fifo = new FIFO();

            foreach ($items as $item) {
                // 1. Record FIFO stock receipt batch
                $fifo->recordReceipt($item->item_id, $item->item_variation_option_id, $grnId, $item->quantity, $item->unit_cost);

                // 2. Update Master Item Stock, Cost, Margins and Prices
                $this->db->query("
                    UPDATE items 
                    SET quantity_on_hand = COALESCE(quantity_on_hand, 0) + :qty, 
                        qty = COALESCE(qty, 0) + :qty, 
                        price = :sprice, 
                        wholesale_price = :wprice, 
                        cost = :cost, 
                        cost_price = :cost, 
                        retail_margin = :rmargin, 
                        wholesale_margin = :wmargin 
                    WHERE id = :iid
                ");
                $this->db->bind(':qty', $item->quantity);
                $this->db->bind(':sprice', $item->selling_price);
                $this->db->bind(':wprice', $item->wholesale_price);
                $this->db->bind(':cost', $item->unit_cost);
                $this->db->bind(':rmargin', $item->retail_margin);
                $this->db->bind(':wmargin', $item->wholesale_margin);
                $this->db->bind(':iid', $item->item_id);
                $this->db->execute();

                // 3. Update Specific Variation Stock, Cost and Selling Price (If applicable)
                if ($item->item_variation_option_id) {
                    $this->db->query("
                        UPDATE item_variation_options 
                        SET quantity_on_hand = COALESCE(quantity_on_hand, 0) + :qty, 
                            price = :sprice, 
                            cost = :cost 
                        WHERE id = :vid
                    ");
                    $this->db->bind(':qty', $item->quantity);
                    $this->db->bind(':sprice', $item->selling_price);
                    $this->db->bind(':cost', $item->unit_cost);
                    $this->db->bind(':vid', $item->item_variation_option_id);
                    $this->db->execute();
                }

                // 3.5 Log Stock Movement in Ledger
                require_once '../app/Models/StockLedger.php';
                $ledger = new StockLedger();
                $this->db->query("SELECT warehouse_id FROM items WHERE id = :id");
                $this->db->bind(':id', $item->item_id);
                $itemRow = $this->db->single();
                $whId = $itemRow ? $itemRow->warehouse_id : null;
                $ledger->logMovement($item->item_id, $item->item_variation_option_id, $item->quantity, 0, 'GRN', $grn->grn_number, $whId, $userId, 'GRN Approved Stock Receipt', $item->unit_cost);
            }

            // 4. Update PO Status if linked
            if (!empty($grn->po_id)) {
                $this->db->query("UPDATE purchase_orders SET status = 'Received' WHERE id = :id");
                $this->db->bind(':id', $grn->po_id);
                $this->db->execute();
            }

            // 5. Mark GRN as approved
            $this->db->query("UPDATE goods_receipt_notes SET is_approved = 1, approved_by = :uid, approved_at = NOW() WHERE id = :id");
            $this->db->bind(':uid', $userId);
            $this->db->bind(':id', $grnId);
            $this->db->execute();

            $this->db->commit();
            return true;
        } catch (Throwable $e) { 
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e; 
        }
    }
}