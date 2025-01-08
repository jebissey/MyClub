<?php
require_once __DIR__ . '/../../includes/header.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL) ?? '';
    $password = $_POST['password'] ?? '';

    require_once  __DIR__ . '/../Database/Tables/Person.php';
    $personFound = (new Person())->getByEmail($email);
    if($personFound){
        require_once __DIR__ . '/../PasswordManager.php';
        if(PasswordManager::verifyPassword($password, $personFound['Password'])){
            
            require_once  __DIR__ . '/../Database/Tables/Debug.php';
            (new Debug())->set("person password OK");

            $_SESSION['user'] = $personFound['Email'];
            header('Location:../../Person.php?p=' . $personFound['Id']);
            exit();
        }
    } else {
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
