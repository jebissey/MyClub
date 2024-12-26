<?php
require 'SiteData.php';

require 'src/Exception.php';
require 'src/PHPMailer.php';
require 'src/SMTP.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Email {

    private static $userName = '';
    private static $password = '';

    function send($to, $subject, $message, $from) {
        if(self::$userName === ''){
            $siteData = new SiteData();
            self::$userName = $siteData->Get('SMTPuserName');
            self::$password = $siteData->Get('SMTPpassword');
            if (!self::$userName || !self::$password) {
                die("Il faut définir le compte d'envoi SMTP.");
            }
        }

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.free.fr';
            $mail->Port = 587;
            $mail->SMTPAuth = true;
            $mail->SMTPSecure = false;
            $mail->SMTPAutoTLS = false;
            $mail->Username = self::$userName;
            $mail->Password = self::$password;
            $mail->setFrom($from);
            $mail->addAddress($to);
            $mail->CharSet = 'UTF-8';
            $mail->SMTPDebug = 2;
            
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $message;

            return $mail->send();
        } catch (Exception $e) {
            return "Erreur d'envoi: " . $mail->ErrorInfo;
        }
    }
}
?>