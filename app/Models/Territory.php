<?php
class Territory {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function getMainAreas() {
        $this->db->query("SELECT * FROM main_areas ORDER BY name ASC");
        return $this->db->resultSet();
    }

    public function getMcaAreasWithDistance($mainAreaId, $parentLat, $parentLng) {
        $this->db->query("
            SELECT m.*, 
            -- Keep Haversine just for distance from Main Area HQ to the Route Start point
            ( 6371 * acos( cos( radians(:plat) ) * cos( radians( m.start_lat ) ) * cos( radians( m.start_lng ) - radians(:plng) ) + sin( radians(:plat) ) * sin( radians( m.start_lat ) ) ) ) AS distance_to_start_km
            FROM mca_areas m
            WHERE m.main_area_id = :mid
            ORDER BY distance_to_start_km ASC
        ");
        $this->db->bind(':mid', $mainAreaId);
        $this->db->bind(':plat', $parentLat);
        $this->db->bind(':plng', $parentLng);
        return $this->db->resultSet();
    }

    public function addMainArea($data) {
        $this->db->query("INSERT INTO main_areas (name, latitude, longitude) VALUES (:name, :lat, :lng)");
        $this->db->bind(':name', $data['name']);
        $this->db->bind(':lat', $data['lat']);
        $this->db->bind(':lng', $data['lng']);
        return $this->db->execute();
    }

    public function addMcaArea($data) {
        // Updated to insert budget_km and actual_route_km
        $this->db->query("INSERT INTO mca_areas (main_area_id, name, start_lat, start_lng, end_lat, end_lng, budget_km, actual_route_km) 
                          VALUES (:mid, :name, :slat, :slng, :elat, :elng, :budget, :actual)");
        $this->db->bind(':mid', $data['main_area_id']);
        $this->db->bind(':name', $data['name']);
        $this->db->bind(':slat', $data['start_lat']);
        $this->db->bind(':slng', $data['start_lng']);
        $this->db->bind(':elat', $data['end_lat']);
        $this->db->bind(':elng', $data['end_lng']);
        $this->db->bind(':budget', $data['budget_km']);
        $this->db->bind(':actual', $data['actual_route_km']);
        return $this->db->execute();
    }
}