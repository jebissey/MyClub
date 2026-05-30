<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;
use app\interfaces\DatabaseMigratorInterface;

class V71ToV72Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $pdo->exec(<<<SQL
INSERT INTO Languages (Name, en_US, fr_FR, pl_PL) VALUES
('navbar.redactor.public_articles', 'Public articles',  'Articles publics',    'Artykuły publiczne'),
('article.label.reference_source',  'Reference source', 'Source de référence', 'Źródło referencyjne'),
('redactor.public_articles.page_title',   
    '<h3>⚖️ All these articles are visible to all visitors ⚖️</h3>',           
    '<h3>⚖️ Tous ces articles sont visibles par tous les visiteurs ⚖️</h3>',                 
    '<h3>⚖️ Wszystkie te artykuły są widoczne dla wszystkich odwiedzających ⚖️</h3>'),
('event.cancelled',                 '(Event Cancelled)', '(Evénement Annulé)', '(Wydarzenie Odwołane)'),
('event.login_required',               'You must be logged in to register for this event.',       "Il faut être connecté pour pouvoir s'inscrire à cet événement.", 'Musisz być zalogowany, aby zapisać się na to wydarzenie.'),
('event.open_google_maps',             'Open in Google Maps',                                     'Ouvrir dans Google Maps',                                        'Otwórz w Google Maps'),
('event.update_calendar',              'Easily update your personal calendar:',                   'Mettez facilement à jour votre agenda personnel :',              'Łatwo zaktualizuj swój osobisty kalendarz:'),
('event.cancelled_calendar_disabled',  'This event is cancelled, adding to calendar is disabled.',"Cet événement est annulé, l'ajout à l''agenda est désactivé.",  'To wydarzenie jest odwołane, dodawanie do kalendarza jest wyłączone.'),
('event.needs',                        'Event needs',                                             'Besoins de l''événement',                                        'Potrzeby wydarzenia'),
('event.needs_click_to_edit',          'Click on quantities to edit your contributions',          'Cliquez sur les quantités pour modifier vos apports',            'Kliknij na ilości, aby edytować swoje wkłady'),
('event.needs_per_participant',        'per participant',                                         'par participant',                                                'na uczestnika'),
('event.needs_you',                    'You',                                                     'Vous',                                                           'Ty'),
('event.needs_validate',               'Validate',                                                'Valider',                                                        'Zatwierdź'),
('event.needs_register_to_contribute', 'Register to contribute',                                  'Inscrivez-vous pour contribuer',                                 'Zapisz się, aby wnieść wkład'),
('event.participant_supplies',         'Participant contributions',                               'Apports des participants',                                       'Wkłady uczestników'),
('event.show_supplies',                'Show contributions',                                      'Afficher les apports',                                           'Pokaż wkłady');
SQL);

        return 72;
    }
}