<?php

declare(strict_types=1);

namespace app\modules\Common\services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use app\modules\Common\valueObjects\EmailMessage;
use app\modules\Common\valueObjects\SmtpConfig;
use app\exceptions\EmailException;

final class EmailService
{
    public function __construct(
        private readonly ?DatabaseSmtpConfigProvider $configProvider,
        private readonly ?JsonEmailQuotaTracker $quotaTracker,
    ) {
    }

    public function getSmtpConfig(): ?SmtpConfig
    {
        return $this->configProvider?->get();
    }

    public function send(EmailMessage $message): bool
    {
        $count = 1 + count($message->cc) + count($message->bcc);
        $config = $this->configProvider?->get();

        if ($config !== null && $this->quotaTracker !== null) {
            $dailySent = $this->quotaTracker->getDailySent();
            $monthlySent = $this->quotaTracker->getMonthlySent();

            $dailyOk = $config->dailyLimit === 0
                || ($dailySent + $count) <= $config->dailyLimit;
            $monthlyOk = $config->monthlyLimit === 0
                || ($monthlySent + $count) <= $config->monthlyLimit;

            if (!$dailyOk || !$monthlyOk) {
                if ($count === 1) {
                    return $this->sendWithNativeMail($message);
                }

                $which = !$dailyOk
                    ? "daily ({$config->dailyLimit})"
                    : "monthly ({$config->monthlyLimit})";

                throw new EmailException(
                    "Cannot send to {$count} recipient(s): {$which} quota reached "
                    . "({$config->method}). Already sent today: {$dailySent}, this month: {$monthlySent}."
                );
            }
        }

        $sent = match ($config?->method) {
            'smtp' => $this->sendWithPHPMailer($message, $config),
            default => $this->sendWithNativeMail($message),
        };

        if ($sent && $this->quotaTracker !== null && $config !== null) {
            $this->quotaTracker->increment($count);
        }

        return $sent;
    }

    #Private functions
    private function sendWithNativeMail(EmailMessage $message): bool
    {
        $headers = [
            'From' => $message->from,
            'X-Mailer' => 'PHP/' . phpversion(),
            'Content-Type' => $message->isHtml
                ? 'text/html; charset=UTF-8'
                : 'text/plain; charset=UTF-8',
        ];

        if ($message->replyTo !== null) {
            $headers['Reply-To'] = $message->replyTo;
        }

        if ($message->cc !== []) {
            $headers['Cc'] = implode(', ', $message->cc);
        }

        if ($message->bcc !== []) {
            $headers['Bcc'] = implode(', ', $message->bcc);
        }

        $headerString = implode("\r\n", array_map(
            static fn($k, $v) => "{$k}: {$v}",
            array_keys($headers),
            $headers
        )) . "\r\n";

        return mail($message->to, $message->subject, $message->body, $headerString);
    }

    private function sendWithPHPMailer(EmailMessage $message, SmtpConfig $config): bool
    {
        $mail = new PHPMailer(true);

        $debugEnabled = filter_var(getenv('MYCLUB_SMTP_DEBUG'), FILTER_VALIDATE_BOOLEAN);

        try {
            $mail->isSMTP();
            $mail->Host = $config->host;
            $mail->SMTPAuth = true;
            $mail->Username = $config->username;
            $mail->Password = $config->password;
            $mail->Port = $config->port;
            $mail->Timeout = 10;
            $mail->CharSet = 'UTF-8';
            $mail->SMTPDebug = $debugEnabled ? 2 : 0;

            $mail->Debugoutput = static function (string $message, int $level): void {
                error_log("[PHPMailer SMTP][{$level}] " . trim($message));
            };

            match ($config->encryption) {
                'tls' => $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS,
                'ssl' => $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS,
                default => null,
            };

            $mail->setFrom($config->from, 'MyClub');

            if ($message->replyTo !== null) {
                $mail->addReplyTo($message->replyTo);
            }

            $mail->addAddress($message->to);

            foreach ($message->cc as $email) {
                $mail->addCC($email);
            }

            foreach ($message->bcc as $email) {
                $mail->addBCC($email);
            }

            $mail->isHTML($message->isHtml);
            $mail->Subject = $message->subject;
            $mail->Body = $message->body;
            $mail->AltBody = strip_tags($message->body);

            $mail->send();

            if ($debugEnabled) {
                error_log(
                    "[PHPMailer SMTP] Successfully sent email"
                    . " | Host: {$config->host}"
                    . " | Port: {$config->port}"
                    . " | From: {$config->from}"
                    . " | To: {$message->to}"
                );
            }

            return true;
        } catch (PHPMailerException $e) {
            $error = $mail->ErrorInfo ?: $e->getMessage();

            error_log(
                "[PHPMailer SMTP] Sending failed"
                . " | Host: {$config->host}"
                . " | Port: {$config->port}"
                . " | Encryption: {$config->encryption}"
                . " | Username: {$config->username}"
                . " | From: {$config->from}"
                . " | To: {$message->to}"
                . " | Error: {$error}"
            );

            throw new EmailException(
                'SMTP sending failed: ' . $error,
                previous: $e
            );
        }
    }
}
