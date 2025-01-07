<?php
require_once __DIR__ . '/../../includes/header.php';

require_once  __DIR__ . '/../Database/Tables/Debug.php';



if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL) ?? '';
    $password = $_POST['password'] ?? '';

(new Debug())->set("email=$email");
(new Debug())->set("password=$password");


    require_once  __DIR__ . '/../Database/Tables/Person.php';
    $personFound = (new Person())->getByEmail($email);
    if($personFound){

(new Debug())->set("personFound['Email']=" . $personFound['Email']);
(new Debug())->set("personFound['Password']=" . $personFound['Password']);

require_once __DIR__ . '/Lib/PasswordManager.php';
$userInputPassword = $password;
$storedHash = $hashedPassword; // Récupéré depuis la base de données
$isValid = PasswordManager::verifyPassword($userInputPassword, $storedHash);

        if(password_verify($password, $personFound['Password'])){
            
(new Debug())->set("personFound OK");

            $_SESSION['user'] = $personFound['Email'];
            header('Location:../Person.php');
            exit();
        }
    } else {

(new Debug())->set("person not found");

?>
<div class="modal fade" id="popUpModal" data-bs-backdrop="static" tabindex="-1" aria-labelledby="popUpModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="popUpModalLabel">Utilisateur inconnu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>L'adresse email ne correspond à aucun utilisateur</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var modal = new bootstrap.Modal(document.getElementById('popUpModal'));
        modal.show();
    });

    document.getElementById('popUpModal').addEventListener('hidden.bs.modal', function (event) {
        window.location.href = '../../Page.php';
    });
</script>

<?php
    }
}
require_once __DIR__ . '/../../includes/footer.php';
?>
