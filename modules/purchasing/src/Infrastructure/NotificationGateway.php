namespace Purchasing\Infrastructure;

require_once __DIR__ . '/../../flow_connector.php';

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
        
        // Fetch Request Details for the Email
        $reqData = null;
        $items = [];
        try {
            $sql = "SELECT r.*, u.nombre_completo as requester_name, s.name as supplier_name 
                    FROM pur_requests r 
                    LEFT JOIN usuarios u ON r.requester_id = u.id 
                    LEFT JOIN pur_suppliers s ON r.supplier_id = s.id
                    WHERE r.id = $requestId";
            $resReq = mysqli_query($this->db, $sql);
            if ($resReq) $reqData = mysqli_fetch_assoc($resReq);

            // Fetch Items
            $resItems = mysqli_query($this->db, "SELECT * FROM pur_request_items WHERE request_id = $requestId");
            while ($row = mysqli_fetch_assoc($resItems)) {
                $items[] = $row;
            }
        } catch (\Exception $e_sql) {
            error_log("NotificationGateway SQL Error: " . $e_sql->getMessage());
        }

        $amount = number_format($reqData['total_amount'] ?? 0, 2);
        $requester = $reqData['requester_name'] ?? 'Colaborador Ohlala';
        $supplier = $reqData['supplier_name'] ?? 'Pendiente de Definir';
        $notes = $reqData['description'] ?? 'Sin observaciones adicionales.';

        $config = \Purchasing\Infrastructure\Email\EmailConfig::getSettings($this->db);
        $appUrl = rtrim($config['app_url'], '/');
        $reviewLink = "$appUrl/modules/purchasing/ui/list_requests.php?focus=$requestId";

        $subject = "Acción Requerida: Solicitud de Compra #$requestId - Nivel $currentLevel";
        
        // Build Items Table HTML
        $itemsHtml = "";
        if (!empty($items)) {
            $itemsHtml = "
            <table style='width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 11px; color: #444;'>
                <thead>
                    <tr style='background-color: #f9f9f9; text-align: left;'>
                        <th style='padding: 8px; border-bottom: 1px solid #eee;'>Insumo</th>
                        <th style='padding: 8px; border-bottom: 1px solid #eee;'>Cant.</th>
                        <th style='padding: 8px; border-bottom: 1px solid #eee; text-align: right;'>Total</th>
                    </tr>
                </thead>
                <tbody>";
            foreach ($items as $item) {
                $sub = number_format($item['subtotal'], 2);
                $itemsHtml .= "
                    <tr>
                        <td style='padding: 8px; border-bottom: 1px solid #eee;'>{$item['description']}</td>
                        <td style='padding: 8px; border-bottom: 1px solid #eee;'>{$item['quantity']}</td>
                        <td style='padding: 8px; border-bottom: 1px solid #eee; text-align: right;'>$$sub</td>
                    </tr>";
            }
            $itemsHtml .= "</tbody></table>";
        }

        $html = "
        <div style='background-color: #F7F5EB; padding: 40px; font-family: sans-serif; color: #021619;'>
            <div style='max-width: 600px; margin: 0 auto; background-color: #ffffff; border-top: 8px solid #BD9A5F; box-shadow: 0 4px 20px rgba(0,0,0,0.1);'>
                <div style='padding: 30px;'>
                    <h1 style='font-family: serif; color: #021619; margin-bottom: 5px; font-size: 24px;'>Ohlala! ERP</h1>
                    <p style='text-transform: uppercase; letter-spacing: 2px; font-size: 10px; color: #BD9A5F; font-weight: bold; margin-bottom: 30px;'>Centro de Aprobaciones Ejecutivas</p>
                    
                    <p style='font-size: 16px; line-height: 1.6;'>Estimado(a) <b>" . ($approverData['nombre'] ?? 'Aprobador') . "</b>,</p>
                    <p style='font-size: 14px; line-height: 1.6; color: #444444;'>Se ha registrado una nueva solicitud que requiere su revisión para el <b>Nivel $currentLevel</b>.</p>
                    
                    <div style='background: #021619; color: #F7F5EB; padding: 25px; margin: 30px 0;'>
                        <table style='width: 100%; border-collapse: collapse;'>
                            <tr>
                                <td style='padding: 5px 0; font-size: 10px; text-transform: uppercase; color: #BD9A5F;'>Folio</td>
                                <td style='padding: 5px 0; font-size: 10px; text-transform: uppercase; color: #BD9A5F; text-align: right;'>Inversión Total</td>
                            </tr>
                            <tr>
                                <td style='font-size: 20px; font-weight: bold;'>#PR-$requestId</td>
                                <td style='font-size: 20px; font-weight: bold; text-align: right;'>$$amount MXN</td>
                            </tr>
                            <tr><td colspan='2' style='padding-top: 20px; font-size: 10px; text-transform: uppercase; color: #BD9A5F;'>Proveedor Sugerido</td></tr>
                            <tr><td colspan='2' style='font-size: 14px; color: #F7F5EB;'>$supplier</td></tr>
                            
                            <tr><td colspan='2' style='padding-top: 20px; font-size: 10px; text-transform: uppercase; color: #BD9A5F;'>Glosa / Notas</td></tr>
                            <tr><td colspan='2' style='font-size: 14px; color: #F7F5EB; font-style: italic;'>$notes</td></tr>
                        </table>
                    </div>

                    <div style='margin-bottom: 30px;'>
                        <h3 style='font-size: 12px; text-transform: uppercase; color: #BD9A5F; border-bottom: 1px solid #eee; padding-bottom: 5px;'>Detalle de Insumos</h3>
                        $itemsHtml
                    </div>

                    <div style='text-align: center; margin-top: 40px;'>
                        <a href='$reviewLink' style='background-color: #BD9A5F; color: white; padding: 15px 40px; text-decoration: none; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; font-size: 14px; display: inline-block;'>Revisar y Procesar</a>
                        <p style='margin-top: 20px; font-size: 11px; color: #999;'>Nota: Se requiere iniciar sesión en el ERP para procesar la decisión.</p>
                    </div>
                </div>
            </div>
            <p style='text-align: center; font-size: 10px; color: #999; margin-top: 30px;'>Ohlala Artisanal &copy; " . date('Y') . " - Excellence in Every Ingredient</p>
        </div>";

        // 1. Email First
        if ($email) {
            $this->sendEmail($email, $subject, $html, $this->db);
        }

        // 2. WhatsApp Next (Rich message via Flow)
        if ($phone) {
            $wsUrl = $config['whatsapp_bridge_url'] ?? null;
            $wsKey = $config['whatsapp_internal_key'] ?? null;

            \FlowConnector::sendApprovalRequest([
                'phone'     => $phone,
                'folio'     => "#PR-$requestId",
                'amount'    => "$$amount MXN",
                'item'      => $supplier, // Using supplier as primary item context for PR header
                'requestor' => $requester,
                'id'        => $requestId
            ], $wsUrl, $wsKey, $appUrl);
        }
        
        return true;
    }

    /**
     * Notify the requester that their process has started.
     */
    public function notifyProcessStarted($requestId, $requesterData, $amount) {
        $email = $requesterData['correo'] ?? null;
        $subject = "Confirmación: Solicitud de Compra #$requestId Iniciada";
        
        $html = "
        <div style='background-color: #f4f4f4; padding: 30px; font-family: sans-serif;'>
            <div style='max-width: 500px; margin: 0 auto; background: white; padding: 25px; border-left: 5px solid #BD9A5F;'>
                <h2 style='color: #021619; margin-top: 0;'>Proceso Iniciado</h2>
                <p style='font-size: 15px; color: #444; line-height: 1.6;'>Su solicitud de compra <b>#$requestId</b> por un monto de <b>$$amount MXN</b> ha sido registrada exitosamente y ha entrado en la cola de aprobación.</p>
                <p style='font-size: 14px; color: #444;'>Le notificaremos por este medio una vez que el proceso sea completado (Aprobación Final o Rechazo).</p>
                <hr style='border: none; border-top: 1px solid #eee; margin: 20px 0;'>
                <p style='font-size: 10px; color: #999;'>Ohlala! Bistro ERP - Abastecimiento Inteligente</p>
            </div>
        </div>";

        if ($email) {
            $this->sendEmail($email, $subject, $html, $this->db);
        }
        return true;
    }

    /**
     * Notify the requester about an approval step or final decision.
     */
    public function notifyRequesterDecision($requestId, $requesterData, $status, $level = null) {
        $email = $requesterData['correo'] ?? null;
        $phone = $requesterData['telefono'] ?? null;
        
        $subject = "Ohlala ERP: Actualización de Solicitud #$requestId";
        
        if ($status === 'approved' && $level !== null) {
            // This case might be used less now since intermediate notifications are requested to be silenced,
            // but we keep the method flexible.
            $message = "Excelente noticia: Su solicitud de compra #$requestId ha sido APROBADA en el Nivel $level y continúa su proceso.";
        } elseif ($status === 'approved') {
            $message = "Felicidades: Su solicitud de compra #$requestId ha sido FINALMENTE APROBADA.";
        } else {
            $message = "Ohlala ERP: Su solicitud de compra #$requestId ha sido RECHAZADA.";
        }
        
        // Simple HTML Wrapper for these notifications
        $html = "
        <div style='background-color: #f4f4f4; padding: 30px; font-family: sans-serif;'>
            <div style='max-width: 500px; margin: 0 auto; background: white; padding: 25px; border-left: 5px solid #BD9A5F;'>
                <h2 style='color: #021619; margin-top: 0;'>Aviso del Sistema</h2>
                <p style='font-size: 15px; color: #444; line-height: 1.6;'>$message</p>
                <hr style='border: none; border-top: 1px solid #eee; margin: 20px 0;'>
                <p style='font-size: 10px; color: #999;'>Este es un mensaje automático de Ohlala! Bistro ERP.</p>
            </div>
        </div>";

        if ($email) {
            $this->sendEmail($email, $subject, $html, $this->db);
        }

        if ($phone) {
            // Decision notification is simpler, we could use a different Flow template if available
            // For now, we'll use a direct message or the same structure if applicable.
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
            throw new \Exception("Error crítico: El servidor de correo rechazó el mensaje. Verifique su configuración SMTP en Ajustes.");
        }

        return $result;
    }

    /**
     * Minimal implementation for Flow Webhook integration.
     */
    private function sendToFlow($phone, $message, $requestId) {
        error_log("Sending WA to $phone: $message (RS: $requestId)");
        // Fallback for simple status messages not using the Approval Template
        return true;
    }
}
