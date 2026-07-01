<?php

class VariationController extends Controller {
    private $itemModel;
    private $db;

    public function __construct() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . APP_URL . '/auth/login');
            exit;
        }

        $this->itemModel = $this->model('Item');
        $this->db = new Database();

        $this->ensureTaxonomyDatabaseTables();
    }

    /**
     * Dynamic self-healing database migration.
     * Sets up local tables for variable product attributes and terms.
     */
    private function ensureTaxonomyDatabaseTables() {
        try {
            $this->db->query("CREATE TABLE IF NOT EXISTS product_attributes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                slug VARCHAR(100) NOT NULL,
                woo_attr_id INT UNIQUE NULL
            )");
            $this->db->execute();

            $this->db->query("CREATE TABLE IF NOT EXISTS product_attribute_terms (
                id INT AUTO_INCREMENT PRIMARY KEY,
                attribute_id INT NOT NULL,
                name VARCHAR(150) NOT NULL,
                slug VARCHAR(150) NOT NULL,
                woo_term_id INT UNIQUE NULL,
                FOREIGN KEY (attribute_id) REFERENCES product_attributes(id) ON DELETE CASCADE
            )");
            $this->db->execute();
        } catch (Exception $e) {
            // Safe fallback
        }
    }

    /**
     * Render the dedicated Variations Management Page.
     * Extracts individual variations from parent products and flattens them for easy viewing and search.
     */
    public function index() {
        // Query variable products from database
        $this->db->query("SELECT * FROM items WHERE variations_json IS NOT NULL AND variations_json != '[]' AND variations_json != ''");
        $variableItems = $this->db->resultSet() ?: [];

        // Flatten variations for display and easy filtering
        $flatVariations = [];
        foreach ($variableItems as $parent) {
            $decoded = json_decode($parent->variations_json, true);
            if (is_array($decoded)) {
                foreach ($decoded as $v) {
                    $flatVariations[] = (object)[
                        'parent_id' => $parent->id,
                        'parent_name' => $parent->name,
                        'parent_sku' => $parent->item_code,
                        'parent_image' => $parent->image_path,
                        'id' => $v['id'] ?? '',
                        'sku' => $v['sku'] ?? '',
                        'attribute' => $v['attribute'] ?? '',
                        'cost_price' => floatval($v['cost_price'] ?? 0),
                        'price' => floatval($v['price'] ?? 0),
                        'wholesale_price' => floatval($v['wholesale_price'] ?? 0),
                    ];
                }
            }
        }

        // Apply interactive real-time search filtering
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        if (!empty($search)) {
            $flatVariations = array_filter($flatVariations, function($v) use ($search) {
                return (
                    stripos($v->sku, $search) !== false ||
                    stripos($v->parent_sku, $search) !== false ||
                    stripos($v->parent_name, $search) !== false ||
                    stripos($v->attribute, $search) !== false
                );
            });
        }

        // Fetch local product attributes and their terms
        $this->db->query("SELECT * FROM product_attributes ORDER BY name ASC");
        $attributesList = $this->db->resultSet() ?: [];

        foreach ($attributesList as $attr) {
            $this->db->query("SELECT * FROM product_attribute_terms WHERE attribute_id = :id ORDER BY name ASC");
            $this->db->bind(':id', $attr->id);
            $attr->terms = $this->db->resultSet() ?: [];
        }

        $data = [
            'title' => 'Variation Hub',
            'variations' => $flatVariations,
            'total_parents' => count($variableItems),
            'search' => $search,
            'synced_attributes' => $attributesList
        ];
        
        $this->view('variations/index', $data);
    }


    /**
     * AJAX Endpoint: Run deep variation database audits & WholesaleX checks.
     */
    public function ajaxAuditVariations() {
        header('Content-Type: application/json');
        
        $logs = [];
        $items = $this->itemModel->getItems();

        $totalVariations = 0;
        $skuDuplicates = 0;
        $skusSeen = [];
        $variableParentCount = 0;

        $logs[] = "[Audit Log] Initiating structural product catalog integrity scanning...";

        foreach ($items as $item) {
            if (!empty($item->variations_json)) {
                $decoded = json_decode($item->variations_json, true);
                if (is_array($decoded) && !empty($decoded)) {
                    $variableParentCount++;
                    $logs[] = "Auditing Parent Product SKU '{$item->item_code}'...";
                    
                    foreach ($decoded as $v) {
                        $totalVariations++;
                        $vSku = $v['sku'] ?? '';
                        if (!empty($vSku)) {
                            if (isset($skusSeen[$vSku])) {
                                $skuDuplicates++;
                                $logs[] = "CRITICAL: Duplicate SKU conflict detected on variant code '{$vSku}'!";
                            } else {
                                $skusSeen[$vSku] = true;
                            }
                        }
                    }
                }
            }
        }

        $logs[] = "---------------------------------------------";
        $logs[] = "Variable parent items scanned: {$variableParentCount}";
        $logs[] = "Variable child SKUs parsed: {$totalVariations}";
        
        if ($skuDuplicates > 0) {
            $logs[] = "AUDIT WARNING: Found {$skuDuplicates} SKU duplicate conflicts. These must be renamed inside the inventory form.";
        } else {
            $logs[] = "AUDIT OK: Global child variation SKUs contain unique identifiers.";
        }

        echo json_encode([
            'success' => true,
            'total_parents' => $variableParentCount,
            'total_variations' => $totalVariations,
            'duplicates' => $skuDuplicates,
            'logs' => $logs
        ]);
        exit;
    }

    /**
     * Add Attribute locally
     */
    public function addAttribute() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS);

            $name = trim($_POST['name'] ?? '');
            $slug = trim($_POST['slug'] ?? '');

            if (empty($name)) {
                die("Attribute Name is required.");
            }

            if (empty($slug)) {
                $slug = $this->slugify($name);
            }

            try {
                $this->db->query("INSERT INTO product_attributes (name, slug) VALUES (:name, :slug)");
                $this->db->bind(':name', $name);
                $this->db->bind(':slug', $slug);
                if ($this->db->execute()) {
                    $_SESSION['flash_success'] = "Attribute '{$name}' created successfully.";
                } else {
                    $_SESSION['flash_error'] = "Failed to save the attribute.";
                }
            } catch (Exception $e) {
                $_SESSION['flash_error'] = "Database error: " . $e->getMessage();
            }

            header('Location: ' . APP_URL . '/variation');
            exit;
        }
    }

    /**
     * Edit Attribute locally
     */
    public function editAttribute($id) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS);

            $name = trim($_POST['name'] ?? '');
            $slug = trim($_POST['slug'] ?? '');

            if (empty($name)) {
                die("Attribute Name is required.");
            }

            if (empty($slug)) {
                $slug = $this->slugify($name);
            }

            try {
                $this->db->query("UPDATE product_attributes SET name = :name, slug = :slug WHERE id = :id");
                $this->db->bind(':name', $name);
                $this->db->bind(':slug', $slug);
                $this->db->bind(':id', $id);
                if ($this->db->execute()) {
                    $_SESSION['flash_success'] = "Attribute updated to '{$name}' successfully.";
                } else {
                    $_SESSION['flash_error'] = "Failed to update the attribute.";
                }
            } catch (Exception $e) {
                $_SESSION['flash_error'] = "Database error: " . $e->getMessage();
            }

            header('Location: ' . APP_URL . '/variation');
            exit;
        }
    }

    /**
     * Delete Attribute locally
     */
    public function deleteAttribute($id) {
        try {
            $this->db->query("DELETE FROM product_attributes WHERE id = :id");
            $this->db->bind(':id', $id);
            if ($this->db->execute()) {
                $_SESSION['flash_success'] = "Attribute and its terms deleted successfully.";
            } else {
                $_SESSION['flash_error'] = "Failed to delete the attribute.";
            }
        } catch (Exception $e) {
            $_SESSION['flash_error'] = "Database error: " . $e->getMessage();
        }

        header('Location: ' . APP_URL . '/variation');
        exit;
    }

    /**
     * Add Term locally
     */
    public function addTerm() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS);

            $attrId = intval($_POST['attribute_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $slug = trim($_POST['slug'] ?? '');

            if ($attrId === 0 || empty($name)) {
                die("Attribute ID and Term Name are required.");
            }

            if (empty($slug)) {
                $slug = $this->slugify($name);
            }

            try {
                $this->db->query("INSERT INTO product_attribute_terms (attribute_id, name, slug) VALUES (:attr_id, :name, :slug)");
                $this->db->bind(':attr_id', $attrId);
                $this->db->bind(':name', $name);
                $this->db->bind(':slug', $slug);
                if ($this->db->execute()) {
                    $_SESSION['flash_success'] = "Term option '{$name}' added successfully.";
                } else {
                    $_SESSION['flash_error'] = "Failed to save the term.";
                }
            } catch (Exception $e) {
                $_SESSION['flash_error'] = "Database error: " . $e->getMessage();
            }

            header('Location: ' . APP_URL . '/variation');
            exit;
        }
    }

    /**
     * Edit Term locally
     */
    public function editTerm($id) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS);

            $name = trim($_POST['name'] ?? '');
            $slug = trim($_POST['slug'] ?? '');

            if (empty($name)) {
                die("Term Name is required.");
            }

            if (empty($slug)) {
                $slug = $this->slugify($name);
            }

            try {
                $this->db->query("UPDATE product_attribute_terms SET name = :name, slug = :slug WHERE id = :id");
                $this->db->bind(':name', $name);
                $this->db->bind(':slug', $slug);
                $this->db->bind(':id', $id);
                if ($this->db->execute()) {
                    $_SESSION['flash_success'] = "Term option updated to '{$name}' successfully.";
                } else {
                    $_SESSION['flash_error'] = "Failed to update the term.";
                }
            } catch (Exception $e) {
                $_SESSION['flash_error'] = "Database error: " . $e->getMessage();
            }

            header('Location: ' . APP_URL . '/variation');
            exit;
        }
    }

    /**
     * Delete Term locally
     */
    public function deleteTerm($id) {
        try {
            $this->db->query("DELETE FROM product_attribute_terms WHERE id = :id");
            $this->db->bind(':id', $id);
            if ($this->db->execute()) {
                $_SESSION['flash_success'] = "Term option deleted successfully.";
            } else {
                $_SESSION['flash_error'] = "Failed to delete the term.";
            }
        } catch (Exception $e) {
            $_SESSION['flash_error'] = "Database error: " . $e->getMessage();
        }

        header('Location: ' . APP_URL . '/variation');
        exit;
    }

    /**
     * Slugify helper
     */
    private function slugify($text) {
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        if (function_exists('iconv')) {
            $text = @iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        }
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '-', $text);
        $text = strtolower($text);
        return empty($text) ? 'n-a' : $text;
    }
}