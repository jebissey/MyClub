<?php

declare(strict_types=1);

namespace app\modules\Common\services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

use app\interfaces\SmtpConfigProviderInterface;
use app\valueObjects\EmailMessage;
use app\valueObjects\SmtpConfig;
use app\exceptions\EmailException;

final class EmailService
{
    public function __construct(
        private readonly ?SmtpConfigProviderInterface $configProvider = null
    ) {}

    public function send(EmailMessage $message): bool
    {
        $config = $this->configProvider?->get();

        if ($config !== null) {
            return $this->sendWithPHPMailer($message, $config);
        }

        return $this->sendWithNativeMail($message);
    }

    #Private functions
    private function sendWithNativeMail(EmailMessage $message): bool
    {
        $headers = [
            'From' => $message->from,
            'Reply-To' => $message->replyTo ?? $message->from,
            'X-Mailer' => 'PHP/' . phpversion(),
            'Content-Type' => $message->isHtml
                ? 'text/html; charset=UTF-8'
                : 'text/plain; charset=UTF-8'
        ];

        if ($message->cc !== []) {
            $headers['Cc'] = implode(', ', $message->cc);
        }

        if ($message->bcc !== []) {
            $headers['Bcc'] = implode(', ', $message->bcc);
        }

        $headerString = '';

        foreach ($headers as $key => $value) {
            $headerString .= "{$key}: {$value}\r\n";
        }

        return mail(
            $message->to,
            $message->subject,
            $message->body,
            $headerString
        );
    }

    private function sendWithPHPMailer(
        EmailMessage $message,
        SmtpConfig $config
    ): bool {

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = $config->host;
            $mail->SMTPAuth   = true;
            $mail->Username   = $config->username;
            $mail->Password   = $config->password;
            $mail->Port       = $config->port;
            $mail->Timeout    = 10;
            $mail->CharSet    = 'UTF-8';

            match ($config->encryption) {
                'tls' => $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS,
                'ssl' => $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS,
                default => null,
            };

            // 🔐 IMPORTANT : always use authenticated sender
            $mail->setFrom(
                $config->username,
                'MyClub'
            );

            $mail->addAddress($message->to);

            // Reply-To (utilisateur par ex)
            if ($message->replyTo !== null) {
                $mail->addReplyTo($message->replyTo);
            }

            foreach ($message->cc as $email) {
                $mail->addCC($email);
            }

            foreach ($message->bcc as $email) {
                $mail->addBCC($email);
            }

            $mail->isHTML($message->isHtml);
            $mail->Subject = $message->subject;
            $mail->Body    = $message->body;
            $mail->AltBody = strip_tags($message->body);

            $mail->send();

            return true;
        } catch (PHPMailerException $e) {
            throw new EmailException(
                'Email sending failed: ' . $mail->ErrorInfo,
                previous: $e
            );
        }
    }
}
