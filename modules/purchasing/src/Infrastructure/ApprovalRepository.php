<?php
namespace Purchasing\Infrastructure;

class ApprovalRepository {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Get the current pending step for a request.
     */
    public function getCurrentStep($requestId) {
        $requestId = (int)$requestId;
        $sql = "SELECT * FROM pur_approval_steps 
                WHERE request_id = $requestId AND status = 'pending' 
                ORDER BY level ASC LIMIT 1";
        $res = mysqli_query($this->db, $sql);
        return mysqli_fetch_assoc($res);
    }

    /**
     * Get the next pending step for a request.
     */
    public function getNextStep($requestId) {
        // Since we order by level ASC in getCurrentStep, 
        // calling getCurrentStep AFTER marking one as approved will return the next one.
        return $this->getCurrentStep($requestId);
    }

    public function markApproved($stepId, $comments = '') {
        $stepId = (int)$stepId;
        $comments = mysqli_real_escape_string($this->db, $comments);
        $now = date('Y-m-d H:i:s');
        return mysqli_query($this->db, "UPDATE pur_approval_steps 
                                       SET status = 'approved', comments = '$comments', processed_at = '$now' 
                                       WHERE id = $stepId");
    }

    public function markRejected($stepId, $comments = '') {
        $stepId = (int)$stepId;
        $comments = mysqli_real_escape_string($this->db, $comments);
        $now = date('Y-m-d H:i:s');
        return mysqli_query($this->db, "UPDATE pur_approval_steps 
                                       SET status = 'rejected', comments = '$comments', processed_at = '$now' 
                                       WHERE id = $stepId");
    }

    /**
     * Pre-generate steps for a new request.
     */
    public function initializeSteps($requestId, $approverLevel1, $approverLevel2, $approverLevel3, $approverLevel4 = null) {
        $requestId = (int)$requestId;
        $v1 = (int)$approverLevel1;
        $v2 = (int)$approverLevel2;
        $v3 = (int)$approverLevel3;
        $v4 = $approverLevel4 ? (int)$approverLevel4 : null;

        $sql = "INSERT INTO pur_approval_steps (request_id, level, approver_id) VALUES 
                ($requestId, 1, $v1),
                ($requestId, 2, $v2),
                ($requestId, 3, $v3)";
        
        if ($v4) {
            $sql .= ", ($requestId, 4, $v4)";
        }
        
        return mysqli_query($this->db, $sql);
    }
}
