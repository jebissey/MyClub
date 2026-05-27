<?php

declare(strict_types=1);

namespace app\modules\Common\services;

use Brevo\Brevo;
use Brevo\TransactionalEmails\Requests\SendTransacEmailRequest;
use Brevo\TransactionalEmails\Types\SendTransacEmailRequestSender;
use Brevo\TransactionalEmails\Types\SendTransacEmailRequestToItem;
use Brevo\TransactionalEmails\Types\SendTransacEmailRequestCcItem;
use Brevo\TransactionalEmails\Types\SendTransacEmailRequestBccItem;
use Brevo\TransactionalEmails\Types\SendTransacEmailRequestReplyTo;
use Brevo\Exceptions\BrevoApiException;
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
    private const MAILJET_BATCH_SIZE = 50;

    public function __construct(
        private readonly ?SmtpConfigProviderInterface $configProvider,
        private readonly ?EmailQuotaTrackerInterface  $quotaTracker,
    ) {}

    public function getSmtpConfig(): ?SmtpConfig
    {
        return $this->configProvider?->get();
    }

    public function send(EmailMessage $message): bool
    {
        $count  = 1 + count($message->cc) + count($message->bcc);
        $config = $this->configProvider?->get();

        if ($config !== null && $this->quotaTracker !== null) {
            $dailySent   = $this->quotaTracker->getDailySent();
            $monthlySent = $this->quotaTracker->getMonthlySent();

            $dailyOk   = $config->dailyLimit   === 0 || ($dailySent   + $count) <= $config->dailyLimit;
            $monthlyOk = $config->monthlyLimit === 0 || ($monthlySent + $count) <= $config->monthlyLimit;

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
            'brevo'   => $this->sendWithBrevoApi($message, $config),
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
            'X-Mailer'     => 'PHP/' . phpversion(),
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

            $mail->setFrom($config->from, 'MyClub');

            if ($message->replyTo !== null) {
                $mail->addReplyTo($message->replyTo);
            }

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
        $baseMsg = [
            'From'     => $from,
            'To'       => [['Email' => $message->to]],
            'Subject'  => $message->subject,
            'HTMLPart' => $message->isHtml  ? $message->body : null,
            'TextPart' => !$message->isHtml ? $message->body : strip_tags($message->body),
        ];
        if ($message->replyTo !== null) {
            $baseMsg['ReplyTo'] = ['Email' => $message->replyTo];
        }
        if ($message->cc !== []) {
            $baseMsg['Cc'] = array_map(static fn($e) => ['Email' => $e], $message->cc);
        }
        $baseMsg = array_filter($baseMsg, static fn($v) => $v !== null);
        $messages = [$baseMsg];
        foreach ($message->bcc as $bccEmail) {
            $bccMsg        = $baseMsg;
            $bccMsg['To']  = [['Email' => $bccEmail]];
            $messages[] = $bccMsg;
        }
        foreach (array_chunk($messages, self::MAILJET_BATCH_SIZE) as $chunk) {
            $response = $mj->post(MailjetResources::$Email, ['body' => ['Messages' => $chunk]]);

            if (!$response->success()) {
                throw new EmailException(sprintf(
                    'Mailjet sending failed (HTTP %d): %s',
                    $response->getStatus(),
                    json_encode($response->getData(), JSON_UNESCAPED_UNICODE)
                ));
            }
        }

        return true;
    }

    private function sendWithBrevoApi(EmailMessage $message, SmtpConfig $config): bool
    {
        if ($config->brevoApikey === '') {
            throw new InvalidArgumentException('Brevo credentials are not configured (brevoApikey is empty).');
        }
        $senderEmail = $config->brevoSenderEmail !== '' ? $config->brevoSenderEmail : $message->from;
        $params = [
            'subject' => $message->subject,
            'sender'  => new SendTransacEmailRequestSender([
                'name'  => 'MyClub',
                'email' => $senderEmail,
            ]),
            'to' => [new SendTransacEmailRequestToItem(['email' => $message->to])],
            'trackClicks'  => false,
            'trackOpens'   => false,
        ];
        if ($message->isHtml) {
            $params['htmlContent'] = $message->body;
            $params['textContent'] = strip_tags($message->body);
        } else {
            $params['textContent'] = $message->body;
        }
        if ($message->replyTo !== null) {
            $params['replyTo'] = new SendTransacEmailRequestReplyTo(['email' => $message->replyTo]);
        }
        if ($message->cc !== []) {
            $params['cc'] = array_map(
                static fn($e) => new SendTransacEmailRequestCcItem(['email' => $e]),
                $message->cc
            );
        }
        if ($message->bcc !== []) {
            $params['bcc'] = array_map(
                static fn($e) => new SendTransacEmailRequestBccItem(['email' => $e]),
                $message->bcc
            );
        }
        try {
            (new Brevo($config->brevoApikey))
                ->transactionalEmails
                ->sendTransacEmail(new SendTransacEmailRequest($params));
            return true;
        } catch (BrevoApiException $e) {
            throw new EmailException(
                sprintf('Brevo sending failed (HTTP %d): %s', $e->getCode(), $e->getMessage()),
                previous: $e
            );
        }
    }
}
