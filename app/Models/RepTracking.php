<?php

class RepTracking {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function getAllRoutes() {
        $this->db->query("
            SELECT r.*, COALESCE(e.first_name, u.username) as first_name, COALESCE(e.last_name, '') as last_name,
                (SELECT COUNT(*) FROM invoices WHERE rep_route_id = r.id AND status != 'Voided') as bill_count,
                (SELECT COALESCE(SUM(total_amount - COALESCE(CASE WHEN global_discount_type = '%' THEN (total_amount * global_discount_val / 100) ELSE global_discount_val END, 0) + COALESCE(tax_amount, 0)), 0) 
                 FROM invoices WHERE rep_route_id = r.id AND status != 'Voided') as total_sales,
                (SELECT COUNT(*) FROM pending_collections WHERE route_id = r.id AND status = 'Pending') as unfinalized_count,
                rb.name as binding_name
            FROM rep_daily_routes r
            LEFT JOIN users u ON r.user_id = u.id
            LEFT JOIN employees e ON u.email = e.email
            LEFT JOIN route_bindings rb ON r.route_binding_id = rb.id
            WHERE r.id NOT IN (SELECT rep_route_id FROM deliveries)
              AND r.status != 'Bound' AND r.status != 'Bound Into Route'
            ORDER BY r.start_time DESC
        ");
        $rawRoutes = $this->db->resultSet() ?: [];
        
        $grouped = [];
        $unbound = [];
        
        foreach ($rawRoutes as $route) {
            if ($route->route_binding_id && $route->is_merged_route == 0) {
                $grouped[$route->route_binding_id][] = $route;
            } else {
                $unbound[] = $route;
            }
        }
        
        $finalRoutes = [];
        foreach ($unbound as $route) {
            $route->is_bound_group = false;
            $finalRoutes[] = $route;
        }
        
        foreach ($grouped as $bindingId => $routesList) {
            // Pick route with lowest ID as primary representative
            usort($routesList, function($a, $b) { return $a->id - $b->id; });
            $rep = $routesList[0];
            
            $merged = clone $rep;
            $merged->route_name = $rep->binding_name; // Use new binding name
            $merged->bill_count = 0;
            $merged->total_sales = 0.0;
            $merged->unfinalized_count = 0;
            $merged->is_bound_group = true;
            
            $names = [];
            foreach ($routesList as $r) {
                $merged->bill_count += intval($r->bill_count);
                $merged->total_sales += floatval($r->total_sales);
                $merged->unfinalized_count += intval($r->unfinalized_count);
                $names[] = $r->route_name;
            }
            
            $merged->constituent_routes_info = implode(' & ', $names);
            $finalRoutes[] = $merged;
        }
        
        // Sort final list by start_time descending
        usort($finalRoutes, function($a, $b) {
            return strtotime($b->start_time) - strtotime($a->start_time);
        });
        
        return $finalRoutes;
    }

    public function getRouteBills($routeId) {
        $routeIds = [$routeId];
        $this->db->query("SELECT route_binding_id FROM rep_daily_routes WHERE id = :rid LIMIT 1");
        $this->db->bind(':rid', $routeId);
        $routeRow = $this->db->single();
        if ($routeRow && $routeRow->route_binding_id) {
            $this->db->query("SELECT id FROM rep_daily_routes WHERE route_binding_id = :bid");
            $this->db->bind(':bid', $routeRow->route_binding_id);
            $boundRoutes = $this->db->resultSet();
            foreach ($boundRoutes as $br) {
                $routeIds[] = intval($br->id);
            }
        }
        $routeIds = array_unique($routeIds);
        $routeIdsStr = implode(',', $routeIds);

        $this->db->query("
            SELECT i.*, c.name as customer_name,
            (total_amount - COALESCE(CASE WHEN global_discount_type = '%' THEN (total_amount * global_discount_val / 100) ELSE global_discount_val END, 0) + COALESCE(tax_amount, 0)) as true_grand_total
            FROM invoices i
            JOIN customers c ON i.customer_id = c.id
            WHERE i.rep_route_id IN ($routeIdsStr)
            ORDER BY i.created_at ASC
        ");
        return $this->db->resultSet();
    }

    // UPDATED: Now fetches live high-level statistics for the printable report header block
    public function getRouteById($routeId) {
        $this->db->query("
            SELECT r.*, COALESCE(e.first_name, u.username) as first_name, COALESCE(e.last_name, '') as last_name,
                (SELECT COUNT(*) FROM invoices WHERE rep_route_id = r.id AND status != 'Voided') as bill_count,
                (SELECT COALESCE(SUM(total_amount - COALESCE(CASE WHEN global_discount_type = '%' THEN (total_amount * global_discount_val / 100) ELSE global_discount_val END, 0) + COALESCE(tax_amount, 0)), 0) 
                 FROM invoices WHERE rep_route_id = r.id AND status != 'Voided') as total_sales
            FROM rep_daily_routes r
            LEFT JOIN users u ON r.user_id = u.id
            LEFT JOIN employees e ON u.email = e.email
            WHERE r.id = :rid
        ");
        $this->db->bind(':rid', $routeId);
        return $this->db->single();
    }

    public function getRouteLoadingItems($routeId) {
        $routeIds = [$routeId];
        $this->db->query("SELECT route_binding_id FROM rep_daily_routes WHERE id = :rid LIMIT 1");
        $this->db->bind(':rid', $routeId);
        $routeRow = $this->db->single();
        if ($routeRow && $routeRow->route_binding_id) {
            $this->db->query("SELECT id FROM rep_daily_routes WHERE route_binding_id = :bid");
            $this->db->bind(':bid', $routeRow->route_binding_id);
            $boundRoutes = $this->db->resultSet();
            foreach ($boundRoutes as $br) {
                $routeIds[] = intval($br->id);
            }
        }
        $routeIds = array_unique($routeIds);
        $routeIdsStr = implode(',', $routeIds);

        $this->db->query("
            SELECT ii.description as item_name,
                   SUM(ii.quantity) as total_qty,
                   AVG(ii.unit_price) as unit_price,
                   COALESCE(ic.name, 'Uncategorized') as category_name
            FROM invoice_items ii
            JOIN invoices i ON ii.invoice_id = i.id
            LEFT JOIN items it ON ii.item_id = it.id
            LEFT JOIN item_categories ic ON it.category_id = ic.id
            WHERE i.rep_route_id IN ($routeIdsStr) AND i.status != 'Voided'
            GROUP BY ii.description, COALESCE(ic.id, 0), COALESCE(ic.name, 'Uncategorized')
            ORDER BY category_name ASC, ii.description ASC
        ");
        return $this->db->resultSet();
    }

    public function getRouteFinalLoadingItems($routeId) {
        $this->db->query("
            SELECT dpi.item_id,
                   dpi.item_name,
                   dpi.required_qty,
                   dpi.loaded_qty as pre_loaded_qty,
                   dpi.final_loaded_qty,
                   dpi.variance,
                   COALESCE(ic.name, 'Uncategorized') as category_name,
                   COALESCE(
                       (SELECT AVG(ii.unit_price) 
                        FROM invoice_items ii 
                        JOIN invoices i ON ii.invoice_id = i.id 
                        WHERE i.rep_route_id = :rid AND ii.item_id = dpi.item_id AND i.status != 'Voided'),
                       (SELECT price FROM items WHERE id = dpi.item_id),
                       0
                   ) as unit_price
            FROM delivery_picking_items dpi
            JOIN deliveries d ON dpi.delivery_id = d.id
            LEFT JOIN items it ON dpi.item_id = it.id
            LEFT JOIN item_categories ic ON it.category_id = ic.id
            WHERE d.rep_route_id = :rid OR d.secondary_rep_route_id = :rid
            ORDER BY category_name ASC, dpi.item_name ASC
        ");
        $this->db->bind(':rid', $routeId);
        return $this->db->resultSet() ?: [];
    }

    /**
     * Chronological GPS path: day start → each invoice → day end.
     */
    public function getRoutePath($routeId) {
        $routeIds = [$routeId];
        $this->db->query("SELECT route_binding_id FROM rep_daily_routes WHERE id = :rid LIMIT 1");
        $this->db->bind(':rid', $routeId);
        $routeRow = $this->db->single();
        if ($routeRow && $routeRow->route_binding_id) {
            $this->db->query("SELECT id FROM rep_daily_routes WHERE route_binding_id = :bid");
            $this->db->bind(':bid', $routeRow->route_binding_id);
            $boundRoutes = $this->db->resultSet();
            foreach ($boundRoutes as $br) {
                $routeIds[] = intval($br->id);
            }
        }
        $routeIds = array_unique($routeIds);
        
        $waypoints = [];
        $seq = 1;
        $primaryRoute = null;

        foreach ($routeIds as $rid) {
            $route = $this->getRouteById($rid);
            if (!$route) {
                continue;
            }
            if ($rid == $routeId) {
                $primaryRoute = $route;
            }

            if (!empty($route->start_lat) && !empty($route->start_lng)) {
                $waypoints[] = [
                    'type' => 'start',
                    'lat' => (float) $route->start_lat,
                    'lng' => (float) $route->start_lng,
                    'time' => $route->start_time,
                    'label' => 'Start (' . htmlspecialchars($route->route_name) . ')',
                    'detail' => $route->route_name,
                ];
            }

            $this->db->query("
                SELECT i.id, i.invoice_number, i.created_at, i.latitude, i.longitude, c.name as customer_name,
                (total_amount - COALESCE(CASE WHEN global_discount_type = '%' THEN (total_amount * global_discount_val / 100) ELSE global_discount_val END, 0) + COALESCE(tax_amount, 0)) as true_grand_total
                FROM invoices i
                JOIN customers c ON i.customer_id = c.id
                WHERE i.rep_route_id = :rid AND i.status != 'Voided'
                  AND i.latitude IS NOT NULL AND i.longitude IS NOT NULL
                ORDER BY i.created_at ASC
            ");
            $this->db->bind(':rid', $rid);
            $bills = $this->db->resultSet();

            foreach ($bills as $bill) {
                $waypoints[] = [
                    'type' => 'invoice',
                    'id' => (int) $bill->id,
                    'lat' => (float) $bill->latitude,
                    'lng' => (float) $bill->longitude,
                    'time' => $bill->created_at,
                    'label' => $bill->invoice_number,
                    'detail' => $bill->customer_name,
                    'amount' => (float) $bill->true_grand_total,
                    'sequence' => $seq++,
                ];
            }

            if (!empty($route->end_lat) && !empty($route->end_lng)) {
                $waypoints[] = [
                    'type' => 'end',
                    'lat' => (float) $route->end_lat,
                    'lng' => (float) $route->end_lng,
                    'time' => $route->end_time,
                    'label' => 'End (' . htmlspecialchars($route->route_name) . ')',
                    'detail' => $route->status === 'Completed' ? 'Route completed' : 'Last recorded position',
                ];
            }
        }

        if (empty($primaryRoute)) {
            return null;
        }

        // Sort waypoints chronologically by time
        usort($waypoints, function($a, $b) {
            return strtotime($a['time']) - strtotime($b['time']);
        });

        // Re-assign sequences chronologically
        $seq = 1;
        foreach ($waypoints as &$wp) {
            if ($wp['type'] === 'invoice') {
                $wp['sequence'] = $seq++;
            }
        }

        return [
            'route_id' => (int) $routeId,
            'route_name' => $primaryRoute->route_name,
            'rep_name' => trim($primaryRoute->first_name . ' ' . $primaryRoute->last_name),
            'status' => $primaryRoute->status,
            'start_time' => $primaryRoute->start_time,
            'end_time' => $primaryRoute->end_time,
            'waypoints' => $waypoints,
            'point_count' => count($waypoints),
        ];
    }

    public function getRouteCollections($routeId) {
        $routeIds = [$routeId];
        $this->db->query("SELECT route_binding_id FROM rep_daily_routes WHERE id = :rid LIMIT 1");
        $this->db->bind(':rid', $routeId);
        $routeRow = $this->db->single();
        if ($routeRow && $routeRow->route_binding_id) {
            $this->db->query("SELECT id FROM rep_daily_routes WHERE route_binding_id = :bid");
            $this->db->bind(':bid', $routeRow->route_binding_id);
            $boundRoutes = $this->db->resultSet();
            foreach ($boundRoutes as $br) {
                $routeIds[] = intval($br->id);
            }
        }
        $routeIds = array_unique($routeIds);
        $routeIdsStr = implode(',', $routeIds);

        $this->db->query("
            SELECT pc.id, pc.customer_id, pc.route_id as rep_route_id, pc.payment_method, pc.amount, 
                   pc.bank_name, pc.cheque_number, pc.cheque_date, pc.created_at, pc.status,
                   c.name as customer_name, pc.finalized_by, pc.notes,
                   pc.is_verified, pc.is_flagged, pc.adjusted_amount, pc.verification_notes,
                   pc.debit_account_id, pc.credit_account_id,
                   CASE WHEN pc.status = 'Finalized' THEN 999999 ELSE NULL END as journal_entry_id,
                   DATE(pc.created_at) as payment_date,
                   COALESCE(pc.cheque_number, pc.bank_name, '') as reference
            FROM pending_collections pc
            JOIN customers c ON pc.customer_id = c.id
            WHERE pc.route_id IN ($routeIdsStr)
            ORDER BY pc.created_at ASC
        ");
        return $this->db->resultSet() ?: [];
    }

    public function finalizePayments($paymentIds, $userId, $bankAllocations = [], $customDebitAccounts = [], $customCreditAccounts = []) {
        if (empty($paymentIds)) return true;

        // Resolve necessary account IDs based on codes
        $this->db->query("SELECT id, account_code FROM chart_of_accounts WHERE account_code IN ('1000', '1010', '1600', '1605', '1200')");
        $accounts = $this->db->resultSet();
        $accMap = [];
        foreach ($accounts as $a) {
            $accMap[$a->account_code] = $a->id;
        }

        $cashAcc = $accMap['1000'] ?? null;
        $chequeAcc = $accMap['1010'] ?? null;
        $tempBankAcc = $accMap['1605'] ?? $accMap['1600'] ?? null;
        $arAcc = $accMap['1200'] ?? null;

        if (!$arAcc) {
            throw new Exception("Accounts Receivable account (1200) not found in Chart of Accounts.");
        }

        foreach ($paymentIds as $pid) {
            // Fetch payment details from pending_collections
            $this->db->query("SELECT * FROM pending_collections WHERE id = :id AND status = 'Pending'");
            $this->db->bind(':id', $pid);
            $payment = $this->db->single();
            if (!$payment) continue;

            $amount = floatval($payment->amount);
            $method = $payment->payment_method;

            // Map payment method to asset account
            $assetAccId = null;
            if ($method === 'Cash') {
                $assetAccId = $cashAcc;
            } elseif ($method === 'Bank Transfer') {
                // Read granular target bank allocation for this specific payment
                $selectedBankAccId = isset($bankAllocations[$pid]) ? $bankAllocations[$pid] : null;
                $assetAccId = !empty($selectedBankAccId) ? $selectedBankAccId : $tempBankAcc;
            } elseif ($method === 'Cheque') {
                $assetAccId = $chequeAcc;
            }

            // Custom Debit Account override if specified
            if (isset($customDebitAccounts[$pid]) && !empty($customDebitAccounts[$pid])) {
                $assetAccId = intval($customDebitAccounts[$pid]);
            } elseif (!empty($payment->debit_account_id)) {
                $assetAccId = intval($payment->debit_account_id);
            }

            // Custom Credit Account override if specified
            $arAccId = $arAcc;
            if (isset($customCreditAccounts[$pid]) && !empty($customCreditAccounts[$pid])) {
                $arAccId = intval($customCreditAccounts[$pid]);
            } elseif (!empty($payment->credit_account_id)) {
                $arAccId = intval($payment->credit_account_id);
            }

            if (!$assetAccId) {
                continue; // Skip if no valid account mapped
            }

            // Create Journal Entry
            $this->db->query("INSERT INTO journal_entries (entry_date, reference, description, created_by, status) 
                              VALUES (CURDATE(), :ref, :desc, :uid, 'Posted')");
            $refStr = "FINAL-PMT-" . $payment->id;
            $descStr = "Finalized Route Collection (" . $method . ") for Customer ID: " . $payment->customer_id;
            $this->db->bind(':ref', $refStr);
            $this->db->bind(':desc', $descStr);
            $this->db->bind(':uid', $userId);
            $this->db->execute();
            $jid = $this->db->lastInsertId();

            // Transaction 1: Debit Asset Account (Cash / Bank / Cheque)
            $this->db->query("INSERT INTO transactions (journal_entry_id, account_id, debit, credit) VALUES (:jid, :aid, :amount, 0)");
            $this->db->bind(':jid', $jid);
            $this->db->bind(':aid', $assetAccId);
            $this->db->bind(':amount', $amount);
            $this->db->execute();

            $this->db->query("UPDATE chart_of_accounts SET balance = balance + :amt WHERE id = :aid");
            $this->db->bind(':amt', $amount);
            $this->db->bind(':aid', $assetAccId);
            $this->db->execute();

            // Transaction 2: Credit Accounts Receivable (or custom credit account)
            $this->db->query("INSERT INTO transactions (journal_entry_id, account_id, debit, credit) VALUES (:jid, :aid, 0, :amount)");
            $this->db->bind(':jid', $jid);
            $this->db->bind(':aid', $arAccId);
            $this->db->bind(':amount', $amount);
            $this->db->execute();

            $this->db->query("UPDATE chart_of_accounts SET balance = balance - :amt WHERE id = :aid");
            $this->db->bind(':amt', $amount);
            $this->db->bind(':aid', $arAccId);
            $this->db->execute();

            // Insert into customer_payments officially to credit customer subledger
            $this->db->query("INSERT INTO customer_payments (customer_id, amount, payment_date, payment_method, reference, journal_entry_id, created_by) 
                              VALUES (:cid, :amt, CURDATE(), :method, :ref, :jid, :uid)");
            $this->db->bind(':cid', $payment->customer_id);
            $this->db->bind(':amt', $amount);
            $this->db->bind(':method', $method);
            
            // Build the reference string
            $refText = '';
            if ($method === 'Cheque') {
                $refText = "Cheque #: " . $payment->cheque_number . " (" . $payment->bank_name . ")";
            } elseif ($method === 'Bank Transfer') {
                $refText = "Transfer (" . $payment->bank_name . ")";
            } else {
                $refText = "Cash Collection";
            }
            $this->db->bind(':ref', $refText);
            $this->db->bind(':jid', $jid);
            $this->db->bind(':uid', $userId);
            $this->db->execute();

            // If it is a cheque, register it in the cheques table as well
            if ($method === 'Cheque') {
                $this->db->query("INSERT INTO cheques (customer_id, bank_name, cheque_number, amount, banking_date, status, created_by) 
                                  VALUES (:cid, :bn, :cn, :amt, :bdate, 'Pending', :uid)");
                $this->db->bind(':cid', $payment->customer_id);
                $this->db->bind(':bn', $payment->bank_name);
                $this->db->bind(':cn', $payment->cheque_number);
                $this->db->bind(':amt', $amount);
                $this->db->bind(':bdate', $payment->cheque_date ?: date('Y-m-d'));
                $this->db->bind(':uid', $userId);
                $this->db->execute();
            }

            // Link pending collection to generated journal entry and mark as Finalized
            $this->db->query("UPDATE pending_collections SET status = 'Finalized', finalized_by = :uid, finalized_at = NOW() WHERE id = :pid");
            $this->db->bind(':uid', $userId);
            $this->db->bind(':pid', $pid);
            $this->db->execute();
        }

        return true;
    }
}