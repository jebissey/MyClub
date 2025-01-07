
<?php

class PasswordManager {
    private const HASH_ALGORITHM = PASSWORD_DEFAULT;
    private const HASH_OPTIONS = ['cost' => 12 ];

    public static function hashPassword(string $password): string {
        return password_hash(
            $password,
            self::HASH_ALGORITHM,
            self::HASH_OPTIONS
        );
    }

    public static function verifyPassword(string $password, string $hashedPassword): bool {
        return password_verify($password, $hashedPassword);
    }
}

// Exemple d'utilisation :

// 1. Lors de l'enregistrement du mot de passe
$password = "admin_";
$hashedPassword = PasswordManager::hashPassword($password);
// Stocker $hashedPassword dans la base de données
echo "<p>password = $password</p>";
echo "<p>hashedPassword = $hashedPassword</p>";

// 2. Lors de la vérification du mot de passe
$userInputPassword = "admin_";
$storedHash = $hashedPassword; // Récupéré depuis la base de données
$isValid = PasswordManager::verifyPassword($userInputPassword, $storedHash);

if ($isValid) {
    echo "Mot de passe correct !";
} else {
    echo "Mot de passe incorrect !";
}
?>



