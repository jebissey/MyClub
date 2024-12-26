<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'];

    if ($email && strlen($password) >= 6) {
        // Exemple : Enregistrer ou vérifier les informations
        echo "Adresse email : " . htmlspecialchars($email) . "<br>";
        echo "Mot de passe (hashé) : " . password_hash($password, PASSWORD_DEFAULT);
    } else {
        echo "Les données soumises ne sont pas valides.";
    }
} else {
    echo "Méthode non autorisée.";
}
?>
