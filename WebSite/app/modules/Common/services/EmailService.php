<?php

declare(strict_types=1);

namespace app\modules\Common\services;

use Mailjet\Client as MailjetClient;
use Mailjet\Resources as MailjetResources;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

use app\interfaces\SmtpConfigProviderInterface;
use app\interfaces\EmailQuotaTrackerInterface;
use app\valueObjects\EmailMessage;
use app\valueObjects\SmtpConfig;
use app\exceptions\EmailException;
use InvalidArgumentException;

final class EmailService
{
    public function __construct(
        private readonly ?SmtpConfigProviderInterface $configProvider,
        private readonly ?EmailQuotaTrackerInterface  $quotaTracker,
    ) {}

    public function send(EmailMessage $message): bool
    {
        $count  = 1 + count($message->cc) + count($message->bcc);
        $config = $this->configProvider?->get();

        if ($config !== null && $this->quotaTracker !== null) {
            $dailySent   = $this->quotaTracker->getDailySent();
            $monthlySent = $this->quotaTracker->getMonthlySent();

            $dailyOk   = $config->dailyLimit   === null || ($dailySent   + $count) <= $config->dailyLimit;
            $monthlyOk = $config->monthlyLimit === null || ($monthlySent + $count) <= $config->monthlyLimit;

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
            'smtp'    => $this->sendWithPHPMailer($message, $config),
            'mailjet' => $this->sendWithMailjet($message, $config),
            default   => $this->sendWithNativeMail($message),
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
            'From'         => $message->from,
            'Reply-To'     => $message->replyTo ?? $message->from,
            'X-Mailer'     => 'PHP/' . phpversion(),
            'Content-Type' => $message->isHtml
                ? 'text/html; charset=UTF-8'
                : 'text/plain; charset=UTF-8',
        ];

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

        try {
            $mail->isSMTP();
            $mail->Host     = $config->host;
            $mail->SMTPAuth = true;
            $mail->Username = $config->username;
            $mail->Password = $config->password;
            $mail->Port     = $config->port;
            $mail->Timeout  = 10;
            $mail->CharSet  = 'UTF-8';

            match ($config->encryption) {
                'tls'   => $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS,
                'ssl'   => $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS,
                default => null,
            };

            $mail->setFrom($config->username, 'MyClub');
            $mail->addReplyTo($message->replyTo ?? $config->username);
            $mail->addAddress($message->to);

            foreach ($message->cc  as $email) {
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
                'SMTP sending failed: ' . $mail->ErrorInfo,
                previous: $e
            );
        }
    }

    private function sendWithMailjet(EmailMessage $message, SmtpConfig $config): bool
    {
        if ($config->apiKey === '' || $config->apiSecret === '') {
            throw new InvalidArgumentException(
                'Mailjet credentials are not configured (api_key or api_secret is empty).'
            );
        }
        $mj = new MailjetClient(
            $config->apiKey,
            $config->apiSecret,
            true,
            ['version' => 'v3.1']
        );
        $from = [
            'Email' => $config->senderEmail !== '' ? $config->senderEmail : $message->from,
            'Name'  => 'MyClub',
        ];
        $msg = [
            'From'     => $from,
            'To'       => [['Email' => $message->to]],
            'Subject'  => $message->subject,
            'HTMLPart' => $message->isHtml  ? $message->body              : null,
            'TextPart' => !$message->isHtml ? $message->body              : strip_tags($message->body),
        ];
        if ($message->replyTo !== null) {
            $msg['ReplyTo'] = ['Email' => $message->replyTo];
        }
        if ($message->cc !== []) {
            $msg['Cc'] = array_map(static fn($e) => ['Email' => $e], $message->cc);
        }
        if ($message->bcc !== []) {
            $msg['Bcc'] = array_map(static fn($e) => ['Email' => $e], $message->bcc);
        }

        $msg = array_filter($msg, static fn($v) => $v !== null);
        $response = $mj->post(MailjetResources::$Email, ['body' => ['Messages' => [$msg]]]);
        if (!$response->success()) {
            throw new EmailException(sprintf(
                'Mailjet sending failed (HTTP %d): %s',
                $response->getStatus(),
                json_encode($response->getData(), JSON_UNESCAPED_UNICODE)
            ));
        }
        return true;
    }
}
