<?php
namespace Purchasing\Infrastructure;

/**
 * NotificationGateway
 * Handles communication with external notification systems (Flow / WhatsApp / Email).
 */
class NotificationGateway {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Notify the next approver in the chain via Email and WhatsApp.
     */
    public function notifyNextApprover($requestId, $approverData, $requestTitle, $currentLevel) {
        $email = $approverData['correo'] ?? null;
        $phone = $approverData['telefono'] ?? null;
        $subject = "Ohlala ERP: Nueva Solicitud de Compra #$requestId";
        $message = "Ohlala ERP: La solicitud '$requestTitle' requiere su aprobación Nivel $currentLevel.";

        // 1. Email First
        if ($email) {
            $this->sendEmail($email, $subject, $message, $this->db);
        }

        // 2. WhatsApp Next (Flow)
        if ($phone) {
            $this->sendToFlow($phone, $message, $requestId);
        }
        
        return true;
    }

    /**
     * Notify the requester about the final decision.
     */
    public function notifyRequesterDecision($requestId, $requesterData, $status) {
        $email = $requesterData['correo'] ?? null;
        $phone = $requesterData['telefono'] ?? null;
        
        $statusText = ($status === 'approved') ? 'APROBADA' : 'RECHAZADA';
        $subject = "Ohlala ERP: Su solicitud #$requestId ha sido $statusText";
        $message = "Ohlala ERP: Su solicitud de compra #$requestId ha sido procesada como: $statusText.";
        
        // 1. Email First
        if ($email) {
            $this->sendEmail($email, $subject, $message, $this->db);
        }

        // 2. WhatsApp Next
        if ($phone) {
            $this->sendToFlow($phone, $message, $requestId);
        }
        
        return true;
    }

    /**
     * Email delivery implementation using professional SMTP.
     */
    private function sendEmail($to, $subject, $message, $db) {
        $config = \Purchasing\Infrastructure\Email\EmailConfig::getSettings($db);
        $mailer = new \Purchasing\Infrastructure\Email\SmtpMailer(
            $config['host'], 
            $config['port'], 
            $config['user'], 
            $config['pass']
        );

        error_log("Attempting SMTP Send to $to: [$subject]");
        
        $result = $mailer->send($to, $subject, $message, $config['from_name']);
        
        if ($result) {
            error_log("SMTP Delivery Success to $to");
        } else {
            error_log("SMTP Delivery FAILED to $to");
        }

        return $result;
    }

    /**
     * Minimal implementation for Flow Webhook integration.
     */
    private function sendToFlow($phone, $message, $requestId) {
        error_log("Sending WA to $phone: $message (RS: $requestId)");
        return true;
    }
}
