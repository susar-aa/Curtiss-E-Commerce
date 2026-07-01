<?php
class VendorController extends Controller {
    public function index($id = null) {
        $target = APP_URL . '/supplier';
        if ($id) {
            $target .= '/index/' . $id;
        }
        if (!empty($_GET)) {
            $target .= '?' . http_build_query($_GET);
        }
        header('Location: ' . $target);
        exit;
    }
}