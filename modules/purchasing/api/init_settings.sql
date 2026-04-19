USE pedidos_ohlala;

CREATE TABLE IF NOT EXISTS pur_settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Seed initial values
INSERT INTO pur_settings (setting_key, setting_value) VALUES 
('smtp_host', 'smtp.gmail.com'),
('smtp_port', '587'),
('smtp_user', 'admin_email_config'),
('smtp_pass', 'admin_smtp_secret_config'),
('smtp_from_name', 'Ohlala ERP Compras'),
('approver_level_1', '2'),
('approver_level_2', '3'),
('approver_level_3', '4')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);
