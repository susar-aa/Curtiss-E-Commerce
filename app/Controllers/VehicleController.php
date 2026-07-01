<?php
class VehicleController extends Controller {
    private $vehicleModel;

    public function __construct() {
        if (!isset($_SESSION['user_id'])) { header('Location: ' . APP_URL . '/auth/login'); exit; }
        $this->vehicleModel = $this->model('Vehicle');
    }

    public function index() {
        $search = $_GET['search'] ?? '';
        $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;

        $totalVehicles = $this->vehicleModel->getTotalVehicles($search);
        $totalPages = ceil($totalVehicles / $limit);

        // Fetch counts for KPIs
        $allVehicles = $this->vehicleModel->getAllVehicles();
        $activeCount = 0;
        $maintenanceCount = 0;
        $inactiveCount = 0;
        foreach($allVehicles as $v) {
            if($v->status === 'Active') $activeCount++;
            elseif($v->status === 'Maintenance') $maintenanceCount++;
            elseif($v->status === 'Inactive') $inactiveCount++;
        }

        $data = [
            'title' => 'Vehicle Management',
            'content_view' => 'vehicles/index',
            'vehicles' => $this->vehicleModel->getVehiclesPaginated($search, $limit, $offset),
            'search' => $search,
            'page' => $page,
            'total_pages' => $totalPages,
            'total_vehicles' => $totalVehicles,
            'active_count' => $activeCount,
            'maintenance_count' => $maintenanceCount,
            'inactive_count' => $inactiveCount,
            'error' => '',
            'success' => ''
        ];

        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
            $vehicleData = [
                'vehicle_number' => trim($_POST['vehicle_number']),
                'model' => trim($_POST['model']),
                'type' => trim($_POST['type']),
                'status' => $_POST['status'] ?? 'Active'
            ];

            if ($_POST['action'] == 'add_vehicle') {
                if (empty($vehicleData['vehicle_number']) || empty($vehicleData['model'])) {
                    $data['error'] = 'Vehicle Number and Model are required.';
                } else {
                    if ($this->vehicleModel->addVehicle($vehicleData)) {
                        header("Location: " . APP_URL . "/vehicle?success=" . urlencode('Vehicle added successfully')); exit;
                    } else { 
                        $data['error'] = 'Failed to add vehicle. Number might already be registered.'; 
                    }
                }
            } 
            elseif ($_POST['action'] == 'edit_vehicle') {
                $id = $_POST['vehicle_id'];
                if (empty($vehicleData['vehicle_number']) || empty($vehicleData['model'])) {
                    $data['error'] = 'Vehicle Number and Model are required.';
                } else {
                    if ($this->vehicleModel->updateVehicle($id, $vehicleData)) {
                        header("Location: " . APP_URL . "/vehicle?page=$page&search=" . urlencode($search) . "&success=" . urlencode('Vehicle updated successfully')); exit;
                    } else { 
                        $data['error'] = 'Failed to update vehicle.'; 
                    }
                }
            }
            elseif ($_POST['action'] == 'delete_vehicle') {
                $id = $_POST['vehicle_id'];
                if ($this->vehicleModel->deleteVehicle($id)) {
                    header("Location: " . APP_URL . "/vehicle?success=" . urlencode('Vehicle deleted successfully')); exit;
                } else { 
                    $data['error'] = 'Failed to delete vehicle.'; 
                }
            }
        }

        if (isset($_GET['success'])) { $data['success'] = $_GET['success']; }
        $this->view('layouts/main', $data);
    }
}
