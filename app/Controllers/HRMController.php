<?php
class HrmController extends Controller {
    public function __construct() {
        if (!isset($_SESSION['user_id'])) { 
            header('Location: ' . APP_URL . '/auth/login'); 
            exit; 
        }
    }

    public function index() {
        header('Location: ' . APP_URL . '/user');
        exit;
    }

    public function edit() {
        header('Location: ' . APP_URL . '/user');
        exit;
    }

    public function delete($id) {
        header('Location: ' . APP_URL . '/user/delete_employee/' . $id);
        exit;
    }
}