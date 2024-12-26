<?php
require '../lib/Email.php';

try {
    $pdo = new PDO("sqlite:../data/MyClub.sqlite");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die("Cannot open the database: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    if ($email) {
        $stmt = $pdo->prepare("SELECT FirstName, LastName, TokenCreatedAt FROM Person WHERE Email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            if ($user['TokenCreatedAt'] === null || (new DateTime($user['TokenCreatedAt']))->diff(new DateTime())->h >= 1 || true) {
                $token = bin2hex(openssl_random_pseudo_bytes(32));
                $tokenCreatedAt = (new DateTime())->format('Y-m-d H:i:s');

                $stmt = $pdo->prepare('UPDATE Person SET Token = :token, TokenCreatedAt = :tokenCreatedAt WHERE Email = :email');
                $stmt->execute([
                    'email' => $email,
                    'token' => $token,
                    'tokenCreatedAt' => $tokenCreatedAt,
                ]);

                $resetLink = "http://cihy21.free.fr/MyClub/SignIn/SignInResetPassword.php?token=" . $token;
                $to = $email;
                $subject = "Réinitialisation du mot de passe";
                $message = "Cliquez sur ce lien pour réinitialiser votre mot de passe : " . $resetLink;
                $from = "b.nw.invitation@free.fr";

                $mail = new Email();
                $result = $mail->Send($to, $subject, $message, $from);
                if ($result === true) {
                    echo "Un email a été envoyé à votre adresse.";
                } else {
                    echo "Une erreur est survenue lors de l'envoi de l'email. ($result)";
                }
            } else {
                echo "Un email de réinitialisation a déjà été envoyé à " . substr($user['TokenCreatedAt'],10) . ". Il est valide pendant 1 heure.";
            }
        } else {
            echo "Aucun utilisateur trouvé avec cette adresse email.";
        }
    } else {
        echo "Veuillez entrer une adresse email valide.";
    }
} else {
    echo "Méthode non autorisée.";
}
?>

