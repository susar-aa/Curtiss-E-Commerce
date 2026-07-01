<?php

interface RbacInterface {
    public function check($userId, $module, $action);
    public function getPermissions($userId);
    public function hasRole($userId, $roleName);
}
