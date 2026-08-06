<?php
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';
require_once __DIR__ . '/PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Sends an email via Gmail SMTP.
 * Returns true on success, false on failure (and logs the error).
 */
function sendResetEmail($toEmail, $toName, $resetLink) {
    $mail = new PHPMailer(true);

    try {
        // ---- SMTP CONFIG: replace these with your own values ----
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'youremail@gmail.com';      // <-- your Gmail address
        $mail->Password   = 'your16charapppassword';     // <-- the App Password from Step 1 (no spaces)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('youremail@gmail.com', 'FoodOrder');
        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(true);
        $mail->Subject = 'Reset Your FoodOrder Password';
        $mail->Body    = "
            <p>Hi " . htmlspecialchars($toName) . ",</p>
            <p>You requested to reset your password. Click the link below to set a new one:</p>
            <p><a href='" . htmlspecialchars($resetLink) . "'>" . htmlspecialchars($resetLink) . "</a></p>
            <p>This link expires in 1 hour. If you didn't request this, you can ignore this email.</p>
        ";
        $mail->AltBody = "Reset your password using this link: " . $resetLink . " (expires in 1 hour)";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
        return false;
    }
}