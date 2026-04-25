<?php
/**
 * Ohlala! ERP - Flow Connector
 * Envia notificaciones de aprobación a Flow (WhatsApp)
 */

class FlowConnector {
    /**
     * Envía una notificación de aprobación de compra
     * 
     * @param array $data { phone, folio, amount, item, requestor, id }
     * @param string $apiUrl (Opcional) URL del Bridge de Flow
     * @param string $apiKey (Opcional) API Key interna
     * @param string $appUrl (Opcional) URL base del ERP para generar el link
     */
    public static function sendApprovalRequest($data, $apiUrl = null, $apiKey = null, $appUrl = null) {
        // Fallbacks a valores por defecto si no se proporcionan
        $apiUrl = $apiUrl ?: 'http://localhost:3003/whatsapp/external/approval';
        $apiKey = $apiKey ?: 'pitaya_internal_secret_2026';
        $appUrl = rtrim($appUrl ?: 'http://localhost/ohlala-erp', '/');

        $link = "$appUrl/modules/purchasing/ui/list_requests.php?id=" . $data['id'];
        
        $payload = [
            'phone' => $data['phone'],
            'folio' => $data['folio'],
            'amount' => $data['amount'],
            'item' => $data['item'],
            'requestor' => $data['requestor'],
            'link' => $link
        ];

        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, JSON_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-Internal-Key: ' . $apiKey
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'success' => ($http_code === 201),
            'code' => $http_code,
            'response' => $response
        ];
    }
}
