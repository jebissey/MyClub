<?php

require_once __DIR__ . '/../../../lib/Database/Tables/Authorization.php';
require_once __DIR__ . '/../../../lib/Database/Tables/Group.php';
require_once __DIR__ . '/../../../lib/Database/Tables/GroupAuthorization.php';

$group = new Group();
$auth = new Authorization();
$groupAuth = new GroupAuthorization();

$idGroup = $_GET['id'] ?? null;
$groupData = $idGroup ? $group->getById($idGroup) : ['Name' => '', 'Id' => null];
$allAuths = $auth->getOrdered('Name');
$selectedAuths = [];
if ($idGroup) {
    $currentAuths = $groupAuth->gets($idGroup);
    $selectedAuths = array_column($currentAuths, 'IdAuthorization');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = [
            'Name' => trim($_POST['name']),
        ];
        
        if ($idGroup) {
            $group->setById($idGroup, $data);
            $groupAuth->removes($idGroup);
        } else {
            $idGroup = $group->set($data);
        }
        
        $newAuths = $_POST['authorizations'] ?? [];
        foreach ($newAuths as $authId) {
            if (is_numeric($authId)) { 
                $groupAuth->set([
                    'IdGroup' => $idGroup,
                    'IdAuthorization' => (int)$authId
                ]);
            }
        }
        
        header('Location: ../menu.php?l=G');
        exit;
    } catch (Exception $e) {
        $error = "Une erreur est survenue : " . htmlspecialchars($e->getMessage());
    }
}
require_once __DIR__ . '/../../../includes/tinyHeader.php';
?>
    <div class="container mt-4">
        <h1><?= $idGroup ? 'Modifier' : 'Créer' ?> un groupe</h1>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        
        <form method="post" class="needs-validation" novalidate>
            <div class="mb-3">
                <label for="name" class="form-label">Nom</label>
                <input type="text" class="form-control" id="name" name="name" 
                       value="<?= htmlspecialchars($groupData['Name']) ?>" required>
                <div class="invalid-feedback">
                    Le nom du groupe est requis
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label d-block">Autorisations</label>
                <div class="border rounded p-3">
                    <?php foreach ($allAuths as $auth): ?>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" 
                               name="authorizations[]" 
                               value="<?= $auth['Id'] ?>" 
                               id="auth<?= $auth['Id'] ?>"
                               <?= in_array($auth['Id'], $selectedAuths) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="auth<?= $auth['Id'] ?>">
                            <?= htmlspecialchars($auth['Name']) ?>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <a href="../menu.php?l=G" class="btn btn-secondary">Annuler</a>
        </form>
    </div>

    <script>
    (function () {
        'use strict'
        var forms = document.querySelectorAll('.needs-validation')
        Array.prototype.slice.call(forms).forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault()
                    event.stopPropagation()
                }
                form.classList.add('was-validated')
            }, false)
        })
    })()
    </script>

<?php
require_once __DIR__ . '/../../../includes/tinyFooter.php';
?>

