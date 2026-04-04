<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;
use app\interfaces\DatabaseMigratorInterface;

class V34ToV35Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $sql = <<<SQL
INSERT INTO Languages (Name, en_US, fr_FR, pl_PL)
VALUES
('account.form.imported.title',
 'Imported account',
 'Compte importé',
 'Importowane konto'),

('account.form.imported.webmaster_message',
 'The account information must be updated in the external system. In case of an email address change, update it here first before performing a new import.',
 'Il faut mettre à jour les informations du compte dans le système externe. En cas de changement d''adresse courriel, il faut d''abord la mettre à jour ici avant de faire une nouvelle importation.',
 'Informacje o koncie muszą zostać zaktualizowane w systemie zewnętrznym. W przypadku zmiany adresu e-mail należy najpierw zaktualizować go tutaj przed wykonaniem nowego importu.'),

('account.form.imported.readonly_message',
 'Greyed-out fields cannot be edited here. They are synchronised with the external system.',
 'Les champs grisés ne sont pas modifiables ici. Ils sont synchronisés avec le système externe.',
 'Wyszarzone pola nie są edytowalne tutaj. Są synchronizowane z systemem zewnętrznym.'),

('account.form.email.label',
 'Email', 'Email', 'Email'),

('account.form.email.invalid',
 'Please enter a valid email address',
 'Veuillez saisir une adresse email valide',
 'Proszę podać prawidłowy adres email'),

('account.form.firstname.label',
 'First name', 'Prénom', 'Imię'),

('account.form.firstname.required',
 'First name is required.',
 'Le prénom est requis.',
 'Imię jest wymagane.'),

('account.form.lastname.label',
 'Last name', 'Nom', 'Nazwisko'),

('account.form.lastname.required',
 'Last name is required.',
 'Le nom est requis.',
 'Nazwisko jest wymagane.'),

('account.form.nickname.label',
 'Nickname', 'Pseudo', 'Pseudonim'),

('account.form.emoji.select_label',
 'Select an Emoji',
 'Sélectionnez un Emoji',
 'Wybierz Emoji'),

('account.form.emoji.paste_placeholder',
 'Paste your emoji here…',
 'Collez votre emoji ici…',
 'Wklej emoji tutaj…'),

('account.form.emoji.getemoji_title',
 'Open getemoji.com to copy an emoji',
 'Ouvrir getemoji.com pour copier un emoji',
 'Otwórz getemoji.com, aby skopiować emoji'),

('account.form.emoji.missing_elements',
 'Emoji picker: missing elements',
 'Emoji picker : éléments manquants',
 'Emoji picker: brakujące elementy'),

('account.form.emoji.none_detected',
 '⚠ No emoji detected. Try 😊',
 '⚠ Aucun emoji détecté. Essayez 😊',
 '⚠ Nie wykryto emoji. Spróbuj 😊'),

('account.form.emoji.selected',
 '✓ Emoji « %s » selected!',
 '✓ Emoji « %s » sélectionné !',
 '✓ Emoji « %s » wybrany!'),

('account.form.gravatar.use',
 'Use my gravatar',
 'Utiliser mon gravatar',
 'Użyj mojego gravatara'),

('account.form.admin_only.note',
 'Fields visible and editable only by PersonManager',
 'Champs visibles et modifiables uniquement par le PersonManager',
 'Pola widoczne i edytowalne tylko przez PersonManager'),

('account.form.alert.label',
 'Alert', 'Alerte', 'Alert'),

('account.form.member_info.label',
 'Member info', 'Info membre', 'Info o członku'),

('account.form.cancel',
 'Cancel', 'Annuler', 'Anuluj'),

('account.form.submit',
 'Save', 'Valider', 'Zatwierdź');
SQL;
        $pdo->exec($sql);

        return 35;
    }
}
