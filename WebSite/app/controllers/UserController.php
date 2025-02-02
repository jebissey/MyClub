<?php
namespace app\controllers;

use DateTime;
use flight\Engine;
use PDO;
use app\helpers\Client; 

class UserController extends BaseController {
    private PDO $pdoForLog;

    public function __construct(PDO $pdo, Engine $flight) {
        parent::__construct($pdo, $flight);
        $this->pdoForLog = \app\helpers\database\Database::getInstance()->getPdoForLog();
    }

    public function resetPassword($encodedEmail) {
        $email = urldecode($encodedEmail);
        
        //echo "<h1>Reset password for email: " . $email . "</h1>";

        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $stmt = $this->pdo->prepare('SELECT * FROM "Person" WHERE Email = ?');
            $stmt->execute([$email]);
            $person = $stmt->fetch(PDO::FETCH_ASSOC);
    
            if ($person) {
                if ($person['TokenCreatedAt'] === null || (new DateTime($person['TokenCreatedAt']))->diff(new DateTime())->h >= 1) {
                    $token = bin2hex(openssl_random_pseudo_bytes(32));
                    $tokenCreatedAt = (new DateTime())->format('Y-m-d H:i:s');
    
                    $stmt = $this->pdo->prepare('UPDATE Person SET Token = ?, TokenCreatedAt = ? WHERE Id = ?');
                    $stmt->execute([$token, $tokenCreatedAt, $person['Id']]);
    
                    $newUrl = preg_replace('/resetPassword.*$/', 'setPassword', $this->flight->request()->url);
                    $resetLink = 'https://' . $newUrl . '/' . $token;
                    $to = $email;
                    $subject = "Initialisation du mot de passe";
                    $message = "Cliquez sur ce lien pour initialiser votre mot de passe : " . $resetLink;
    
                    if (mail($to, $subject, $message)) {
                        $this->message('Un email a été envoyé pour réinitialiser votre mot de passe');
                    } else {
                        $this->message("Une erreur est survenue lors de l'envoi de l'email");
                    }
                } else {
                    $this->message( "Un email de réinitialisation a déjà été envoyé à " . substr($person['TokenCreatedAt'],10) . ". Il est valide pendant 1 heure.");
                }
            } else {
                $this->error480($email);
            }
        } else {
            $this->error481($email);
        }
    }

    public function log($code ='', $message ='') {
        $email = filter_var($_SESSION['user'] ?? '', FILTER_VALIDATE_EMAIL);
        $client = new Client();
        $stmt = $this->pdoForLog->prepare('INSERT INTO Log(IpAddress, Referer, Os, Browser, ScreenResolution, Type, Uri, Token, Who, Code, Message) 
        VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ? ,?)');
        $stmt->execute([$client->getIp(), $client->getReferer(), $client->getOs(), $client->getBrowser(), $client->getScreenResolution(), $client->getType(), $client->getUri(), $client->getToken(), $email, $code, $message]);
    }

    public function message($message, $timeout = 3000) {
        $this->error(200, $message, $timeout);
    }

    public function error403() {
        $this->error(403, 'Not allowed');
    }

    public function error404() {
        $this->error(404, 'Page not found' );
    }
    
    public function error480($email, $timeout = 10000) {
        $this->error(480, "Unknown user with this $email", $timeout);
    }
    
    public function error481($email) {
        $this->error(481, "Invalid email address: $email");
    }

    public function error499($table, $id, $file, $line) {
        $this->error(499, "Record $id not found in table $table in file $file in line $line");
    }


    private function error($code, $message, $timeout = 1000) {
        $this->flight->setData('code', $code);
        $this->flight->setData('message', $message);

        echo "<h1>$code</h1><h2>$message</h2>";
        ?>
        <script>
            setTimeout(function() {
                window.location.href = '/';
            }, <?php echo $timeout; ?>);
        </script>
        <?php
    }
}