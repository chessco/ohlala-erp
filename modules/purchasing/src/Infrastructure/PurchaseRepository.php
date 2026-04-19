<?php
namespace Purchasing\Infrastructure;

class PurchaseRepository {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function findById($id) {
        $id = (int)$id;
        $res = mysqli_query($this->db, "SELECT * FROM pur_requests WHERE id = $id");
        return mysqli_fetch_assoc($res);
    }

    public function updateStatus($id, $status) {
        $id = (int)$id;
        $status = mysqli_real_escape_string($this->db, $status);
        return mysqli_query($this->db, "UPDATE pur_requests SET status = '$status' WHERE id = $id");
    }

    public function moveToLevel($id, $level) {
        $id = (int)$id;
        $level = (int)$level;
        return mysqli_query($this->db, "UPDATE pur_requests SET current_level = $level WHERE id = $id");
    }
}
