<?php
namespace Purchasing\Infrastructure\Email;

/**
 * SmtpMailer
 * A robust, zero-dependency SMTP client for Gmail (TLS).
 */
class SmtpMailer {
    private $host;
    private $port;
    private $user;
    private $pass;
    private $timeout = 10;
    private $debug = true;

    public function __construct($host, $port, $user, $pass) {
        $this->host = $host;
        $this->port = $port;
        $this->user = $user;
        $this->pass = $pass;
    }

    public function send($to, $subject, $message, $fromName = "Ohlala ERP") {
        try {
            $connection = fsockopen($this->host, $this->port, $errno, $errstr, $this->timeout);
            if (!$connection) throw new \Exception("Could not connect to SMTP: $errstr ($errno)");

            $this->getResponse($connection, "220");
            $this->sendCommand($connection, "EHLO " . gethostname(), "250");
            
            // Start TLS
            $this->sendCommand($connection, "STARTTLS", "220");
            if (!stream_socket_enable_crypto($connection, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new \Exception("Failed to start encryption");
            }

            // Authenticate
            $this->sendCommand($connection, "EHLO " . gethostname(), "250");
            $this->sendCommand($connection, "AUTH LOGIN", "334");
            $this->sendCommand($connection, base64_encode($this->user), "334");
            $this->sendCommand($connection, base64_encode($this->pass), "235");

            // Transaction
            $this->sendCommand($connection, "MAIL FROM:<$this->user>", "250");
            $this->sendCommand($connection, "RCPT TO:<$to>", "250");
            $this->sendCommand($connection, "DATA", "354");

            $headers = [
                "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=",
                "To: <$to>",
                "From: $fromName <$this->user>",
                "MIME-Version: 1.0",
                "Content-Type: text/html; charset=UTF-8",
                "Content-Transfer-Encoding: 8bit",
                "Date: " . date("r")
            ];

            $body = "<html><body style='font-family: sans-serif; color: #333;'>";
            $body .= "<h2>Ohlala ERP - Notificación</h2>";
            $body .= "<p>" . nl2br(htmlspecialchars($message)) . "</p>";
            $body .= "<hr><small>Este es un correo automático, por favor no responda.</small>";
            $body .= "</body></html>";

            fwrite($connection, implode("\r\n", $headers) . "\r\n\r\n" . $body . "\r\n.\r\n");
            $this->getResponse($connection, "250");

            $this->sendCommand($connection, "QUIT", "221");
            fclose($connection);
            return true;

        } catch (\Exception $e) {
            error_log("SmtpMailer Error: " . $e->getMessage());
            return false;
        }
    }

    private function sendCommand($connection, $command, $expectedResponse) {
        fwrite($connection, $command . "\r\n");
        return $this->getResponse($connection, $expectedResponse);
    }

    private function getResponse($connection, $expectedResponse) {
        $response = "";
        while ($str = fgets($connection, 515)) {
            $response .= $str;
            if (substr($str, 3, 1) == " ") break;
        }
        if (strpos($response, $expectedResponse) !== 0) {
            throw new \Exception("SMTP Error: Expected $expectedResponse, got: $response");
        }
        return $response;
    }
}
