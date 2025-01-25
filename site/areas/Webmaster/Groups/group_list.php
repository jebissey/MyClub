<?php
require_once __DIR__ . '/../../../lib/Database/Tables/Group.php';
require_once __DIR__ . '/../../../lib/Database/Tables/Authorization.php';

$group = new Group();
$groups = $group->getOrdered('Name');

$areaCurrentPage = "Groups";
$areaPath = "..";
?>

    <div class="container mt-4">
        <h1>Gestion des groupes</h1>
        <a href="group_edit.php" class="btn btn-primary mb-3">Nouveau groupe</a>
        
        <table class="table">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Autorisations</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($groups as $group_): ?>
                <tr>
                    <td><?= htmlspecialchars($group_['Name']) ?></td>
                    <td>
                        <?php
                        $auths = $group->getAuthorizations($group_['Id']);
                        echo implode(', ', array_column($auths, 'Name'));
                        ?>
                    </td>
                    <td>
                    <?php if($group_['Id'] != 1){ ?>
                        <a href="Groups/group_edit.php?id=<?= $group_['Id'] ?>" class="btn btn-sm btn-warning">Modifier</a>
                        <a href="Groups/group_delete.php?id=<?= $group_['Id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Êtes-vous sûr ?')">Supprimer</a>
                    <?php } ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>



