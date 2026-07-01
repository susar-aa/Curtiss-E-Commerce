<?php

class Delivery {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function createDelivery($data) {
        $this->db->beginTransaction();

        try {
            // 1. Insert into deliveries table
            $this->db->query("INSERT INTO deliveries (rep_route_id, secondary_rep_route_id, delivery_date, vehicle_number, driver_name, partner_name, selected_credit_invoices) 
                              VALUES (:rep_route_id, :secondary_rep_route_id, :delivery_date, :vehicle_number, :driver_name, :partner_name, :selected_credit_invoices)");
            $this->db->bind(':rep_route_id', $data['rep_route_id']);
            $this->db->bind(':secondary_rep_route_id', $data['secondary_rep_route_id'] ?? null);
            $this->db->bind(':delivery_date', $data['delivery_date']);
            $this->db->bind(':vehicle_number', $data['vehicle_number']);
            $this->db->bind(':driver_name', $data['driver_name']);
            $this->db->bind(':partner_name', $data['partner_name']);
            $this->db->bind(':selected_credit_invoices', $data['selected_credit_invoices'] ?? null);
            $this->db->execute();
            $deliveryId = $this->db->lastInsertId();

            // 2. Update status of rep_daily_routes to 'Delivery Arranged'
            $rids = [intval($data['rep_route_id'])];
            if (!empty($data['secondary_rep_route_id'])) {
                $rids[] = intval($data['secondary_rep_route_id']);
            }
            $rids = $this->resolveAllBoundRouteIds($rids);
            $ridsStr = implode(',', $rids);

            $this->db->query("UPDATE rep_daily_routes SET status = 'Delivery Arranged' WHERE id IN ($ridsStr)");
            $this->db->execute();

            $this->db->commit();
            return $deliveryId;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function getAllDeliveries() {
        $this->db->query("
            SELECT d.*, r.route_name, r.start_time, r.user_id as user_id, COALESCE(e.first_name, u.username) as first_name, COALESCE(e.last_name, '') as last_name,
                (SELECT COUNT(*) FROM invoices WHERE rep_route_id = r.id AND status != 'Voided') as bill_count,
                (SELECT COALESCE(SUM(total_amount - COALESCE(CASE WHEN global_discount_type = '%' THEN (total_amount * global_discount_val / 100) ELSE global_discount_val END, 0) + COALESCE(tax_amount, 0)), 0) 
                 FROM invoices WHERE rep_route_id = r.id AND status != 'Voided') as total_sales
            FROM deliveries d
            JOIN rep_daily_routes r ON d.rep_route_id = r.id
            LEFT JOIN users u ON r.user_id = u.id
            LEFT JOIN employees e ON u.employee_id = e.id
            ORDER BY d.delivery_date DESC, d.created_at DESC
        ");
        return $this->db->resultSet();
    }

    public function getDeliveryById($id) {
        $this->db->query("
            SELECT d.*, r.route_name, r.start_time, r.user_id as user_id, COALESCE(e.first_name, u.username) as first_name, COALESCE(e.last_name, '') as last_name,
                (SELECT COUNT(*) FROM invoices WHERE rep_route_id = r.id AND status != 'Voided') as bill_count,
                (SELECT COALESCE(SUM(total_amount - COALESCE(CASE WHEN global_discount_type = '%' THEN (total_amount * global_discount_val / 100) ELSE global_discount_val END, 0) + COALESCE(tax_amount, 0)), 0) 
                 FROM invoices WHERE rep_route_id = r.id AND status != 'Voided') as total_sales
            FROM deliveries d
            JOIN rep_daily_routes r ON d.rep_route_id = r.id
            LEFT JOIN users u ON r.user_id = u.id
            LEFT JOIN employees e ON u.employee_id = e.id
            WHERE d.id = :id
        ");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    public function getDeliveryInvoices($routeId, $secondaryRouteId = null) {
        $rids = [intval($routeId)];
        if ($secondaryRouteId) {
            $rids[] = intval($secondaryRouteId);
        }
        $rids = $this->resolveAllBoundRouteIds($rids);
        $ridsStr = implode(',', $rids);

        $this->db->query("
            SELECT i.*, c.name as customer_name,
            (total_amount - COALESCE(CASE WHEN global_discount_type = '%' THEN (total_amount * global_discount_val / 100) ELSE global_discount_val END, 0) + COALESCE(tax_amount, 0)) as true_grand_total
            FROM invoices i
            JOIN customers c ON i.customer_id = c.id
            WHERE i.rep_route_id IN ($ridsStr)
            ORDER BY i.created_at ASC
        ");
        return $this->db->resultSet();
    }

    public function getDeliveryCreditInvoices($routeId, $secondaryRouteId = null) {
        // Fetch the delivery first to check if there are selected credit invoices
        $this->db->query("SELECT selected_credit_invoices FROM deliveries WHERE rep_route_id = :rid LIMIT 1");
        $this->db->bind(':rid', $routeId);
        $del = $this->db->single();
        
        $selectedIds = [];
        if ($del && !empty($del->selected_credit_invoices)) {
            $selectedIds = json_decode($del->selected_credit_invoices, true);
        }
        
        if (!empty($selectedIds) && is_array($selectedIds)) {
            $idList = implode(',', array_map('intval', $selectedIds));
            $this->db->query("
                SELECT i.*, c.name as customer_name,
                (i.total_amount - COALESCE(CASE WHEN i.global_discount_type = '%' THEN (i.total_amount * i.global_discount_val / 100) ELSE i.global_discount_val END, 0) + COALESCE(i.tax_amount, 0)) as true_grand_total
                FROM invoices i
                JOIN customers c ON i.customer_id = c.id
                WHERE i.customer_id IN ($idList) AND i.status = 'Unpaid'
                ORDER BY i.invoice_date ASC, i.id ASC
            ");
            return $this->db->resultSet();
        }

        // Only show ticked bills. If none are ticked, return empty array.
        return [];
    }

    public function getDeliverySpreadsheetData($routeId, $secondaryRouteId = null) {
        $rids = [intval($routeId)];
        if ($secondaryRouteId) {
            $rids[] = intval($secondaryRouteId);
        }
        $rids = $this->resolveAllBoundRouteIds($rids);
        $ridsStr = implode(',', $rids);

        $this->db->query("
            SELECT ii.description as item_name, SUM(ii.quantity) as total_qty 
            FROM invoice_items ii 
            JOIN invoices i ON ii.invoice_id = i.id 
            WHERE i.rep_route_id IN ($ridsStr) AND i.status != 'Voided' 
            GROUP BY ii.description 
            ORDER BY ii.description ASC
        ");
        return $this->db->resultSet();
    }

    public function getDeliveryBalancingData($deliveryId) {
        $delivery = $this->getDeliveryById($deliveryId);
        if (!$delivery) return null;

        $rids = [intval($delivery->rep_route_id)];
        if (!empty($delivery->secondary_rep_route_id)) {
            $rids[] = intval($delivery->secondary_rep_route_id);
        }
        $rids = $this->resolveAllBoundRouteIds($rids);
        $ridsStr = implode(',', $rids);

        // Ensure 1605 account exists in chart of accounts
        try {
            $this->db->query("SELECT id FROM chart_of_accounts WHERE account_code = '1605'");
            if (!$this->db->single()) {
                $this->db->query("SELECT id FROM chart_of_accounts WHERE account_code = '1600' LIMIT 1");
                $parentRow = $this->db->single();
                $pId = $parentRow ? $parentRow->id : null;

                $this->db->query("INSERT INTO chart_of_accounts (account_code, account_name, account_type, balance, parent_id) 
                                  VALUES ('1605', 'Temporary Bank Account', 'Asset', 0.00, :pid)");
                $this->db->bind(':pid', $pId);
                $this->db->execute();
            }
        } catch (Exception $e) {}

        // Ensure 1010 account exists for cheques
        try {
            $this->db->query("SELECT id FROM chart_of_accounts WHERE account_code = '1010'");
            if (!$this->db->single()) {
                $this->db->query("INSERT INTO chart_of_accounts (account_code, account_name, account_type, balance, parent_id) 
                                  VALUES ('1010', 'Cheque in Hand', 'Asset', 0.00, NULL)");
                $this->db->execute();
            }
        } catch (Exception $e) {}

        // Ensure 1090 account exists
        try {
            $this->db->query("SELECT id FROM chart_of_accounts WHERE account_code = '1090'");
            if (!$this->db->single()) {
                $this->db->query("INSERT INTO chart_of_accounts (account_code, account_name, account_type, balance, parent_id) 
                                  VALUES ('1090', 'Driver Transit Collections (Temp)', 'Asset', 0.00, NULL)");
                $this->db->execute();
            }
        } catch (Exception $e) {}

        // 1. Fetch Invoices stats (today's Cash Sales vs today's Credit Sales)
        $this->db->query("
            SELECT id, customer_id, delivery_status, status, total_amount, global_discount_val, global_discount_type, tax_amount 
            FROM invoices 
            WHERE rep_route_id IN ($ridsStr) AND status != 'Voided'
        ");
        $routeInvoices = $this->db->resultSet();

        $cash_sales = 0.0;
        $cheque_sales = 0.0;
        $bank_sales = 0.0;
        $credit_sales = 0.0;

        // Group dispatches by customer
        $custDispatches = [];
        foreach ($routeInvoices as $inv) {
            $amt = $this->getTrueGrandTotal($inv);
            if ($inv->delivery_status === 'Delivered') {
                $custDispatches[$inv->customer_id] = ($custDispatches[$inv->customer_id] ?? 0.0) + $amt;
            }
        }

        // For each customer who received a delivery, calculate their cash & credit sales
        foreach ($custDispatches as $cid => $dispatchAmt) {
            // Fetch customer payments on this route.
            $this->db->query("SELECT payment_method, COALESCE(SUM(amount), 0) as amt FROM customer_payments WHERE rep_route_id IN ($ridsStr) AND customer_id = :cid GROUP BY payment_method");
            $this->db->bind(':cid', $cid);
            $pays = $this->db->resultSet() ?: [];
            
            $totPaid = 0.0;
            $pMap = ['Cash' => 0.0, 'Cheque' => 0.0, 'Bank Transfer' => 0.0];
            foreach ($pays as $p) {
                $amt = floatval($p->amt);
                $totPaid += $amt;
                if (isset($pMap[$p->payment_method])) {
                    $pMap[$p->payment_method] += $amt;
                }
            }

            $pool = $dispatchAmt;
            // Allocate in order: Cash, then Bank Transfer, then Cheque
            $cCash = min($pool, $pMap['Cash']);
            $cash_sales += $cCash;
            $pool -= $cCash;

            $cBank = min($pool, $pMap['Bank Transfer']);
            $bank_sales += $cBank;
            $pool -= $cBank;

            $cCheque = min($pool, $pMap['Cheque']);
            $cheque_sales += $cCheque;
            $pool -= $cCheque;

            $credit_sales += $pool;
        }

        // 2. Fetch Driver Collections logged today
        $this->db->query("
            SELECT 
                COALESCE(SUM(CASE WHEN payment_method = 'Cash' THEN amount ELSE 0 END), 0) as cash_collections,
                COALESCE(SUM(CASE WHEN payment_method = 'Cheque' THEN amount ELSE 0 END), 0) as cheque_collections,
                COALESCE(SUM(CASE WHEN payment_method = 'Bank Transfer' THEN amount ELSE 0 END), 0) as bank_collections
            FROM customer_payments 
            WHERE rep_route_id IN ($ridsStr)
        ");
        $collectionsStats = $this->db->single();

        // 3. Fetch Stock summary
        $this->db->query("
            SELECT 
                MAX(ii.item_id) as item_id, 
                MAX(ii.variation_option_id) as variation_option_id,
                TRIM(ii.description) as item_name,
                SUM(ii.loaded_quantity) as loaded_qty,
                SUM(CASE WHEN i.delivery_status = 'Delivered' THEN ii.quantity ELSE 0 END) as delivered_qty,
                (SUM(ii.loaded_quantity) - SUM(CASE WHEN i.delivery_status = 'Delivered' THEN ii.quantity ELSE 0 END)) as remaining_qty
            FROM invoice_items ii
            JOIN invoices i ON ii.invoice_id = i.id
            WHERE i.rep_route_id IN ($ridsStr) AND i.status != 'Voided'
            GROUP BY TRIM(ii.description)
            ORDER BY TRIM(ii.description) ASC
        ");
        $stockItems = $this->db->resultSet();

        // 4. Fetch cheques collected on this delivery
        $this->db->query("
            SELECT c.*, cust.name as customer_name
            FROM cheques c
            JOIN customers cust ON c.customer_id = cust.id
            WHERE c.rep_route_id IN ($ridsStr)
        ");
        $chequesCollected = $this->db->resultSet();

        // 5. Fetch all ledger accounts
        $this->db->query("SELECT id, account_code, account_name FROM chart_of_accounts ORDER BY account_code ASC");
        $allAccounts = $this->db->resultSet() ?: [];

        // 6. Fetch bank accounts for selector
        $this->db->query("SELECT id FROM chart_of_accounts WHERE account_code = '1600'");
        $parent = $this->db->single();
        $parentId = $parent ? $parent->id : 0;
        $this->db->query("SELECT id, account_code, account_name FROM chart_of_accounts WHERE parent_id = :pid ORDER BY account_code ASC");
        $this->db->bind(':pid', $parentId);
        $bankAccounts = $this->db->resultSet() ?: [];

        // Fetch route collections payments
        $this->db->query("
            SELECT cp.*, cust.name as customer_name
            FROM customer_payments cp
            JOIN customers cust ON cp.customer_id = cust.id
            WHERE cp.rep_route_id IN ($ridsStr)
        ");
        $payments = $this->db->resultSet() ?: [];

        return [
            'delivery' => $delivery,
            'cash_sales' => floatval($cash_sales),
            'cheque_sales' => floatval($cheque_sales),
            'bank_sales' => floatval($bank_sales),
            'credit_sales' => floatval($credit_sales),
            'cash_collections' => floatval($collectionsStats->cash_collections),
            'cheque_collections' => floatval($collectionsStats->cheque_collections),
            'bank_collections' => floatval($collectionsStats->bank_collections),
            'stock_items' => $stockItems,
            'cheques' => $chequesCollected,
            'all_accounts' => $allAccounts,
            'bank_accounts' => $bankAccounts,
            'payments' => $payments
        ];
    }

    public function finalizeDelivery($deliveryId, $adminUserId, $selectedPaymentIds = [], $selectedInvoiceIds = [], $debitAccounts = [], $creditAccounts = [], $returnedItems = [], $vehicleNumber = null, $driverName = null, $partnerName = null) {
        $this->db->beginTransaction();
        try {
            $delivery = $this->getDeliveryById($deliveryId);
            if (!$delivery) {
                throw new Exception("Delivery not found");
            }
            if ($delivery->status === 'Finalized') {
                throw new Exception("Delivery is already finalized");
            }

            if ($vehicleNumber !== null && $driverName !== null) {
                $this->db->query("UPDATE deliveries 
                                  SET vehicle_number = :v, driver_name = :d, partner_name = :p 
                                  WHERE id = :id");
                $this->db->bind(':v', $vehicleNumber);
                $this->db->bind(':d', $driverName);
                $this->db->bind(':p', $partnerName);
                $this->db->bind(':id', $deliveryId);
                $this->db->execute();
            }

            // Ensure 1605 account exists in chart of accounts
            $this->db->query("SELECT id FROM chart_of_accounts WHERE account_code = '1605'");
            if (!$this->db->single()) {
                $this->db->query("SELECT id FROM chart_of_accounts WHERE account_code = '1600' LIMIT 1");
                $parentRow = $this->db->single();
                $pId = $parentRow ? $parentRow->id : null;

                $this->db->query("INSERT INTO chart_of_accounts (account_code, account_name, account_type, balance, parent_id) 
                                  VALUES ('1605', 'Temporary Bank Account', 'Asset', 0.00, :pid)");
                $this->db->bind(':pid', $pId);
                $this->db->execute();
            }

            // Ensure 1010 account exists for cheques
            $this->db->query("SELECT id FROM chart_of_accounts WHERE account_code = '1010'");
            if (!$this->db->single()) {
                $this->db->query("INSERT INTO chart_of_accounts (account_code, account_name, account_type, balance, parent_id) 
                                  VALUES ('1010', 'Cheque in Hand', 'Asset', 0.00, NULL)");
                $this->db->execute();
            }

            // 1. Update statuses to Finalized
            $this->db->query("UPDATE deliveries SET status = 'Finalized' WHERE id = :id");
            $this->db->bind(':id', $deliveryId);
            $this->db->execute();

            $rids = [intval($delivery->rep_route_id)];
            if (!empty($delivery->secondary_rep_route_id)) {
                $rids[] = intval($delivery->secondary_rep_route_id);
            }
            $rids = $this->resolveAllBoundRouteIds($rids);
            $ridsStr = implode(',', $rids);

            $this->db->query("UPDATE rep_daily_routes SET status = 'Finalized' WHERE id IN ($ridsStr)");
            $this->db->execute();

            // 2. Stock deductions & reservation releases

            $this->db->query("SELECT id, delivery_status, stock_status FROM invoices WHERE rep_route_id IN ($ridsStr) AND status != 'Voided'");
            $invoices = $this->db->resultSet();

            require_once '../app/Models/FIFO.php';
            $fifo = new FIFO();

            foreach ($invoices as $invoice) {
                $this->db->query("SELECT * FROM invoice_items WHERE invoice_id = :iid");
                $this->db->bind(':iid', $invoice->id);
                $items = $this->db->resultSet();

                if ($invoice->delivery_status === 'Delivered') {
                    if ($invoice->stock_status === 'reserved') {
                        foreach ($items as $item) {
                            $qty = floatval($item->quantity);
                            $loadedQty = floatval($item->loaded_quantity);
                            $itemId = $item->item_id;
                            $varId = $item->variation_option_id;

                            if (!$itemId && !empty($item->description)) {
                                $this->db->query("SELECT id FROM items WHERE name = :name LIMIT 1");
                                $this->db->bind(':name', $item->description);
                                $rowItem = $this->db->single();
                                if ($rowItem) $itemId = $rowItem->id;
                            }

                            if ($itemId) {
                                require_once '../app/Models/Item.php';
                                $itemModel = new Item();
                                $itemModel->updateStockDelta($itemId, -$qty);

                                $this->db->query("UPDATE items SET quantity_reserved = GREATEST(0, CAST(quantity_reserved AS SIGNED) - :loadedQty) WHERE id = :id");
                                $this->db->bind(':loadedQty', $loadedQty);
                                $this->db->bind(':id', $itemId);
                                $this->db->execute();
                            }
                            if ($varId) {
                                $this->db->query("UPDATE item_variation_options SET quantity_on_hand = GREATEST(0, CAST(quantity_on_hand AS SIGNED) - :qty) WHERE id = :id");
                                $this->db->bind(':qty', $qty);
                                $this->db->bind(':id', $varId);
                                $this->db->execute();

                                $this->db->query("UPDATE item_variation_options SET quantity_reserved = GREATEST(0, CAST(quantity_reserved AS SIGNED) - :loadedQty) WHERE id = :id");
                                $this->db->bind(':loadedQty', $loadedQty);
                                $this->db->bind(':id', $varId);
                                $this->db->execute();
                            }

                            $fifo->depleteStock($itemId, $varId, $qty, $item->id, null);

                            // Log stock movement in ledger (depletion)
                            require_once '../app/Models/StockLedger.php';
                            $ledger = new StockLedger();
                            $this->db->query("SELECT warehouse_id, cost, cost_price FROM items WHERE id = :id");
                            $this->db->bind(':id', $itemId);
                            $itemRow = $this->db->single();
                            $whId = $itemRow ? $itemRow->warehouse_id : null;
                            $itemCost = $itemRow ? floatval($itemRow->cost > 0 ? $itemRow->cost : ($itemRow->cost_price > 0 ? $itemRow->cost_price : 0.00)) : 0.00;
                            
                            $this->db->query("SELECT invoice_number FROM invoices WHERE id = :iid");
                            $this->db->bind(':iid', $invoice->id);
                            $invRow = $this->db->single();
                            $invNum = $invRow ? $invRow->invoice_number : '';
                            $ledger->logMovement($itemId, $varId ?: null, 0, $qty, 'Sales Invoice', $invNum, $whId, $adminUserId, 'Delivery Finalized - Stock Deducted', $itemCost);
                        }
                        $this->db->query("UPDATE invoices SET stock_status = 'deducted' WHERE id = :iid");
                        $this->db->bind(':iid', $invoice->id);
                        $this->db->execute();
                    }
                } else {
                    if ($invoice->stock_status === 'reserved') {
                        foreach ($items as $item) {
                            $loadedQty = floatval($item->loaded_quantity);
                            $itemId = $item->item_id;
                            $varId = $item->variation_option_id;

                            if (!$itemId && !empty($item->description)) {
                                $this->db->query("SELECT id FROM items WHERE name = :name LIMIT 1");
                                $this->db->bind(':name', $item->description);
                                $rowItem = $this->db->single();
                                if ($rowItem) $itemId = $rowItem->id;
                            }

                            if ($itemId) {
                                $this->db->query("UPDATE items SET quantity_reserved = GREATEST(0, CAST(quantity_reserved AS SIGNED) - :loadedQty) WHERE id = :id");
                                $this->db->bind(':loadedQty', $loadedQty);
                                $this->db->bind(':id', $itemId);
                                $this->db->execute();
                            }
                            if ($varId) {
                                $this->db->query("UPDATE item_variation_options SET quantity_reserved = GREATEST(0, CAST(quantity_reserved AS SIGNED) - :loadedQty) WHERE id = :id");
                                $this->db->bind(':loadedQty', $loadedQty);
                                $this->db->bind(':id', $varId);
                                $this->db->execute();
                            }
                        }
                        $this->db->query("UPDATE invoices SET stock_status = 'returned' WHERE id = :iid");
                        $this->db->bind(':iid', $invoice->id);
                        $this->db->execute();
                    }
                }
            }

            // 2b. Apply administrator's actual counted returned products adjustments
            if (!empty($returnedItems) && is_array($returnedItems)) {
                foreach ($returnedItems as $ret) {
                    $itemId = intval($ret['item_id'] ?? 0);
                    $varId = intval($ret['variation_option_id'] ?? 0);
                    $loadedQty = floatval($ret['loaded_qty'] ?? 0);
                    $deliveredQty = floatval($ret['delivered_qty'] ?? 0);
                    $actualReturnedQty = floatval($ret['actual_returned_qty'] ?? 0);

                    if (!$itemId && !empty($ret['item_name'])) {
                        $this->db->query("SELECT id FROM items WHERE name = :name LIMIT 1");
                        $this->db->bind(':name', $ret['item_name']);
                        $rowItem = $this->db->single();
                        if ($rowItem) $itemId = $rowItem->id;
                    }

                    $expectedReturned = $loadedQty - $deliveredQty;
                    $adjustment = $actualReturnedQty - $expectedReturned;
                    if ($adjustment != 0) {
                        if ($varId) {
                            $this->db->query("UPDATE item_variation_options SET quantity_on_hand = GREATEST(0, CAST(quantity_on_hand AS SIGNED) + :adj) WHERE id = :id");
                            $this->db->bind(':adj', $adjustment);
                            $this->db->bind(':id', $varId);
                            $this->db->execute();
                        } else if ($itemId) {
                            require_once '../app/Models/Item.php';
                            $itemModel = new Item();
                            $itemModel->updateStockDelta($itemId, $adjustment);
                        }

                        // Log stock movement in ledger (counted returns adjustment)
                        require_once '../app/Models/StockLedger.php';
                        $ledger = new StockLedger();
                        $this->db->query("SELECT warehouse_id, cost, cost_price FROM items WHERE id = :id");
                        $this->db->bind(':id', $itemId);
                        $itemRow = $this->db->single();
                        $whId = $itemRow ? $itemRow->warehouse_id : null;
                        $itemCost = $itemRow ? floatval($itemRow->cost > 0 ? $itemRow->cost : ($itemRow->cost_price > 0 ? $itemRow->cost_price : 0.00)) : 0.00;

                        $qtyIn = $adjustment > 0 ? $adjustment : 0;
                        $qtyOut = $adjustment < 0 ? abs($adjustment) : 0;
                        $ledger->logMovement($itemId, $varId ?: null, $qtyIn, $qtyOut, 'Stock Adjustment', 'DEL-' . $deliveryId, $whId, $adminUserId, 'Delivery Finalized - Counted Returns Adjustment', $itemCost);
                    }
                }
            }

            // 3. Post Double Entry accounting entries for the delivered sales invoices if checked
            $this->db->query("SELECT id, account_code FROM chart_of_accounts WHERE account_code IN ('1200', '4000')");
            $salesAccs = $this->db->resultSet();
            $sAccMap = [];
            foreach ($salesAccs as $sa) { $sAccMap[$sa->account_code] = $sa->id; }
            $defaultArAcc = $sAccMap['1200'] ?? null;
            $defaultSalesAcc = $sAccMap['4000'] ?? null;

            foreach ($invoices as $invoice) {
                if ($invoice->delivery_status === 'Delivered') {
                    $invId = intval($invoice->id);
                    if (!empty($selectedInvoiceIds) && !in_array($invId, $selectedInvoiceIds)) {
                        continue;
                    }

                    $gTotal = $this->getTrueGrandTotal($invoice);
                    if ($gTotal <= 0) continue;

                    // Ensure sales JE not already posted
                    $this->db->query("SELECT id FROM journal_entries WHERE reference = :ref LIMIT 1");
                    $this->db->bind(':ref', "INV-SALES-" . $invId);
                    if ($this->db->single()) {
                        continue;
                    }

                    $invDebAcc = isset($debitAccounts["inv_" . $invId]) ? intval($debitAccounts["inv_" . $invId]) : $defaultArAcc;
                    $invCredAcc = isset($creditAccounts["inv_" . $invId]) ? intval($creditAccounts["inv_" . $invId]) : $defaultSalesAcc;

                    if ($invDebAcc && $invCredAcc) {
                        $this->db->query("INSERT INTO journal_entries (entry_date, reference, description, created_by, status) 
                                          VALUES (CURDATE(), :ref, :desc, :uid, 'Posted')");
                        $this->db->bind(':ref', "INV-SALES-" . $invId);
                        $this->db->bind(':desc', "Sales Invoice Delivery Revenue Posting (" . $invoice->invoice_number . ")");
                        $this->db->bind(':uid', $adminUserId);
                        $this->db->execute();
                        $invJid = $this->db->lastInsertId();

                        // Debit Accounts Receivable
                        $this->db->query("INSERT INTO transactions (journal_entry_id, account_id, debit, credit) VALUES (:jid, :aid, :deb, 0)");
                        $this->db->bind(':jid', $invJid);
                        $this->db->bind(':aid', $invDebAcc);
                        $this->db->bind(':deb', $gTotal);
                        $this->db->execute();

                        $this->db->query("UPDATE chart_of_accounts SET balance = balance + :amt WHERE id = :aid");
                        $this->db->bind(':amt', $gTotal);
                        $this->db->bind(':aid', $invDebAcc);
                        $this->db->execute();

                        // Credit Sales Revenue
                        $this->db->query("INSERT INTO transactions (journal_entry_id, account_id, debit, credit) VALUES (:jid, :aid, 0, :cred)");
                        $this->db->bind(':jid', $invJid);
                        $this->db->bind(':aid', $invCredAcc);
                        $this->db->bind(':cred', $gTotal);
                        $this->db->execute();

                        $this->db->query("UPDATE chart_of_accounts SET balance = balance - :amt WHERE id = :aid");
                        $this->db->bind(':amt', $gTotal);
                        $this->db->bind(':aid', $invCredAcc);
                        $this->db->execute();
                    }
                }
            }

            // 4. Financial Clearance collections balancing
            $this->db->query("
                SELECT * 
                FROM customer_payments 
                WHERE rep_route_id = :rid
            ");
            $this->db->bind(':rid', $delivery->rep_route_id);
            $routePayments = $this->db->resultSet();

            if (!empty($routePayments)) {
                $this->db->query("SELECT id, account_code FROM chart_of_accounts WHERE account_code IN ('1000', '1010', '1600', '1605', '1200', '1090')");
                $accounts = $this->db->resultSet();
                $accMap = [];
                foreach ($accounts as $a) { $accMap[$a->account_code] = $a->id; }

                $cashAcc = $accMap['1000'] ?? null;
                $chequeAcc = $accMap['1010'] ?? null;
                $tempBankAcc = $accMap['1605'] ?? ($accMap['1600'] ?? null);
                $arAcc = $accMap['1200'] ?? null;
                $transitAcc = $accMap['1090'] ?? null;

                foreach ($routePayments as $pay) {
                    $payId = intval($pay->id);
                    if (!empty($selectedPaymentIds) && !in_array($payId, $selectedPaymentIds)) {
                        continue;
                    }

                    $amount = floatval($pay->amount);
                    if ($amount <= 0) continue;

                    $debAccId = isset($debitAccounts[$payId]) ? intval($debitAccounts[$payId]) : null;
                    if (!$debAccId) {
                        $method = $pay->payment_method;
                        if ($method === 'Cash') {
                            $debAccId = $cashAcc;
                        } elseif ($method === 'Cheque') {
                            $debAccId = $chequeAcc;
                        } elseif ($method === 'Bank Transfer') {
                            $debAccId = $tempBankAcc;
                        }
                    }
                    if (!$debAccId) continue;

                    $credAccId = isset($creditAccounts[$payId]) ? intval($creditAccounts[$payId]) : ($transitAcc ?: $arAcc);

                    $refCode = "PMT-BAL-" . time() . rand(10,99);

                    // Insert Journal Entry
                    $this->db->query("INSERT INTO journal_entries (entry_date, reference, description, created_by, status) 
                                      VALUES (CURDATE(), :ref, :desc, :uid, 'Posted')");
                    $this->db->bind(':ref', $refCode);
                    $this->db->bind(':desc', "Finalized Delivery Collection (" . $pay->payment_method . ")");
                    $this->db->bind(':uid', $adminUserId);
                    $this->db->execute();
                    $payJid = $this->db->lastInsertId();

                    // Update payment record to associate with this journal entry
                    $this->db->query("UPDATE customer_payments SET journal_entry_id = :jid WHERE id = :pid");
                    $this->db->bind(':jid', $payJid);
                    $this->db->bind(':pid', $pay->id);
                    $this->db->execute();

                    // Debit Asset Account
                    $this->db->query("INSERT INTO transactions (journal_entry_id, account_id, debit, credit) VALUES (:jid, :aid, :deb, 0)");
                    $this->db->bind(':jid', $payJid);
                    $this->db->bind(':aid', $debAccId);
                    $this->db->bind(':deb', $amount);
                    $this->db->execute();

                    $this->db->query("UPDATE chart_of_accounts SET balance = balance + :amt WHERE id = :aid");
                    $this->db->bind(':amt', $amount);
                    $this->db->bind(':aid', $debAccId);
                    $this->db->execute();

                    // Credit cleared transit account
                    $this->db->query("INSERT INTO transactions (journal_entry_id, account_id, debit, credit) VALUES (:jid, :aid, 0, :cred)");
                    $this->db->bind(':jid', $payJid);
                    $this->db->bind(':aid', $credAccId);
                    $this->db->bind(':cred', $amount);
                    $this->db->execute();

                    $this->db->query("UPDATE chart_of_accounts SET balance = balance - :amt WHERE id = :aid");
                    $this->db->bind(':amt', $amount);
                    $this->db->bind(':aid', $credAccId);
                    $this->db->execute();

                    // FIFO mark invoices Paid
                    $customerId = $pay->customer_id;
                    $this->db->query("
                        SELECT id, total_amount, global_discount_val, global_discount_type, tax_amount 
                        FROM invoices 
                        WHERE customer_id = :cid AND status = 'Unpaid' 
                        ORDER BY invoice_date ASC, id ASC
                    ");
                    $this->db->bind(':cid', $customerId);
                    $unpaidList = $this->db->resultSet();

                    $pool = $amount;
                    foreach ($unpaidList as $unp) {
                        $gTotal = $this->getTrueGrandTotal($unp);
                        if ($pool >= $gTotal) {
                            $this->db->query("UPDATE invoices SET status = 'Paid' WHERE id = :id");
                            $this->db->bind(':id', $unp->id);
                            $this->db->execute();
                            $pool -= $gTotal;
                        } else {
                            break;
                        }
                    }
                }
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("finalizeDelivery Error: " . $e->getMessage());
            throw $e;
        }
    }

    private function getTrueGrandTotal($invoice) {
        $subTotal = floatval($invoice->total_amount ?? 0);
        $globalDiscVal = floatval($invoice->global_discount_val ?? 0);
        $globalDiscType = $invoice->global_discount_type ?? 'Rs';
        $globalDisc = ($globalDiscType === '%') ? ($subTotal * $globalDiscVal / 100) : $globalDiscVal;
        return max(0, $subTotal - $globalDisc) + floatval($invoice->tax_amount ?? 0);
    }

    private function resolveAllBoundRouteIds($rids) {
        if (empty($rids)) return [];
        $rids = array_map('intval', $rids);
        $ridsStr = implode(',', $rids);
        
        $this->db->query("SELECT DISTINCT route_binding_id FROM rep_daily_routes WHERE id IN ($ridsStr) AND route_binding_id IS NOT NULL");
        $bindings = $this->db->resultSet();
        
        if (!empty($bindings)) {
            $bindingIds = [];
            foreach ($bindings as $b) {
                $bindingIds[] = intval($b->route_binding_id);
            }
            $bindingIdsStr = implode(',', $bindingIds);
            
            $this->db->query("SELECT id FROM rep_daily_routes WHERE route_binding_id IN ($bindingIdsStr)");
            $allRoutes = $this->db->resultSet();
            foreach ($allRoutes as $r) {
                $rids[] = intval($r->id);
            }
        }
        return array_unique($rids);
    }
}
