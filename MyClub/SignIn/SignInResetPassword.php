<?php
try {
    $pdo = new PDO("sqlite:../data/MyClub.sqlite");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die("Cannot open the database: " . $e->getMessage());
}

$token = $_GET['token'];
if (empty($token)) {
    die("Lien invalide.");
}

// Vérifiez si le jeton existe dans la base de données
$stmt = $pdo->prepare("SELECT Email FROM Person WHERE Token = :token AND TokenCreatedAt >= datetime('now', '-1 hour')");
$stmt->execute(['token' => $token]);
$reset = $stmt->fetch();

if (!$reset) {
    die("Lien de réinitialisation expiré ou invalide.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialisation du mot de passe</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2>Réinitialisation du mot de passe</h2>
        <form action="SignInProcessResetPassword.php" method="POST">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
            <div class="mb-3">
                <label for="password" class="form-label">Nouveau mot de passe</label>
                <input type="password" class="form-control" id="password" name="password" required minlength="6">
            </div>
            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirmer le mot de passe</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="6">
            </div>
            <button type="submit" class="btn btn-primary">Réinitialiser le mot de passe</button>
        </form>
    </div>
</body>
</html>

<?php
?>
