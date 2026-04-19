<?php
namespace Purchasing\Application;

use Purchasing\Infrastructure\ApprovalRepository;
use Purchasing\Infrastructure\PurchaseRepository;
use Purchasing\Infrastructure\NotificationGateway;

class ApprovalService {
    private $approvalRepo;
    private $purchaseRepo;
    private $notificationGateway;
    private $db;

    public function __construct($db, ApprovalRepository $approvalRepo, PurchaseRepository $purchaseRepo, NotificationGateway $notificationGateway) {
        $this->db = $db;
        $this->approvalRepo = $approvalRepo;
        $this->purchaseRepo = $purchaseRepo;
        $this->notificationGateway = $notificationGateway;
    }

    /**
     * Process an approval or rejection action.
     */
    public function process($requestId, $action, $userId) {
        $requestId = (int)$requestId;
        $userId = (int)$userId;
        
        // 1. Get the current pending step
        $currentStep = $this->approvalRepo->getCurrentStep($requestId);
        if (!$currentStep) {
            throw new \Exception("No hay aprobaciones pendientes para esta solicitud.");
        }

        // 2. Security & Idempotency: Validate user matches current level approver
        if ($currentStep['approver_id'] != $userId) {
            throw new \Exception("Usuario no autorizado para aprobar este nivel ($requestId / L".$currentStep['level'].").");
        }

        if ($currentStep['status'] !== 'pending') {
            throw new \Exception("Este paso ya ha sido procesado.");
        }

        // 3. Execute Action
        if ($action === 'approved') {
            $this->approvalRepo->markApproved($currentStep['id']);
            return $this->handleNextLevel($requestId, $currentStep['level']);
        } elseif ($action === 'rejected') {
            $this->approvalRepo->markRejected($currentStep['id']);
            $this->purchaseRepo->updateStatus($requestId, 'rejected');
            
            // Notify requester
            $purchase = $this->purchaseRepo->findById($requestId);
            $requesterData = $this->getUserData($purchase['requester_id']);
            $this->notificationGateway->notifyRequesterDecision($requestId, $requesterData, 'rejected');
            
            return ["status" => "rejected", "message" => "Solicitud rechazada."];
        }

        throw new \Exception("Acción no válida.");
    }

    private function handleNextLevel($requestId, $lastLevel) {
        $nextLevel = $lastLevel + 1;
        
        if ($nextLevel > 3) {
            // Flow finished: Total Approval
            $this->purchaseRepo->updateStatus($requestId, 'approved');
            
            $purchase = $this->purchaseRepo->findById($requestId);
            $requesterData = $this->getUserData($purchase['requester_id']);
            $this->notificationGateway->notifyRequesterDecision($requestId, $requesterData, 'approved');
            
            return ["status" => "finalized", "message" => "Solicitud aprobada totalmente."];
        }

        // Move to next level
        $this->purchaseRepo->moveToLevel($requestId, $nextLevel);
        
        // Get next step info to notify
        $nextStep = $this->approvalRepo->getCurrentStep($requestId);
        if ($nextStep) {
            $approverData = $this->getUserData($nextStep['approver_id']);
            $purchase = $this->purchaseRepo->findById($requestId);
            $this->notificationGateway->notifyNextApprover($requestId, $approverData, $purchase['description'], $nextLevel);
        }

        return ["status" => "next_level", "message" => "Aprobación Nivel $lastLevel registrada. Notificando Nivel $nextLevel."];
    }

    /**
     * Helper to get user data (phone & email).
     */
    private function getUserData($userId) {
        $userId = (int)$userId;
        $res = mysqli_query($this->db, "SELECT correo, telefono FROM usuarios WHERE id = $userId");
        $data = mysqli_fetch_assoc($res);
        return $data ?: ['correo' => null, 'telefono' => null];
    }
}
