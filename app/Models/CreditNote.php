<?php
class CreditNote {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function getAllCreditNotes() {
        $this->db->query("SELECT cn.*, c.name as customer_name 
                          FROM credit_notes cn 
                          JOIN customers c ON cn.customer_id = c.id 
                          ORDER BY cn.created_at DESC");
        return $this->db->resultSet();
    }

    public function getCreditNoteById($id) {
        $this->db->query("SELECT cn.*, c.name as customer_name, c.email, c.phone, c.address 
                          FROM credit_notes cn 
                          JOIN customers c ON cn.customer_id = c.id 
                          WHERE cn.id = :id");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    public function getCreditNoteItems($id) {
        $this->db->query("SELECT * FROM credit_note_items WHERE credit_note_id = :id");
        $this->db->bind(':id', $id);
        return $this->db->resultSet();
    }

    /**
     * Get only the products purchased by a specific customer that haven't been fully returned yet
     */
    public function getCustomerProducts($customerId) {
        $this->db->query("
            SELECT DISTINCT 
                ii.item_id, 
                ii.variation_option_id, 
                ii.description as product_name,
                itm.item_code as sku,
                itm.sample_code as sample_code,
                SUM(ii.quantity) as total_sold,
                COALESCE(
                    (SELECT SUM(cni.quantity) 
                     FROM credit_note_items cni 
                     JOIN credit_notes cn ON cni.credit_note_id = cn.id 
                     WHERE cn.customer_id = :cid1 
                       AND cni.item_id = ii.item_id 
                       AND COALESCE(cni.variation_option_id, 0) = COALESCE(ii.variation_option_id, 0)
                    ), 0
                ) as total_returned
            FROM invoice_items ii
            JOIN invoices i ON ii.invoice_id = i.id
            LEFT JOIN items itm ON ii.item_id = itm.id
            WHERE i.customer_id = :cid2 
              AND i.is_deleted = 0 
              AND i.status != 'Voided'
            GROUP BY ii.item_id, ii.variation_option_id, ii.description, itm.item_code, itm.sample_code
        ");
        $this->db->bind(':cid1', $customerId);
        $this->db->bind(':cid2', $customerId);
        $results = $this->db->resultSet() ?: [];

        $filtered = [];
        foreach ($results as $r) {
            $maxReturnable = floatval($r->total_sold) - floatval($r->total_returned);
            if ($maxReturnable > 0) {
                $r->max_returnable = $maxReturnable;
                $filtered[] = $r;
            }
        }
        return $filtered;
    }

    /**
     * Get sale price and invoice history for a specific product purchased by a customer
     */
    public function getProductSaleHistory($customerId, $itemId, $varOptId) {
        $sql = "
            SELECT 
                i.id as invoice_id, 
                i.invoice_number, 
                i.invoice_date, 
                ii.id as invoice_item_id, 
                ii.unit_price, 
                ii.quantity, 
                ii.cost_at_sale,
                COALESCE(
                    (SELECT SUM(cni.quantity) 
                     FROM credit_note_items cni 
                     JOIN credit_notes cn ON cni.credit_note_id = cn.id 
                     WHERE cn.customer_id = :cid1 
                       AND cni.invoice_item_id = ii.id
                    ), 0
                ) as returned_qty
            FROM invoice_items ii
            JOIN invoices i ON ii.invoice_id = i.id
            WHERE i.customer_id = :cid2 
              AND ii.item_id = :iid 
              AND COALESCE(ii.variation_option_id, 0) = COALESCE(:vid, 0)
              AND i.is_deleted = 0 
              AND i.status != 'Voided'
            ORDER BY i.invoice_date DESC
        ";
        $this->db->query($sql);
        $this->db->bind(':cid1', $customerId);
        $this->db->bind(':cid2', $customerId);
        $this->db->bind(':iid', $itemId);
        $this->db->bind(':vid', $varOptId ? $varOptId : 0);
        
        $results = $this->db->resultSet() ?: [];
        foreach ($results as $r) {
            $r->max_returnable = floatval($r->quantity) - floatval($r->returned_qty);
        }
        return $results;
    }

    /**
     * Get a list of all damaged returned items
     */
    public function getDamagedProducts() {
        $this->db->query("
            SELECT 
                cni.*, 
                cn.credit_note_number, 
                cn.note_date, 
                c.name as customer_name,
                i.cost as current_cost
            FROM credit_note_items cni
            JOIN credit_notes cn ON cni.credit_note_id = cn.id
            JOIN customers c ON cn.customer_id = c.id
            LEFT JOIN items i ON cni.item_id = i.id
            WHERE cni.condition_status = 'Damaged'
            ORDER BY cn.note_date DESC, cn.created_at DESC
        ");
        return $this->db->resultSet() ?: [];
    }

    public function createCreditNoteWithAccounting($noteData, $items, $arAccountId = null, $revenueAccountId = null, $expenseAccountId = null, $userId = null) {
        try {
            $this->db->beginTransaction();

            // Auto-locate AR Account
            if (empty($arAccountId)) {
                $this->db->query("SELECT id FROM chart_of_accounts WHERE account_type = 'Asset' AND (account_name LIKE '%Receivable%' OR account_code = '1200') LIMIT 1");
                $arAcc = $this->db->single();
                if ($arAcc) {
                    $arAccountId = $arAcc->id;
                } else {
                    $this->db->query("SELECT id FROM chart_of_accounts WHERE account_type = 'Asset' LIMIT 1");
                    $arAcc = $this->db->single();
                    $arAccountId = $arAcc ? $arAcc->id : null;
                }
            }

            // Auto-locate Revenue Account
            if (empty($revenueAccountId)) {
                $this->db->query("SELECT id FROM chart_of_accounts WHERE account_type = 'Revenue' AND (account_name LIKE '%Sales%' OR account_name LIKE '%Revenue%' OR account_code = '4000') LIMIT 1");
                $revAcc = $this->db->single();
                if ($revAcc) {
                    $revenueAccountId = $revAcc->id;
                } else {
                    $this->db->query("SELECT id FROM chart_of_accounts WHERE account_type = 'Revenue' LIMIT 1");
                    $revAcc = $this->db->single();
                    $revenueAccountId = $revAcc ? $revAcc->id : null;
                }
            }

            // Auto-locate Damaged Inventory Expense Account
            if (empty($expenseAccountId)) {
                $this->db->query("SELECT id FROM chart_of_accounts WHERE account_type = 'Expense' AND (account_name LIKE '%Damaged%' OR account_name LIKE '%Operating%' OR account_name LIKE '%Loss%' OR account_code = '5050') LIMIT 1");
                $expAcc = $this->db->single();
                if ($expAcc) {
                    $expenseAccountId = $expAcc->id;
                } else {
                    $this->db->query("SELECT id FROM chart_of_accounts WHERE account_type = 'Expense' LIMIT 1");
                    $expAcc = $this->db->single();
                    $expenseAccountId = $expAcc ? $expAcc->id : null;
                }
            }

            // Auto-locate COGS Account
            $this->db->query("SELECT id FROM chart_of_accounts WHERE account_code = '5000' OR account_name LIKE '%COGS%' OR account_name LIKE '%Cost of Goods%' LIMIT 1");
            $cogsAcc = $this->db->single();
            if ($cogsAcc) {
                $cogsAccId = $cogsAcc->id;
            } else {
                $this->db->query("SELECT id FROM chart_of_accounts WHERE account_type = 'Expense' LIMIT 1");
                $cogsAcc = $this->db->single();
                $cogsAccId = $cogsAcc ? $cogsAcc->id : null;
            }

            // Auto-locate Inventory Asset Account
            $this->db->query("SELECT id FROM chart_of_accounts WHERE account_type = 'Asset' AND (account_name LIKE '%Inventory%' OR account_name LIKE '%Stock%' OR account_code = '1200' OR account_code = '1090') LIMIT 1");
            $invAcc = $this->db->single();
            if ($invAcc) {
                $invAccId = $invAcc->id;
            } else {
                $this->db->query("SELECT id FROM chart_of_accounts WHERE account_type = 'Asset' LIMIT 1");
                $invAcc = $this->db->single();
                $invAccId = $invAcc ? $invAcc->id : null;
            }

            $totalAmount = 0;
            foreach ($items as $item) {
                $totalAmount += ($item['qty'] * $item['price']);
            }

            // 1. Post Reverse Journal Entry (Debit Revenue, Credit AR)
            $desc = "Credit Note Issued: " . $noteData['credit_note_number'];
            $this->db->query("INSERT INTO journal_entries (entry_date, reference, description, created_by, status) 
                              VALUES (:date, :ref, :desc, :user, 'Posted')");
            $this->db->bind(':date', $noteData['date']);
            $this->db->bind(':ref', $noteData['credit_note_number']);
            $this->db->bind(':desc', $desc);
            $this->db->bind(':user', $userId);
            $this->db->execute();
            
            $journalId = $this->db->lastInsertId();

            $lines = [
                ['account_id' => $revenueAccountId, 'debit' => $totalAmount, 'credit' => 0],
                ['account_id' => $arAccountId, 'debit' => 0, 'credit' => $totalAmount]
            ];

            // 2. Create Credit Note Header
            $this->db->query("INSERT INTO credit_notes (credit_note_number, customer_id, note_date, total_amount, journal_entry_id, created_by) 
                              VALUES (:cn_num, :cust_id, :ndate, :total, :jid, :uid)");
            $this->db->bind(':cn_num', $noteData['credit_note_number']);
            $this->db->bind(':cust_id', $noteData['customer_id']);
            $this->db->bind(':ndate', $noteData['date']);
            $this->db->bind(':total', $totalAmount);
            $this->db->bind(':jid', $journalId);
            $this->db->bind(':uid', $userId);
            $this->db->execute();
            
            $cnId = $this->db->lastInsertId();

            require_once '../app/Models/FIFO.php';
            $fifo = new FIFO();

            // 3. Create Credit Note Items & handle inventory / restocking / FIFO
            $totalGoodCost = 0;
            $totalDamagedCost = 0;

            foreach ($items as $item) {
                $itemTotal = $item['qty'] * $item['price'];
                
                $isGood = ($item['condition'] === 'Good');
                $restocked = $isGood ? 1 : 0;
                
                $costPrice = 0.00;
                if (!empty($item['invoice_item_id'])) {
                    $this->db->query("SELECT cost_at_sale FROM invoice_items WHERE id = :id");
                    $this->db->bind(':id', $item['invoice_item_id']);
                    $invItem = $this->db->single();
                    if ($invItem) {
                        $costPrice = floatval($invItem->cost_at_sale);
                    }
                }
                
                if ($costPrice <= 0 && $item['item_id']) {
                    $this->db->query("SELECT cost, cost_price FROM items WHERE id = :id");
                    $this->db->bind(':id', $item['item_id']);
                    $itm = $this->db->single();
                    if ($itm) {
                        $costPrice = floatval($itm->cost > 0 ? $itm->cost : $itm->cost_price);
                    }
                }

                $this->db->query("INSERT INTO credit_note_items (credit_note_id, item_id, variation_option_id, invoice_id, invoice_item_id, description, quantity, unit_price, total, condition_status, restocked) 
                                  VALUES (:cnid, :iid, :vid, :invid, :invitemid, :desc, :qty, :price, :total, :cond, :restocked)");
                $this->db->bind(':cnid', $cnId);
                $this->db->bind(':iid', $item['item_id'] ?: null);
                $this->db->bind(':vid', $item['var_opt_id'] ?: null);
                $this->db->bind(':invid', $item['invoice_id'] ?: null);
                $this->db->bind(':invitemid', $item['invoice_item_id'] ?: null);
                $this->db->bind(':desc', $item['desc']);
                $this->db->bind(':qty', $item['qty']);
                $this->db->bind(':price', $item['price']);
                $this->db->bind(':total', $itemTotal);
                $this->db->bind(':cond', $item['condition']);
                $this->db->bind(':restocked', $restocked);
                $this->db->execute();

                if ($item['item_id']) {
                    if ($isGood) {
                        require_once '../app/Models/Item.php';
                        $itemModel = new Item();
                        $itemModel->updateStockDelta($item['item_id'], $item['qty']);

                        if ($item['var_opt_id']) {
                            $this->db->query("UPDATE item_variation_options SET quantity_on_hand = quantity_on_hand + :qty WHERE id = :id");
                            $this->db->bind(':qty', $item['qty']);
                            $this->db->bind(':id', $item['var_opt_id']);
                            $this->db->execute();
                        }

                        $fifo->recordReceipt($item['item_id'], $item['var_opt_id'], null, $item['qty'], $costPrice);
                        $totalGoodCost += ($item['qty'] * $costPrice);

                        // Log Stock Movement in Ledger
                        require_once '../app/Models/StockLedger.php';
                        $ledger = new StockLedger();
                        $this->db->query("SELECT warehouse_id FROM items WHERE id = :id");
                        $this->db->bind(':id', $item['item_id']);
                        $itemRow = $this->db->single();
                        $whId = $itemRow ? $itemRow->warehouse_id : null;
                        $ledger->logMovement($item['item_id'], $item['var_opt_id'] ?: null, $item['qty'], 0, 'Sales Return', $noteData['credit_note_number'], $whId, $userId, 'Sales Return Restocking', $costPrice);
                    } else {
                        $totalDamagedCost += ($item['qty'] * $costPrice);
                    }
                }
            }

            // 4. Record Cost / Inventory Adjustments
            if ($totalGoodCost > 0 && !empty($invAccId) && !empty($cogsAccId)) {
                $lines[] = ['account_id' => $invAccId, 'debit' => $totalGoodCost, 'credit' => 0];
                $lines[] = ['account_id' => $cogsAccId, 'debit' => 0, 'credit' => $totalGoodCost];
            }

            if ($totalDamagedCost > 0 && !empty($expenseAccountId) && !empty($cogsAccId)) {
                $lines[] = ['account_id' => $expenseAccountId, 'debit' => $totalDamagedCost, 'credit' => 0];
                $lines[] = ['account_id' => $cogsAccId, 'debit' => 0, 'credit' => $totalDamagedCost];
            }

            // 5. Post all transaction lines and update Chart of Account balances
            foreach ($lines as $line) {
                if (empty($line['account_id'])) continue;
                
                $this->db->query("INSERT INTO transactions (journal_entry_id, account_id, debit, credit) 
                                  VALUES (:jid, :aid, :deb, :cred)");
                $this->db->bind(':jid', $journalId);
                $this->db->bind(':aid', $line['account_id']);
                $this->db->bind(':deb', $line['debit']);
                $this->db->bind(':cred', $line['credit']);
                $this->db->execute();

                $this->db->query("SELECT account_type FROM chart_of_accounts WHERE id = :id");
                $this->db->bind(':id', $line['account_id']);
                $acc = $this->db->single();

                if ($acc) {
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
            }

            $this->db->commit();
            return $cnId;
        } catch (PDOException $e) {
            $this->db->rollBack();
            return false;
        }
    }
}