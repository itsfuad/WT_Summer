<?php
// SMTP Configuration
define('SMTP_HOST', 'mail.smtp2go.com');
define('SMTP_PORT', 2525);
define('SMTP_USERNAME', 'crowdfund');
define('SMTP_PASSWORD', 'c_fund_9987');
define('SMTP_FROM_EMAIL', 'support@brainbird.org');
define('SMTP_FROM_NAME', 'CrowdFund Support');

class EmailManager {
    
    public static function sendOTP($email, $subject, $message = null) {
        // If called with 3 parameters (email, subject, message), use them directly
        if ($message !== null) {
            return self::sendEmail($email, $subject, $message);
        }
        
        // If called with 2 parameters (email, otp), create formatted message
        $otp = $subject; // In this case, second parameter is the OTP
        $subject = "Password Reset OTP - CrowdFund";
        $message = "
        <div style='max-width:600px;margin:0 auto;padding:20px;border:1px solid #ddd;border-radius:10px'>
            <h2 style='color:#333;text-align:center'>Password Reset Request</h2>
            <p>Hello,</p>
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
    
    private static function sendViaSMTP($to, $subject, $message) {
        $socket = fsockopen(SMTP_HOST, SMTP_PORT, $errno, $errstr, 10);
        
        if (!$socket) {
            error_log("SMTP Connection failed: $errstr ($errno)");
            return false;
        }
        
        // Set socket timeout to 10 seconds
        stream_set_timeout($socket, 10);
        
        // Read server greeting
        $response = fgets($socket, 512);
        if (strpos($response, '220') === false) {
            fclose($socket);
            return false;
        }
        
        // Send EHLO (Extended HELO)
        fputs($socket, "EHLO localhost\r\n");
        $response = fgets($socket, 512);
        if (strpos($response, '250') === false) {
            fclose($socket);
            return false;
        }
        
        // Read all EHLO extensions
        while (strpos($response, '250-') !== false) {
            $response = fgets($socket, 512);
        }
        
        // Use AUTH PLAIN (more reliable than LOGIN)
        $auth_string = base64_encode("\0" . SMTP_USERNAME . "\0" . SMTP_PASSWORD);
        fputs($socket, "AUTH PLAIN $auth_string\r\n");
        $response = fgets($socket, 512);
        if (strpos($response, '235') === false) {
            fclose($socket);
            return false;
        }
        
        // Send MAIL FROM
        fputs($socket, "MAIL FROM: <" . SMTP_FROM_EMAIL . ">\r\n");
        $response = fgets($socket, 512);
        if (strpos($response, '250') === false) {
            fclose($socket);
            return false;
        }
        
        // Send RCPT TO
        fputs($socket, "RCPT TO: <$to>\r\n");
        $response = fgets($socket, 512);
        if (strpos($response, '250') === false) {
            fclose($socket);
            return false;
        }
        
        // Send DATA
        fputs($socket, "DATA\r\n");
        $response = fgets($socket, 512);
        if (strpos($response, '354') === false) {
            fclose($socket);
            return false;
        }
        
        // Send email content
        $email_data = "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM_EMAIL . ">\r\n";
        $email_data .= "To: $to\r\n";
        $email_data .= "Subject: $subject\r\n";
        $email_data .= "MIME-Version: 1.0\r\n";
        $email_data .= "Content-Type: text/html; charset=UTF-8\r\n";
        $email_data .= "\r\n";
        $email_data .= $message;
        $email_data .= "\r\n.\r\n";
        
        fputs($socket, $email_data);
        $response = fgets($socket, 512);
        
        // Send QUIT
        fputs($socket, "QUIT\r\n");
        fclose($socket);
        
        return strpos($response, '250') !== false;
    }
}
