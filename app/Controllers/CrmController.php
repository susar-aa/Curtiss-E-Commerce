<?php
class CrmController extends Controller {
    private $leadModel;
    private $userModel;

    public function __construct() {
        if (!isset($_SESSION['user_id'])) { header('Location: ' . APP_URL . '/auth/login'); exit; }
        $this->leadModel = $this->model('Lead');
        $this->userModel = $this->model('User');
    }

    public function index() {
        $data = [
            'title' => 'CRM & Leads',
            'content_view' => 'crm/index',
            'leads' => $this->leadModel->getAllLeads(),
            'users' => $this->userModel->getAllUsers(),
            'error' => '',
            'success' => ''
        ];

        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
            if ($_POST['action'] == 'add_lead') {
                $leadData = [
                    'first_name' => trim($_POST['first_name']),
                    'last_name' => trim($_POST['last_name']),
                    'company_name' => trim($_POST['company_name']),
                    'email' => trim($_POST['email']),
                    'phone' => trim($_POST['phone']),
                    'source' => trim($_POST['source']),
                    'status' => $_POST['status'],
                    'assigned_to' => $_POST['assigned_to']
                ];

                if (!empty($leadData['first_name'])) {
                    if ($this->leadModel->addLead($leadData)) {
                        $data['success'] = 'Lead added successfully.';
                        $data['leads'] = $this->leadModel->getAllLeads();
                    } else {
                        $data['error'] = 'Failed to add lead.';
                    }
                }
            } elseif ($_POST['action'] == 'convert_lead') {
                $leadId = $_POST['lead_id'];
                if ($this->leadModel->convertToCustomer($leadId)) {
                    $data['success'] = 'Lead successfully converted to Customer! They will now appear in Sales & AR.';
                    $data['leads'] = $this->leadModel->getAllLeads();
                } else {
                    $data['error'] = 'Failed to convert lead.';
                }
            } elseif ($_POST['action'] == 'update_status') {
                $leadId = $_POST['lead_id'];
                $status = $_POST['new_status'];
                if ($this->leadModel->updateStatus($leadId, $status)) {
                    $data['success'] = 'Lead status updated.';
                    $data['leads'] = $this->leadModel->getAllLeads();
                }
            }
        }

        $this->view('layouts/main', $data);
    }
}