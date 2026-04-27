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
    private static function getAppUrl(): string
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'];
        if ($host == 'localhost') {
            $host .= ':3000';
        }

        return "{$protocol}://{$host}/" . APP_DIR;
    }

    private static function getEmailTemplate(string $title, string $subtitle, string $description, string $buttonText, string $buttonUrl): string
    {
        $baseUrl = self::getAppUrl();

        return "
            <div style='margin: 0; padding: 0; background-color: #121212; font-family: \"PT Sans Narrow\", sans-serif;'>
                <table role='presentation' border='0' cellpadding='0' cellspacing='0' width='100%' style='padding: 60px 0;'>
                    <tr>
                        <td align='center'>
                            <table role='presentation' border='0' cellpadding='0' cellspacing='0' width='100%' style='max-width: 500px; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 6px; margin: 20px;'>
                                <tr>
                                    <td style='padding: 40px; text-align: center;'>
                                        
                                        <div style='margin-bottom: 30px;'>
                                            <h1 style='margin: 0; font-size: 42px; color: #ffffff; font-family: serif; font-weight: 300; line-height: 0.8;'>Aurea Mensa</h1>
                                            <div style='font-size: 11px; text-transform: uppercase; letter-spacing: 3px; color: rgba(255,255,255,0.5); margin-top: 10px;'>restoran</div>
                                        </div>
        
                                        <h2 style='color: #f8f9fa; font-size: 24px; font-weight: 700; margin-bottom: 20px; letter-spacing: 1px;'>{$title}</h2>
                                        
                                        <p style='color: #d1d1d1; font-size: 16px; line-height: 1.6; margin-bottom: 35px;'>
                                            {$description}
                                        </p>
        
                                        <table role='presentation' border='0' cellpadding='0' cellspacing='0' style='margin: 0 auto;'>
                                            <tr>
                                                <td align='center' style='border-radius: 6px; background-color: #f8f9fa;'>
                                                    <a href='{$buttonUrl}' style='display: inline-block; padding: 16px 40px; font-size: 14px; color: #333333; text-decoration: none; font-weight: 700; letter-spacing: 1px; text-transform: uppercase;'>
                                                        {$buttonText}
                                                    </a>
                                                </td>
                                            </tr>
                                        </table>
        
                                        <p style='margin-top: 40px; font-size: 13px; color: #a5a5a5;'>
                                            {$subtitle}
                                        </p>
        
                                        <div style='margin-top: 40px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px;'>
                                            <p style='font-size: 11px; color: rgba(255,255,255,0.3);'>
                                                Ovaj link važi 10 minuta. Ako niste vi ovo zatražili, slobodno ignorišite ovaj mejl.
                                            </p>
                                        </div>
        
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </div>";
    }

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

            $mail->setFrom('no-reply@restoran.rs', 'Restoran Rezervacije');
            $mail->addAddress($to);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;

//            return $mail->send();
        } catch (Exception $e) {
            var_dump($mail->ErrorInfo);
            var_dump($e->getMessage());
            return false;
        }

        // Ovo je da ne bi poslao mail zapravo da ne trosim, default kada kreira korisnika je da stavi da je emial_verified = 1
        return true;
    }

    public static function sendActivationEmail(string $email, string $token): bool
    {
        $baseUrl = self::getAppUrl();
        $url = "{$baseUrl}/pages/auth/activate-account/index.php?token={$token}";

        $body = self::getEmailTemplate(
            "Dobrodošli",
            "Već imate nalog? <a href='{$baseUrl}/pages/auth/login' style='color: #a0c4ff; text-decoration: none;'>Prijavite se</a>",
            "Hvala vam što ste postali deo naše priče. Kliknite na dugme ispod kako biste aktivirali svoj nalog i potvrdili registraciju.",
            "Aktiviraj nalog",
            $url
        );

        return self::send($email, "Aktivacija vašeg naloga - Aurea Mensa", $body);
    }

    public static function sendPasswordResetEmail(string $email, string $token): bool
    {
        $baseUrl = self::getAppUrl();
        $url = "{$baseUrl}/pages/auth/forgot-password/index.php?token={$token}";

        $body = self::getEmailTemplate(
            "Reset lozinke",
            "Setili ste se lozinke? <a href='{$baseUrl}/pages/auth/login' style='color: #a0c4ff; text-decoration: none;'>Prijavite se</a>",
            "Dobili smo zahtev za promenu lozinke na vašem nalogu. Kliknite na dugme ispod da biste postavili novu lozinku.",
            "Postavi novu lozinku",
            $url
        );

        return self::send($email, "Resetovanje vaše lozinke - Aurea Mensa", $body);
    }
}