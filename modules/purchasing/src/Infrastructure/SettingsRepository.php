<?php
namespace Purchasing\Infrastructure;

/**
 * SettingsRepository
 * Handles retrieval and persistence of module settings.
 */
class SettingsRepository {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Get a single setting value by key.
     */
    public function get($key, $default = null) {
        $key = mysqli_real_escape_string($this->db, $key);
        $res = mysqli_query($this->db, "SELECT setting_value FROM pur_settings WHERE setting_key = '$key'");
        $row = mysqli_fetch_assoc($res);
        return $row ? $row['setting_value'] : $default;
    }

    /**
     * Get all settings as an associative array.
     */
    public function getAll() {
        $res = mysqli_query($this->db, "SELECT setting_key, setting_value FROM pur_settings");
        $settings = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        return $settings;
    }

    /**
     * Save/Update a setting.
     */
    public function save($key, $value) {
        $key = mysqli_real_escape_string($this->db, $key);
        $value = mysqli_real_escape_string($this->db, $value);
        $sql = "INSERT INTO pur_settings (setting_key, setting_value) 
                VALUES ('$key', '$value') 
                ON DUPLICATE KEY UPDATE setting_value = '$value'";
        return mysqli_query($this->db, $sql);
    }
}
