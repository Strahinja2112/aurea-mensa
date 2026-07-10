<?php

declare(strict_types=1);

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

require_once BASE_PATH . '/vendor/phpmailer/phpmailer/src/Exception.php';
require_once BASE_PATH . '/vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once BASE_PATH . '/vendor/phpmailer/phpmailer/src/SMTP.php';

require_once BASE_PATH . '/src/classes/Env.php';

final class Mailer
{
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

    private static function getAppUrl(): string
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';

        $fullPath = "{$protocol}://{$host}";
        if ($host == "localhost") {
            $fullPath .= APP_DIR;
        }

        return $fullPath;
    }

    private static function getEmailTemplate(
        string $title,
        string $subtitle,
        string $description,
        string $buttonText,
        string $buttonUrl
    ): string {
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
            $mail->Timeout = 10;

            $mail->setFrom('no-reply@restoran.rs', 'Restoran Rezervacije');
            $mail->addAddress($to);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;

            return $mail->send();
        } catch (Exception $e) {
            if (class_exists('Logger')) {
                Logger::logException($e, [
                    'mail_to' => $to,
                    'mail_subject' => $subject,
                    'mail_host' => Env::$MAILTRAP_DOMAIN,
                    'mail_port' => Env::$MAILTRAP_PORT,
                    'mail_error' => $mail->ErrorInfo,
                ]);
            }

            return false;
        }
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

    public static function sendJobApplicationStatusEmail(
        string $email,
        string $status,
        string $firstName = ''
    ): bool {
        $baseUrl = self::getAppUrl();
        $jobsUrl = "{$baseUrl}/pages/jobs";
        $statusLabels = [
            'PENDING' => 'Na cekanju',
            'REVIEWED' => 'Pregledano',
            'ACCEPTED' => 'Prihvaceno',
            'REJECTED' => 'Odbijeno',
        ];

        $normalizedStatus = strtoupper($status);
        $statusLabel = $statusLabels[$normalizedStatus] ?? $normalizedStatus;
        $recipientName = trim($firstName);
        $title = "Status prijave je azuriran";
        $subtitle = "Prijavu mozete pogledati na stranici za posao.";

        $description = match ($normalizedStatus) {
            'REVIEWED' => ($recipientName !== '' ? "Zdravo {$recipientName}," : 'Zdravo,')
                . " vasa prijava za posao je pregledana. Trenutni status prijave je: {$statusLabel}.",
            'ACCEPTED' => ($recipientName !== '' ? "Zdravo {$recipientName}," : 'Zdravo,')
                . " vasa prijava za posao je prihvacena. Kontaktiracemo vas uskoro sa narednim koracima.",
            'REJECTED' => ($recipientName !== '' ? "Zdravo {$recipientName}," : 'Zdravo,')
                . " hvala vam na prijavi. Trenutno ne nastavljamo proces za ovu prijavu, a status je: {$statusLabel}.",
            default => ($recipientName !== '' ? "Zdravo {$recipientName}," : 'Zdravo,')
                . " status vase prijave za posao je azuriran na: {$statusLabel}.",
        };

        $body = self::getEmailTemplate(
            $title,
            $subtitle,
            $description,
            "Pogledaj prijavu",
            $jobsUrl
        );

        return self::send($email, "Promena statusa prijave - Aurea Mensa", $body);
    }

    public static function sendReservationConfirmationEmail(
        string $email,
        string $reservationCode,
        string $reservationDate,
        string $startTime,
        string $endTime,
        int $tableCount
    ): bool {
        $baseUrl = self::getAppUrl();
        $profileUrl = "{$baseUrl}/pages/profile";

        $formattedDate = (DateTime::createFromFormat('Y-m-d', $reservationDate))->format('d. m. Y.');
        $formattedTime = date('H:i', strtotime($startTime)) . ' - ' . date('H:i', strtotime($endTime));

        $body = self::getReservationEmailTemplate(
            "Potvrda rezervacije",
            "Vaša rezervacija je potvrđena!",
            $reservationCode,
            $formattedDate,
            $formattedTime,
            $tableCount,
            $profileUrl
        );

        return self::send($email, "Potvrda rezervacije - {$reservationCode} - Aurea Mensa", $body);
    }

    private static function getReservationEmailTemplate(
        string $title,
        string $subtitle,
        string $code,
        string $date,
        string $time,
        int $tableCount,
        string $profileUrl
    ): string {
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
        
                                        <h2 style='color: #f8f9fa; font-size: 24px; font-weight: 700; margin-bottom: 10px; letter-spacing: 1px;'>{$title}</h2>
                                        
                                        <p style='color: #d1d1d1; font-size: 16px; line-height: 1.6; margin-bottom: 30px;'>
                                            {$subtitle}
                                        </p>

                                        <div style='background: rgba(255,255,255,0.05); border-radius: 6px; padding: 25px; margin-bottom: 30px;'>
                                            <p style='margin: 0 0 15px 0;'>
                                                <span style='color: #a0c4ff; font-size: 12px; text-transform: uppercase; letter-spacing: 1px;'>Kod rezervacije</span>
                                            </p>
                                            <p style='margin: 0; font-size: 28px; font-weight: 700; color: #ffffff; letter-spacing: 2px;'>{$code}</p>
                                        </div>

                                        <table role='presentation' border='0' cellpadding='0' cellspacing='0' width='100%' style='margin-bottom: 30px;'>
                                            <tr>
                                                <td style='padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,0.1);'>
                                                    <span style='color: #a0c4ff; font-size: 12px; text-transform: uppercase; letter-spacing: 1px;'>Datum</span>
                                                    <p style='margin: 5px 0 0 0; color: #ffffff; font-size: 16px;'>{$date}</p>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style='padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,0.1);'>
                                                    <span style='color: #a0c4ff; font-size: 12px; text-transform: uppercase; letter-spacing: 1px;'>Vreme</span>
                                                    <p style='margin: 5px 0 0 0; color: #ffffff; font-size: 16px;'>{$time}</p>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style='padding: 10px 0;'>
                                                    <span style='color: #a0c4ff; font-size: 12px; text-transform: uppercase; letter-spacing: 1px;'>Broj stolova</span>
                                                    <p style='margin: 5px 0 0 0; color: #ffffff; font-size: 16px;'>{$tableCount}</p>
                                                </td>
                                            </tr>
                                        </table>
        
                                        <table role='presentation' border='0' cellpadding='0' cellspacing='0' style='margin: 0 auto;'>
                                            <tr>
                                                <td align='center' style='border-radius: 6px; background-color: #f8f9fa;'>
                                                    <a href='{$profileUrl}' style='display: inline-block; padding: 16px 40px; font-size: 14px; color: #333333; text-decoration: none; font-weight: 700; letter-spacing: 1px; text-transform: uppercase;'>
                                                        Moje rezervacije
                                                    </a>
                                                </td>
                                            </tr>
                                        </table>

                                        <div style='margin-top: 40px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px;'>
                                            <p style='font-size: 11px; color: rgba(255,255,255,0.3);'>
                                                Molimo sačuvajte kod rezervacije. Za otkazivanje ili izmene, posetite stranicu vaših rezervacija.
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
}
