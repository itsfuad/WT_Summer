<?php
require_once __DIR__ . '/env.php';

EnvReader::load();

class EmailManager {

    public static function sendOTP($email, $otp) {
        $subject = "Password Reset OTP - CrowdFund";
        $message = "
        <div style='max-width:600px;margin:0 auto;padding:20px;border:1px solid #ddd;border-radius:10px'>
            <h2 style='color:#333;text-align:center'>Password Reset Request</h2>
            <p>You have requested to reset your password. Please use the following OTP to proceed:</p>
            <div style='background:#f8f9fa;padding:20px;text-align:center;border-radius:5px;margin:20px 0'>
                <h1 style='color:#007bff;font-size:36px;margin:0;letter-spacing:5px'>$otp</h1>
            </div>
            <p>This OTP will expire in 10 minutes for security reasons.</p>
            <p>If you didn't request this password reset, please ignore this email.</p>
            <hr style='margin:30px 0'>
            <p style='color:#666;font-size:12px;text-align:center'>
                This is an automated message from CrowdFund. Please do not reply to this email.
            </p>
        </div>
        ";
        
        return self::sendEmail($email, $subject, $message);
    }

    public static function sendEmail($to, $subject, $message) {
        return self::sendViaSMTP($to, $subject, $message);
    }

    private static function getResponse($socket) {
        $data = '';
        while ($line = fgets($socket, 512)) {
            $data .= $line;
            if (preg_match('/^\d{3} /', $line)) break; // last line ends with space after code
        }
        return $data;
    }

    private static function sendViaSMTP($to, $subject, $message) {
        // SSL direct connection
        $socket = fsockopen("ssl://" . SMTP_HOST, SMTP_PORT, $errno, $errstr, 5);
        if (!$socket) {
            error_log("SMTP Connection failed: $errstr ($errno)");
            return false;
        }

        stream_set_timeout($socket, 3); // short timeout

        if (strpos(self::getResponse($socket), '220') === false) return false;

        // EHLO
        fputs($socket, "EHLO localhost\r\n");
        if (strpos(self::getResponse($socket), '250') === false) return false;

        // AUTH PLAIN
        $auth_string = base64_encode("\0" . SMTP_USERNAME . "\0" . SMTP_PASSWORD);
        fputs($socket, "AUTH PLAIN $auth_string\r\n");
        if (strpos(self::getResponse($socket), '235') === false) return false;

        // MAIL FROM
        fputs($socket, "MAIL FROM: <" . SMTP_FROM_EMAIL . ">\r\n");
        if (strpos(self::getResponse($socket), '250') === false) return false;

        // RCPT TO
        fputs($socket, "RCPT TO: <$to>\r\n");
        if (strpos(self::getResponse($socket), '250') === false) return false;

        // DATA
        fputs($socket, "DATA\r\n");
        if (strpos(self::getResponse($socket), '354') === false) return false;

        // Build email
        $email_data = "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM_EMAIL . ">\r\n";
        $email_data .= "To: $to\r\n";
        $email_data .= "Subject: $subject\r\n";
        $email_data .= "MIME-Version: 1.0\r\n";
        $email_data .= "Content-Type: text/html; charset=UTF-8\r\n";
        $email_data .= "\r\n";
        $email_data .= $message;
        $email_data .= "\r\n.\r\n";

        fputs($socket, $email_data);
        $success = strpos(self::getResponse($socket), '250') !== false;

        // Quit
        fputs($socket, "QUIT\r\n");
        fclose($socket);

        return $success;
    }
}
