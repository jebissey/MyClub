<?php

namespace app\helpers;

use InvalidArgumentException;

class Email
{
    public static function send($emailFrom, $emailTo, $subject, $body, $cc = null, $bcc = null, $isHtml = false): bool
    {
        if (!filter_var($emailFrom, FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException("Invalid from email: $emailFrom");
        if (!filter_var($emailTo, FILTER_VALIDATE_EMAIL))   throw new InvalidArgumentException("Invalid to email: $emailTo");
        $headers = array(
            'From' => $emailFrom,
            'Reply-To' => $emailFrom,
            'Return-Path' => $emailFrom,
            'X-Mailer' => 'PHP/' . phpversion(),
            'Content-Type' => $isHtml ? 'text/html; charset=UTF-8' : 'text/plain; charset=UTF-8'
        );
        if ($cc) {
            $ccList = is_array($cc) ? $cc : explode(',', $cc);
            $ccList = array_filter(array_map('trim', $ccList), function ($email) {
                return filter_var($email, FILTER_VALIDATE_EMAIL) ?: '';
            });
            if ($ccList) $headers['Cc'] = implode(', ', $ccList);
        }
        if ($bcc) {
            $bccList = is_array($bcc) ? $bcc : explode(',', $bcc);
            $bccList = array_filter(array_map('trim', $bccList), function ($email) {
                return filter_var($email, FILTER_VALIDATE_EMAIL) ?: '';
            });
            if ($bccList) $headers['Bcc'] = implode(', ', $bccList);
        }
        $headerString = '';
        foreach ($headers as $key => $value) {
            $headerString .= $key . ': ' . $value . "\r\n";
        }
        return mail($emailTo, $subject, $body, $headerString);
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
        $body .= "IP : " . $_SERVER['REMOTE_ADDR'] ?? 'Inconnue';
        return Email::send($email, $adminEmail, $subject, $body);
    }
}
