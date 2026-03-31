<?php

declare(strict_types=1);

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

require_once BASE_PATH . '/_modules/PHPMailer/src/Exception.php';
require_once BASE_PATH . '/_modules/PHPMailer/src/PHPMailer.php';
require_once BASE_PATH . '/_modules/PHPMailer/src/SMTP.php';

final class Mailer
{
    public static function send(string $to, string $subject, string $body): bool
    {
        $mail = new PHPMailer(true);

        try {
            // Server settings - ovde unesi svoje SMTP podatke
            $mail->isSMTP();
            $mail->Host = 'smtp.example.com'; // Npr. smtp.gmail.com
            $mail->SMTPAuth = true;
            $mail->Username = 'pavicevicstrahinja99@gmail.com';
            $mail->Password = 'nekalozinka';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->CharSet = 'UTF-8';

            // Recipients
            $mail->setFrom('no-reply@restoran.rs', 'Restoran Rezervacije');
            $mail->addAddress($to);

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;

            return $mail->send();
        } catch (Exception $e) {
            // U produkciji loguj grešku: error_log($mail->ErrorInfo);
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