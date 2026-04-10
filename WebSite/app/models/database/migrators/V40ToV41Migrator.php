<?php
declare(strict_types=1);
namespace app\models\database\migrators;
use PDO;
use app\interfaces\DatabaseMigratorInterface;

class V40ToV41Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $sql = <<<SQL
INSERT INTO Languages (Name, en_US, fr_FR, pl_PL)
VALUES
('chat.edit_title',
'Edit message',
'Modifier le message',
'Edytuj wiadomość'),

('chat.edit_icon_title',
'Edit',
'Modifier',
'Edytuj'),

('chat.message_label',
'Message:',
'Message :',
'Wiadomość:'),

('chat.delete',
'Delete',
'Supprimer',
'Usuń'),

('chat.cancel',
'Cancel',
'Annuler',
'Anuluj'),

('chat.save',
'Save',
'Enregistrer',
'Zapisz');

SQL;
        $pdo->exec($sql);

        return 41;
    }
}