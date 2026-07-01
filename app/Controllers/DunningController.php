<?php
class DunningController extends Controller {
    private $companyModel;

    public function __construct() {
        $this->companyModel = $this->model('Company');
    }

    public function index() {
        if (!isset($_SESSION['user_id'])) { header('Location: ' . APP_URL . '/auth/login'); exit; }
        
        $db = new Database();
        // Fetch all overdue invoices
        $db->query("SELECT i.*, c.name as customer_name, c.email, c.phone, 
                           DATEDIFF(CURRENT_DATE, i.due_date) as days_overdue
                    FROM invoices i 
                    JOIN customers c ON i.customer_id = c.id
                    WHERE i.status = 'Unpaid' AND DATEDIFF(CURRENT_DATE, i.due_date) > 0
                    ORDER BY days_overdue DESC");
        $overdue = $db->resultSet();
        
        $data = [
            'title' => 'Dunning & AR Reminders',
            'content_view' => 'dunning/index',
            'overdue_invoices' => $overdue
        ];
        
        $this->view('layouts/main', $data);
    }

    // This can be triggered via Cron Job or manually from the dashboard
    public function cron() {
        $db = new Database();
        
        // Target invoices exactly 3, 7, 14, 30, or 60 days overdue
        $db->query("SELECT i.*, c.name as customer_name, c.email,
                           DATEDIFF(CURRENT_DATE, i.due_date) as days_overdue
                    FROM invoices i 
                    JOIN customers c ON i.customer_id = c.id
                    WHERE i.status = 'Unpaid' AND DATEDIFF(CURRENT_DATE, i.due_date) IN (3, 7, 14, 30, 60)");
        $targets = $db->resultSet();
        
        require_once '../app/Services/BrevoMailer.php';
        $mailer = new BrevoMailer();
        $company = $this->companyModel->getSettings();
        
        $sent = 0;
        foreach($targets as $inv) {
            if(!empty($inv->email)) {
                $subject = "Action Required: Invoice {$inv->invoice_number} is {$inv->days_overdue} days overdue";
                $link = APP_URL . "/sales/show/" . $inv->id;
                
                $html = "<div style='font-family: sans-serif; color:#333;'>";
                $html .= "<p>Dear {$inv->customer_name},</p>";
                $html .= "<p>This is an automated reminder that your invoice <strong>{$inv->invoice_number}</strong> is currently {$inv->days_overdue} days past due.</p>";
                $html .= "<p>Please view your invoice here: <a href='{$link}'>{$link}</a></p>";
                $html .= "<p>If you have already processed this payment, please ignore this email.</p>";
                $html .= "<p>Thank you,<br><strong>{$company->company_name}</strong></p></div>";
                
                $mailer->sendEmail($inv->email, $inv->customer_name, $subject, $html, null, null, $company->company_name);
                $sent++;
            }
        }
        
        echo "<div style='font-family:sans-serif; padding:20px;'>";
        echo "<h2>Dunning Cron Executed Successfully.</h2>";
        echo "<p>Emails Dispatched: " . $sent . "</p>";
        echo "<a href='" . APP_URL . "/dunning'>Return to Dashboard</a></div>";
    }
}