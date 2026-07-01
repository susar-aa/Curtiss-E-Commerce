<?php
class AppRelease {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function getAllReleases() {
        $this->db->query("SELECT * FROM app_releases ORDER BY major DESC, minor DESC, patch DESC, id DESC");
        return $this->db->resultSet() ?: [];
    }

    public function getLatestRelease() {
        $this->db->query("SELECT * FROM app_releases WHERE is_latest = 1 LIMIT 1");
        $latest = $this->db->single();
        if (!$latest) {
            // Fallback to the highest version
            $this->db->query("SELECT * FROM app_releases ORDER BY major DESC, minor DESC, patch DESC, id DESC LIMIT 1");
            $latest = $this->db->single();
        }
        return $latest;
    }

    public function getReleaseById($id) {
        $this->db->query("SELECT * FROM app_releases WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    public function addRelease($data) {
        $this->db->query("INSERT INTO app_releases (version, build_version, version_name, package_name, app_name, major, minor, patch, release_notes, apk_path, force_update, is_latest) 
                          VALUES (:version, :build_version, :version_name, :package_name, :app_name, :major, :minor, :patch, :release_notes, :apk_path, :force_update, :is_latest)");
        $this->db->bind(':version', $data['version']);
        $this->db->bind(':build_version', $data['build_version']);
        $this->db->bind(':version_name', $data['version_name']);
        $this->db->bind(':package_name', $data['package_name']);
        $this->db->bind(':app_name', $data['app_name']);
        $this->db->bind(':major', $data['major']);
        $this->db->bind(':minor', $data['minor']);
        $this->db->bind(':patch', $data['patch']);
        $this->db->bind(':release_notes', $data['release_notes']);
        $this->db->bind(':apk_path', $data['apk_path']);
        $this->db->bind(':force_update', $data['force_update']);
        $this->db->bind(':is_latest', $data['is_latest']);
        
        if ($this->db->execute()) {
            $insertId = $this->db->lastInsertId();
            if ($data['is_latest'] == 1) {
                $this->setLatestRelease($insertId);
            }
            return $insertId;
        }
        return false;
    }

    public function setLatestRelease($id) {
        // Reset all to is_latest = 0
        $this->db->query("UPDATE app_releases SET is_latest = 0");
        $this->db->execute();

        // Set specified to is_latest = 1
        $this->db->query("UPDATE app_releases SET is_latest = 1 WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    public function deleteRelease($id) {
        $this->db->query("DELETE FROM app_releases WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    public function getReleaseByBuildVersion($buildVersion) {
        $this->db->query("SELECT * FROM app_releases WHERE build_version = :build_version LIMIT 1");
        $this->db->bind(':build_version', $buildVersion);
        return $this->db->single();
    }

    public function getReleaseByVersionName($versionName) {
        $this->db->query("SELECT * FROM app_releases WHERE version = :version LIMIT 1");
        $this->db->bind(':version', $versionName);
        return $this->db->single();
    }
}
