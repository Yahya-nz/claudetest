<?php
/**
 * SMTP Configuration for Email Notifications
 * Setup Gmail SMTP for sending emails
 */

// SMTP Settings (untuk Gmail)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587); // 587 for TLS, 465 for SSL
define('SMTP_SECURE', 'tls'); // 'tls' or 'ssl'
define('SMTP_USERNAME', 'aunymuser@gmail.com'); // Your Gmail
define('SMTP_PASSWORD', 'xlxq fkvx qkhe niwc'); // App Password (bukan password Gmail biasa!)

// IMPORTANT: Cara setup Gmail SMTP:
// 1. Login ke Gmail: geloraminisoccer.gerung@gmail.com
// 2. Buka: https://myaccount.google.com/security
// 3. Enable "2-Step Verification" (wajib)
// 4. Buka: https://myaccount.google.com/apppasswords
// 5. Generate "App Password" untuk "Mail"
// 6. Copy 16-digit password
// 7. Paste di SMTP_PASSWORD di atas (tanpa spasi)
// 8. Save file ini

/**
 * Send Email via SMTP (PHPMailer-style but using built-in)
 * Alternative: Install PHPMailer via composer
 */
function sendEmailSMTP($to, $subject, $message, $fromName = '') {
    if (empty(SMTP_PASSWORD)) {
        // SMTP not configured, use basic mail() function
        return @mail($to, $subject, $message, implode("\r\n", [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . ($fromName ?: getSetting('site_name')) . ' <' . getSetting('site_email') . '>',
        ]));
    }

    // For production, use PHPMailer library
    // Install: composer require phpmailer/phpmailer
    //
    // Example PHPMailer code:
    // require 'vendor/autoload.php';
    // use PHPMailer\PHPMailer\PHPMailer;
    //
    // $mail = new PHPMailer(true);
    // $mail->isSMTP();
    // $mail->Host = SMTP_HOST;
    // $mail->SMTPAuth = true;
    // $mail->Username = SMTP_USERNAME;
    // $mail->Password = SMTP_PASSWORD;
    // $mail->SMTPSecure = SMTP_SECURE;
    // $mail->Port = SMTP_PORT;
    // $mail->setFrom(SMTP_USERNAME, $fromName ?: getSetting('site_name'));
    // $mail->addAddress($to);
    // $mail->isHTML(true);
    // $mail->Subject = $subject;
    // $mail->Body = $message;
    // return $mail->send();

    // Fallback to basic mail() if PHPMailer not available
    return @mail($to, $subject, $message, implode("\r\n", [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: ' . ($fromName ?: getSetting('site_name')) . ' <' . SMTP_USERNAME . '>',
    ]));
}
