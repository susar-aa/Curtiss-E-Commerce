<?php

class Item {
    private $db;
    private $priceColumn = 'price'; // Default fallback
    private $itemCodeColumn = 'item_code';
    private $nameColumn = 'name';
    private $qtyColumn = 'qty';
    private $descColumn = 'description';
    private $wholesalePriceColumn = 'wholesale_price'; // For WholesaleX B2B compatibility
    private $orderByColumn = 'id DESC';
    private $hasQtyColumn = true;
    private $hasQuantityOnHandColumn = true;

    // Static cache for detected columns to optimize performance
    private static $cachedColumns = null;

    public function __construct() {
        $this->db = new Database();
        $this->detectColumns();
    }

    /**
     * Dynamically inspect the 'items' table schema to adapt to different column names.
     * Caches results in a static property to run exactly once per request.
     */
    private function detectColumns() {
        if (self::$cachedColumns !== null) {
            $this->priceColumn = self::$cachedColumns['priceColumn'];
            $this->itemCodeColumn = self::$cachedColumns['itemCodeColumn'];
            $this->nameColumn = self::$cachedColumns['nameColumn'];
            $this->qtyColumn = self::$cachedColumns['qtyColumn'];
            $this->descColumn = self::$cachedColumns['descColumn'];
            $this->wholesalePriceColumn = self::$cachedColumns['wholesalePriceColumn'];
            $this->orderByColumn = self::$cachedColumns['orderByColumn'];
            $this->hasQtyColumn = self::$cachedColumns['hasQtyColumn'];
            $this->hasQuantityOnHandColumn = self::$cachedColumns['hasQuantityOnHandColumn'];
            return;
        }

        $cached = Cache::get('items_schema_columns');
        if ($cached) {
            self::$cachedColumns = $cached;
            $this->priceColumn = $cached['priceColumn'];
            $this->itemCodeColumn = $cached['itemCodeColumn'];
            $this->nameColumn = $cached['nameColumn'];
            $this->qtyColumn = $cached['qtyColumn'];
            $this->descColumn = $cached['descColumn'];
            $this->wholesalePriceColumn = $cached['wholesalePriceColumn'];
            $this->orderByColumn = $cached['orderByColumn'];
            $this->hasQtyColumn = $cached['hasQtyColumn'];
            $this->hasQuantityOnHandColumn = $cached['hasQuantityOnHandColumn'];
            return;
        }

        try {
            $this->db->query("DESCRIBE items");
            $columns = $this->db->resultSet();
            if ($columns) {
                $fields = array_map(function($col) {
                    return strtolower($col->Field ?? $col->field ?? '');
                }, $columns);

                $this->hasQtyColumn = in_array('qty', $fields);
                $this->hasQuantityOnHandColumn = in_array('quantity_on_hand', $fields);

                // 1. Detect Price
                $priceCol = 'price';
                foreach ($fields as $f) {
                    if ($f === 'selling_price' || $f === 'price' || $f === 'unit_price' || $f === 'rate') {
                        $priceCol = $f;
                        break;
                    }
                }
                $this->priceColumn = $priceCol;

                // 2. Detect Wholesale Price
                $wholesaleCol = 'wholesale_price';
                foreach ($fields as $f) {
                    if ($f === 'wholesale_price' || $f === 'b2b_price' || $f === 'wholesale' || $f === 'trade_price') {
                        $wholesaleCol = $f;
                        break;
                    }
                }
                $this->wholesalePriceColumn = $wholesaleCol;

                // 3. Detect Item Code / SKU
                $codeCol = 'item_code';
                foreach ($fields as $f) {
                    if ($f === 'item_code' || $f === 'sku' || $f === 'code' || $f === 'barcode') {
                        $codeCol = $f;
                        break;
                    }
                }
                $this->itemCodeColumn = $codeCol;

                // 4. Detect Name / Title
                $this->nameColumn = in_array('name', $fields) ? 'name' : (in_array('title', $fields) ? 'title' : 'name');

                // 5. Detect Qty / Stock
                $qtyCol = 'qty';
                if (in_array('qty', $fields)) {
                    $qtyCol = 'qty';
                } elseif (in_array('quantity_on_hand', $fields)) {
                    $qtyCol = 'quantity_on_hand';
                } else {
                    foreach ($fields as $f) {
                        if ($f === 'quantity' || $f === 'stock' || $f === 'stock_quantity' || $f === 'stock_qty') {
                            $qtyCol = $f;
                            break;
                        }
                    }
                }
                $this->qtyColumn = $qtyCol;

                // 6. Detect Description
                $this->descColumn = in_array('description', $fields) ? 'description' : (in_array('desc', $fields) ? 'desc' : 'description');

                // Set order parameter
                if (in_array('created_at', $fields)) {
                    $this->orderByColumn = 'created_at DESC';
                } else {
                    $this->orderByColumn = 'id DESC';
                }

                // Cache the values
                self::$cachedColumns = [
                    'priceColumn' => $this->priceColumn,
                    'itemCodeColumn' => $this->itemCodeColumn,
                    'nameColumn' => $this->nameColumn,
                    'qtyColumn' => $this->qtyColumn,
                    'descColumn' => $this->descColumn,
                    'wholesalePriceColumn' => $this->wholesalePriceColumn,
                    'orderByColumn' => $this->orderByColumn,
                    'hasQtyColumn' => $this->hasQtyColumn,
                    'hasQuantityOnHandColumn' => $this->hasQuantityOnHandColumn,
                    'fields' => $fields
                ];
                Cache::set('items_schema_columns', self::$cachedColumns, 86400);
            }
        } catch (Exception $e) {
            // Fall back silently
        }
    }

    /**
     * Sanitizes dynamic column names to prevent SQL Injection and satisfy static analysis.
     */
    private function safeCol($column) {
        $column = trim($column);
        if (preg_match('/^[a-zA-Z0-9_]+\s+(ASC|DESC)$/i', $column)) {
            return $column;
        }
        return preg_replace('/[^a-zA-Z0-9_]/', '', $column);
    }

    /**
     * Builds standard SQL where clauses dynamically to keep database queries highly performant.
     */
    private function buildFilterConditions($filters, &$params) {
        $whereClauses = [];

        $priceCol = $this->safeCol($this->priceColumn);
        $itemCodeCol = $this->safeCol($this->itemCodeColumn);
        $nameCol = $this->safeCol($this->nameColumn);
        $qtyCol = $this->safeCol($this->qtyColumn);

        if (!empty($filters['search'])) {
            $searchStr = trim($filters['search']);
            
            // Split search string into individual words/tokens
            $tokens = preg_split('/\s+/', $searchStr);
            
            // Limit tokens to prevent extreme database load on complex queries
            if (count($tokens) > 8) {
                $tokens = array_slice($tokens, 0, 8);
            }
            
            $tokenConditions = [];
            foreach ($tokens as $index => $token) {
                if (strlen($token) === 0) continue;
                
                $tokenParam = ':search_token_' . $index;
                // Match each word against SKU, Name, and Sample Code (order-independent)
                $tokenConditions[] = "(i.{$itemCodeCol} LIKE {$tokenParam} OR i.{$nameCol} LIKE {$tokenParam} OR i.sample_code LIKE {$tokenParam})";
                $params[$tokenParam] = '%' . $token . '%';
            }
            
            if (!empty($tokenConditions)) {
                $whereClauses[] = "(" . implode(" AND ", $tokenConditions) . ")";
            }
        }

        if (isset($filters['min_price']) && $filters['min_price'] !== '') {
            $whereClauses[] = "CAST(i.{$priceCol} AS DECIMAL(10,2)) >= :min_price";
            $params[':min_price'] = floatval($filters['min_price']);
        }

        if (isset($filters['max_price']) && $filters['max_price'] !== '') {
            $whereClauses[] = "CAST(i.{$priceCol} AS DECIMAL(10,2)) <= :max_price";
            $params[':max_price'] = floatval($filters['max_price']);
        }

        if (!empty($filters['stock_status'])) {
            if ($filters['stock_status'] === 'instock') {
                $whereClauses[] = "CAST(i.{$qtyCol} AS SIGNED) > 5";
            } elseif ($filters['stock_status'] === 'lowstock') {
                $whereClauses[] = "CAST(i.{$qtyCol} AS SIGNED) > 0 AND CAST(i.{$qtyCol} AS SIGNED) <= 5";
            } elseif ($filters['stock_status'] === 'outstock') {
                $whereClauses[] = "(CAST(i.{$qtyCol} AS SIGNED) <= 0 OR i.{$qtyCol} IS NULL)";
            }
        }

        if (!empty($filters['category_id'])) {
            $whereClauses[] = "i.category_id = :category_id";
            $params[':category_id'] = intval($filters['category_id']);
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $whereClauses[] = "i.status = :status";
            $params[':status'] = $filters['status'];
        }

        return !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";
    }

    /**
     * Fetch items in paginated batches at database level (resolves PHP out of memory error).
     */
    public function getItemsPaged($limit, $offset, $filters = []) {
        $params = [];
        $whereSql = $this->buildFilterConditions($filters, $params);

        $priceCol = $this->safeCol($this->priceColumn);
        $wholesalePriceCol = $this->safeCol($this->wholesalePriceColumn);
        $itemCodeCol = $this->safeCol($this->itemCodeColumn);
        $qtyCol = $this->safeCol($this->qtyColumn);
        $descCol = $this->safeCol($this->descColumn);
        $orderByCol = $this->safeCol($this->orderByColumn);

        $this->db->query("SELECT i.*, 
                          cat.name AS category_name,
                          i.{$priceCol} AS selling_price, 
                          i.{$wholesalePriceCol} AS wholesale_price, 
                          i.{$itemCodeCol} AS item_code, 
                          i.{$qtyCol} AS qty, 
                          i.{$descCol} AS description 
                          FROM items i 
                          LEFT JOIN item_categories cat ON i.category_id = cat.id
                          $whereSql 
                          ORDER BY i.{$orderByCol} 
                          LIMIT :limit OFFSET :offset");

        $this->db->bind(':limit', (int)$limit, PDO::PARAM_INT);
        $this->db->bind(':offset', (int)$offset, PDO::PARAM_INT);

        foreach ($params as $key => $val) {
            $this->db->bind($key, $val);
        }

        return $this->db->resultSet();
    }

    /**
     * Return total quantity of items matching filters (essential for pagination calculations).
     */
    public function countItems($filters = []) {
        $params = [];
        $whereSql = $this->buildFilterConditions($filters, $params);

        $this->db->query("SELECT COUNT(*) AS total FROM items i $whereSql");
        foreach ($params as $key => $val) {
            $this->db->bind($key, $val);
        }

        $row = $this->db->single();
        return (int)($row->total ?? 0);
    }

    /**
     * Calculate global item analytics dynamically at the database level.
     */
    public function getStockStats() {
        try {
            $qtyCol = $this->safeCol($this->qtyColumn);
            $this->db->query("SELECT 
                COUNT(*) AS total_items,
                SUM(CASE WHEN CAST({$qtyCol} AS SIGNED) <= 0 OR {$qtyCol} IS NULL THEN 1 ELSE 0 END) AS out_of_stock_count,
                SUM(CASE WHEN CAST({$qtyCol} AS SIGNED) > 0 AND CAST({$qtyCol} AS SIGNED) <= 5 THEN 1 ELSE 0 END) AS low_stock_count
                FROM items");
            
            $stats = $this->db->single();
            if ($stats) {
                return (object)[
                    'total_items' => (int)($stats->total_items ?? 0),
                    'low_stock_count' => (int)($stats->low_stock_count ?? 0),
                    'out_of_stock_count' => (int)($stats->out_of_stock_count ?? 0)
                ];
            }
        } catch (Exception $e) {
            // Safe fallbacks on failure
        }

        return (object)[
            'total_items' => 0,
            'low_stock_count' => 0,
            'out_of_stock_count' => 0
        ];
    }

    /**
     * Retrieve all items inside the local database
     */
    public function getItems() {
        $priceCol = $this->safeCol($this->priceColumn);
        $wholesalePriceCol = $this->safeCol($this->wholesalePriceColumn);
        $itemCodeCol = $this->safeCol($this->itemCodeColumn);
        $qtyCol = $this->safeCol($this->qtyColumn);
        $descCol = $this->safeCol($this->descColumn);
        $orderByCol = $this->safeCol($this->orderByColumn);

        $this->db->query("SELECT i.*, cat.name AS category_name, i.{$priceCol} AS selling_price, i.{$wholesalePriceCol} AS wholesale_price, i.{$itemCodeCol} AS item_code, i.{$qtyCol} AS qty, i.{$descCol} AS description FROM items i LEFT JOIN item_categories cat ON i.category_id = cat.id ORDER BY i.{$orderByCol}");
        return $this->db->resultSet();
    }

    public function getItemsDelta($lastSync = '') {
        $whereSql = '';
        if (!empty($lastSync)) {
            $fields = self::$cachedColumns['fields'] ?? [];
            if (in_array('updated_at', $fields)) {
                $whereSql = " WHERE i.updated_at > :last_sync";
            } elseif (in_array('created_at', $fields)) {
                $whereSql = " WHERE i.created_at > :last_sync";
            } else {
                $this->db->query("SHOW COLUMNS FROM items LIKE 'updated_at'");
                if ($this->db->single()) {
                    $whereSql = " WHERE i.updated_at > :last_sync";
                } else {
                    $this->db->query("SHOW COLUMNS FROM items LIKE 'created_at'");
                    if ($this->db->single()) {
                        $whereSql = " WHERE i.created_at > :last_sync";
                    }
                }
            }
        } else {
            $whereSql = " WHERE (i.status IS NULL OR i.status != 'inactive')";
        }

        $priceCol = $this->safeCol($this->priceColumn);
        $wholesalePriceCol = $this->safeCol($this->wholesalePriceColumn);
        $itemCodeCol = $this->safeCol($this->itemCodeColumn);
        $qtyCol = $this->safeCol($this->qtyColumn);
        $descCol = $this->safeCol($this->descColumn);
        $orderByCol = $this->safeCol($this->orderByColumn);
        
        $this->db->query("SELECT i.*, cat.name AS category_name, i.{$priceCol} AS selling_price, i.{$wholesalePriceCol} AS wholesale_price, i.{$itemCodeCol} AS item_code, i.{$qtyCol} AS qty, i.{$descCol} AS description 
                          FROM items i 
                          LEFT JOIN item_categories cat ON i.category_id = cat.id 
                          $whereSql
                          ORDER BY i.{$orderByCol}");
                          
        if (!empty($lastSync)) {
            $this->db->bind(':last_sync', $lastSync);
        }
        return $this->db->resultSet();
    }

    /**
     * Alias method for SalesController compatibility.
     * Prevents "Call to undefined method Item::getAllItems()" Fatal Error on create invoice page.
     */
    public function getAllItems() {
        return $this->getItems();
    }

    public function getItemById($id) {
        $priceCol = $this->safeCol($this->priceColumn);
        $wholesalePriceCol = $this->safeCol($this->wholesalePriceColumn);
        $itemCodeCol = $this->safeCol($this->itemCodeColumn);
        $qtyCol = $this->safeCol($this->qtyColumn);
        $descCol = $this->safeCol($this->descColumn);

        $this->db->query("SELECT *, {$priceCol} AS selling_price, {$wholesalePriceCol} AS wholesale_price, {$itemCodeCol} AS item_code, {$qtyCol} AS qty, {$descCol} AS description FROM items WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    /**
     * Variation options for an item (PO mix resolver, GRN create, etc.).
     */
    public function getItemVariations($itemId) {
        if (!$itemId) {
            return [];
        }

        try {
            $this->db->query("
                SELECT ivo.*, v.name AS variation_name, vv.value_name
                FROM item_variation_options ivo
                JOIN variations v ON ivo.variation_id = v.id
                JOIN variation_values vv ON ivo.variation_value_id = vv.id
                WHERE ivo.item_id = :id
                ORDER BY v.name ASC, vv.value_name ASC
            ");
            $this->db->bind(':id', $itemId);
            return $this->db->resultSet() ?: [];
        } catch (Exception $e) {
            return [];
        }
    }

    public function getItemByCode($item_code) {
        $priceCol = $this->safeCol($this->priceColumn);
        $wholesalePriceCol = $this->safeCol($this->wholesalePriceColumn);
        $itemCodeCol = $this->safeCol($this->itemCodeColumn);
        $qtyCol = $this->safeCol($this->qtyColumn);
        $descCol = $this->safeCol($this->descColumn);

        $this->db->query("SELECT *, {$priceCol} AS selling_price, {$wholesalePriceCol} AS wholesale_price, {$itemCodeCol} AS item_code, {$qtyCol} AS qty, {$descCol} AS description FROM items WHERE {$itemCodeCol} = :item_code");
        $this->db->bind(':item_code', $item_code);
        return $this->db->single();
    }

    public function addItem($data) {
        $priceCol = $this->safeCol($this->priceColumn);
        $wholesalePriceCol = $this->safeCol($this->wholesalePriceColumn);
        $itemCodeCol = $this->safeCol($this->itemCodeColumn);
        $descCol = $this->safeCol($this->descColumn);

        $qtyColumnNames = [];
        $qtyParamBindings = [];
        if ($this->hasQtyColumn) {
            $qtyColumnNames[] = 'qty';
            $qtyParamBindings[] = ':qty';
        }
        if ($this->hasQuantityOnHandColumn) {
            $qtyColumnNames[] = 'quantity_on_hand';
            $qtyParamBindings[] = ':qty';
        }
        if (empty($qtyColumnNames)) {
            $qtyColumnNames[] = 'quantity_on_hand';
            $qtyParamBindings[] = ':qty';
        }

        $qtyColsStr = implode(', ', $qtyColumnNames);
        $qtyValsStr = implode(', ', $qtyParamBindings);

        $this->db->query("INSERT INTO items (
            {$itemCodeCol}, name, {$priceCol}, {$wholesalePriceCol}, {$qtyColsStr}, {$descCol},
            barcode, category_id, brand, warehouse, alert_qty, unit, status, weight, sync_woo, variations_json, image_path,
            additional_images, cost_price, warehouse_id, vendor_id, sample_code, retail_margin, wholesale_margin
        ) VALUES (
            :item_code, :name, :price, :wholesale_price, {$qtyValsStr}, :description,
            :barcode, :category_id, :brand, :warehouse, :alert_qty, :unit, :status, :weight, :sync_woo, :variations_json, :image_path,
            :additional_images, :cost_price, :warehouse_id, :vendor_id, :sample_code, :retail_margin, :wholesale_margin
        )");

        $this->db->bind(':item_code', $data['item_code']);
        $this->db->bind(':name', $data['name']);
        $this->db->bind(':price', $data['selling_price']);
        $this->db->bind(':wholesale_price', $data['wholesale_price'] ?? '0.00');
        $this->db->bind(':qty', $data['qty']);
        $this->db->bind(':description', $data['description'] ?? '');
        $this->db->bind(':barcode', $data['barcode'] ?? '');
        $this->db->bind(':category_id', !empty($data['category_id']) ? intval($data['category_id']) : null);
        $this->db->bind(':brand', $data['brand'] ?? '');
        $this->db->bind(':warehouse', $data['warehouse'] ?? '');
        $this->db->bind(':alert_qty', intval($data['alert_qty'] ?? 5));
        $this->db->bind(':unit', $data['unit'] ?? 'pcs');
        $this->db->bind(':status', $data['status'] ?? 'active');
        $this->db->bind(':weight', $data['weight'] ?? '');
        $this->db->bind(':sync_woo', intval($data['sync_woo'] ?? 1));
        $this->db->bind(':variations_json', $data['variations_json'] ?? '[]');
        $this->db->bind(':image_path', $data['image_path'] ?? '');
        $this->db->bind(':additional_images', $data['additional_images'] ?? '[]');
        $this->db->bind(':cost_price', $data['cost_price'] ?? '0.00');
        $this->db->bind(':warehouse_id', $data['warehouse_id'] ?? null);
        $this->db->bind(':vendor_id', $data['vendor_id'] ?? null);
        $this->db->bind(':sample_code', $data['sample_code'] ?? null);
        $this->db->bind(':retail_margin', $data['retail_margin'] ?? '0.00');
        $this->db->bind(':wholesale_margin', $data['wholesale_margin'] ?? '0.00');

        return $this->db->execute();
    }

    public function updateItem($data) {
        $priceCol = $this->safeCol($this->priceColumn);
        $wholesalePriceCol = $this->safeCol($this->wholesalePriceColumn);
        $itemCodeCol = $this->safeCol($this->itemCodeColumn);
        $descCol = $this->safeCol($this->descColumn);

        $qtyUpdates = [];
        if ($this->hasQtyColumn) {
            $qtyUpdates[] = "qty = :qty";
        }
        if ($this->hasQuantityOnHandColumn) {
            $qtyUpdates[] = "quantity_on_hand = :qty";
        }
        if (empty($qtyUpdates)) {
            $qtyUpdates[] = "quantity_on_hand = :qty";
        }
        $qtyUpdatesStr = implode(', ', $qtyUpdates);

        $this->db->query("UPDATE items SET 
            {$itemCodeCol} = :item_code,
            name = :name,
            {$priceCol} = :price,
            {$wholesalePriceCol} = :wholesale_price,
            {$qtyUpdatesStr},
            {$descCol} = :description,
            barcode = :barcode,
            category_id = :category_id,
            brand = :brand,
            warehouse = :warehouse,
            alert_qty = :alert_qty,
            unit = :unit,
            status = :status,
            weight = :weight,
            sync_woo = :sync_woo,
            variations_json = :variations_json,
            image_path = :image_path,
            additional_images = :additional_images,
            cost_price = :cost_price,
            warehouse_id = :warehouse_id,
            vendor_id = :vendor_id,
            sample_code = :sample_code,
            retail_margin = :retail_margin,
            wholesale_margin = :wholesale_margin
            WHERE id = :id");

        $this->db->bind(':id', $data['id']);
        $this->db->bind(':item_code', $data['item_code']);
        $this->db->bind(':name', $data['name']);
        $this->db->bind(':price', $data['selling_price']);
        $this->db->bind(':wholesale_price', $data['wholesale_price'] ?? '0.00');
        $this->db->bind(':qty', $data['qty']);
        $this->db->bind(':description', $data['description'] ?? '');
        $this->db->bind(':barcode', $data['barcode'] ?? '');
        $this->db->bind(':category_id', !empty($data['category_id']) ? intval($data['category_id']) : null);
        $this->db->bind(':brand', $data['brand'] ?? '');
        $this->db->bind(':warehouse', $data['warehouse'] ?? '');
        $this->db->bind(':alert_qty', intval($data['alert_qty'] ?? 5));
        $this->db->bind(':unit', $data['unit'] ?? 'pcs');
        $this->db->bind(':status', $data['status'] ?? 'active');
        $this->db->bind(':weight', $data['weight'] ?? '');
        $this->db->bind(':sync_woo', intval($data['sync_woo'] ?? 1));
        $this->db->bind(':variations_json', $data['variations_json'] ?? '[]');
        $this->db->bind(':image_path', $data['image_path'] ?? '');
        $this->db->bind(':additional_images', $data['additional_images'] ?? '[]');
        $this->db->bind(':cost_price', $data['cost_price'] ?? '0.00');
        $this->db->bind(':warehouse_id', $data['warehouse_id'] ?? null);
        $this->db->bind(':vendor_id', $data['vendor_id'] ?? null);
        $this->db->bind(':sample_code', $data['sample_code'] ?? null);
        $this->db->bind(':retail_margin', $data['retail_margin'] ?? '0.00');
        $this->db->bind(':wholesale_margin', $data['wholesale_margin'] ?? '0.00');

        return $this->db->execute();
    }

    public function updateStockOnly($id, $newQty) {
        if (is_numeric($this->qtyColumn)) {
            return true;
        }
        $qtyUpdates = [];
        if ($this->hasQtyColumn) {
            $qtyUpdates[] = "qty = :qty";
        }
        if ($this->hasQuantityOnHandColumn) {
            $qtyUpdates[] = "quantity_on_hand = :qty";
        }
        if (empty($qtyUpdates)) {
            $qtyUpdates[] = "quantity_on_hand = :qty";
        }
        $qtyUpdatesStr = implode(', ', $qtyUpdates);

        $this->db->query("UPDATE items SET {$qtyUpdatesStr} WHERE id = :id");
        $this->db->bind(':id', $id);
        $this->db->bind(':qty', $newQty);
        return $this->db->execute();
    }

    /**
     * Increment or decrement stock by a delta in a schema-aware manner.
     * Prevents negative stock underflow using GREATEST(0, ...).
     */
    public function updateStockDelta($id, $delta) {
        $qtyUpdates = [];
        if ($this->hasQtyColumn) {
            $qtyUpdates[] = "qty = GREATEST(0, CAST(qty AS SIGNED) + :delta)";
        }
        if ($this->hasQuantityOnHandColumn) {
            $qtyUpdates[] = "quantity_on_hand = GREATEST(0, CAST(quantity_on_hand AS SIGNED) + :delta)";
        }
        if (empty($qtyUpdates)) {
            $qtyUpdates[] = "quantity_on_hand = GREATEST(0, CAST(quantity_on_hand AS SIGNED) + :delta)";
        }
        $qtyUpdatesStr = implode(', ', $qtyUpdates);

        $this->db->query("UPDATE items SET {$qtyUpdatesStr} WHERE id = :id");
        $this->db->bind(':id', $id);
        $this->db->bind(':delta', $delta);
        return $this->db->execute();
    }

    /**
     * Automatically generate sample codes ONLY for products that don't have one yet.
     * Existing sample codes are preserved and never overwritten.
     * Orders categories alphabetically (name ASC) and then orders products within each
     * category by creation (id ASC). The first category starts at 100, second at 200, etc.
     */
    public function regenerateSampleCodes() {
        try {
            // Fetch all categories sorted by name ASC
            $this->db->query("SELECT id FROM item_categories ORDER BY name ASC");
            $categories = $this->db->resultSet() ?: [];

            $categoryIndex = 0;
            foreach ($categories as $cat) {
                // Determine base code (100, 200, 300, etc.)
                $baseCode = ($categoryIndex + 1) * 100;
                
                // Fetch all products in this category that DON'T have a sample_code yet, ordered by id ASC
                $this->db->query("SELECT id FROM items WHERE category_id = :category_id AND (sample_code IS NULL OR sample_code = '') ORDER BY id ASC");
                $this->db->bind(':category_id', $cat->id);
                $items = $this->db->resultSet() ?: [];
                
                $itemIndex = 0;
                foreach ($items as $item) {
                    $sampleCode = (string)($baseCode + $itemIndex);
                    
                    // Update this item's sample code
                    $this->db->query("UPDATE items SET sample_code = :sample_code WHERE id = :id");
                    $this->db->bind(':sample_code', $sampleCode);
                    $this->db->bind(':id', $item->id);
                    $this->db->execute();
                    
                    $itemIndex++;
                }
                
                $categoryIndex++;
            }

            // Products without any valid category still get NULL sample_code
            $this->db->query("UPDATE items SET sample_code = NULL WHERE (category_id IS NULL OR category_id = 0) AND (sample_code IS NOT NULL AND sample_code != '')");
            $this->db->execute();
            
        } catch (Exception $e) {
            // Log error or ignore gracefully
        }
    }

    public function deleteItem($id) {
        $this->db->beginTransaction();
        try {
            // Delete related discount rules
            $this->db->query("DELETE FROM discount_rules WHERE target_item_id = :id");
            $this->db->bind(':id', $id);
            $this->db->execute();

            // Delete related item images
            $this->db->query("DELETE FROM item_images WHERE item_id = :id");
            $this->db->bind(':id', $id);
            $this->db->execute();

            // Delete related variation options
            $this->db->query("DELETE FROM item_variation_options WHERE item_id = :id");
            $this->db->bind(':id', $id);
            $this->db->execute();

            // Delete item itself
            $this->db->query("DELETE FROM items WHERE id = :id");
            $this->db->bind(':id', $id);
            $this->db->execute();

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function getPriceColumn() {
        return $this->priceColumn;
    }

    public function getWholesalePriceColumn() {
        return $this->wholesalePriceColumn;
    }
}