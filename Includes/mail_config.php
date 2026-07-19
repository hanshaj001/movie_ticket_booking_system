<?php
// Includes/mail_config.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Require the Composer autoloader
$autoload_path = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoload_path)) {
    require_once $autoload_path;
}

/**
 * Helper function to send email using PHPMailer
 *
 * @param string $to Recipient email address
 * @param string $subject Email subject
 * @param string $body Email body (HTML allowed)
 * @param boolean $isHtml Whether the body is HTML
 * @return array ['success' => boolean, 'message' => string]
 */
function sendMail($to, $subject, $body, $isHtml = true) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; // Set the SMTP server to send through
        $mail->SMTPAuth   = true;
        
        // --- CONFIGURE YOUR CREDENTIALS HERE ---
        $env = parse_ini_file(__DIR__ . '/../.env');
        $mail->Username   = $env['SMTP_USERNAME'] ?? 'mtbs.hansh@gmail.com'; 
        $mail->Password   = $env['SMTP_PASSWORD'] ?? ''; 
        // ---------------------------------------
        
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Enable TLS encryption
        $mail->Port       = $env['SMTP_PORT'] ?? 587; // TCP port to connect to

        // Default Sender Name
        $mail->setFrom($env['SMTP_FROM_EMAIL'] ?? 'mtbs.hansh@gmail.com', $env['SMTP_FROM_NAME'] ?? 'MTBS Cinema');
        $mail->addAddress($to);

        // Content
        $mail->isHTML($isHtml);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
        return ['success' => true, 'message' => 'Email sent successfully'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => "Message could not be sent. Mailer Error: {$mail->ErrorInfo}"];
    }
}
?>
