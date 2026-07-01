<?php
class NotificationController extends Controller {
    private $notificationModel;

    public function __construct() {
        if (!isset($_SESSION['user_id'])) { header('Location: ' . APP_URL . '/auth/login'); exit; }
        $this->notificationModel = $this->model('Notification');
    }

    public function index() {
        $data = [
            'title' => 'System Notifications',
            'content_view' => 'notifications/index',
            'notifications' => $this->notificationModel->getAllForUser($_SESSION['user_id']),
            'error' => '',
            'success' => ''
        ];

        $this->view('layouts/main', $data);
    }

    public function read($id) {
        // Mark as read
        $this->notificationModel->markAsRead($id, $_SESSION['user_id']);
        
        // Find if there is a link to redirect to
        $db = new Database();
        $db->query("SELECT link_url FROM notifications WHERE id = :id AND user_id = :uid");
        $db->bind(':id', $id);
        $db->bind(':uid', $_SESSION['user_id']);
        $notif = $db->single();

        if ($notif && !empty($notif->link_url)) {
            header('Location: ' . APP_URL . '/' . ltrim($notif->link_url, '/'));
        } else {
            header('Location: ' . APP_URL . '/notification');
        }
        exit;
    }

    public function read_all() {
        $this->notificationModel->markAllAsRead($_SESSION['user_id']);
        header('Location: ' . APP_URL . '/notification');
        exit;
    }
}