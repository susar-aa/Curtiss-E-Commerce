<?php
class Notification {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function getUnreadCount($userId) {
        $this->db->query("SELECT COUNT(*) as unread FROM notifications WHERE user_id = :uid AND is_read = 0");
        $this->db->bind(':uid', $userId);
        $row = $this->db->single();
        return $row->unread ?? 0;
    }

    public function getAllForUser($userId) {
        $this->db->query("SELECT * FROM notifications WHERE user_id = :uid ORDER BY created_at DESC");
        $this->db->bind(':uid', $userId);
        return $this->db->resultSet();
    }

    public function markAsRead($id, $userId) {
        $this->db->query("UPDATE notifications SET is_read = 1 WHERE id = :id AND user_id = :uid");
        $this->db->bind(':id', $id);
        $this->db->bind(':uid', $userId);
        return $this->db->execute();
    }

    public function markAllAsRead($userId) {
        $this->db->query("UPDATE notifications SET is_read = 1 WHERE user_id = :uid AND is_read = 0");
        $this->db->bind(':uid', $userId);
        return $this->db->execute();
    }

    public function createNotification($userId, $title, $message, $link_url = null) {
        $this->db->query("INSERT INTO notifications (user_id, title, message, link_url) VALUES (:uid, :title, :msg, :link)");
        $this->db->bind(':uid', $userId);
        $this->db->bind(':title', $title);
        $this->db->bind(':msg', $message);
        $this->db->bind(':link', $link_url);
        return $this->db->execute();
    }
}