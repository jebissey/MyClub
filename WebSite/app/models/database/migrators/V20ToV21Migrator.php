<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;

use app\interfaces\DatabaseMigratorInterface;

class V20ToV21Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $sql = <<<SQL
INSERT INTO Languages (Name, en_US, fr_FR, pl_PL)
VALUES
(
'person.add.emailAlreadyExistsDetailed',
'<div class="alert alert-warning">
<h5>⚠️ Email already used</h5>
<p>
A person with this email address already exists in the system.
</p>
<p>
<strong>Existing account:</strong> {name}<br>
<strong>Status:</strong> {status}
</p>
<p class="mt-3">
Before creating a new person, please check:
</p>
<ul>
<li>the list of <strong>active accounts</strong></li>
<li>the list of <strong>disabled accounts</strong></li>
</ul>
<p class="mt-3">
If the account already exists but is disabled, you can simply
<strong>reactivate the existing account</strong> instead of creating a new one.
</p>
</div>',

'<div class="alert alert-warning">
<h5>⚠️ Adresse courriel déjà utilisée</h5>
<p>
Une personne avec cette adresse courriel existe déjà dans le système.
</p>
<p>
<strong>Compte existant :</strong> {name}<br>
<strong>Statut :</strong> {status}
</p>
<p class="mt-3">
Avant de créer une nouvelle fiche, veuillez vérifier :
</p>
<ul>
<li>la liste des <strong>comptes actifs</strong></li>
<li>la liste des <strong>comptes désactivés</strong></li>
</ul>
<p class="mt-3">
Si la personne existe déjà mais que son compte est désactivé,
vous pouvez simplement <strong>réactiver le compte existant</strong>
au lieu d''en créer un nouveau.
</p>
</div>',

'<div class="alert alert-warning">
<h5>⚠️ Adres email już użyty</h5>
<p>
Osoba z tym adresem email już istnieje w systemie.
</p>
<p>
<strong>Istniejące konto:</strong> {name}<br>
<strong>Status:</strong> {status}
</p>
<p class="mt-3">
Przed utworzeniem nowej osoby sprawdź:
</p>
<ul>
<li>listę <strong>aktywnych kont</strong></li>
<li>listę <strong>wyłączonych kont</strong></li>
</ul>
<p class="mt-3">
Jeśli konto istnieje, ale jest wyłączone, możesz je po prostu
<strong>ponownie aktywować</strong> zamiast tworzyć nowe.
</p>
</div>'
),
(
'quick_actions',
'Quick actions',
'Vous voulez gagner du temps ? Accédez aux actions rapides.',
'Chcesz zaoszczędzić czas? Przejdź do szybkich działań.'
);
SQL;
        $pdo->exec($sql);

        return 21;
    }
}
