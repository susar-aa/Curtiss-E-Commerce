<?php

class CategoryController extends Controller {
    private $categoryModel;
    private $itemModel;
    private $db;

    public function __construct() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . APP_URL . '/auth/login');
            exit;
        }

        $this->categoryModel = $this->model('Category');
        $this->itemModel = $this->model('Item');
        $this->db = new Database();
    }

    /**
     * Display categories list
     */
    public function index() {
        $this->checkPermission('category', 'view');
        $categories = $this->categoryModel->getCategories();
        $data = [
            'title' => 'Category Management',
            'categories' => $categories,
            'content_view' => 'categories/index'
        ];
        $this->view('layouts/main', $data);
    }

    /**
     * Add category locally
     */
    public function add() {
        $this->checkPermission('category', 'create_edit');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS);

            $name = html_entity_decode(trim($_POST['name'] ?? ''), ENT_QUOTES, 'UTF-8');
            $description = html_entity_decode(trim($_POST['description'] ?? ''), ENT_QUOTES, 'UTF-8');

            if (empty($name)) {
                die("Category Name is required.");
            }

            if ($this->categoryModel->addCategory($name, $description, null)) {
                header('Location: ' . APP_URL . '/category');
                exit;
            } else {
                die("Something went wrong saving the category locally.");
            }
        }
    }

    /**
     * Edit category locally
     */
    public function edit($id) {
        $this->checkPermission('category', 'create_edit');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS);

            $name = html_entity_decode(trim($_POST['name'] ?? ''), ENT_QUOTES, 'UTF-8');
            $description = html_entity_decode(trim($_POST['description'] ?? ''), ENT_QUOTES, 'UTF-8');

            $existingCategory = $this->categoryModel->getCategoryById($id);
            if (!$existingCategory) {
                die("Category not found.");
            }

            if ($this->categoryModel->updateCategory($id, $name, $description, null)) {
                header('Location: ' . APP_URL . '/category');
                exit;
            } else {
                die("Something went wrong updating the category locally.");
            }
        }
    }

    /**
     * Delete Category locally
     */
    public function delete($id) {
        $this->checkPermission('category', 'delete');
        $existingCategory = $this->categoryModel->getCategoryById($id);
        if ($existingCategory) {
            if ($this->categoryModel->deleteCategory($id)) {
                header('Location: ' . APP_URL . '/category');
                exit;
            } else {
                die("Something went wrong deleting the category locally.");
            }
        }
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
     * AJAX Endpoint: Get products for a category
     */
    public function products($id) {
        $this->checkPermission('category', 'view');
        header('Content-Type: application/json');
        
        $this->db->query("SELECT * 
                          FROM items 
                          WHERE category_id = :category_id 
                          ORDER BY name ASC");
        $this->db->bind(':category_id', $id);
        $products = $this->db->resultSet();
        
        echo json_encode($products);
        exit;
    }
}