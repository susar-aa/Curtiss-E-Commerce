<?php
class DriverInvoice {
    private $db;

    public function __construct() {
        $this->db = new Database();
        
        // Failsafe DDL: Auto-insert Driver Transit Collections (Temp) account if missing
        try {
            $this->db->query("SELECT id FROM chart_of_accounts WHERE account_code = '1090'");
            if (!$this->db->single()) {
                $this->db->query("INSERT INTO chart_of_accounts (account_code, account_name, account_type, balance, parent_id) 
                                  VALUES ('1090', 'Driver Transit Collections (Temp)', 'Asset', 0.00, NULL)");
                $this->db->execute();
            }
        } catch (Exception $e) {
            // Silently catch errors
        }
        
        // Failsafe DDL: Ensure pending_collections table exists for offline collections
        try {
            $createSql = "CREATE TABLE IF NOT EXISTS pending_collections (
                id INT AUTO_INCREMENT PRIMARY KEY,
                customer_id INT NOT NULL,
                route_id INT NOT NULL,
                payment_method VARCHAR(50) DEFAULT 'Cash',
                amount DECIMAL(12,2) DEFAULT 0.00,
                bank_name VARCHAR(255) DEFAULT '',
                cheque_number VARCHAR(255) DEFAULT '',
                cheque_date DATE DEFAULT NULL,
                status VARCHAR(20) DEFAULT 'Pending',
                created_by INT DEFAULT NULL,
                finalized_by INT DEFAULT NULL,
                finalized_at DATETIME DEFAULT NULL,
                rejected_by INT DEFAULT NULL,
                rejected_at DATETIME DEFAULT NULL,
                notes TEXT DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            $this->db->query($createSql);
            $this->db->execute();
        } catch (Exception $e) {
            // If creation fails, log to sync_debug and continue so push reports meaningful error
            $logPath = dirname(dirname(__DIR__)) . '/sync_debug.log';
            file_put_contents($logPath, "[" . date('Y-m-d H:i:s') . "] pending_collections table creation failed: " . $e->getMessage() . "\n", FILE_APPEND);
        }
    }

    public function getCustomerInvoices($customerId, $routeId) {
        $this->db->query("
            SELECT i.*, 
                (total_amount - COALESCE(CASE WHEN global_discount_type = '%' THEN (total_amount * global_discount_val / 100) ELSE global_discount_val END, 0) + COALESCE(tax_amount, 0)) as true_grand_total
            FROM invoices i
            WHERE i.customer_id = :cid AND i.rep_route_id = :rid AND i.status != 'Voided'
            ORDER BY i.created_at ASC
        ");
        $this->db->bind(':cid', $customerId);
        $this->db->bind(':rid', $routeId);
        return $this->db->resultSet();
    }

    public function getCustomerCreditBills($customerId) {
        $this->db->query("
            SELECT i.*, 
                (total_amount - COALESCE(CASE WHEN global_discount_type = '%' THEN (total_amount * global_discount_val / 100) ELSE global_discount_val END, 0) + COALESCE(tax_amount, 0)) as true_grand_total
            FROM invoices i
            WHERE i.customer_id = :cid AND i.status IN ('Unpaid')
            ORDER BY i.invoice_date ASC
        ");
        $this->db->bind(':cid', $customerId);
        return $this->db->resultSet();
    }

    public function getInvoiceItems($invoiceId) {
        $this->db->query("SELECT * FROM invoice_items WHERE invoice_id = :iid");
        $this->db->bind(':iid', $invoiceId);
        return $this->db->resultSet();
    }

    public function getInvoiceDetails($invoiceId) {
        $this->db->query("SELECT i.*, c.name as customer_name, c.phone, c.address 
                          FROM invoices i
                          JOIN customers c ON i.customer_id = c.id
                          WHERE i.id = :id");
        $this->db->bind(':id', $invoiceId);
        return $this->db->single();
    }

    public function getCustomerDetails($customerId) {
        $this->db->query("SELECT * FROM customers WHERE id = :id");
        $this->db->bind(':id', $customerId);
        return $this->db->single();
    }

    public function getCustomerTotalArrears($customerId, $currentRouteId) {
        $this->db->query("
            SELECT COALESCE(SUM(
                total_amount - COALESCE(CASE WHEN global_discount_type = '%' THEN (total_amount * global_discount_val / 100) ELSE global_discount_val END, 0) + COALESCE(tax_amount, 0)
            ), 0) as arrears
            FROM invoices
            WHERE customer_id = :cid AND status = 'Unpaid' AND (rep_route_id != :rid OR rep_route_id IS NULL)
        ");
        $this->db->bind(':cid', $customerId);
        $this->db->bind(':rid', $currentRouteId);
        $res = $this->db->single();
        return $res ? floatval($res->arrears) : 0.0;
    }

    public function updateInvoiceItemQty($itemId, $newQty) {
        $this->db->beginTransaction();
        try {
            // 1. Fetch item
            $this->db->query("SELECT * FROM invoice_items WHERE id = :id");
            $this->db->bind(':id', $itemId);
            $item = $this->db->single();
            if (!$item) throw new Exception("Invoice item not found.");

            $invoiceId = $item->invoice_id;
            $oldQty = floatval($item->quantity);
            $newQty = floatval($newQty);
            $diffQty = $oldQty - $newQty;

            // 2. Fetch invoice
            $this->db->query("SELECT * FROM invoices WHERE id = :id");
            $this->db->bind(':id', $invoiceId);
            $invoice = $this->db->single();
            if (!$invoice) throw new Exception("Invoice not found.");

            // 3. Update reservation stock in database
            if ($item->item_id) {
                $this->db->query("UPDATE items SET quantity_reserved = GREATEST(0, CAST(quantity_reserved AS SIGNED) - :diff) WHERE id = :id");
                $this->db->bind(':diff', $diffQty);
                $this->db->bind(':id', $item->item_id);
                $this->db->execute();
            }
            if ($item->variation_option_id) {
                $this->db->query("UPDATE item_variation_options SET quantity_reserved = GREATEST(0, CAST(quantity_reserved AS SIGNED) - :diff) WHERE id = :id");
                $this->db->bind(':diff', $diffQty);
                $this->db->bind(':id', $item->variation_option_id);
                $this->db->execute();
            }

            // 4. Update the item
            $rowGross = $newQty * floatval($item->unit_price);
            $rowDisc = ($item->discount_type === '%') ? ($rowGross * floatval($item->discount_value) / 100) : floatval($item->discount_value);
            $newTotal = max(0, $rowGross - $rowDisc);

            $this->db->query("UPDATE invoice_items SET quantity = :qty, total = :total WHERE id = :id");
            $this->db->bind(':qty', $newQty);
            $this->db->bind(':total', $newTotal);
            $this->db->bind(':id', $itemId);
            $this->db->execute();

            // 5. Recalculate invoice totals
            $this->recalculateInvoiceTotals($invoiceId);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("updateInvoiceItemQty error: " . $e->getMessage());
            return false;
        }
    }

    public function deleteInvoiceItem($itemId) {
        $this->db->beginTransaction();
        try {
            // 1. Fetch item
            $this->db->query("SELECT * FROM invoice_items WHERE id = :id");
            $this->db->bind(':id', $itemId);
            $item = $this->db->single();
            if (!$item) throw new Exception("Invoice item not found.");

            $invoiceId = $item->invoice_id;
            $oldQty = floatval($item->quantity);

            // 2. Release full reservation stock
            if ($item->item_id) {
                $this->db->query("UPDATE items SET quantity_reserved = GREATEST(0, CAST(quantity_reserved AS SIGNED) - :diff) WHERE id = :id");
                $this->db->bind(':diff', $oldQty);
                $this->db->bind(':id', $item->item_id);
                $this->db->execute();
            }
            if ($item->variation_option_id) {
                $this->db->query("UPDATE item_variation_options SET quantity_reserved = GREATEST(0, CAST(quantity_reserved AS SIGNED) - :diff) WHERE id = :id");
                $this->db->bind(':diff', $oldQty);
                $this->db->bind(':id', $item->variation_option_id);
                $this->db->execute();
            }

            // 3. Update quantity and total to 0 to preserve audit trail for returns
            $this->db->query("UPDATE invoice_items SET quantity = 0, total = 0 WHERE id = :id");
            $this->db->bind(':id', $itemId);
            $this->db->execute();

            // 4. Recalculate invoice totals
            $this->recalculateInvoiceTotals($invoiceId);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("deleteInvoiceItem error: " . $e->getMessage());
            return false;
        }
    }

    private function recalculateInvoiceTotals($invoiceId) {
        // Fetch invoice
        $this->db->query("SELECT * FROM invoices WHERE id = :id");
        $this->db->bind(':id', $invoiceId);
        $invoice = $this->db->single();
        if (!$invoice) return;

        $oldGrandTotal = $this->getTrueGrandTotal($invoice);

        // Fetch all items
        $this->db->query("SELECT * FROM invoice_items WHERE invoice_id = :iid");
        $this->db->bind(':iid', $invoiceId);
        $items = $this->db->resultSet();

        $subTotal = 0;
        foreach ($items as $item) {
            $subTotal += floatval($item->total);
        }

        $globalDiscVal = floatval($invoice->global_discount_val ?? 0);
        $globalDiscType = $invoice->global_discount_type ?? 'Rs';
        $globalDisc = ($globalDiscType === '%') ? ($subTotal * $globalDiscVal / 100) : $globalDiscVal;
        $netSub = max(0, $subTotal - $globalDisc);

        $taxAmount = 0.0;
        if (!empty($invoice->tax_rate_id)) {
            $this->db->query("SELECT rate_percentage FROM tax_rates WHERE id = :id");
            $this->db->bind(':id', $invoice->tax_rate_id);
            $tr = $this->db->single();
            if ($tr) {
                $taxAmount = ($netSub * floatval($tr->rate_percentage) / 100);
            }
        } else {
            $oldSub = floatval($invoice->total_amount ?? 0);
            if ($oldSub > 0) {
                $taxPercentage = floatval($invoice->tax_amount ?? 0) / $oldSub;
                $taxAmount = $netSub * $taxPercentage;
            }
        }

        $newGrandTotal = $netSub + $taxAmount;

        // Update invoice
        $this->db->query("UPDATE invoices SET total_amount = :subtotal, tax_amount = :tax WHERE id = :id");
        $this->db->bind(':subtotal', $subTotal);
        $this->db->bind(':tax', $taxAmount);
        $this->db->bind(':id', $invoiceId);
        $this->db->execute();

        // Ledger adjustments
        $this->db->query("SELECT id, account_code FROM chart_of_accounts WHERE account_code IN ('1200', '4000')");
        $accounts = $this->db->resultSet();
        $accMap = [];
        foreach($accounts as $a) { $accMap[$a->account_code] = $a->id; }

        $arAcc = $accMap['1200'] ?? null;
        $salesAcc = $accMap['4000'] ?? null;

        if ($arAcc && $salesAcc) {
            // Update chart_of_accounts balances
            $this->db->query("UPDATE chart_of_accounts SET balance = balance - :old_amt + :new_amt WHERE id = :id");
            $this->db->bind(':old_amt', $oldGrandTotal);
            $this->db->bind(':new_amt', $newGrandTotal);
            $this->db->bind(':id', $arAcc);
            $this->db->execute();

            $this->db->query("UPDATE chart_of_accounts SET balance = balance - :old_amt + :new_amt WHERE id = :id");
            $this->db->bind(':old_amt', $oldGrandTotal);
            $this->db->bind(':new_amt', $newGrandTotal);
            $this->db->bind(':id', $salesAcc);
            $this->db->execute();

            // Update journal transactions
            $jid = $invoice->journal_entry_id;
            if ($jid) {
                $this->db->query("UPDATE transactions SET debit = :new_amt WHERE journal_entry_id = :jid AND account_id = :aid AND debit > 0");
                $this->db->bind(':new_amt', $newGrandTotal);
                $this->db->bind(':jid', $jid);
                $this->db->bind(':aid', $arAcc);
                $this->db->execute();

                $this->db->query("UPDATE transactions SET credit = :new_amt WHERE journal_entry_id = :jid AND account_id = :aid AND credit > 0");
                $this->db->bind(':new_amt', $newGrandTotal);
                $this->db->bind(':jid', $jid);
                $this->db->bind(':aid', $salesAcc);
                $this->db->execute();
            }
        }
    }

    private function getTrueGrandTotal($invoice) {
        $subTotal = floatval($invoice->total_amount ?? 0);
        $globalDiscVal = floatval($invoice->global_discount_val ?? 0);
        $globalDiscType = $invoice->global_discount_type ?? 'Rs';
        $globalDisc = ($globalDiscType === '%') ? ($subTotal * $globalDiscVal / 100) : $globalDiscVal;
        return max(0, $subTotal - $globalDisc) + floatval($invoice->tax_amount ?? 0);
    }

    public function checkoutShop($customerId, $routeId, $userId, $collections) {
        $logPath = dirname(dirname(__DIR__)) . '/sync_debug.log';
        
        $this->db->beginTransaction();
        try {
            file_put_contents($logPath, "[" . date('Y-m-d H:i:s') . "] checkoutShop START: cust=$customerId, route=$routeId\n", FILE_APPEND);
            
            // 1. Fetch today's invoices for this customer
            $invoices = $this->getCustomerInvoices($customerId, $routeId);
            file_put_contents($logPath, "[" . date('Y-m-d H:i:s') . "] Found " . count($invoices) . " invoices\n", FILE_APPEND);

            // 2. Update delivery status to 'Delivered'
            foreach ($invoices as $invoice) {
                $this->db->query("UPDATE invoices SET delivery_status = 'Delivered' WHERE id = :id");
                $this->db->bind(':id', $invoice->id);
                $result = $this->db->execute();
                if (!$result) {
                    throw new Exception("Failed to update invoice delivery status for invoice ID: {$invoice->id}");
                }
            }

            // 3. Process collections & save to customer_payments, cheques, and chart_of_accounts using 1090 transit account
            $cashAmt = floatval($collections['cash'] ?? 0);
            $bankAmt = floatval($collections['bank'] ?? 0);

            file_put_contents($logPath, "[" . date('Y-m-d H:i:s') . "] Processing payments: cash=$cashAmt, bank=$bankAmt\n", FILE_APPEND);

            $savePaymentRecord = function($amount, $methodStr, $chequeDetails = null) use ($userId, $customerId, $routeId, $logPath) {
                if ($amount <= 0) {
                    file_put_contents($logPath, "[" . date('Y-m-d H:i:s') . "] Skipping $methodStr payment with amount=$amount\n", FILE_APPEND);
                    return;
                }

                $refCode = "PMT-" . time() . rand(10,99);

                try {
                    // Fetch account IDs for 1090 and 1200
                    $this->db->query("SELECT id FROM chart_of_accounts WHERE account_code = '1090'");
                    $rowTemp = $this->db->single();
                    $tempAccId = $rowTemp ? $rowTemp->id : null;

                    $this->db->query("SELECT id FROM chart_of_accounts WHERE account_code = '1200'");
                    $rowAR = $this->db->single();
                    $arAccId = $rowAR ? $rowAR->id : null;

                    $payJid = null;
                    if ($tempAccId && $arAccId) {
                        // Create temporary journal entry
                        $this->db->query("INSERT INTO journal_entries (entry_date, reference, description, created_by, status) 
                                          VALUES (CURDATE(), :ref, :desc, :uid, 'Posted')");
                        $this->db->bind(':ref', $refCode);
                        $this->db->bind(':desc', "Driver Transit Collection ($methodStr) - Customer ID: $customerId");
                        $this->db->bind(':uid', $userId);
                        $this->db->execute();
                        $payJid = $this->db->lastInsertId();

                        // Debit 1090 (Driver Transit Collections (Temp))
                        $this->db->query("INSERT INTO transactions (journal_entry_id, account_id, debit, credit) VALUES (:jid, :aid, :deb, 0)");
                        $this->db->bind(':jid', $payJid);
                        $this->db->bind(':aid', $tempAccId);
                        $this->db->bind(':deb', $amount);
                        $this->db->execute();

                        $this->db->query("UPDATE chart_of_accounts SET balance = balance + :amt WHERE id = :aid");
                        $this->db->bind(':amt', $amount);
                        $this->db->bind(':aid', $tempAccId);
                        $this->db->execute();

                        // Credit 1200 (Accounts Receivable)
                        $this->db->query("INSERT INTO transactions (journal_entry_id, account_id, debit, credit) VALUES (:jid, :aid, 0, :cred)");
                        $this->db->bind(':jid', $payJid);
                        $this->db->bind(':aid', $arAccId);
                        $this->db->bind(':cred', $amount);
                        $this->db->execute();

                        $this->db->query("UPDATE chart_of_accounts SET balance = balance - :amt WHERE id = :aid");
                        $this->db->bind(':amt', $amount);
                        $this->db->bind(':aid', $arAccId);
                        $this->db->execute();
                    }

                    // If PDC Cheque details provided, write to cheques table
                    if ($methodStr === 'Cheque' && $chequeDetails) {
                        file_put_contents($logPath, "[" . date('Y-m-d H:i:s') . "] Inserting cheque record: bank={$chequeDetails['bank']}, number={$chequeDetails['number']}, amount=$amount\n", FILE_APPEND);
                        
                        $this->db->query("INSERT INTO cheques (customer_id, bank_name, cheque_number, amount, banking_date, status, rep_route_id, created_by) 
                                          VALUES (:cid, :bn, :cn, :amt, :bdate, 'Pending', :route_id, :uid)");
                        $this->db->bind(':cid', $customerId);
                        $this->db->bind(':bn', $chequeDetails['bank'] ?? 'Unknown');
                        $this->db->bind(':cn', $chequeDetails['number'] ?? 'Unknown');
                        $this->db->bind(':amt', $amount);
                        $this->db->bind(':bdate', $chequeDetails['date'] ?: date('Y-m-d'));
                        $this->db->bind(':route_id', $routeId);
                        $this->db->bind(':uid', $userId);
                        $result = $this->db->execute();
                        if (!$result) {
                            throw new Exception("Failed to insert cheque record for customer $customerId");
                        }
                    }

                    // Log Customer Payment History with rep_route_id (associated with transit journal entry)
                    file_put_contents($logPath, "[" . date('Y-m-d H:i:s') . "] Inserting payment record: method=$methodStr, amount=$amount, ref=" . ($chequeDetails['number'] ?? $refCode) . "\n", FILE_APPEND);
                    
                    $this->db->query("INSERT INTO customer_payments (customer_id, amount, payment_date, payment_method, reference, journal_entry_id, rep_route_id, created_by) 
                                      VALUES (:cid, :amt, CURDATE(), :method, :ref, :jid, :route_id, :uid)");
                    $this->db->bind(':cid', $customerId);
                    $this->db->bind(':amt', $amount);
                    $this->db->bind(':method', $methodStr);
                    $this->db->bind(':ref', $chequeDetails['number'] ?? $refCode);
                    $this->db->bind(':jid', $payJid);
                    $this->db->bind(':route_id', $routeId);
                    $this->db->bind(':uid', $userId);
                    $result = $this->db->execute();
                    if (!$result) {
                        throw new Exception("Failed to insert payment record for customer $customerId, method=$methodStr");
                    }

                    // ALSO log to pending_collections so it displays on "≡ƒÆ░ Route Collections & GL Finalization" at rep tracking page
                    $this->db->query("INSERT INTO pending_collections (customer_id, route_id, payment_method, amount, bank_name, cheque_number, cheque_date, created_by, status) 
                                      VALUES (:cid, :route_id, :method, :amt, :bn, :cn, :cdate, :uid, 'Pending')");
                    $this->db->bind(':cid', $customerId);
                    $this->db->bind(':route_id', $routeId);
                    $this->db->bind(':method', $methodStr);
                    $this->db->bind(':amt', $amount);
                    $this->db->bind(':bn', ($methodStr === 'Bank Transfer' || $methodStr === 'Bank') ? ($chequeDetails['bank'] ?? 'Bank Transfer') : '');
                    $this->db->bind(':cn', ($methodStr === 'Cheque') ? ($chequeDetails['number'] ?? 'Unknown') : '');
                    $this->db->bind(':cdate', ($methodStr === 'Cheque') ? ($chequeDetails['date'] ?: date('Y-m-d')) : null);
                    $this->db->bind(':uid', $userId);
                    $resultPending = $this->db->execute();
                    if (!$resultPending) {
                        throw new Exception("Failed to insert pending collection for customer $customerId, method=$methodStr");
                    }
                    
                    file_put_contents($logPath, "[" . date('Y-m-d H:i:s') . "] Successfully saved $methodStr payment\n", FILE_APPEND);
                } catch (Exception $e) {
                    file_put_contents($logPath, "[" . date('Y-m-d H:i:s') . "] ERROR in savePaymentRecord ($methodStr): " . $e->getMessage() . "\n", FILE_APPEND);
                    throw $e;
                }
            };

            $savePaymentRecord($cashAmt, 'Cash');
            $savePaymentRecord($bankAmt, 'Bank Transfer');
            
            if (isset($collections['cheques']) && is_array($collections['cheques'])) {
                file_put_contents($logPath, "[" . date('Y-m-d H:i:s') . "] Processing cheque array with " . count($collections['cheques']) . " items\n", FILE_APPEND);
                foreach ($collections['cheques'] as $chequeObj) {
                    $amt = floatval($chequeObj['amount'] ?? 0);
                    if ($amt > 0) {
                        $savePaymentRecord($amt, 'Cheque', [
                            'bank' => $chequeObj['bank'] ?? 'Unknown',
                            'number' => $chequeObj['number'] ?? 'Unknown',
                            'date' => $chequeObj['date'] ?? date('Y-m-d')
                        ]);
                    }
                }
            } else {
                // Fallback to legacy single cheque details if present
                $chequeAmt = floatval($collections['cheque'] ?? 0);
                if ($chequeAmt > 0) {
                    file_put_contents($logPath, "[" . date('Y-m-d H:i:s') . "] Processing single cheque (legacy format): amount=$chequeAmt\n", FILE_APPEND);
                    $chequeDetails = [
                        'bank' => $collections['cheque_bank'] ?? '',
                        'number' => $collections['cheque_number'] ?? '',
                        'date' => $collections['cheque_date'] ?? ''
                    ];
                    $savePaymentRecord($chequeAmt, 'Cheque', $chequeDetails);
                }
            }

            // [Antigravity EDIT]
            // 4. Real-time stock deduction and reservation release
            // We comment this out to ensure all stock deductions and reservation releases
            // are deferred until administrative Finalization in Delivery::finalizeDelivery.
            /*
            require_once dirname(__DIR__, 2) . '/app/Models/FIFO.php';
            $fifo = new FIFO();

            foreach ($invoices as $invoice) {
                // If already processed/deducted, don't do it again
                if ($invoice->stock_status === 'deducted') {
                    continue;
                }

                // Fetch invoice items
                $this->db->query("SELECT * FROM invoice_items WHERE invoice_id = :iid");
                $this->db->bind(':iid', $invoice->id);
                $items = $this->db->resultSet();

                foreach ($items as $item) {
                    $deliveredQty = floatval($item->quantity);
                    $loadedQty = floatval($item->loaded_quantity);
                    $itemId = $item->item_id;
                    $varId = $item->variation_option_id;

                    // Fallback to description name match if itemId is null
                    if (!$itemId && !empty($item->description)) {
                        $this->db->query("SELECT id FROM items WHERE name = :name LIMIT 1");
                        $this->db->bind(':name', $item->description);
                        $rowItem = $this->db->single();
                        if ($rowItem) {
                            $itemId = $rowItem->id;
                        }
                    }

                    if ($itemId) {
                        // A. Deduct delivered stock from physical inventory (quantity_on_hand)
                        if ($deliveredQty > 0) {
                            if ($varId) {
                                $this->db->query("UPDATE item_variation_options SET quantity_on_hand = GREATEST(0, CAST(quantity_on_hand AS SIGNED) - :qty) WHERE id = :id");
                                $this->db->bind(':qty', $deliveredQty);
                                $this->db->bind(':id', $varId);
                                $this->db->execute();
                            } else {
                                require_once '../app/Models/Item.php';
                                $itemModel = new Item();
                                $itemModel->updateStockDelta($itemId, -$deliveredQty);
                            }

                            // B. Deduct FIFO stock costing
                            try {
                                $fifo->depleteStock($itemId, $varId ?: null, $deliveredQty, $item->id, null);
                            } catch (Exception $e) {
                                file_put_contents($logPath, "[" . date('Y-m-d H:i:s') . "] FIFO error for item $itemId: " . $e->getMessage() . "\n", FILE_APPEND);
                            }
                        }

                        // C. Remove reserved stock (loaded quantity was the reserved amount)
                        if ($loadedQty > 0) {
                            if ($varId) {
                                $this->db->query("UPDATE item_variation_options SET quantity_reserved = GREATEST(0, CAST(quantity_reserved AS SIGNED) - :qty) WHERE id = :id");
                                $this->db->bind(':qty', $loadedQty);
                                $this->db->bind(':id', $varId);
                                $this->db->execute();
                            } else {
                                $this->db->query("UPDATE items SET quantity_reserved = GREATEST(0, CAST(quantity_reserved AS SIGNED) - :qty) WHERE id = :id");
                                $this->db->bind(':qty', $loadedQty);
                                $this->db->bind(':id', $itemId);
                                $this->db->execute();
                            }
                        }
                    }
                }

                // Update invoice stock status to 'deducted'
                $this->db->query("UPDATE invoices SET stock_status = 'deducted' WHERE id = :id");
                $this->db->bind(':id', $invoice->id);
                $this->db->execute();
            }
            */

            $this->db->commit();
            file_put_contents($logPath, "[" . date('Y-m-d H:i:s') . "] checkoutShop COMPLETED SUCCESSFULLY\n", FILE_APPEND);
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            file_put_contents($logPath, "[" . date('Y-m-d H:i:s') . "] checkoutShop ROLLBACK: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString() . "\n", FILE_APPEND);
            throw $e;
        }
    }

    public function updateInvoiceDeliveryStatus($invoiceId, $status) {
        $this->db->query("UPDATE invoices SET delivery_status = :status WHERE id = :id");
        $this->db->bind(':status', $status);
        $this->db->bind(':id', $invoiceId);
        return $this->db->execute();
    }
}
