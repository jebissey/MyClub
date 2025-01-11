<?php
require_once __DIR__. '/../../includes/tinyHeader.php';

require __DIR__ . '/../../lib/Database/Tables/Person.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    if ($email) {
        $person = new Person();
        $personFound = $person->getByEmail($email);

        if ($personFound) {
            if ($personFound['TokenCreatedAt'] === null || (new DateTime($personFound['TokenCreatedAt']))->diff(new DateTime())->h >= 1) {
                $token = bin2hex(openssl_random_pseudo_bytes(32));
                $tokenCreatedAt = (new DateTime())->format('Y-m-d H:i:s');

                $result = $person->setById($personFound['Id'], [
                    'Token' => $token,
                    'TokenCreatedAt' => $tokenCreatedAt,
                ]);

                $resetLink = "https://myclub.alwaysdata.net/b.nw/areas/SignIn/ResetPassword.php?token=" . $token;
                $to = $email;
                $subject = "Initialisation du mot de passe";
                $message = "Cliquez sur ce lien pour initialiser votre mot de passe : " . $resetLink;

                if (mail($to, $subject, $message)) {
                    echo '<h1>Un email a été envoyé à votre adresse.</h1>';
                } else {
                    echo "Une erreur est survenue lors de l'envoi de l'email.";
                }
            } else {
                echo "<h1>Un email de réinitialisation a déjà été envoyé à " . substr($user['TokenCreatedAt'],10) . ". Il est valide pendant 1 heure.</h1>";
            }
        } else {
            echo "<h1>Aucun utilisateur trouvé avec cette adresse email.</h1>";
        }
    } else {
        echo "<h1>Veuillez entrer une adresse email valide.</h1>";
    }
} else {
    echo "<h1>Méthode non autorisée.</h1>";
}

require_once __DIR__ . '/../../includes/tinyFooter.php';
?>

