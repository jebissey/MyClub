<?php

declare(strict_types=1);

namespace app\services;

use InvalidArgumentException;
use PHPMailer\PHPMailer\PHPMailer;
use Throwable;

use app\enums\ApplicationError;
use app\helpers\Application;
use app\models\DataHelper;

class EmailService
{
    public function __construct(private Application $application ,private DataHelper $dataHelper) {}

    public function send(
        string $emailFrom,
        string $emailTo,
        string $subject,
        string $body,
        $cc = null,
        $bcc = null,
        bool $isHtml = false
    ): bool {
        if (!filter_var($emailFrom, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid from email: $emailFrom");
        }
        if (!filter_var($emailTo, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid to email: $emailTo");
        }
        $metadata = $this->dataHelper->get('Metadata', ['Id' => 1], 'SendEmailAddress, SendEmailPassword, SendEmailHost');
        $smtpUser = $metadata->SendEmailAddress ?? null;
        $smtpPass = $metadata->SendEmailPassword ?? null;
        $smtpHost = $metadata->SendEmailHost ?? null;

        if ($smtpUser && $smtpPass  && $smtpHost) {
            return $this->sendWithPHPMailer($emailTo, $subject, $body, $cc, $bcc, $isHtml, $smtpUser, $smtpPass, $smtpHost);
        }
        return $this->sendWithNativeMail($emailFrom, $emailTo, $subject, $body, $cc, $bcc, $isHtml);
    }

    public function sendContactEmail($adminEmail, $name, $email, $message): bool
    {
        $subject = 'Nouveau message de contact - ' . $name;
        $body = "Nouveau message de contact reçu :\n\n";
        $body .= "Nom & Prénom : " . $name . "\n";
        $body .= "Email : " . $email . "\n";
        $body .= "Message :\n" . $message . "\n\n";
        $body .= "---\n";
        $body .= "Envoyé le : " . date('d/m/Y à H:i') . "\n";
        $body .= "IP : " . ($_SERVER['REMOTE_ADDR'] ?? 'Inconnue');

        return $this->send($email, $adminEmail, $subject, $body);
    }

    #region Private functions
    private function sendWithNativeMail(
        string $emailFrom,
        string $emailTo,
        string $subject,
        string $body,
        $cc = null,
        $bcc = null,
        bool $isHtml = false
    ): bool {
        $headers = [
            'From' => $emailFrom,
            'Reply-To' => $emailFrom,
            'Return-Path' => $emailFrom,
            'X-Mailer' => 'PHP/' . phpversion(),
            'Content-Type' => $isHtml ? 'text/html; charset=UTF-8' : 'text/plain; charset=UTF-8'
        ];
        if ($cc) {
            $ccList = is_array($cc) ? $cc : explode(',', $cc);
            $ccList = array_filter(array_map('trim', $ccList), fn($email) => filter_var($email, FILTER_VALIDATE_EMAIL));
            if ($ccList) $headers['Cc'] = implode(', ', $ccList);
        }
        if ($bcc) {
            $bccList = is_array($bcc) ? $bcc : explode(',', $bcc);
            $bccList = array_filter(array_map('trim', $bccList), fn($email) => filter_var($email, FILTER_VALIDATE_EMAIL));
            if ($bccList) $headers['Bcc'] = implode(', ', $bccList);
        }
        $headerString = '';
        foreach ($headers as $key => $value) {
            $headerString .= $key . ': ' . $value . "\r\n";
        }
        return mail($emailTo, $subject, $body, $headerString);
    }

    private function sendWithPHPMailer(
        string $emailTo,
        string $subject,
        string $body,
        $cc,
        $bcc,
        bool $isHtml,
        string $smtpUser,
        string $smtpPass,
        string $smtpHost
    ): bool {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = $smtpHost;
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtpUser;
            $mail->Password   = $smtpPass;
            $mail->AuthType   = 'LOGIN';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';
            $mail->setFrom($smtpUser, 'No Reply');
            $mail->addAddress($emailTo);

//$mail->SMTPDebug  = 2;
//$mail->Debugoutput = 'error_log';

            if ($cc) {
                $ccList = is_array($cc) ? $cc : explode(',', $cc);
                foreach ($ccList as $email) {
                    $email = trim($email);
                    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $mail->addCC($email);
                    }
                }
            }
            if ($bcc) {
                $bccList = is_array($bcc) ? $bcc : explode(',', $bcc);
                foreach ($bccList as $email) {
                    $email = trim($email);
                    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $mail->addBCC($email);
                    }
                }
            }
            $mail->isHTML($isHtml);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->AltBody = strip_tags($body);

            $mail->send();
            return true;
        } catch (Throwable $e) {
            $this->application->getErrorManager()->raise(ApplicationError::Error, 'PHPMailer error: ' . $mail->ErrorInfo . "{$e->getMessage()} in {$e->getFile()}:{$e->getLine()}");
            return false;
        }
    }

}
