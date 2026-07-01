<?php
class AttendanceController extends Controller {
    private $attendanceModel;
    private $employeeModel;

    public function __construct() {
        if (!isset($_SESSION['user_id'])) { header('Location: ' . APP_URL . '/auth/login'); exit; }
        $this->attendanceModel = $this->model('Attendance');
        $this->employeeModel = $this->model('Employee');
    }

    public function index() {
        $this->checkPermission('hrm', 'view');

        $data = [
            'title' => 'Attendance Tracking',
            'content_view' => 'hrm/attendance',
            'employees' => $this->employeeModel->getActiveEmployees(),
            'attendance_records' => $this->attendanceModel->getAllAttendanceRecords(),
            'error' => '',
            'success' => ''
        ];

        if (isset($_SESSION['flash_success'])) {
            $data['success'] = $_SESSION['flash_success'];
            unset($_SESSION['flash_success']);
        }
        if (isset($_SESSION['flash_error'])) {
            $data['error'] = $_SESSION['flash_error'];
            unset($_SESSION['flash_error']);
        }

        $this->view('layouts/main', $data);
    }

    public function clock() {
        $this->checkPermission('hrm', 'create_edit');
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $employeeId = intval($_POST['employee_id']);
            $action = $_POST['clock_action'] ?? 'in'; // 'in' or 'out'
            $date = date('Y-m-d');
            $time = date('H:i:s');

            $emp = $this->employeeModel->getEmployeeById($employeeId);
            $empName = $emp ? ($emp->first_name . ' ' . $emp->last_name) : 'ID: ' . $employeeId;

            if ($action === 'in') {
                // Check if already clocked in today (without clocking out)
                $activeClockIn = $this->attendanceModel->getActiveClockIn($employeeId, $date);
                if ($activeClockIn) {
                    $_SESSION['flash_error'] = "$empName is already clocked in today. Please clock out first.";
                } else {
                    if ($this->attendanceModel->recordClockIn($employeeId, $date, $time)) {
                        $this->logActivity('Clock In Recorded', 'HRM', "Recorded Clock In for $empName at $time.");
                        $_SESSION['flash_success'] = "Clocked In successfully for $empName at $time.";
                    } else {
                        $_SESSION['flash_error'] = 'Failed to record clock-in.';
                    }
                }
            } elseif ($action === 'out') {
                $activeClockIn = $this->attendanceModel->getActiveClockIn($employeeId, $date);
                if (!$activeClockIn) {
                    $_SESSION['flash_error'] = "$empName does not have an active clock-in log for today.";
                } else {
                    if ($this->attendanceModel->recordClockOut($activeClockIn->id, $time)) {
                        $this->logActivity('Clock Out Recorded', 'HRM', "Recorded Clock Out for $empName at $time.");
                        $_SESSION['flash_success'] = "Clocked Out successfully for $empName at $time.";
                    } else {
                        $_SESSION['flash_error'] = 'Failed to record clock-out.';
                    }
                }
            }
        }
        header('Location: ' . APP_URL . '/attendance');
        exit;
    }

    public function manual() {
        $this->checkPermission('hrm', 'create_edit');
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $attendanceData = [
                'employee_id' => intval($_POST['employee_id']),
                'work_date' => $_POST['work_date'],
                'clock_in' => $_POST['clock_in'],
                'clock_out' => !empty($_POST['clock_out']) ? $_POST['clock_out'] : null,
                'status' => $_POST['status']
            ];

            if ($this->attendanceModel->addManualAttendance($attendanceData)) {
                $emp = $this->employeeModel->getEmployeeById($attendanceData['employee_id']);
                $empName = $emp ? ($emp->first_name . ' ' . $emp->last_name) : 'ID: ' . $attendanceData['employee_id'];
                $this->logActivity('Manual Attendance Recorded', 'HRM', "Added manual attendance for $empName on {$attendanceData['work_date']}.");
                $_SESSION['flash_success'] = 'Manual attendance recorded successfully.';
            } else {
                $_SESSION['flash_error'] = 'Failed to record manual attendance.';
            }
        }
        header('Location: ' . APP_URL . '/attendance');
        exit;
    }

    public function delete($id) {
        $this->checkPermission('hrm', 'delete');
        if ($this->attendanceModel->deleteAttendance($id)) {
            $this->logActivity('Attendance Deleted', 'HRM', "Deleted attendance record ID: $id.");
            $_SESSION['flash_success'] = 'Attendance record deleted successfully.';
        } else {
            $_SESSION['flash_error'] = 'Failed to delete attendance record.';
        }
        header('Location: ' . APP_URL . '/attendance');
        exit;
    }
}
