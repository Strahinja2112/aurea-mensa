<?php

declare(strict_types=1);

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

require_once BASE_PATH . '/vendor/phpmailer/phpmailer/src/Exception.php';
require_once BASE_PATH . '/vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once BASE_PATH . '/vendor/phpmailer/phpmailer/src/SMTP.php';

require_once BASE_PATH . '/env.php';

final class Mailer
{
    public static function send(string $to, string $subject, string $body): bool
    {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = Env::$MAILTRAP_DOMAIN;
            $mail->Port = Env::$MAILTRAP_PORT;
            $mail->SMTPAuth = true;
            $mail->Username = Env::$MAILTRAP_USERNAME;
            $mail->Password = Env::$MAILTRAP_PASSWORD;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->CharSet = 'UTF-8';

            // Recipients
            $mail->setFrom('no-reply@restoran.rs', 'Restoran Rezervacije');
            $mail->addAddress($to);

            // Content
            $mail->isHTML(false);
            $mail->Subject = $subject;
            $mail->Body = $body;

            return $mail->send();
        } catch (Exception $e) {
            var_dump($mail->ErrorInfo);
            var_dump($e->getMessage());
            return false;
        }
    }

    public static function sendActivationEmail(string $email, string $token): bool
    {
        $link = "http://localhost/" . APP_DIR . "/activate.php?token=" . $token;
        $subject = "Aktivacija vašeg naloga - Restoran";
        $body = "
            <h1>Dobrodošli!</h1>
            <p>Kliknite na link ispod kako biste aktivirali svoj nalog:</p>
            <p><a href='{$link}'>Aktiviraj nalog</a></p>
            <p>Ako niste napravili nalog, ignorišite ovaj email.</p>
        ";

        return self::send($email, $subject, $body);
    }
}