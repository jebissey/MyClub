<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;
use app\interfaces\DatabaseMigratorInterface;

class V39ToV40Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $pdo->exec("UPDATE Languages SET Name = 'close' WHERE Name = 'user_connections.modal.close'");
        $pdo->exec("
            ALTER TABLE Person 
            ADD COLUMN LastPageView TEXT 
            CHECK (
                LastPageView IS NULL 
                OR (
                    LastPageView LIKE '____-__-__ __:__:__'
                    AND datetime(LastPageView) IS NOT NULL
                )
            )
        ");

        $sql = <<<SQL
INSERT INTO Languages (Name, en_US, fr_FR, pl_PL)
VALUES
('chat.no_messages',
'No messages yet.\nBe the first to write!',
'Aucun message pour le moment.\nSoyez le premier à écrire !',
'Brak wiadomości.\nBądź pierwszą osobą, która napisze!'),

('chat.online',
'Online:',
'En ligne :',
'Online:'),

('chat.placeholder',
'Write your message...',
'Écrivez votre message...',
'Napisz wiadomość...'),

('chat.send',
'Send',
'Envoyer',
'Wyślij'),

('chat.edit_modal.title',
'Edit message',
'Modifier le message',
'Edytuj wiadomość'),

('chat.edit_modal.message_label',
'Message:',
'Message :',
'Wiadomość:'),

('chat.edit_modal.delete',
'Delete',
'Supprimer',
'Usuń'),

('chat.edit_modal.cancel',
'Cancel',
'Annuler',
'Anuluj'),

('chat.edit_modal.save',
'Save',
'Enregistrer',
'Zapisz'),

('chat.error.send_failed',
'Unable to send message',
'Impossible d''envoyer le message',
'Nie można wysłać wiadomości'),

('chat.confirm.delete',
'Are you sure you want to delete this message?',
'Êtes-vous sûr de vouloir supprimer ce message ?',
'Czy na pewno chcesz usunąć tę wiadomość?'),

('chat.error.update_failed',
'An error occurred while editing the message',
'Une erreur est survenue lors de la modification du message',
'Wystąpił błąd podczas edycji wiadomości'),

('chat.error.delete_failed',
'An error occurred while deleting the message',
'Une erreur est survenue lors de la suppression du message',
'Wystąpił błąd podczas usuwania wiadomości'),

('chat.no_active_users',
'No active users',
'Aucun utilisateur actif',
'Brak aktywnych użytkowników');
SQL;
        $pdo->exec($sql);

        return 40;
    }
}
