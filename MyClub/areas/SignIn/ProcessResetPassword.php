<?php
require __DIR__ . '/../../lib/Database/Tables/Person.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'];
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];

    if ($password !== $confirmPassword) {
        die("<h1>Les mots de passe ne correspondent pas.</h1>");
    }

    $person = new Person();
    $personFound = $person->getByToken($token);
    
    if ($personFound) {
        if ($personFound['TokenCreatedAt'] === null || (new DateTime($personFound['TokenCreatedAt']))->diff(new DateTime())->h >= 1 ) {
            die("<h1>Lien de réinitialisation expiré ou invalide.</h1>");
        }

        $person->setById($personFound['Id'], array(
            'Password' => password_hash($password, PASSWORD_DEFAULT),
            'Token' => null,
            'TokenCreatedAt' => null,
        ));
        echo "<h1>Le mot de passe a été mis à jour.</h1>";
    } else {
        echo "<h1>Lien de réinitialisation invalide.</h1>";
    }
} else {
    echo "Méthode non autorisée.";
}

?>
