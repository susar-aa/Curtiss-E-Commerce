<?php
class PurchaseOrder {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function getPOsPaginated($search = '', $limit = 10, $offset = 0, $filters = []) {
        $sql = "SELECT p.*, v.name as vendor_name, v.email, u.username as creator_name 
                FROM purchase_orders p 
                JOIN vendors v ON p.vendor_id = v.id 
                LEFT JOIN users u ON p.created_by = u.id
                WHERE (p.po_number LIKE :search OR v.name LIKE :search)";
        
        if (!empty($filters['vendor_id'])) { $sql .= " AND p.vendor_id = :vid"; }
        if (!empty($filters['status'])) { $sql .= " AND p.status = :status"; }
        
        $sql .= " ORDER BY p.created_at DESC LIMIT :limit OFFSET :offset";
        
        $this->db->query($sql);
        $this->db->bind(':search', "%$search%");
        if (!empty($filters['vendor_id'])) { $this->db->bind(':vid', $filters['vendor_id']); }
        if (!empty($filters['status'])) { $this->db->bind(':status', $filters['status']); }
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        $this->db->bind(':offset', $offset, PDO::PARAM_INT);
        return $this->db->resultSet();
    }

    public function getTotalPOs($search = '', $filters = []) {
        $sql = "SELECT COUNT(*) as total FROM purchase_orders p JOIN vendors v ON p.vendor_id = v.id WHERE (p.po_number LIKE :search OR v.name LIKE :search)";
        if (!empty($filters['vendor_id'])) { $sql .= " AND p.vendor_id = :vid"; }
        if (!empty($filters['status'])) { $sql .= " AND p.status = :status"; }
        
        $this->db->query($sql);
        $this->db->bind(':search', "%$search%");
        if (!empty($filters['vendor_id'])) { $this->db->bind(':vid', $filters['vendor_id']); }
        if (!empty($filters['status'])) { $this->db->bind(':status', $filters['status']); }
        $row = $this->db->single();
        return $row->total ?? 0;
    }

    public function getPOById($id) {
        $this->db->query("SELECT p.*, v.name as vendor_name, v.email, v.phone, v.address, 
                                 u.username as creator_name, u.signature_path as creator_signature
                          FROM purchase_orders p 
                          JOIN vendors v ON p.vendor_id = v.id 
                          LEFT JOIN users u ON p.created_by = u.id
                          WHERE p.id = :id");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    public function getPOItems($id) {
        $this->db->query("SELECT * FROM purchase_order_items WHERE po_id = :id");
        $this->db->bind(':id', $id);
        return $this->db->resultSet();
    }

    // New Helper: Checks if a PO contains any unassigned mixed variations
    public function hasMixItems($poId) {
        $this->db->query("SELECT COUNT(*) as mix_count FROM purchase_order_items WHERE po_id = :id AND is_mix = 1");
        $this->db->bind(':id', $poId);
        $row = $this->db->single();
        return ($row->mix_count > 0);
    }

    public function getVendorProductsSales($vendorId, $startDate, $endDate) {
        $this->db->query("
            SELECT i.id, i.name as item_name, COALESCE(NULLIF(i.cost_price, 0.00), i.cost) as cost, i.quantity_on_hand, 
                   COALESCE(SUM(ii.quantity), 0) as sold_qty
            FROM items i
            LEFT JOIN invoice_items ii ON i.name = ii.description
            LEFT JOIN invoices inv ON ii.invoice_id = inv.id 
                                  AND inv.invoice_date BETWEEN :sdate AND :edate 
                                  AND inv.status != 'Voided'
            WHERE i.vendor_id = :vid
            GROUP BY i.id, i.name, i.cost, i.cost_price, i.quantity_on_hand
            ORDER BY sold_qty DESC, i.name ASC
        ");
        $this->db->bind(':vid', $vendorId);
        $this->db->bind(':sdate', $startDate);
        $this->db->bind(':edate', $endDate);
        return $this->db->resultSet();
    }

    public function createPO($poData, $items, $userId) {
        try {
            $this->db->beginTransaction();
            $totalAmount = 0; foreach ($items as $item) { $totalAmount += ($item['qty'] * $item['price']); }

            $this->db->query("INSERT INTO purchase_orders (po_number, vendor_id, po_date, expected_date, notes, total_amount, created_by) 
                              VALUES (:po_num, :vid, :pdate, :edate, :notes, :total, :uid)");
            $this->db->bind(':po_num', $poData['po_number']);
            $this->db->bind(':vid', $poData['vendor_id']);
            $this->db->bind(':pdate', $poData['po_date']);
            $this->db->bind(':edate', $poData['expected_date']);
            $this->db->bind(':notes', $poData['notes']);
            $this->db->bind(':total', $totalAmount);
            $this->db->bind(':uid', $userId);
            $this->db->execute();
            $poId = $this->db->lastInsertId();

            foreach ($items as $item) {
                $this->db->query("INSERT INTO purchase_order_items (po_id, item_id, item_variation_option_id, is_mix, description, quantity, unit_price, total) 
                                  VALUES (:pid, :iid, :vid, :mix, :desc, :qty, :price, :total)");
                $this->db->bind(':pid', $poId);
                $this->db->bind(':iid', $item['item_id']);
                $this->db->bind(':vid', $item['var_opt_id']);
                $this->db->bind(':mix', $item['is_mix']);
                $this->db->bind(':desc', $item['desc']);
                $this->db->bind(':qty', $item['qty']);
                $this->db->bind(':price', $item['price']);
                $this->db->bind(':total', ($item['qty'] * $item['price']));
                $this->db->execute();
            }

            $this->db->commit();
            return true;
        } catch (PDOException $e) { $this->db->rollBack(); return false; }
    }

    public function updatePO($poData, $items) {
        try {
            $this->db->beginTransaction();
            $totalAmount = 0; foreach ($items as $item) { $totalAmount += ($item['qty'] * $item['price']); }

            $this->db->query("UPDATE purchase_orders SET vendor_id = :vid, po_date = :pdate, expected_date = :edate, notes = :notes, total_amount = :total WHERE id = :id");
            $this->db->bind(':vid', $poData['vendor_id']);
            $this->db->bind(':pdate', $poData['po_date']);
            $this->db->bind(':edate', $poData['expected_date']);
            $this->db->bind(':notes', $poData['notes']);
            $this->db->bind(':total', $totalAmount);
            $this->db->bind(':id', $poData['id']);
            $this->db->execute();

            $this->db->query("DELETE FROM purchase_order_items WHERE po_id = :id");
            $this->db->bind(':id', $poData['id']);
            $this->db->execute();

            foreach ($items as $item) {
                $this->db->query("INSERT INTO purchase_order_items (po_id, item_id, item_variation_option_id, is_mix, description, quantity, unit_price, total) 
                                  VALUES (:pid, :iid, :vid, :mix, :desc, :qty, :price, :total)");
                $this->db->bind(':pid', $poData['id']);
                $this->db->bind(':iid', $item['item_id']);
                $this->db->bind(':vid', $item['var_opt_id']);
                $this->db->bind(':mix', $item['is_mix']);
                $this->db->bind(':desc', $item['desc']);
                $this->db->bind(':qty', $item['qty']);
                $this->db->bind(':price', $item['price']);
                $this->db->bind(':total', ($item['qty'] * $item['price']));
                $this->db->execute();
            }

            $this->db->commit();
            return true;
        } catch (PDOException $e) { $this->db->rollBack(); return false; }
    }

    public function deletePO($id) {
        $this->db->query("DELETE FROM purchase_orders WHERE id = :id");
        $this->db->bind(':id', $id);
        try { return $this->db->execute(); } catch (PDOException $e) { return false; }
    }

    // Advanced 1-Tap Transfer to GRN Engine
    public function transferToGRN($poId, $userId, $resolvedItems = null) {
        $po = $this->getPOById($poId);
        
        // If resolved items were submitted by the Mix Resolver, use them. Otherwise, pull defaults.
        $items = $resolvedItems ? $resolvedItems : $this->getPOItems($poId);
        
        if (!$po || empty($items) || $po->status === 'Received') return false;

        try {
            $this->db->beginTransaction();

            $grnNum = 'GRN-' . time();
            $this->db->query("INSERT INTO goods_receipt_notes (grn_number, po_id, vendor_id, grn_date, created_by) VALUES (:num, :pid, :vid, :date, :uid)");
            $this->db->bind(':num', $grnNum);
            $this->db->bind(':pid', $poId);
            $this->db->bind(':vid', $po->vendor_id);
            $this->db->bind(':date', date('Y-m-d'));
            $this->db->bind(':uid', $userId);
            $this->db->execute();
            $grnId = $this->db->lastInsertId();

            foreach ($items as $item) {
                // Support both objects (standard bypass) and arrays (resolved mixes)
                $itemId   = is_object($item) ? $item->item_id : $item['item_id'];
                $varOptId = is_object($item) ? $item->item_variation_option_id : $item['var_opt_id'];
                $desc     = is_object($item) ? $item->description : $item['desc'];
                $qty      = is_object($item) ? $item->quantity : $item['qty'];
                $cost     = is_object($item) ? $item->unit_price : $item['price'];
                $total    = is_object($item) ? $item->total : $item['total'];

                $this->db->query("INSERT INTO grn_items (grn_id, item_id, item_variation_option_id, description, quantity, unit_cost, total) 
                                  VALUES (:gid, :iid, :vid, :desc, :qty, :cost, :total)");
                $this->db->bind(':gid', $grnId);
                $this->db->bind(':iid', $itemId);
                $this->db->bind(':vid', $varOptId);
                $this->db->bind(':desc', $desc);
                $this->db->bind(':qty', $qty);
                $this->db->bind(':cost', $cost);
                $this->db->bind(':total', $total);
                $this->db->execute();

                // Instantly update Stock in Inventory
                require_once '../app/Models/Item.php';
                $itemModel = new Item();
                if ($itemId) {
                    $itemModel->updateStockDelta($itemId, $qty);
                } else {
                    $this->db->query("SELECT id FROM items WHERE name = :desc LIMIT 1");
                    $this->db->bind(':desc', $desc);
                    $rowItem = $this->db->single();
                    if ($rowItem) {
                        $itemModel->updateStockDelta($rowItem->id, $qty);
                    }
                }

                // Update precise Variation Stock if provided
                if ($varOptId) {
                    $this->db->query("UPDATE item_variation_options SET quantity_on_hand = quantity_on_hand + :qty WHERE id = :vid");
                    $this->db->bind(':qty', $qty);
                    $this->db->bind(':vid', $varOptId);
                    $this->db->execute();
                }
            }

            $this->db->query("UPDATE purchase_orders SET status = 'Received' WHERE id = :id");
            $this->db->bind(':id', $poId);
            $this->db->execute();

            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            return false;
        }
    }
}