<?php
namespace Purchasing\Infrastructure\Email;

/**
 * EmailConfig
 * Configuration for SMTP delivery.
 */
class EmailConfig {
    public static function getSettings($db) {
        $res = mysqli_query($db, "SELECT setting_key, setting_value FROM pur_settings WHERE setting_key LIKE 'smtp_%' OR setting_key LIKE 'whatsapp_%' OR setting_key LIKE 'enable_%' OR setting_key = 'app_url'");
        $settings = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }

        return [
            'host' => $settings['smtp_host'] ?? 'smtp.gmail.com',
            'port' => (int)($settings['smtp_port'] ?? 587),
            'user' => $settings['smtp_user'] ?? 'smtp_user_placeholder',
            'pass' => $settings['smtp_pass'] ?? 'smtp_pass_placeholder',
            'from_name' => $settings['smtp_from_name'] ?? 'Ohlala ERP Compras',
            'app_url' => $settings['app_url'] ?? 'http://localhost/ohlala-erp',
            'enable_email_notifications' => $settings['enable_email_notifications'] ?? '1',
            'enable_whatsapp_notifications' => $settings['enable_whatsapp_notifications'] ?? '1',
            'flow_tenant_slug' => $settings['flow_tenant_slug'] ?? 'default',
            'whatsapp_bridge_url' => $settings['whatsapp_bridge_url'] ?? 'http://localhost:3003/whatsapp/external/approval',
            'whatsapp_internal_key' => $settings['whatsapp_internal_key'] ?? 'pitaya_internal_secret_2026'
        ];
    }
}
