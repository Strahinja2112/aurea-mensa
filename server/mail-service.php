<?php

declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../PHPMailer/src/Exception.php';
require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';

final class MailService
{
    private static function createMailer(): PHPMailer
    {
        $mail = new PHPMailer(true);

        $mail->isSMTP();
        $mail->Host = 'sandbox.smtp.mailtrap.io';
        $mail->SMTPAuth = true;
        $mail->Port = 2525;
        $mail->Username = 'a216bf90d168a4';
        $mail->Password = '******';

        $mail->CharSet = 'UTF-8';

        $mail->setFrom('webmaster@example.com', 'Restaurant');

        return $mail;
    }

    public static function send(
            string $to,
            string $subject,
            string $body,
            string $altBody = ''
    ): void
    {
        try {

            $mail = self::createMailer();

            $mail->addAddress($to);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = $altBody;

            $mail->send();

        } catch (Exception $e) {
            throw new RuntimeException("Mail send failed: " . $e->getMessage());
        }
    }

    public static function sendActivationEmail(string $email, string $token): void
    {
        $activationLink = "http://localhost:3000/activate.php?token=" . $token;

        $subject = "Aktivacija naloga";

        $body = "
            <h2>Aktivacija naloga</h2>
            <p>Kliknite na link da aktivirate nalog:</p>
            <a href='{$activationLink}'>{$activationLink}</a>
        ";

        $altBody = "Aktivirajte nalog: {$activationLink}";

        self::send($email, $subject, $body, $altBody);
    }

    public static function sendPasswordReset(string $email, string $token): void
    {
        $resetLink = "http://localhost:3000/reset-password.php?token=" . $token;

        $subject = "Reset lozinke";

        $body = "
            <h2>Reset lozinke</h2>
            <p>Kliknite na link da resetujete lozinku:</p>
            <a href='{$resetLink}'>{$resetLink}</a>
        ";

        $altBody = "Reset lozinke: {$resetLink}";

        self::send($email, $subject, $body, $altBody);
    }
}