<?php
class DeliveryController extends Controller {
    private $deliveryModel;

    public function __construct() {
        if (!isset($_SESSION['user_id'])) { header('Location: ' . APP_URL . '/auth/login'); exit; }
        $this->deliveryModel = $this->model('Delivery');
    }

    public function index() {
        header('Location: ' . APP_URL . '/RepTracking/index');
        exit;
    }

    // Endpoint for AJAX saving of delivery arrangements
    public function arrange() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Invalid Request Method']);
            exit;
        }

        $rawInput = file_get_contents('php://input');
        $postData = json_decode($rawInput, true);

        if (!$postData) {
            $postData = $_POST;
        }

        $deliveryData = [
            'rep_route_id' => intval($postData['rep_route_id'] ?? 0),
            'secondary_rep_route_id' => !empty($postData['secondary_rep_route_id']) ? intval($postData['secondary_rep_route_id']) : null,
            'delivery_date' => trim($postData['delivery_date'] ?? ''),
            'vehicle_number' => trim($postData['vehicle_number'] ?? ''),
            'driver_name' => trim($postData['driver_name'] ?? ''),
            'partner_name' => trim($postData['partner_name'] ?? ''),
            'selected_credit_invoices' => !empty($postData['selected_credit_invoices']) ? json_encode($postData['selected_credit_invoices']) : null
        ];

        if (empty($deliveryData['rep_route_id']) || empty($deliveryData['delivery_date']) || empty($deliveryData['vehicle_number']) || empty($deliveryData['driver_name'])) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'All mandatory fields (Route, Date, Vehicle, Driver) are required.']);
            exit;
        }

        $deliveryId = $this->deliveryModel->createDelivery($deliveryData);

        header('Content-Type: application/json');
        if ($deliveryId) {
            echo json_encode(['status' => 'success', 'message' => 'Delivery arranged successfully!', 'delivery_id' => $deliveryId]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to arrange delivery. Database transaction error.']);
        }
        exit;
    }

    // Endpoint to retrieve details of a specific arranged delivery
    public function api_get_delivery_details($id) {
        $delivery = $this->deliveryModel->getDeliveryById($id);
        if (!$delivery) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Delivery not found.']);
            exit;
        }

        $invoices = $this->deliveryModel->getDeliveryInvoices($delivery->rep_route_id, $delivery->secondary_rep_route_id ?? null);
        $creditInvoices = $this->deliveryModel->getDeliveryCreditInvoices($delivery->rep_route_id, $delivery->secondary_rep_route_id ?? null);
        $balancing = $this->deliveryModel->getDeliveryBalancingData($id);

        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'delivery' => $delivery,
            'invoices' => $invoices,
            'credit_invoices' => $creditInvoices,
            'balancing' => $balancing
        ]);
        exit;
    }

    // Endpoint to finalize a completed delivery (stock deductions + transit financial moves)
    public function finalize() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Invalid Request Method']);
            exit;
        }

        $rawInput = file_get_contents('php://input');
        $postData = json_decode($rawInput, true);
        if (!$postData) {
            $postData = $_POST;
        }

        $deliveryId = intval($postData['delivery_id'] ?? 0);
        if ($deliveryId <= 0) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Delivery ID is required.']);
            exit;
        }

        try {
            $adminUserId = $_SESSION['user_id'];
            $selectedPaymentIds = isset($postData['selected_payment_ids']) ? array_map('intval', $postData['selected_payment_ids']) : [];
            $selectedInvoiceIds = isset($postData['selected_invoice_ids']) ? array_map('intval', $postData['selected_invoice_ids']) : [];
            $debitAccounts = $postData['debit_accounts'] ?? [];
            $creditAccounts = $postData['credit_accounts'] ?? [];
            $returnedItems = $postData['returned_items'] ?? [];

            $this->deliveryModel->finalizeDelivery(
                $deliveryId, 
                $adminUserId, 
                $selectedPaymentIds, 
                $selectedInvoiceIds, 
                $debitAccounts, 
                $creditAccounts,
                $returnedItems
            );

            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'message' => 'Delivery route finalized successfully! Physical stock and ledger cleared.']);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }

    // Printer-friendly balancing report for finalized deliveries
    public function balancing_report($id) {
        $delivery = $this->deliveryModel->getDeliveryById($id);
        if (!$delivery) {
            die("<div style='padding:20px; font-family:sans-serif; color:red;'><h3>Delivery Not Found</h3></div>");
        }

        $balancing = $this->deliveryModel->getDeliveryBalancingData($id);

        $data = [
            'title' => 'Delivery Balancing & Settlement Report',
            'delivery' => $delivery,
            'balancing' => $balancing
        ];

        $this->view('deliveries/balancing_report', $data);
    }

    // Renders the spreadsheet loading sheet page
    public function spreadsheet($id) {
        $delivery = $this->deliveryModel->getDeliveryById($id);
        if (!$delivery) {
            die("<div style='padding:20px; font-family:sans-serif; color:red;'><h3>Delivery Not Found</h3><p>The requested delivery arrangement ID does not exist.</p></div>");
        }

        $data = [
            'title' => 'Delivery Loading Spreadsheet',
            'delivery' => $delivery,
            'items' => $this->deliveryModel->getDeliverySpreadsheetData($delivery->rep_route_id, $delivery->secondary_rep_route_id ?? null),
            'bills' => $this->deliveryModel->getDeliveryInvoices($delivery->rep_route_id, $delivery->secondary_rep_route_id ?? null)
        ];

        $this->view('deliveries/spreadsheet', $data);
    }

    // Export Excel/CSV loading summary
    public function export_csv($id) {
        $delivery = $this->deliveryModel->getDeliveryById($id);
        if (!$delivery) {
            die("Delivery not found.");
        }

        $items = $this->deliveryModel->getDeliverySpreadsheetData($delivery->rep_route_id, $delivery->secondary_rep_route_id ?? null);
        $bills = $this->deliveryModel->getDeliveryInvoices($delivery->rep_route_id, $delivery->secondary_rep_route_id ?? null);

        // Define file download parameters
        $filename = "Loading_Sheet_" . str_replace(" ", "_", $delivery->route_name) . "_" . $delivery->delivery_date . ".csv";
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);

        $output = fopen('php://output', 'w');

        // 1. Title and Metadata Headers
        fputcsv($output, ['DELIVERY LOADING SHEET & SPREADSHEET SUMMARY']);
        fputcsv($output, ['']);
        fputcsv($output, ['Route Name:', $delivery->route_name, 'Delivery Date:', $delivery->delivery_date]);
        fputcsv($output, ['Vehicle Number:', $delivery->vehicle_number, 'Representative:', $delivery->first_name . ' ' . $delivery->last_name]);
        fputcsv($output, ['Driver Name:', $delivery->driver_name, 'Partner/Helper:', $delivery->partner_name ?: 'None']);
        fputcsv($output, ['Status:', $delivery->status]);
        fputcsv($output, ['Total Sales value:', 'Rs. ' . number_format($delivery->total_sales, 2), 'Total Bills:', $delivery->bill_count]);
        fputcsv($output, ['']);

        // 2. Aggregate Items Table (Loading Summary)
        fputcsv($output, ['--- AGGREGATE LOADING SHEET SUMMARY (Total quantities of each product to load) ---']);
        fputcsv($output, ['Product / Item Description', 'Total Quantity to Load', 'Loaded Status Verification']);
        foreach ($items as $item) {
            fputcsv($output, [$item->item_name, $item->total_qty, '[  ] Checked']);
        }
        fputcsv($output, ['']);

        // 3. Invoice / Bills breakdown
        fputcsv($output, ['--- CUSTOMER INVOICES & BILLS (Accounts Receivable & Collection Sheet) ---']);
        fputcsv($output, ['Invoice Number', 'Time / Date', 'Customer Name', 'Grand Total (Rs)', 'Payment Status', 'Collection Details']);
        foreach ($bills as $bill) {
            $time = date('h:i A', strtotime($bill->created_at));
            fputcsv($output, [
                $bill->invoice_number,
                $time,
                $bill->customer_name,
                number_format($bill->true_grand_total, 2),
                $bill->status,
                $bill->status === 'Paid' ? 'Paid Online/Cash' : 'Credit Collection (Pending)'
            ]);
        }

        fclose($output);
        exit;
    }
}
