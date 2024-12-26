<?php
try {
    $pdo = new PDO("sqlite:../data/MyClub.sqlite");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die("Cannot open the database: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'];
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];

    if ($password !== $confirmPassword) {
        die("Les mots de passe ne correspondent pas.");
    }

    // Vérifiez si le jeton existe
    $stmt = $pdo->prepare('SELECT Email FROM Person WHERE token = :token');
    $stmt->execute(['token' => $token]);
    $reset = $stmt->fetch();

    if ($reset) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare('UPDATE Person SET Password = :password WHERE Email = :email');
        $stmt->execute(['password' => $hashedPassword, 'email' => $reset['email']]);

        $stmt = $pdo->prepare('UPDATE Person SET Token = null, TokenCreatedAt = null WHERE email = :email');
        $stmt->execute(['email' => $reset['email']]);

        echo "Votre mot de passe a été mis à jour.";
    } else {
        echo "Lien de réinitialisation invalide.";
    }
} else {
    echo "Méthode non autorisée.";
}

?>
