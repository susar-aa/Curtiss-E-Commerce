<?php
class Report {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    /** Invoice grand total expression (subtotal − discount + tax). */
    public function invoiceGrandTotalExpr($alias = 'i') {
        return "({$alias}.total_amount - COALESCE(CASE WHEN {$alias}.global_discount_type = '%' 
                THEN ({$alias}.total_amount * {$alias}.global_discount_val / 100) 
                ELSE {$alias}.global_discount_val END, 0) + COALESCE({$alias}.tax_amount, 0))";
    }

    public function normalizeDateRange($start = null, $end = null) {
        $end = $end ?: date('Y-m-d');
        $start = $start ?: date('Y-m-d', strtotime('-30 days'));
        if (strtotime($start) > strtotime($end)) {
            [$start, $end] = [$end, $start];
        }
        return [$start, $end];
    }

    public function getTrialBalanceData($endDate = null) {
        if ($endDate) {
            $this->db->query("SELECT c.id, c.account_code, c.account_name, c.account_type,
                                     SUM(COALESCE(t.debit, 0)) as debit_sum,
                                     SUM(COALESCE(t.credit, 0)) as credit_sum,
                                     SUM(CASE WHEN c.account_type IN ('Asset', 'Expense') THEN (COALESCE(t.debit, 0) - COALESCE(t.credit, 0))
                                              ELSE (COALESCE(t.credit, 0) - COALESCE(t.debit, 0)) END) as balance
                              FROM chart_of_accounts c
                              LEFT JOIN transactions t ON c.id = t.account_id
                              LEFT JOIN journal_entries je ON t.journal_entry_id = je.id AND je.status = 'Posted' AND je.entry_date <= :end_date AND je.reference NOT LIKE 'YE-CLOSE-%'
                              GROUP BY c.id, c.account_code, c.account_name, c.account_type
                              HAVING balance != 0
                              ORDER BY c.account_code ASC");
            $this->db->bind(':end_date', $endDate);
            return $this->db->resultSet();
        } else {
            $this->db->query("SELECT * FROM chart_of_accounts WHERE balance != 0 ORDER BY account_code ASC");
            return $this->db->resultSet();
        }
    }

    public function getAccountsByTypes($types, $endDate = null, $startDate = null) {
        $placeholders = str_repeat('?,', count($types) - 1) . '?';
        if ($endDate) {
            $hasStart = ($startDate && (in_array('Revenue', $types) || in_array('Expense', $types)));
            if ($hasStart) {
                $sql = "SELECT c.id, c.account_code, c.account_name, c.account_type, c.parent_id,
                               SUM(CASE WHEN c.account_type IN ('Asset', 'Expense') THEN (COALESCE(t.debit, 0) - COALESCE(t.credit, 0))
                                        ELSE (COALESCE(t.credit, 0) - COALESCE(t.debit, 0)) END) as balance
                        FROM chart_of_accounts c
                        LEFT JOIN transactions t ON c.id = t.account_id
                        LEFT JOIN journal_entries je ON t.journal_entry_id = je.id AND je.status = 'Posted' 
                            AND je.entry_date BETWEEN ? AND ? AND je.reference NOT LIKE 'YE-CLOSE-%'
                        WHERE c.account_type IN ($placeholders)
                        GROUP BY c.id, c.account_code, c.account_name, c.account_type, c.parent_id
                        HAVING balance != 0
                        ORDER BY FIELD(c.account_type, 'Asset', 'Liability', 'Equity', 'Revenue', 'Expense'), c.account_code ASC";
                
                $params = array_merge([$startDate, $endDate], $types);
            } else {
                $sql = "SELECT c.id, c.account_code, c.account_name, c.account_type, c.parent_id,
                               SUM(CASE WHEN c.account_type IN ('Asset', 'Expense') THEN (COALESCE(t.debit, 0) - COALESCE(t.credit, 0))
                                        ELSE (COALESCE(t.credit, 0) - COALESCE(t.debit, 0)) END) as balance
                        FROM chart_of_accounts c
                        LEFT JOIN transactions t ON c.id = t.account_id
                        LEFT JOIN journal_entries je ON t.journal_entry_id = je.id AND je.status = 'Posted' 
                            AND je.entry_date <= ? AND je.reference NOT LIKE 'YE-CLOSE-%'
                        WHERE c.account_type IN ($placeholders)
                        GROUP BY c.id, c.account_code, c.account_name, c.account_type, c.parent_id
                        HAVING balance != 0
                        ORDER BY FIELD(c.account_type, 'Asset', 'Liability', 'Equity', 'Revenue', 'Expense'), c.account_code ASC";
                
                $params = array_merge([$endDate], $types);
            }
            
            $this->db->query($sql);
            $this->db->stmt->execute($params);
            return $this->db->stmt->fetchAll(PDO::FETCH_OBJ);
        } else {
            $sql = "SELECT * FROM chart_of_accounts 
                    WHERE account_type IN ($placeholders) AND balance != 0 
                    ORDER BY FIELD(account_type, 'Asset', 'Liability', 'Equity', 'Revenue', 'Expense'), account_code ASC";
            $this->db->query($sql);
            $this->db->stmt->execute($types);
            return $this->db->stmt->fetchAll(PDO::FETCH_OBJ);
        }
    }

    public function getARAging() {
        $gt = $this->invoiceGrandTotalExpr('i');
        $this->db->query("SELECT i.id, i.invoice_number, i.due_date, i.invoice_date, i.status,
                                 {$gt} as total_amount,
                                 c.name as customer_name,
                                 DATEDIFF(CURRENT_DATE, i.due_date) as days_overdue
                          FROM invoices i
                          JOIN customers c ON i.customer_id = c.id
                          WHERE i.status IN ('Unpaid', 'Draft')
                          ORDER BY c.name ASC, i.due_date ASC");
        return $this->db->resultSet();
    }

    public function getFIFOSalesData($start = null, $end = null) {
        [$start, $end] = $this->normalizeDateRange($start, $end);
        $this->db->query("
            SELECT 
                'ERP Sales' as source,
                i.id as invoice_id,
                i.invoice_number,
                i.invoice_date as sale_date,
                ii.description as item_name,
                ii.quantity as qty,
                ii.unit_price as price,
                ii.total as revenue,
                COALESCE(ii.cost_at_sale, 0) as unit_cost,
                (ii.quantity * COALESCE(ii.cost_at_sale, 0)) as total_cost,
                (ii.total - (ii.quantity * COALESCE(ii.cost_at_sale, 0))) as profit
            FROM invoice_items ii
            JOIN invoices i ON ii.invoice_id = i.id
            WHERE i.status != 'Voided' AND i.invoice_date BETWEEN :start AND :end
            ORDER BY sale_date DESC, invoice_number DESC
        ");
        $this->db->bind(':start', $start);
        $this->db->bind(':end', $end);
        return $this->db->resultSet() ?: [];
    }

    public function getSalesSummary($start, $end) {
        $gt = $this->invoiceGrandTotalExpr('i');
        $this->db->query("
            SELECT 
                COUNT(*) as invoice_count,
                COALESCE(SUM({$gt}), 0) as gross_sales,
                COALESCE(SUM(i.tax_amount), 0) as total_tax,
                COALESCE(SUM(CASE WHEN i.status = 'Paid' THEN {$gt} ELSE 0 END), 0) as paid_sales,
                COALESCE(SUM(CASE WHEN i.status = 'Unpaid' THEN {$gt} ELSE 0 END), 0) as unpaid_sales,
                COALESCE(SUM(CASE WHEN i.status = 'Voided' THEN 1 ELSE 0 END), 0) as voided_count
            FROM invoices i
            WHERE i.invoice_date BETWEEN :start AND :end AND i.status != 'Voided'
        ");
        $this->db->bind(':start', $start);
        $this->db->bind(':end', $end);
        $summary = $this->db->single();

        $this->db->query("
            SELECT i.invoice_date as period_date, COUNT(*) as cnt,
                   COALESCE(SUM({$gt}), 0) as daily_total
            FROM invoices i
            WHERE i.invoice_date BETWEEN :start AND :end AND i.status != 'Voided'
            GROUP BY i.invoice_date
            ORDER BY i.invoice_date ASC
        ");
        $this->db->bind(':start', $start);
        $this->db->bind(':end', $end);
        $daily = $this->db->resultSet();

        return ['summary' => $summary, 'daily' => $daily];
    }

    public function getSalesByCustomer($start, $end) {
        $gt = $this->invoiceGrandTotalExpr('i');
        $this->db->query("
            SELECT c.id, c.name as customer_name, c.phone,
                   COUNT(i.id) as invoice_count,
                   COALESCE(SUM({$gt}), 0) as total_sales,
                   COALESCE(SUM(CASE WHEN i.status = 'Paid' THEN {$gt} ELSE 0 END), 0) as paid_amount,
                   COALESCE(SUM(CASE WHEN i.status = 'Unpaid' THEN {$gt} ELSE 0 END), 0) as outstanding
            FROM invoices i
            JOIN customers c ON i.customer_id = c.id
            WHERE i.invoice_date BETWEEN :start AND :end AND i.status != 'Voided'
            GROUP BY c.id, c.name, c.phone
            ORDER BY total_sales DESC
        ");
        $this->db->bind(':start', $start);
        $this->db->bind(':end', $end);
        return $this->db->resultSet();
    }

    public function getSalesByProduct($start, $end) {
        $this->db->query("
            SELECT ii.description as item_name,
                   SUM(ii.quantity) as total_qty,
                   COALESCE(SUM(ii.total), 0) as total_revenue,
                   COALESCE(SUM(ii.quantity * COALESCE(ii.cost_at_sale, 0)), 0) as total_cost,
                   COALESCE(SUM(ii.total - (ii.quantity * COALESCE(ii.cost_at_sale, 0))), 0) as gross_profit
            FROM invoice_items ii
            JOIN invoices i ON ii.invoice_id = i.id
            WHERE i.invoice_date BETWEEN :start AND :end AND i.status != 'Voided'
            GROUP BY ii.description
            ORDER BY total_revenue DESC
        ");
        $this->db->bind(':start', $start);
        $this->db->bind(':end', $end);
        return $this->db->resultSet();
    }

    public function getCollectionsReport($start, $end) {
        $this->db->query("
            SELECT cp.payment_date, cp.payment_method,
                   COUNT(*) as tx_count,
                   COALESCE(SUM(cp.amount), 0) as total_collected
            FROM customer_payments cp
            WHERE cp.payment_date BETWEEN :start AND :end
            GROUP BY cp.payment_date, cp.payment_method
            ORDER BY cp.payment_date DESC, cp.payment_method ASC
        ");
        $this->db->bind(':start', $start);
        $this->db->bind(':end', $end);
        $lines = $this->db->resultSet();

        $this->db->query("
            SELECT cp.payment_method,
                   COUNT(*) as tx_count,
                   COALESCE(SUM(cp.amount), 0) as total_collected
            FROM customer_payments cp
            WHERE cp.payment_date BETWEEN :start AND :end
            GROUP BY cp.payment_method
            ORDER BY total_collected DESC
        ");
        $this->db->bind(':start', $start);
        $this->db->bind(':end', $end);
        $byMethod = $this->db->resultSet();

        $this->db->query("
            SELECT cp.*, c.name as customer_name, u.username as collected_by
            FROM customer_payments cp
            JOIN customers c ON cp.customer_id = c.id
            LEFT JOIN users u ON cp.created_by = u.id
            WHERE cp.payment_date BETWEEN :start AND :end
            ORDER BY cp.payment_date DESC, cp.id DESC
        ");
        $this->db->bind(':start', $start);
        $this->db->bind(':end', $end);
        $detail = $this->db->resultSet();

        return ['lines' => $lines, 'by_method' => $byMethod, 'detail' => $detail];
    }

    public function getPurchasesSummary($start, $end) {
        $this->db->query("
            SELECT v.name as vendor_name,
                   COUNT(DISTINCT p.id) as po_count,
                   COALESCE(SUM(p.total_amount), 0) as po_total,
                   COUNT(DISTINCT g.id) as grn_count,
                   COALESCE(SUM(gi.line_total), 0) as grn_received_value
            FROM vendors v
            LEFT JOIN purchase_orders p ON p.vendor_id = v.id 
                AND p.po_date BETWEEN :start AND :end
            LEFT JOIN goods_receipt_notes g ON g.vendor_id = v.id 
                AND g.grn_date BETWEEN :start2 AND :end2
            LEFT JOIN (
                SELECT grn_id, SUM(quantity * unit_cost) as line_total
                FROM grn_items GROUP BY grn_id
            ) gi ON gi.grn_id = g.id
            GROUP BY v.id, v.name
            HAVING po_count > 0 OR grn_count > 0
            ORDER BY grn_received_value DESC, po_total DESC
        ");
        $this->db->bind(':start', $start);
        $this->db->bind(':end', $end);
        $this->db->bind(':start2', $start);
        $this->db->bind(':end2', $end);
        return $this->db->resultSet();
    }

    public function getGeneralLedger($start, $end) {
        $this->db->query("
            SELECT je.entry_date, je.reference, je.description, je.status,
                   c.account_code, c.account_name, c.account_type,
                   t.debit, t.credit,
                   u.username as posted_by
            FROM transactions t
            JOIN journal_entries je ON t.journal_entry_id = je.id
            JOIN chart_of_accounts c ON t.account_id = c.id
            LEFT JOIN users u ON je.created_by = u.id
            WHERE je.entry_date BETWEEN :start AND :end
            ORDER BY je.entry_date ASC, je.id ASC, t.id ASC
        ");
        $this->db->bind(':start', $start);
        $this->db->bind(':end', $end);
        return $this->db->resultSet();
    }

    public function getSalesByRep($start, $end) {
        $gt = $this->invoiceGrandTotalExpr('i');
        $this->db->query("
            SELECT r.id, r.route_name, r.start_time, r.end_time, r.status,
                   COALESCE(e.first_name, u.username) as rep_first,
                   COALESCE(e.last_name, '') as rep_last,
                   COUNT(i.id) as invoice_count,
                   COALESCE(SUM({$gt}), 0) as route_sales
            FROM rep_daily_routes r
            LEFT JOIN users u ON r.user_id = u.id
            LEFT JOIN employees e ON u.email = e.email
            LEFT JOIN invoices i ON i.rep_route_id = r.id AND i.status != 'Voided'
                AND i.invoice_date BETWEEN :start AND :end
            WHERE DATE(r.start_time) BETWEEN :start2 AND :end2
            GROUP BY r.id, r.route_name, r.start_time, r.end_time, r.status,
                     e.first_name, e.last_name, u.username
            ORDER BY r.start_time DESC
        ");
        $this->db->bind(':start', $start);
        $this->db->bind(':end', $end);
        $this->db->bind(':start2', $start);
        $this->db->bind(':end2', $end);
        return $this->db->resultSet();
    }

    public function getTaxSummary($start, $end) {
        $gt = $this->invoiceGrandTotalExpr('i');
        $this->db->query("
            SELECT i.invoice_date,
                   COUNT(*) as invoice_count,
                   COALESCE(SUM(i.total_amount), 0) as subtotal,
                   COALESCE(SUM(CASE WHEN i.global_discount_type = '%' 
                       THEN i.total_amount * i.global_discount_val / 100 
                       ELSE i.global_discount_val END), 0) as total_discount,
                   COALESCE(SUM(i.tax_amount), 0) as total_tax,
                   COALESCE(SUM({$gt}), 0) as grand_total
            FROM invoices i
            WHERE i.invoice_date BETWEEN :start AND :end AND i.status != 'Voided'
            GROUP BY i.invoice_date
            ORDER BY i.invoice_date ASC
        ");
        $this->db->bind(':start', $start);
        $this->db->bind(':end', $end);
        $daily = $this->db->resultSet();

        $this->db->query("
            SELECT COALESCE(SUM(i.tax_amount), 0) as total_tax,
                   COALESCE(SUM({$gt}), 0) as grand_total
            FROM invoices i
            WHERE i.invoice_date BETWEEN :start AND :end AND i.status != 'Voided'
        ");
        $this->db->bind(':start', $start);
        $this->db->bind(':end', $end);
        $totals = $this->db->single();

        return ['daily' => $daily, 'totals' => $totals];
    }

    public function getInventoryValuation() {
        $this->db->query("
            SELECT i.id, i.name, i.item_code,
                   COALESCE(ic.name, 'Uncategorized') as category_name,
                   COALESCE(i.quantity_on_hand, i.qty, 0) as qty_on_hand,
                   COALESCE(i.cost_price, 0) as unit_cost,
                   COALESCE(i.price, 0) as selling_price,
                   (COALESCE(i.quantity_on_hand, i.qty, 0) * COALESCE(i.cost_price, 0)) as stock_value_cost,
                   (COALESCE(i.quantity_on_hand, i.qty, 0) * COALESCE(i.price, 0)) as stock_value_retail
            FROM items i
            LEFT JOIN item_categories ic ON i.category_id = ic.id
            WHERE COALESCE(i.quantity_on_hand, i.qty, 0) > 0
            ORDER BY category_name ASC, i.name ASC
        ");
        $rows = $this->db->resultSet();

        $totalCost = 0;
        $totalRetail = 0;
        foreach ($rows as $r) {
            $totalCost += $r->stock_value_cost;
            $totalRetail += $r->stock_value_retail;
        }
        return ['rows' => $rows, 'total_cost' => $totalCost, 'total_retail' => $totalRetail];
    }

    public function getPeriodProfitLoss($start, $end) {
        $this->db->query("
            SELECT c.id, c.account_code, c.account_name, c.account_type,
                   COALESCE(SUM(t.debit), 0) as total_debit,
                   COALESCE(SUM(t.credit), 0) as total_credit
            FROM transactions t
            JOIN journal_entries je ON t.journal_entry_id = je.id
            JOIN chart_of_accounts c ON t.account_id = c.id
            WHERE je.entry_date BETWEEN :start AND :end
              AND je.status = 'Posted'
              AND je.reference NOT LIKE 'YE-CLOSE-%'
              AND c.account_type IN ('Revenue', 'Expense')
            GROUP BY c.id, c.account_code, c.account_name, c.account_type
            ORDER BY FIELD(c.account_type, 'Revenue', 'Expense'), c.account_code ASC
        ");
        $this->db->bind(':start', $start);
        $this->db->bind(':end', $end);
        return $this->db->resultSet();
    }
}
