<?php
/**
 * Curtiss ERP - Centralized Reporting Engine
 * Implements a dynamic, metadata-driven report processor.
 */
class ReportEngine {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public static function getCategories() {
        return [
            'inventory' => 'Inventory Reports',
            'sales' => 'Sales Reports',
            'procurement' => 'Procurement Reports',
            'customer' => 'Customer Reports',
            'supplier' => 'Supplier Reports',
            'finance' => 'Finance & Accounts Reports',
            'collection' => 'Collection & Payment Reports',
            'route' => 'Route & Distribution Reports',
            'management' => 'Management Reports'
        ];
    }

    public static function getReportsRegistry() {
        return [
            // 1. Inventory Reports
            'stock_summary' => [
                'title' => 'Stock Summary',
                'category' => 'inventory',
                'filters' => ['product', 'category', 'warehouse', 'brand'],
                'columns' => [
                    'item_code' => ['label' => 'Item Code', 'type' => 'text', 'sortable' => true],
                    'name' => ['label' => 'Product Name', 'type' => 'text', 'sortable' => true, 'drilldown' => 'product'],
                    'category_name' => ['label' => 'Category', 'type' => 'text'],
                    'qty_on_hand' => ['label' => 'Stock Level', 'type' => 'number', 'align' => 'right', 'sortable' => true],
                    'cost_price' => ['label' => 'Cost Price', 'type' => 'currency', 'align' => 'right'],
                    'price' => ['label' => 'Retail Price', 'type' => 'currency', 'align' => 'right'],
                    'val_cost' => ['label' => 'Value (Cost)', 'type' => 'currency', 'align' => 'right', 'total' => 'sum'],
                    'val_retail' => ['label' => 'Value (Retail)', 'type' => 'currency', 'align' => 'right', 'total' => 'sum']
                ],
                'sql' => "SELECT i.id, i.item_code, i.name, ic.name as category_name, i.brand, i.warehouse_id,
                                 COALESCE(i.quantity_on_hand, i.qty, 0) as qty_on_hand,
                                 COALESCE(i.cost_price, 0) as cost_price,
                                 COALESCE(i.price, 0) as price,
                                 (COALESCE(i.quantity_on_hand, i.qty, 0) * COALESCE(i.cost_price, 0)) as val_cost,
                                 (COALESCE(i.quantity_on_hand, i.qty, 0) * COALESCE(i.price, 0)) as val_retail
                          FROM items i
                          LEFT JOIN item_categories ic ON i.category_id = ic.id
                          WHERE 1=1"
            ],
            'stock_balance' => [
                'title' => 'Stock Balance Report',
                'category' => 'inventory',
                'filters' => ['product', 'warehouse'],
                'columns' => [
                    'name' => ['label' => 'Product Name', 'type' => 'text', 'sortable' => true],
                    'warehouse_name' => ['label' => 'Warehouse', 'type' => 'text'],
                    'qty_on_hand' => ['label' => 'In Stock', 'type' => 'number', 'align' => 'right', 'total' => 'sum']
                ],
                'sql' => "SELECT i.id as item_id, i.name, COALESCE(w.name, 'Main Warehouse') as warehouse_name, i.warehouse_id,
                                 COALESCE(i.quantity_on_hand, i.qty, 0) as qty_on_hand
                          FROM items i
                          LEFT JOIN warehouses w ON i.warehouse_id = w.id
                          WHERE 1=1"
            ],
            'stock_movement' => [
                'title' => 'Stock Movement Report',
                'category' => 'inventory',
                'filters' => ['date_range', 'product', 'warehouse'],
                'date_column' => 'sl.transaction_date',
                'columns' => [
                    'created_at' => ['label' => 'Date', 'type' => 'date', 'sortable' => true],
                    'product_name' => ['label' => 'Product Name', 'type' => 'text'],
                    'activity_type' => ['label' => 'Type', 'type' => 'badge'],
                    'ref_doc' => ['label' => 'Document Ref', 'type' => 'text'],
                    'qty_change' => ['label' => 'Qty Change', 'type' => 'number', 'align' => 'right'],
                    'new_balance' => ['label' => 'New Balance', 'type' => 'number', 'align' => 'right']
                ],
                'sql' => "SELECT sl.transaction_date as created_at, i.name as product_name, sl.transaction_type as activity_type, 
                                 sl.reference_number as ref_doc, (sl.quantity_in - sl.quantity_out) as qty_change, sl.running_balance as new_balance,
                                 sl.item_id, sl.warehouse_id
                          FROM stock_ledger sl
                          JOIN items i ON sl.item_id = i.id
                          WHERE 1=1"
            ],
            'stock_ledger' => [
                'title' => 'Stock Ledger',
                'category' => 'inventory',
                'filters' => ['date_range', 'product', 'warehouse'],
                'date_column' => 'sl.transaction_date',
                'columns' => [
                    'created_at' => ['label' => 'Date & Time', 'type' => 'date', 'sortable' => true],
                    'item_code' => ['label' => 'SKU', 'type' => 'text'],
                    'name' => ['label' => 'Product Name', 'type' => 'text'],
                    'activity_type' => ['label' => 'Reference', 'type' => 'text'],
                    'qty_change' => ['label' => 'Qty Delta', 'type' => 'number', 'align' => 'right'],
                    'new_balance' => ['label' => 'Balance', 'type' => 'number', 'align' => 'right']
                ],
                'sql' => "SELECT sl.transaction_date as created_at, i.item_code, i.name, 
                                 CONCAT(sl.transaction_type, ' (', COALESCE(sl.reference_number, 'N/A'), ')') as activity_type, 
                                 (sl.quantity_in - sl.quantity_out) as qty_change, sl.running_balance as new_balance,
                                 sl.item_id, sl.warehouse_id
                          FROM stock_ledger sl
                          JOIN items i ON sl.item_id = i.id
                          WHERE 1=1"
            ],
            'stock_aging' => [
                'title' => 'Stock Aging Report',
                'category' => 'inventory',
                'filters' => ['product', 'category', 'brand'],
                'columns' => [
                    'name' => ['label' => 'Product Name', 'type' => 'text', 'sortable' => true],
                    'category_name' => ['label' => 'Category', 'type' => 'text'],
                    'qty_0_30' => ['label' => '0 - 30 Days', 'type' => 'number', 'align' => 'right', 'total' => 'sum'],
                    'qty_31_60' => ['label' => '31 - 60 Days', 'type' => 'number', 'align' => 'right', 'total' => 'sum'],
                    'qty_61_90' => ['label' => '61 - 90 Days', 'type' => 'number', 'align' => 'right', 'total' => 'sum'],
                    'qty_90_plus' => ['label' => '90+ Days', 'type' => 'number', 'align' => 'right', 'total' => 'sum'],
                    'total_stock' => ['label' => 'Total Qty', 'type' => 'number', 'align' => 'right', 'total' => 'sum']
                ],
                'sql' => "SELECT i.name, ic.name as category_name, i.brand, i.id as item_id, i.category_id,
                                 SUM(CASE WHEN DATEDIFF(NOW(), i.created_at) <= 30 THEN COALESCE(i.quantity_on_hand, i.qty, 0) ELSE 0 END) as qty_0_30,
                                 SUM(CASE WHEN DATEDIFF(NOW(), i.created_at) > 30 AND DATEDIFF(NOW(), i.created_at) <= 60 THEN COALESCE(i.quantity_on_hand, i.qty, 0) ELSE 0 END) as qty_31_60,
                                 SUM(CASE WHEN DATEDIFF(NOW(), i.created_at) > 60 AND DATEDIFF(NOW(), i.created_at) <= 90 THEN COALESCE(i.quantity_on_hand, i.qty, 0) ELSE 0 END) as qty_61_90,
                                 SUM(CASE WHEN DATEDIFF(NOW(), i.created_at) > 90 THEN COALESCE(i.quantity_on_hand, i.qty, 0) ELSE 0 END) as qty_90_plus,
                                 SUM(COALESCE(i.quantity_on_hand, i.qty, 0)) as total_stock
                          FROM items i
                          LEFT JOIN item_categories ic ON i.category_id = ic.id
                          WHERE 1=1
                          GROUP BY i.id, i.name, ic.name, i.brand"
            ],
            'inventory_valuation' => [
                'title' => 'Inventory Valuation Report',
                'category' => 'inventory',
                'filters' => ['product', 'category', 'brand'],
                'columns' => [
                    'item_code' => ['label' => 'SKU', 'type' => 'text'],
                    'name' => ['label' => 'Product Name', 'type' => 'text', 'sortable' => true],
                    'category_name' => ['label' => 'Category', 'type' => 'text'],
                    'qty_on_hand' => ['label' => 'Qty On Hand', 'type' => 'number', 'align' => 'right', 'total' => 'sum'],
                    'cost_price' => ['label' => 'Unit Cost', 'type' => 'currency', 'align' => 'right'],
                    'stock_value_cost' => ['label' => 'Valuation (Cost)', 'type' => 'currency', 'align' => 'right', 'total' => 'sum'],
                    'price' => ['label' => 'Unit Retail', 'type' => 'currency', 'align' => 'right'],
                    'stock_value_retail' => ['label' => 'Valuation (Retail)', 'type' => 'currency', 'align' => 'right', 'total' => 'sum']
                ],
                'sql' => "SELECT i.id as item_id, i.category_id, i.brand, i.item_code, i.name, ic.name as category_name,
                                 COALESCE(i.quantity_on_hand, i.qty, 0) as qty_on_hand,
                                 COALESCE(i.cost_price, 0) as cost_price,
                                 (COALESCE(i.quantity_on_hand, i.qty, 0) * COALESCE(i.cost_price, 0)) as stock_value_cost,
                                 COALESCE(i.price, 0) as price,
                                 (COALESCE(i.quantity_on_hand, i.qty, 0) * COALESCE(i.price, 0)) as stock_value_retail
                          FROM items i
                          LEFT JOIN item_categories ic ON i.category_id = ic.id
                          WHERE 1=1"
            ],
            'reorder_level' => [
                'title' => 'Reorder Level Report',
                'category' => 'inventory',
                'filters' => ['product', 'category'],
                'columns' => [
                    'name' => ['label' => 'Product Name', 'type' => 'text'],
                    'qty_on_hand' => ['label' => 'In Stock', 'type' => 'number', 'align' => 'right'],
                    'reorder_point' => ['label' => 'Reorder Point', 'type' => 'number', 'align' => 'right'],
                    'reorder_qty' => ['label' => 'Reorder Qty', 'type' => 'number', 'align' => 'right'],
                    'shortage' => ['label' => 'Deficit', 'type' => 'number', 'align' => 'right', 'total' => 'sum']
                ],
                'sql' => "SELECT i.id as item_id, i.category_id, i.name, 
                                 COALESCE(i.quantity_on_hand, i.qty, 0) as qty_on_hand,
                                 COALESCE(i.minimum_stock_level, i.alert_qty, 5) as reorder_point,
                                 (COALESCE(i.minimum_stock_level, i.alert_qty, 5) * 2) as reorder_qty,
                                 CASE WHEN COALESCE(i.minimum_stock_level, i.alert_qty, 5) > COALESCE(i.quantity_on_hand, i.qty, 0)
                                      THEN (COALESCE(i.minimum_stock_level, i.alert_qty, 5) - COALESCE(i.quantity_on_hand, i.qty, 0))
                                      ELSE 0 END as shortage
                          FROM items i
                          WHERE 1=1"
            ],
            'negative_stock' => [
                'title' => 'Negative Stock Report',
                'category' => 'inventory',
                'filters' => ['warehouse'],
                'columns' => [
                    'item_code' => ['label' => 'SKU', 'type' => 'text'],
                    'name' => ['label' => 'Product Name', 'type' => 'text'],
                    'warehouse_name' => ['label' => 'Warehouse', 'type' => 'text'],
                    'qty_on_hand' => ['label' => 'Quantity', 'type' => 'number', 'align' => 'right', 'total' => 'sum']
                ],
                'sql' => "SELECT i.id as item_id, i.item_code, i.name, COALESCE(w.name, 'Main Warehouse') as warehouse_name, i.warehouse_id,
                                 COALESCE(i.quantity_on_hand, i.qty, 0) as qty_on_hand
                          FROM items i
                          LEFT JOIN warehouses w ON i.warehouse_id = w.id
                          WHERE COALESCE(i.quantity_on_hand, i.qty, 0) < 0"
            ],
            'damaged_stock' => [
                'title' => 'Damaged Stock Report',
                'category' => 'inventory',
                'filters' => ['date_range', 'product', 'warehouse'],
                'date_column' => 'sl.transaction_date',
                'columns' => [
                    'note_date' => ['label' => 'Date', 'type' => 'date'],
                    'product_name' => ['label' => 'Product Name', 'type' => 'text'],
                    'qty' => ['label' => 'Qty Damaged', 'type' => 'number', 'align' => 'right', 'total' => 'sum'],
                    'cost' => ['label' => 'Cost Valuation', 'type' => 'currency', 'align' => 'right', 'total' => 'sum'],
                    'reason' => ['label' => 'Reason', 'type' => 'text']
                ],
                'sql' => "SELECT sl.transaction_date as note_date, i.name as product_name, 
                                 sl.quantity_out as qty,
                                 (sl.quantity_out * COALESCE(sl.unit_cost, i.cost_price, 0)) as cost,
                                 COALESCE(sl.remarks, 'Stock adjustment / damage write-off') as reason,
                                 sl.item_id, sl.warehouse_id
                          FROM stock_ledger sl
                          JOIN items i ON sl.item_id = i.id
                          WHERE sl.transaction_type IN ('Damage', 'Adjustment') AND sl.quantity_out > 0"
            ],
            'batch_lot' => [
                'title' => 'Batch / Lot Tracking Report',
                'category' => 'inventory',
                'filters' => ['product', 'status'],
                'columns' => [
                    'batch_code' => ['label' => 'Batch Code', 'type' => 'text'],
                    'product_name' => ['label' => 'Product Name', 'type' => 'text'],
                    'expiry_date' => ['label' => 'Expiry Date', 'type' => 'date'],
                    'qty' => ['label' => 'Qty On Hand', 'type' => 'number', 'align' => 'right', 'total' => 'sum'],
                    'status' => ['label' => 'Status', 'type' => 'badge']
                ],
                'sql' => "SELECT CONCAT('BATCH-', sb.id) as batch_code, i.name as product_name, 
                                 DATE(DATE_ADD(sb.created_at, INTERVAL 1 YEAR)) as expiry_date, 
                                 sb.quantity_remaining as qty, 
                                 CASE WHEN sb.quantity_remaining <= 0 THEN 'Depleted' ELSE 'Active' END as status,
                                 sb.item_id
                          FROM stock_batches sb
                          JOIN items i ON sb.item_id = i.id
                          WHERE 1=1"
            ],
            'product_movement_analysis' => [
                'title' => 'Product Movement Analysis',
                'category' => 'inventory',
                'filters' => ['product', 'category'],
                'columns' => [
                    'name' => ['label' => 'Product Name', 'type' => 'text'],
                    'inwards' => ['label' => 'Total Inwards', 'type' => 'number', 'align' => 'right', 'total' => 'sum'],
                    'outwards' => ['label' => 'Total Outwards', 'type' => 'number', 'align' => 'right', 'total' => 'sum'],
                    'net_change' => ['label' => 'Net Change', 'type' => 'number', 'align' => 'right', 'total' => 'sum']
                ],
                'sql' => "SELECT i.name, i.id as item_id, i.category_id,
                                 SUM(COALESCE(sl.quantity_in, 0)) as inwards, 
                                 SUM(COALESCE(sl.quantity_out, 0)) as outwards, 
                                 SUM(COALESCE(sl.quantity_in, 0) - COALESCE(sl.quantity_out, 0)) as net_change
                          FROM items i
                          LEFT JOIN stock_ledger sl ON i.id = sl.item_id
                          WHERE 1=1
                          GROUP BY i.id, i.name, i.category_id"
            ],
            'fast_moving' => [
                'title' => 'Fast Moving Items',
                'category' => 'inventory',
                'filters' => ['category'],
                'columns' => [
                    'name' => ['label' => 'Product Name', 'type' => 'text'],
                    'sales_count' => ['label' => 'Orders Count', 'type' => 'number', 'align' => 'right'],
                    'sales_qty' => ['label' => 'Qty Sold', 'type' => 'number', 'align' => 'right', 'total' => 'sum'],
                    'revenue' => ['label' => 'Total Revenue', 'type' => 'currency', 'align' => 'right', 'total' => 'sum']
                ],
                'sql' => "SELECT ii.description as name, it.category_id,
                                 COUNT(DISTINCT i.id) as sales_count, 
                                 SUM(ii.quantity) as sales_qty, 
                                 SUM(ii.total) as revenue
                          FROM invoice_items ii
                          JOIN invoices i ON ii.invoice_id = i.id
                          LEFT JOIN items it ON ii.item_id = it.id
                          WHERE i.status != 'Voided'
                          GROUP BY ii.item_id, ii.description, it.category_id
                          ORDER BY sales_qty DESC"
            ],
            'slow_moving' => [
                'title' => 'Slow Moving Items',
                'category' => 'inventory',
                'filters' => ['category'],
                'columns' => [
                    'name' => ['label' => 'Product Name', 'type' => 'text'],
                    'qty_on_hand' => ['label' => 'In Stock', 'type' => 'number', 'align' => 'right'],
                    'days_since_sold' => ['label' => 'Days Since Last Sale', 'type' => 'number', 'align' => 'right']
                ],
                'sql' => "SELECT i.name, i.category_id,
                                 COALESCE(i.quantity_on_hand, i.qty, 0) as qty_on_hand,
                                 COALESCE(DATEDIFF(NOW(), (SELECT MAX(inv.invoice_date) FROM invoice_items ii JOIN invoices inv ON ii.invoice_id = inv.id WHERE ii.item_id = i.id AND inv.status != 'Voided')), 999) as days_since_sold
                          FROM items i
                          WHERE COALESCE(i.quantity_on_hand, i.qty, 0) > 0
                          ORDER BY days_since_sold DESC"
            ],
            'dead_stock' => [
                'title' => 'Dead Stock Report',
                'category' => 'inventory',
                'filters' => ['category'],
                'columns' => [
                    'name' => ['label' => 'Product Name', 'type' => 'text'],
                    'qty_on_hand' => ['label' => 'Stock Level', 'type' => 'number', 'align' => 'right'],
                    'cost_value' => ['label' => 'Capital Blocked', 'type' => 'currency', 'align' => 'right', 'total' => 'sum'],
                    'days_dormant' => ['label' => 'Days Dormant', 'type' => 'number', 'align' => 'right']
                ],
                'sql' => "SELECT i.name, i.category_id,
                                 COALESCE(i.quantity_on_hand, i.qty, 0) as qty_on_hand,
                                 (COALESCE(i.quantity_on_hand, i.qty, 0) * COALESCE(i.cost_price, 0)) as cost_value,
                                 COALESCE(DATEDIFF(NOW(), (SELECT MAX(inv.invoice_date) FROM invoice_items ii JOIN invoices inv ON ii.invoice_id = inv.id WHERE ii.item_id = i.id AND inv.status != 'Voided')), DATEDIFF(NOW(), i.created_at)) as days_dormant
                          FROM items i
                          WHERE COALESCE(i.quantity_on_hand, i.qty, 0) > 0
                            AND NOT EXISTS (
                                SELECT 1 FROM invoice_items ii 
                                JOIN invoices inv ON ii.invoice_id = inv.id 
                                WHERE ii.item_id = i.id AND inv.invoice_date >= DATE_SUB(NOW(), INTERVAL 90 DAY) AND inv.status != 'Voided'
                            )
                          ORDER BY days_dormant DESC"
            ],
            'warehouse_stock' => [
                'title' => 'Warehouse Stock Report',
                'category' => 'inventory',
                'filters' => ['warehouse'],
                'columns' => [
                    'warehouse_name' => ['label' => 'Warehouse', 'type' => 'text'],
                    'product_name' => ['label' => 'Product Name', 'type' => 'text'],
                    'qty' => ['label' => 'Qty On Hand', 'type' => 'number', 'align' => 'right', 'total' => 'sum']
                ],
                'sql' => "SELECT COALESCE(w.name, 'Main Warehouse') as warehouse_name, i.name as product_name, COALESCE(i.quantity_on_hand, i.qty, 0) as qty, i.warehouse_id
                          FROM items i
                          LEFT JOIN warehouses w ON i.warehouse_id = w.id
                          WHERE 1=1"
            ],
            'stock_transfer' => [
                'title' => 'Stock Transfer Report',
                'category' => 'inventory',
                'filters' => ['date_range', 'warehouse'],
                'date_column' => 'wt.transfer_date',
                'columns' => [
                    'transfer_date' => ['label' => 'Date', 'type' => 'date'],
                    'transfer_no' => ['label' => 'Transfer Ref', 'type' => 'text'],
                    'from_wh' => ['label' => 'From Warehouse', 'type' => 'text'],
                    'to_wh' => ['label' => 'To Warehouse', 'type' => 'text'],
                    'product_name' => ['label' => 'Product', 'type' => 'text'],
                    'qty' => ['label' => 'Qty', 'type' => 'number', 'align' => 'right', 'total' => 'sum']
                ],
                'sql' => "SELECT wt.transfer_date, wt.transfer_number as transfer_no, f.name as from_wh, t.name as to_wh, i.name as product_name, wt.qty,
                                 wt.from_warehouse_id, wt.to_warehouse_id, wt.item_id
                          FROM warehouse_transfers wt
                          JOIN items i ON wt.item_id = i.id
                          LEFT JOIN warehouses f ON wt.from_warehouse_id = f.id
                          LEFT JOIN warehouses t ON wt.to_warehouse_id = t.id
                          WHERE 1=1"
            ],

            // 2. Sales Reports
            'sales_report' => [
                'title' => 'Sales Report',
                'category' => 'sales',
                'filters' => ['date_range', 'customer', 'rep', 'route', 'payment_method', 'status', 'vehicle', 'driver', 'partner', 'territory'],
                'date_column' => 'i.invoice_date',
                'columns' => [
                    'invoice_date' => ['label' => 'Date', 'type' => 'date', 'sortable' => true],
                    'invoice_number' => ['label' => 'Invoice Ref', 'type' => 'text', 'drilldown' => 'invoice', 'sortable' => true],
                    'customer_name' => ['label' => 'Customer', 'type' => 'text', 'sortable' => true],
                    'subtotal' => ['label' => 'Subtotal', 'type' => 'currency', 'align' => 'right', 'total' => 'sum'],
                    'tax_amount' => ['label' => 'Tax', 'type' => 'currency', 'align' => 'right', 'total' => 'sum'],
                    'grand_total' => ['label' => 'Grand Total', 'type' => 'currency', 'align' => 'right', 'total' => 'sum'],
                    'status' => ['label' => 'Status', 'type' => 'badge']
                ],
                'sql' => "SELECT i.invoice_date, i.invoice_number, c.name as customer_name,
                                 i.total_amount as subtotal, i.tax_amount, i.id,
                                 (i.total_amount - COALESCE(CASE WHEN i.global_discount_type = '%' THEN (i.total_amount * i.global_discount_val / 100) ELSE i.global_discount_val END, 0) + COALESCE(i.tax_amount, 0)) as grand_total,
                                 i.status, i.customer_id, i.created_by, i.rep_route_id, c.territory
                          FROM invoices i
                          JOIN customers c ON i.customer_id = c.id
                          WHERE i.status != 'Voided'"
            ],
            'sales_summary' => [
                'title' => 'Sales Summary',
                'category' => 'sales',
                'filters' => ['date_range', 'rep', 'route'],
                'date_column' => 'i.invoice_date',
                'columns' => [
                    'period_date' => ['label' => 'Date', 'type' => 'date', 'sortable' => true],
                    'invoice_count' => ['label' => 'Invoice Count', 'type' => 'number', 'align' => 'right', 'total' => 'sum'],
                    'daily_total' => ['label' => 'Gross Sales', 'type' => 'currency', 'align' => 'right', 'total' => 'sum']
                ],
                'sql' => "SELECT i.invoice_date as period_date, COUNT(*) as invoice_count,
                                 SUM(i.total_amount - COALESCE(CASE WHEN i.global_discount_type = '%' THEN (i.total_amount * i.global_discount_val / 100) ELSE i.global_discount_val END, 0) + COALESCE(i.tax_amount, 0)) as daily_total,
                                 i.created_by, i.rep_route_id
                          FROM invoices i
                          WHERE i.status != 'Voided'
                          GROUP BY i.invoice_date, i.created_by, i.rep_route_id"
            ],
            'sales_by_customer' => [
                'title' => 'Sales by Customer',
                'category' => 'sales',
                'filters' => ['date_range', 'customer', 'rep', 'route', 'territory', 'group'],
                'date_column' => 'i.invoice_date',
                'columns' => [
                    'customer_name' => ['label' => 'Customer Name', 'type' => 'text', 'sortable' => true],
                    'phone' => ['label' => 'Phone', 'type' => 'text'],
                    'invoice_count' => ['label' => 'Invoices Count', 'type' => 'number', 'align' => 'right', 'total' => 'sum'],
                    'total_sales' => ['label' => 'Total Sales', 'type' => 'currency', 'align' => 'right', 'total' => 'sum'],
                    'paid_amount' => ['label' => 'Paid Amount', 'type' => 'currency', 'align' => 'right', 'total' => 'sum'],
                    'outstanding' => ['label' => 'Outstanding', 'type' => 'currency', 'align' => 'right', 'total' => 'sum']
                ],
                'sql' => "SELECT c.name as customer_name, c.phone, COUNT(i.id) as invoice_count,
                                 SUM(i.total_amount - COALESCE(CASE WHEN i.global_discount_type = '%' THEN (i.total_amount * i.global_discount_val / 100) ELSE i.global_discount_val END, 0) + COALESCE(i.tax_amount, 0)) as total_sales,
                                 SUM(CASE WHEN i.status = 'Paid' THEN (i.total_amount - COALESCE(CASE WHEN i.global_discount_type = '%' THEN (i.total_amount * i.global_discount_val / 100) ELSE i.global_discount_val END, 0) + COALESCE(i.tax_amount, 0)) ELSE 0 END) as paid_amount,
                                 SUM(CASE WHEN i.status != 'Paid' THEN (i.total_amount - COALESCE(CASE WHEN i.global_discount_type = '%' THEN (i.total_amount * i.global_discount_val / 100) ELSE i.global_discount_val END, 0) + COALESCE(i.tax_amount, 0)) ELSE 0 END) as outstanding,
                                 i.customer_id, i.created_by, i.rep_route_id, c.territory, c.customer_type
                          FROM invoices i
                          JOIN customers c ON i.customer_id = c.id
                          WHERE i.status != 'Voided'
                          GROUP BY c.id, c.name, c.phone, c.territory, c.customer_type"
            ],
            'sales_by_item' => [
                'title' => 'Sales by Item',
                'category' => 'sales',
                'filters' => ['date_range', 'product', 'category', 'brand'],
                'date_column' => 'i.invoice_date',
                'columns' => [
                    'item_name' => ['label' => 'Item Name', 'type' => 'text', 'sortable' => true],
                    'total_qty' => ['label' => 'Quantity Sold', 'type' => 'number', 'align' => 'right', 'total' => 'sum'],
                    'total_revenue' => ['label' => 'Revenue', 'type' => 'currency', 'align' => 'right', 'total' => 'sum'],
                    'total_cost' => ['label' => 'Total Cost', 'type' => 'currency', 'align' => 'right', 'total' => 'sum'],
                    'gross_profit' => ['label' => 'Gross Profit', 'type' => 'currency', 'align' => 'right', 'total' => 'sum']
                ],
                'sql' => "SELECT ii.description as item_name, SUM(ii.quantity) as total_qty,
                                 SUM(ii.total) as total_revenue,
                                 SUM(ii.quantity * COALESCE(ii.cost_at_sale, 0)) as total_cost,
                                 SUM(ii.total - (ii.quantity * COALESCE(ii.cost_at_sale, 0))) as gross_profit,
                                 ii.item_id, it.category_id, it.brand
                          FROM invoice_items ii
                          JOIN invoices i ON ii.invoice_id = i.id
                          LEFT JOIN items it ON ii.item_id = it.id
                          WHERE i.status != 'Voided'
                          GROUP BY ii.description, ii.item_id, it.category_id, it.brand"
            ],

            // 3. Procurement Reports
            'purchase_order_report' => [
                'title' => 'Purchase Order Report',
                'category' => 'procurement',
                'filters' => ['date_range', 'supplier', 'status'],
                'date_column' => 'p.po_date',
                'columns' => [
                    'po_date' => ['label' => 'Date', 'type' => 'date'],
                    'po_number' => ['label' => 'PO Number', 'type' => 'text'],
                    'vendor_name' => ['label' => 'Supplier', 'type' => 'text'],
                    'total_amount' => ['label' => 'Amount', 'type' => 'currency', 'align' => 'right', 'total' => 'sum'],
                    'status' => ['label' => 'Status', 'type' => 'badge']
                ],
                'sql' => "SELECT p.po_date, p.po_number, v.name as vendor_name, p.total_amount, p.status, p.vendor_id
                          FROM purchase_orders p
                          JOIN vendors v ON p.vendor_id = v.id
                          WHERE 1=1"
            ],
            'grn_report' => [
                'title' => 'GRN Report',
                'category' => 'procurement',
                'filters' => ['date_range', 'supplier', 'status'],
                'date_column' => 'g.grn_date',
                'columns' => [
                    'grn_date' => ['label' => 'Date', 'type' => 'date'],
                    'grn_number' => ['label' => 'GRN Ref', 'type' => 'text'],
                    'vendor_name' => ['label' => 'Supplier', 'type' => 'text'],
                    'total_value' => ['label' => 'Received Value', 'type' => 'currency', 'align' => 'right', 'total' => 'sum'],
                    'status' => ['label' => 'Status', 'type' => 'badge']
                ],
                'sql' => "SELECT g.grn_date, g.grn_number, v.name as vendor_name, g.status, g.vendor_id,
                                 COALESCE((SELECT SUM(quantity * unit_cost) FROM grn_items WHERE grn_id = g.id), 0) as total_value
                          FROM goods_receipt_notes g
                          JOIN vendors v ON g.vendor_id = v.id
                          WHERE 1=1"
            ],

            // 4. Customer Reports
            'customer_aging' => [
                'title' => 'Customer Aging Report',
                'category' => 'customer',
                'filters' => ['customer', 'rep', 'route', 'territory', 'group'],
                'columns' => [
                    'customer_name' => ['label' => 'Customer Name', 'type' => 'text'],
                    'current' => ['label' => 'Current', 'type' => 'currency', 'align' => 'right', 'total' => 'sum'],
                    'thirty' => ['label' => '1 - 30 Days', 'type' => 'currency', 'align' => 'right', 'total' => 'sum'],
                    'sixty' => ['label' => '31 - 60 Days', 'type' => 'currency', 'align' => 'right', 'total' => 'sum'],
                    'ninety' => ['label' => '61 - 90 Days', 'type' => 'currency', 'align' => 'right', 'total' => 'sum'],
                    'older' => ['label' => '90+ Days', 'type' => 'currency', 'align' => 'right', 'total' => 'sum'],
                    'total' => ['label' => 'Outstanding', 'type' => 'currency', 'align' => 'right', 'total' => 'sum']
                ],
                'sql' => "SELECT c.name as customer_name, i.customer_id, i.created_by, i.rep_route_id, c.territory, c.customer_type,
                                 SUM(CASE WHEN DATEDIFF(NOW(), i.invoice_date) <= 0 THEN (i.total_amount - COALESCE(CASE WHEN i.global_discount_type = '%' THEN (i.total_amount * i.global_discount_val / 100) ELSE i.global_discount_val END, 0) + COALESCE(i.tax_amount, 0)) ELSE 0 END) as current,
                                 SUM(CASE WHEN DATEDIFF(NOW(), i.invoice_date) > 0 AND DATEDIFF(NOW(), i.invoice_date) <= 30 THEN (i.total_amount - COALESCE(CASE WHEN i.global_discount_type = '%' THEN (i.total_amount * i.global_discount_val / 100) ELSE i.global_discount_val END, 0) + COALESCE(i.tax_amount, 0)) ELSE 0 END) as `thirty`,
                                 SUM(CASE WHEN DATEDIFF(NOW(), i.invoice_date) > 30 AND DATEDIFF(NOW(), i.invoice_date) <= 60 THEN (i.total_amount - COALESCE(CASE WHEN i.global_discount_type = '%' THEN (i.total_amount * i.global_discount_val / 100) ELSE i.global_discount_val END, 0) + COALESCE(i.tax_amount, 0)) ELSE 0 END) as `sixty`,
                                 SUM(CASE WHEN DATEDIFF(NOW(), i.invoice_date) > 60 AND DATEDIFF(NOW(), i.invoice_date) <= 90 THEN (i.total_amount - COALESCE(CASE WHEN i.global_discount_type = '%' THEN (i.total_amount * i.global_discount_val / 100) ELSE i.global_discount_val END, 0) + COALESCE(i.tax_amount, 0)) ELSE 0 END) as `ninety`,
                                 SUM(CASE WHEN DATEDIFF(NOW(), i.invoice_date) > 90 THEN (i.total_amount - COALESCE(CASE WHEN i.global_discount_type = '%' THEN (i.total_amount * i.global_discount_val / 100) ELSE i.global_discount_val END, 0) + COALESCE(i.tax_amount, 0)) ELSE 0 END) as `older`,
                                 SUM(i.total_amount - COALESCE(CASE WHEN i.global_discount_type = '%' THEN (i.total_amount * i.global_discount_val / 100) ELSE i.global_discount_val END, 0) + COALESCE(i.tax_amount, 0)) as total
                          FROM invoices i
                          JOIN customers c ON i.customer_id = c.id
                          WHERE i.status != 'Paid' AND i.status != 'Voided'
                          GROUP BY c.id, c.name, i.customer_id, i.created_by, i.rep_route_id, c.territory, c.customer_type"
            ],
            'customer_statement' => [
                'title' => 'Customer Statement',
                'category' => 'customer',
                'filters' => ['date_range', 'customer'],
                'date_column' => 'i.date',
                'columns' => [
                    'date' => ['label' => 'Date', 'type' => 'date'],
                    'type' => ['label' => 'Type', 'type' => 'text'],
                    'ref' => ['label' => 'Reference', 'type' => 'text'],
                    'debit' => ['label' => 'Debit (Sales)', 'type' => 'currency', 'align' => 'right', 'total' => 'sum'],
                    'credit' => ['label' => 'Credit (Payments)', 'type' => 'currency', 'align' => 'right', 'total' => 'sum'],
                    'balance' => ['label' => 'Running Balance', 'type' => 'currency', 'align' => 'right']
                ],
                'sql' => "SELECT i.date, i.type, i.ref, i.debit, i.credit,
                                 SUM(i.debit - i.credit) OVER (ORDER BY i.date, i.ref) as balance,
                                 i.customer_id
                          FROM (
                              SELECT i2.invoice_date as date, 'Invoice' as type, i2.invoice_number as ref, 
                                     (i2.total_amount - COALESCE(CASE WHEN i2.global_discount_type = '%' THEN (i2.total_amount * i2.global_discount_val / 100) ELSE i2.global_discount_val END, 0) + COALESCE(i2.tax_amount, 0)) as debit,
                                     0 as credit,
                                     i2.customer_id
                              FROM invoices i2
                              WHERE i2.status != 'Voided'
                              UNION ALL
                              SELECT cp.payment_date as date, 'Payment' as type, COALESCE(cp.reference, 'Collection') as ref,
                                     0 as debit,
                                     cp.amount as credit,
                                     cp.customer_id
                              FROM customer_payments cp
                              WHERE cp.status = 'Active'
                          ) as i
                          WHERE 1=1"
            ],

            // 5. Supplier Reports
            'supplier_aging' => [
                'title' => 'Supplier Aging Report',
                'category' => 'supplier',
                'filters' => ['supplier'],
                'columns' => [
                    'vendor_name' => ['label' => 'Supplier', 'type' => 'text'],
                    'current' => ['label' => 'Current', 'type' => 'currency', 'align' => 'right', 'total' => 'sum'],
                    'thirty' => ['label' => '1 - 30 Days', 'type' => 'currency', 'align' => 'right', 'total' => 'sum'],
                    'sixty' => ['label' => '31 - 60 Days', 'type' => 'currency', 'align' => 'right', 'total' => 'sum'],
                    'older' => ['label' => '60+ Days', 'type' => 'currency', 'align' => 'right', 'total' => 'sum'],
                    'total' => ['label' => 'Total Payable', 'type' => 'currency', 'align' => 'right', 'total' => 'sum']
                ],
                'sql' => "SELECT v.name as vendor_name, p.vendor_id,
                                 SUM(CASE WHEN DATEDIFF(NOW(), p.grn_date) <= 0 THEN (COALESCE((SELECT SUM(quantity * unit_cost) FROM grn_items WHERE grn_id = p.id), 0) - COALESCE((SELECT SUM(amount) FROM supplier_payment_allocations WHERE grn_id = p.id AND is_reversed = 0), 0)) ELSE 0 END) as current,
                                 SUM(CASE WHEN DATEDIFF(NOW(), p.grn_date) > 0 AND DATEDIFF(NOW(), p.grn_date) <= 30 THEN (COALESCE((SELECT SUM(quantity * unit_cost) FROM grn_items WHERE grn_id = p.id), 0) - COALESCE((SELECT SUM(amount) FROM supplier_payment_allocations WHERE grn_id = p.id AND is_reversed = 0), 0)) ELSE 0 END) as `thirty`,
                                 SUM(CASE WHEN DATEDIFF(NOW(), p.grn_date) > 30 AND DATEDIFF(NOW(), p.grn_date) <= 60 THEN (COALESCE((SELECT SUM(quantity * unit_cost) FROM grn_items WHERE grn_id = p.id), 0) - COALESCE((SELECT SUM(amount) FROM supplier_payment_allocations WHERE grn_id = p.id AND is_reversed = 0), 0)) ELSE 0 END) as `sixty`,
                                 SUM(CASE WHEN DATEDIFF(NOW(), p.grn_date) > 60 THEN (COALESCE((SELECT SUM(quantity * unit_cost) FROM grn_items WHERE grn_id = p.id), 0) - COALESCE((SELECT SUM(amount) FROM supplier_payment_allocations WHERE grn_id = p.id AND is_reversed = 0), 0)) ELSE 0 END) as `older`,
                                 SUM(COALESCE((SELECT SUM(quantity * unit_cost) FROM grn_items WHERE grn_id = p.id), 0) - COALESCE((SELECT SUM(amount) FROM supplier_payment_allocations WHERE grn_id = p.id AND is_reversed = 0), 0)) as total
                          FROM goods_receipt_notes p
                          JOIN vendors v ON p.vendor_id = v.id
                          WHERE 1=1
                          GROUP BY v.id, v.name, p.vendor_id
                          HAVING total > 0"
            ],

            // 6. Finance & Accounts Reports
            'trial_balance' => [
                'title' => 'Trial Balance',
                'category' => 'finance',
                'columns' => [
                    'account_code' => ['label' => 'Account Code', 'type' => 'text'],
                    'account_name' => ['label' => 'Account Name', 'type' => 'text'],
                    'account_type' => ['label' => 'Type', 'type' => 'text'],
                    'debit' => ['label' => 'Debit', 'type' => 'currency', 'align' => 'right', 'total' => 'sum'],
                    'credit' => ['label' => 'Credit', 'type' => 'currency', 'align' => 'right', 'total' => 'sum']
                ],
                'sql' => "SELECT c.account_code, c.account_name, c.account_type,
                                 SUM(COALESCE(t.debit, 0)) as debit,
                                 SUM(COALESCE(t.credit, 0)) as credit
                          FROM chart_of_accounts c
                          LEFT JOIN transactions t ON c.id = t.account_id
                          LEFT JOIN journal_entries je ON t.journal_entry_id = je.id AND je.status = 'Posted' AND je.reference NOT LIKE 'YE-CLOSE-%'
                          WHERE 1=1
                          GROUP BY c.id, c.account_code, c.account_name, c.account_type"
            ],
            'profit_loss' => [
                'title' => 'Profit & Loss Statement',
                'category' => 'finance',
                'filters' => ['date_range'],
                'date_column' => 'je.entry_date',
                'columns' => [
                    'account_name' => ['label' => 'Account', 'type' => 'text'],
                    'account_type' => ['label' => 'Category', 'type' => 'text'],
                    'balance' => ['label' => 'Net Amount', 'type' => 'currency', 'align' => 'right']
                ],
                'sql' => "SELECT c.account_name, c.account_type,
                                 SUM(CASE WHEN c.account_type = 'Revenue' THEN (COALESCE(t.credit, 0) - COALESCE(t.debit, 0))
                                          WHEN c.account_type = 'Expense' THEN (COALESCE(t.debit, 0) - COALESCE(t.credit, 0))
                                          ELSE 0 END) as balance
                          FROM chart_of_accounts c
                          LEFT JOIN transactions t ON c.id = t.account_id
                          LEFT JOIN journal_entries je ON t.journal_entry_id = je.id AND je.status = 'Posted' AND je.reference NOT LIKE 'YE-CLOSE-%'
                          WHERE c.account_type IN ('Revenue', 'Expense')
                          GROUP BY c.id, c.account_name, c.account_type"
            ],
            'general_ledger' => [
                'title' => 'General Ledger',
                'category' => 'finance',
                'filters' => ['date_range'],
                'date_column' => 'je.entry_date',
                'columns' => [
                    'entry_date' => ['label' => 'Date', 'type' => 'date'],
                    'reference' => ['label' => 'Reference', 'type' => 'text'],
                    'account_name' => ['label' => 'Account', 'type' => 'text'],
                    'debit' => ['label' => 'Debit', 'type' => 'currency', 'align' => 'right', 'total' => 'sum'],
                    'credit' => ['label' => 'Credit', 'type' => 'currency', 'align' => 'right', 'total' => 'sum'],
                    'description' => ['label' => 'Description', 'type' => 'text']
                ],
                'sql' => "SELECT je.entry_date, je.reference, c.account_name, t.debit, t.credit, je.description
                          FROM transactions t
                          JOIN journal_entries je ON t.journal_entry_id = je.id
                          JOIN chart_of_accounts c ON t.account_id = c.id
                          WHERE 1=1"
            ],
            'balance_sheet' => [
                'title' => 'Balance Sheet',
                'category' => 'finance',
                'custom_render' => true
            ],
            'cash_flow' => [
                'title' => 'Statement of Cash Flows',
                'category' => 'finance',
                'custom_render' => true
            ],
            'multi_period_comparison' => [
                'title' => 'Multi-Period Comparison (YoY & MoM)',
                'category' => 'finance',
                'custom_render' => true
            ],

            // 7. Collection Reports
            'credit_collection' => [
                'title' => 'Credit Collection Report',
                'category' => 'collection',
                'filters' => ['date_range', 'customer', 'rep', 'route', 'payment_method'],
                'date_column' => 'cp.payment_date',
                'columns' => [
                    'payment_date' => ['label' => 'Collection Date', 'type' => 'date'],
                    'customer_name' => ['label' => 'Customer', 'type' => 'text'],
                    'payment_method' => ['label' => 'Method', 'type' => 'text'],
                    'reference' => ['label' => 'Ref', 'type' => 'text'],
                    'amount' => ['label' => 'Collected Amount', 'type' => 'currency', 'align' => 'right', 'total' => 'sum']
                ],
                'sql' => "SELECT cp.payment_date, c.name as customer_name, cp.payment_method, cp.reference, cp.amount,
                                 cp.customer_id, cp.created_by, cp.rep_route_id
                          FROM customer_payments cp
                          JOIN customers c ON cp.customer_id = c.id
                          WHERE cp.status = 'Active'"
            ],

            // 8. Route & Distribution Reports
            'route_performance' => [
                'title' => 'Route Performance Report',
                'category' => 'route',
                'filters' => ['date_range', 'route', 'rep', 'vehicle', 'driver'],
                'date_column' => 'r.start_time',
                'columns' => [
                    'route_name' => ['label' => 'Route', 'type' => 'text'],
                    'rep_name' => ['label' => 'Representative', 'type' => 'text'],
                    'orders_count' => ['label' => 'Invoices issued', 'type' => 'number', 'align' => 'right', 'total' => 'sum'],
                    'route_sales' => ['label' => 'Sales Total', 'type' => 'currency', 'align' => 'right', 'total' => 'sum']
                ],
                'sql' => "SELECT r.route_name, r.id, r.user_id, r.start_time,
                                 COALESCE(CONCAT(e.first_name, ' ', e.last_name), u.username, 'N/A') as rep_name,
                                 COUNT(i.id) as orders_count,
                                 SUM(COALESCE(i.total_amount - COALESCE(CASE WHEN i.global_discount_type = '%' THEN (i.total_amount * i.global_discount_val / 100) ELSE i.global_discount_val END, 0) + COALESCE(i.tax_amount, 0), 0)) as route_sales
                          FROM rep_daily_routes r
                          LEFT JOIN users u ON r.user_id = u.id
                          LEFT JOIN employees e ON u.employee_id = e.id
                          LEFT JOIN invoices i ON i.rep_route_id = r.id AND i.status != 'Voided'
                          WHERE 1=1
                          GROUP BY r.id, r.route_name, u.username, e.first_name, e.last_name, r.user_id, r.start_time"
            ],

            // 9. Management Reports
            'monthly_kpi' => [
                'title' => 'Monthly KPI Report',
                'category' => 'management',
                'columns' => [
                    'metric' => ['label' => 'Key Performance Indicator', 'type' => 'text'],
                    'target' => ['label' => 'Target', 'type' => 'text', 'align' => 'right'],
                    'actual' => ['label' => 'Actual Value', 'type' => 'text', 'align' => 'right'],
                    'variance' => ['label' => 'Variance', 'type' => 'text', 'align' => 'right']
                ],
                'sql' => "SELECT 'Monthly Sales Revenue' as metric, 'Rs. 1,000,000.00' as target, 
                                 CONCAT('Rs. ', FORMAT(COALESCE((SELECT SUM(total_amount - COALESCE(CASE WHEN global_discount_type = '%' THEN (total_amount * global_discount_val / 100) ELSE global_discount_val END, 0) + COALESCE(tax_amount, 0)) FROM invoices WHERE status != 'Voided' AND MONTH(invoice_date) = MONTH(NOW()) AND YEAR(invoice_date) = YEAR(NOW())), 0), 2)) as actual,
                                 CONCAT(FORMAT(COALESCE((SELECT SUM(total_amount - COALESCE(CASE WHEN global_discount_type = '%' THEN (total_amount * global_discount_val / 100) ELSE global_discount_val END, 0) + COALESCE(tax_amount, 0)) FROM invoices WHERE status != 'Voided' AND MONTH(invoice_date) = MONTH(NOW()) AND YEAR(invoice_date) = YEAR(NOW())), 0) - 1000000.00, 2)) as variance
                          UNION ALL
                          SELECT 'New Customers Registered' as metric, '50' as target, 
                                 CAST(COALESCE((SELECT COUNT(*) FROM customers WHERE MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())), 0) as CHAR) as actual,
                                 CAST(COALESCE((SELECT COUNT(*) FROM customers WHERE MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())), 0) - 50 as CHAR) as variance
                          UNION ALL
                          SELECT 'Outstanding Receivables' as metric, 'Rs. 200,000.00' as target, 
                                 CONCAT('Rs. ', FORMAT(COALESCE((SELECT SUM(total_amount - COALESCE(CASE WHEN global_discount_type = '%' THEN (total_amount * global_discount_val / 100) ELSE global_discount_val END, 0) + COALESCE(tax_amount, 0)) FROM invoices WHERE status != 'Paid' AND status != 'Voided'), 0), 2)) as actual,
                                 CONCAT(FORMAT(COALESCE((SELECT SUM(total_amount - COALESCE(CASE WHEN global_discount_type = '%' THEN (total_amount * global_discount_val / 100) ELSE global_discount_val END, 0) + COALESCE(tax_amount, 0)) FROM invoices WHERE status != 'Paid' AND status != 'Voided'), 0) - 200000.00, 2)) as variance
                          UNION ALL
                          SELECT 'Procurement (GRN) Value' as metric, 'Rs. 500,000.00' as target, 
                                 CONCAT('Rs. ', FORMAT(COALESCE((SELECT SUM(quantity * unit_cost) FROM grn_items), 0), 2)) as actual,
                                 CONCAT(FORMAT(COALESCE((SELECT SUM(quantity * unit_cost) FROM grn_items), 0) - 500000.00, 2)) as variance"
            ]
        ];
    }

    /**
     * Fetch the report dataset and format it server-side.
     */
    public function fetchData($reportKey, $filters = [], $page = 1, $limit = 50, $sortCol = null, $sortDir = 'ASC') {
        $registry = self::getReportsRegistry();
        if (!isset($registry[$reportKey])) {
            throw new Exception("Report code not registered: " . $reportKey);
        }

        $metadata = $registry[$reportKey];
        $offset = ($page - 1) * $limit;

        if (!isset($metadata['sql'])) {
            throw new Exception("SQL query not defined for report: " . $reportKey);
        }

        $baseSql = $metadata['sql'];
        $params = [];
        $clauses = [];

        // Apply dynamic filter clauses to the base SQL query
        $dateColumn = $metadata['date_column'] ?? null;
        if ($dateColumn) {
            if (isset($filters['start_date']) && !empty($filters['start_date'])) {
                $clauses[] = $dateColumn . " >= :start_date";
                $params[':start_date'] = $filters['start_date'];
            }
            if (isset($filters['end_date']) && !empty($filters['end_date'])) {
                $clauses[] = $dateColumn . " <= :end_date";
                $params[':end_date'] = $filters['end_date'];
            }
        }

        // 1. Customer Filter
        if (isset($filters['customer']) && !empty($filters['customer'])) {
            if (strpos($baseSql, 'customer_id') !== false) {
                if (strpos($baseSql, 'i.customer_id') !== false) {
                    $clauses[] = "i.customer_id = :customer";
                } elseif (strpos($baseSql, 'cp.customer_id') !== false) {
                    $clauses[] = "cp.customer_id = :customer";
                } else {
                    $clauses[] = "customer_id = :customer";
                }
                $params[':customer'] = $filters['customer'];
            }
        }

        // 2. Supplier Filter
        if (isset($filters['supplier']) && !empty($filters['supplier'])) {
            if (strpos($baseSql, 'vendor_id') !== false) {
                if (strpos($baseSql, 'p.vendor_id') !== false) {
                    $clauses[] = "p.vendor_id = :supplier";
                } elseif (strpos($baseSql, 'g.vendor_id') !== false) {
                    $clauses[] = "g.vendor_id = :supplier";
                } else {
                    $clauses[] = "vendor_id = :supplier";
                }
                $params[':supplier'] = $filters['supplier'];
            }
        }

        // 3. Product Filter
        if (isset($filters['product']) && !empty($filters['product'])) {
            if (strpos($baseSql, 'item_id') !== false) {
                if (strpos($baseSql, 'sl.item_id') !== false) {
                    $clauses[] = "sl.item_id = :product";
                } elseif (strpos($baseSql, 'ii.item_id') !== false) {
                    $clauses[] = "ii.item_id = :product";
                } elseif (strpos($baseSql, 'wt.item_id') !== false) {
                    $clauses[] = "wt.item_id = :product";
                } elseif (strpos($baseSql, 'sb.item_id') !== false) {
                    $clauses[] = "sb.item_id = :product";
                } else {
                    $clauses[] = "item_id = :product";
                }
                $params[':product'] = $filters['product'];
            } elseif (strpos($baseSql, 'i.id') !== false) {
                $clauses[] = "i.id = :product";
                $params[':product'] = $filters['product'];
            }
        }

        // 4. Warehouse Filter
        if (isset($filters['warehouse']) && !empty($filters['warehouse'])) {
            if (strpos($baseSql, 'warehouse_id') !== false) {
                if (strpos($baseSql, 'i.warehouse_id') !== false) {
                    $clauses[] = "i.warehouse_id = :warehouse";
                } elseif (strpos($baseSql, 'sl.warehouse_id') !== false) {
                    $clauses[] = "sl.warehouse_id = :warehouse";
                } else {
                    $clauses[] = "warehouse_id = :warehouse";
                }
                $params[':warehouse'] = $filters['warehouse'];
            } elseif (strpos($baseSql, 'wt.from_warehouse_id') !== false) {
                $clauses[] = "(wt.from_warehouse_id = :warehouse OR wt.to_warehouse_id = :warehouse)";
                $params[':warehouse'] = $filters['warehouse'];
            }
        }

        // 5. Category Filter
        if (isset($filters['category']) && !empty($filters['category'])) {
            if (strpos($baseSql, 'category_id') !== false) {
                if (strpos($baseSql, 'i.category_id') !== false) {
                    $clauses[] = "i.category_id = :category";
                } elseif (strpos($baseSql, 'it.category_id') !== false) {
                    $clauses[] = "it.category_id = :category";
                } else {
                    $clauses[] = "category_id = :category";
                }
                $params[':category'] = $filters['category'];
            }
        }

        // 6. Route Filter
        if (isset($filters['route']) && !empty($filters['route'])) {
            if (strpos($baseSql, 'rep_route_id') !== false) {
                if (strpos($baseSql, 'i.rep_route_id') !== false) {
                    $clauses[] = "i.rep_route_id = :route";
                } elseif (strpos($baseSql, 'cp.rep_route_id') !== false) {
                    $clauses[] = "cp.rep_route_id = :route";
                } else {
                    $clauses[] = "rep_route_id = :route";
                }
                $params[':route'] = $filters['route'];
            } elseif (strpos($baseSql, 'r.id') !== false) {
                $clauses[] = "r.id = :route";
                $params[':route'] = $filters['route'];
            }
        }

        // 7. Sales Rep Filter
        if (isset($filters['rep']) && !empty($filters['rep'])) {
            if (strpos($baseSql, 'created_by') !== false) {
                if (strpos($baseSql, 'i.created_by') !== false) {
                    $clauses[] = "i.created_by = :rep";
                } elseif (strpos($baseSql, 'cp.created_by') !== false) {
                    $clauses[] = "cp.created_by = :rep";
                } else {
                    $clauses[] = "created_by = :rep";
                }
                $params[':rep'] = $filters['rep'];
            } elseif (strpos($baseSql, 'r.user_id') !== false) {
                $clauses[] = "r.user_id = :rep";
                $params[':rep'] = $filters['rep'];
            }
        }

        // 8. Payment Method Filter
        if (isset($filters['payment_method']) && !empty($filters['payment_method'])) {
            if (strpos($baseSql, 'payment_method') !== false) {
                if (strpos($baseSql, 'cp.payment_method') !== false) {
                    $clauses[] = "cp.payment_method = :payment_method";
                } else {
                    $clauses[] = "payment_method = :payment_method";
                }
                $params[':payment_method'] = $filters['payment_method'];
            }
        }

        // 9. Status Filter
        if (isset($filters['status']) && !empty($filters['status'])) {
            if (strpos($baseSql, 'status') !== false) {
                if (strpos($baseSql, 'i.status') !== false) {
                    $clauses[] = "i.status = :status";
                } elseif (strpos($baseSql, 'p.status') !== false) {
                    $clauses[] = "p.status = :status";
                } elseif (strpos($baseSql, 'g.status') !== false) {
                    $clauses[] = "g.status = :status";
                } else {
                    $clauses[] = "status = :status";
                }
                $params[':status'] = $filters['status'];
            }
        }

        // 10. Brand Filter
        if (isset($filters['brand']) && !empty($filters['brand'])) {
            if (strpos($baseSql, 'brand') !== false) {
                if (strpos($baseSql, 'i.brand') !== false) {
                    $clauses[] = "i.brand = :brand";
                } elseif (strpos($baseSql, 'it.brand') !== false) {
                    $clauses[] = "it.brand = :brand";
                } else {
                    $clauses[] = "brand = :brand";
                }
                $params[':brand'] = $filters['brand'];
            }
        }

        // 11. Customer/Supplier Group Filter
        if (isset($filters['group']) && !empty($filters['group'])) {
            if (strpos($baseSql, 'customer_type') !== false) {
                if (strpos($baseSql, 'c.customer_type') !== false) {
                    $clauses[] = "c.customer_type = :group";
                } else {
                    $clauses[] = "customer_type = :group";
                }
                $params[':group'] = $filters['group'];
            }
        }

        // 12. Vehicle Filter
        if (isset($filters['vehicle']) && !empty($filters['vehicle'])) {
            if (strpos($baseSql, 'rep_route_id') !== false) {
                if (strpos($baseSql, 'i.rep_route_id') !== false) {
                    $clauses[] = "EXISTS (SELECT 1 FROM deliveries d WHERE d.rep_route_id = i.rep_route_id AND d.vehicle_number = :vehicle)";
                } else {
                    $clauses[] = "EXISTS (SELECT 1 FROM deliveries d WHERE d.rep_route_id = rep_route_id AND d.vehicle_number = :vehicle)";
                }
                $params[':vehicle'] = $filters['vehicle'];
            } elseif (strpos($baseSql, 'r.id') !== false) {
                $clauses[] = "EXISTS (SELECT 1 FROM deliveries d WHERE d.rep_route_id = r.id AND d.vehicle_number = :vehicle)";
                $params[':vehicle'] = $filters['vehicle'];
            }
        }

        // 13. Driver Filter
        if (isset($filters['driver']) && !empty($filters['driver'])) {
            if (strpos($baseSql, 'rep_route_id') !== false) {
                if (strpos($baseSql, 'i.rep_route_id') !== false) {
                    $clauses[] = "EXISTS (SELECT 1 FROM deliveries d WHERE d.rep_route_id = i.rep_route_id AND d.driver_name = :driver)";
                } else {
                    $clauses[] = "EXISTS (SELECT 1 FROM deliveries d WHERE d.rep_route_id = rep_route_id AND d.driver_name = :driver)";
                }
                $params[':driver'] = $filters['driver'];
            } elseif (strpos($baseSql, 'r.id') !== false) {
                $clauses[] = "EXISTS (SELECT 1 FROM deliveries d WHERE d.rep_route_id = r.id AND d.driver_name = :driver)";
                $params[':driver'] = $filters['driver'];
            }
        }

        // 14. Partner Filter
        if (isset($filters['partner']) && !empty($filters['partner'])) {
            if (strpos($baseSql, 'rep_route_id') !== false) {
                if (strpos($baseSql, 'i.rep_route_id') !== false) {
                    $clauses[] = "EXISTS (SELECT 1 FROM deliveries d WHERE d.rep_route_id = i.rep_route_id AND d.partner_name = :partner)";
                } else {
                    $clauses[] = "EXISTS (SELECT 1 FROM deliveries d WHERE d.rep_route_id = rep_route_id AND d.partner_name = :partner)";
                }
                $params[':partner'] = $filters['partner'];
            } elseif (strpos($baseSql, 'r.id') !== false) {
                $clauses[] = "EXISTS (SELECT 1 FROM deliveries d WHERE d.rep_route_id = r.id AND d.partner_name = :partner)";
                $params[':partner'] = $filters['partner'];
            }
        }

        // 15. Territory Filter
        if (isset($filters['territory']) && !empty($filters['territory'])) {
            if (strpos($baseSql, 'territory') !== false) {
                if (strpos($baseSql, 'c.territory') !== false) {
                    $clauses[] = "c.territory = :territory";
                } else {
                    $clauses[] = "territory = :territory";
                }
                $params[':territory'] = $filters['territory'];
            }
        }

        // Inject clauses prior to GROUP BY if present
        if (!empty($clauses)) {
            $conditions = " AND " . implode(" AND ", $clauses);
            $groupByPos = stripos($baseSql, ' GROUP BY ');
            if ($groupByPos !== false) {
                $baseSql = substr($baseSql, 0, $groupByPos) . $conditions . substr($baseSql, $groupByPos);
            } else {
                $baseSql .= $conditions;
            }
        }

        // Live execution
        try {
            // Get Total Row count for server-side pagination
            $countSql = "SELECT COUNT(*) as cnt FROM (" . $baseSql . ") as temp_table";
            $this->db->query($countSql);
            foreach ($params as $k => $v) {
                $this->db->bind($k, $v);
            }
            $countRow = $this->db->single();
            $totalRows = $countRow ? (int)$countRow->cnt : 0;

            // Calculate Grand Totals dynamically on the full filtered dataset (not paginated)
            $grandTotals = [];
            $totalSelects = [];
            foreach ($metadata['columns'] as $colKey => $colDef) {
                if (isset($colDef['total']) && $colDef['total'] === 'sum') {
                    if (preg_match('/^[a-zA-Z0-9_]+$/', $colKey)) {
                        $totalSelects[] = "SUM(" . $colKey . ") as sum_" . $colKey;
                    }
                }
            }
            if (!empty($totalSelects)) {
                $totalsSql = "SELECT " . implode(', ', $totalSelects) . " FROM (" . $baseSql . ") as totals_table";
                try {
                    $this->db->query($totalsSql);
                    foreach ($params as $k => $v) {
                        $this->db->bind($k, $v);
                    }
                    $totalsRow = $this->db->single();
                    if ($totalsRow) {
                        foreach ($metadata['columns'] as $colKey => $colDef) {
                            if (isset($colDef['total']) && $colDef['total'] === 'sum') {
                                $grandTotals[$colKey] = floatval($totalsRow->{"sum_" . $colKey} ?? 0);
                            }
                        }
                    }
                } catch (PDOException $totalEx) {
                    error_log("ReportEngine failed to calculate full grand totals: " . $totalEx->getMessage());
                }
            }

            // Apply Sorting & Pagination
            if (!empty($sortCol) && isset($metadata['columns'][$sortCol]) && preg_match('/^[a-zA-Z0-9_\.]+$/', $sortCol)) {
                $baseSql .= " ORDER BY " . $sortCol . " " . ($sortDir === 'DESC' ? 'DESC' : 'ASC');
            }
            $baseSql .= " LIMIT :limit OFFSET :offset";

            $this->db->query($baseSql);
            foreach ($params as $k => $v) {
                $this->db->bind($k, $v);
            }
            $this->db->bind(':limit', $limit, PDO::PARAM_INT);
            $this->db->bind(':offset', $offset, PDO::PARAM_INT);
            $rows = $this->db->resultSet() ?: [];

            return [
                'rows' => $rows,
                'total_rows' => $totalRows,
                'grand_totals' => $grandTotals,
                'simulation' => false
            ];
        } catch (PDOException $ex) {
            // Log database error for debugging and return standard empty structure with database error
            error_log("ReportEngine Live Query Error: " . $ex->getMessage());
            return [
                'rows' => [],
                'total_rows' => 0,
                'grand_totals' => [],
                'simulation' => false,
                'db_error' => $ex->getMessage()
            ];
        }
    }
}
