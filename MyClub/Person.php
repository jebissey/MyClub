<?php
require 'includes/header.php';
echo "<main>\n";

$user = $_SESSION['user'] ?? '';
if($user != ''){

    require_once  __DIR__ . '/lib/Database/Tables/Debug.php';
    (new Debug())->set("user=$user");


} else {
   // require_once  __DIR__ . '/lib/SignIn/Form.php';

?>
    <div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="loginModalLabel">Connexion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="loginForm" action="lib/SignIn/Check.php" method="POST" novalidate>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="email" class="form-label">Adresse email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                            <div class="invalid-feedback">Veuillez entrer une adresse email valide.</div>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Mot de passe</label>
                            <input type="password" class="form-control" id="password" name="password" required minlength="6">
                            <div class="invalid-feedback">Le mot de passe doit contenir au moins 6 caractères.</div>
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-link" data-bs-toggle="modal" data-bs-target="#forgotPasswordModal" data-bs-dismiss="modal">
                                Mot de passe oublié ?
                            </button>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                        <button type="submit" class="btn btn-primary">Se connecter</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="forgotPasswordModal" tabindex="-1" aria-labelledby="forgotPasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="forgotPasswordModalLabel">Réinitialisation du mot de passe</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="forgotPasswordForm" action="lib/SignIn/ForgotPassword.php" method="POST" novalidate>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="forgotEmail" class="form-label">Adresse email</label>
                            <input type="email" class="form-control" id="forgotEmail" name="email" required>
                            <div class="invalid-feedback">Veuillez entrer une adresse email valide.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                        <button type="submit" class="btn btn-primary">Envoyer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
            loginModal.show();
        });
    </script>
<?php
}

echo "</main>\n";
require 'includes/footer.php';
?>