<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;

use app\interfaces\DatabaseMigratorInterface;

class V16ToV17Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $sql = <<<SQL
INSERT INTO Languages (Name, en_US, fr_FR, pl_PL)
VALUES
('menu.add_item',
 'Add item',
 'Ajouter un élément',
 'Dodaj element'),

('menu.edit_item',
 'Edit item',
 'Modifier un élément',
 'Edytuj element'),

('menu.delete_confirm',
 'Delete this item?',
 'Supprimer cet élément ?',
 'Usunąć ten element?'),

('menu.label_required',
 'The label is required.',
 'Le label est requis.',
 'Etykieta jest wymagana.'),

('menu.url_required',
 'The URL is required for a link.',
 'L''URL est requise pour un lien.',
 'Adres URL jest wymagany dla łącza.'),

('menu.save_failed',
 'Save failed.',
 'Échec de la sauvegarde.',
 'Błąd zapisu.'),

('menu.save_error',
 'Error during save:',
 'Erreur lors de la sauvegarde :',
 'Błąd podczas zapisywania:'),

('menu.delete_failed',
 'Delete failed.',
 'Échec de la suppression.',
 'Błąd usuwania.'),

('menu.delete_error',
 'Error during deletion.',
 'Erreur lors de la suppression.',
 'Błąd podczas usuwania.'),

('menu.load_error',
 'Error loading:',
 'Erreur lors du chargement :',
 'Błąd ładowania:'),

('menu.error',
 'Error:',
 'Erreur :',
 'Błąd:'),

('menu.positions_error',
 'Error updating positions:',
 'Erreur mise à jour positions :',
 'Błąd aktualizacji pozycji:'),

('menu.positions_error_generic',
 'Error updating positions.',
 'Erreur mise à jour positions.',
 'Błąd aktualizacji pozycji.'),
 ('menu.modal_title',
 'Menu Item',
 'Menu Item',
 'Element menu'),

('menu.field_label',
 'Label:',
 'Label :',
 'Etykieta:'),

('menu.field_url',
 'Address:',
 'Adresse :',
 'Adres:'),

('menu.field_url_placeholder',
 '/my/route',
 '/ma/route',
 '/moja/trasa'),

('menu.field_group',
 'Group:',
 'Groupe :',
 'Grupa:'),

('menu.field_none',
 'None',
 'Aucun',
 'Brak'),

('menu.field_visible_for',
 'Visible for:',
 'Visible pour :',
 'Widoczny dla:'),

('menu.field_members',
 'Members',
 'Membres',
 'Członkowie'),

('menu.field_contacts',
 'Contacts',
 'Contacts',
 'Kontakty'),

('menu.field_anonymous',
 'Anonymous',
 'Anonymes',
 'Anonimowi'),

('menu.field_type',
 'Type:',
 'Type :',
 'Typ:'),

('menu.type_link',
 'Link',
 'Lien',
 'Łącze'),

('menu.type_heading',
 'Heading',
 'Titre',
 'Nagłówek'),

('menu.type_divider',
 'Divider',
 'Séparateur',
 'Separator'),

('menu.type_submenu',
 'Submenu',
 'Sous-menu',
 'Podmenu'),

('menu.field_icon',
 'Icon',
 'Icône',
 'Ikona'),

('menu.field_icon_placeholder',
 'bi-house',
 'bi-house',
 'bi-house'),

('menu.field_parent',
 'Parent:',
 'Parent :',
 'Nadrzędny:'),

 ('menu.page_title',
 'Menu Items',
 'Menu Items',
 'Elementy menu'),

('menu.tab_navbar',
 'Navbar',
 'Navbar',
 'Navbar'),

('menu.tab_sidebar',
 'Sidebar',
 'Sidebar',
 'Sidebar'),

('menu.col_name',
 'Name',
 'Nom',
 'Nazwa'),

('menu.col_url',
 'URL',
 'URL',
 'URL'),

('menu.col_group',
 'Group',
 'Groupe',
 'Grupa'),

('menu.col_members',
 'Members',
 'Membres',
 'Członkowie'),

('menu.col_contacts',
 'Contacts',
 'Contacts',
 'Kontakty'),

('menu.col_anonymous',
 'Anonymous',
 'Anonymes',
 'Anonimowi'),

('menu.col_actions',
 'Actions',
 'Actions',
 'Akcje'),

('menu.col_type',
 'Type',
 'Type',
 'Typ'),

('menu.col_icon',
 'Icon',
 'Icône',
 'Ikona'),

('menu.col_label',
 'Label',
 'Label',
 'Etykieta'),

('menu.col_parent',
 'Parent',
 'Parent',
 'Nadrzędny');
SQL;
        $pdo->exec($sql);

        return 17;
    }
}
