<?php
require '../lib/Email.php';
require '../data/Person.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    if ($email) {
        $person = new Person();
        $personFound = $person->getByEmail($email);

        if ($personFound) {
            if ($personFound['TokenCreatedAt'] === null || (new DateTime($personFound['TokenCreatedAt']))->diff(new DateTime())->h >= 1) {
                $token = bin2hex(openssl_random_pseudo_bytes(32));
                $tokenCreatedAt = (new DateTime())->format('Y-m-d H:i:s');

                $person->setByEmail($email, array(
                    'token' => $token,
                    'tokenCreatedAt' => $tokenCreatedAt,
                ));

                $resetLink = "http://cihy21.free.fr/MyClub/SignIn/SignInResetPassword.php?token=" . $token;
                $to = $email;
                $subject = "Réinitialisation du mot de passe";
                $message = "Cliquez sur ce lien pour réinitialiser votre mot de passe : " . $resetLink;
                $from = "b.nw.invitation@free.fr";

                $mail = new Email();
                $result = $mail->Send($to, $subject, $message, $from);
                if ($result === true) {
                    echo "<h1>Un email a été envoyé à votre adresse.</h1>";
                } else {
                    echo "Une erreur est survenue lors de l'envoi de l'email. ($result)";
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
?>

