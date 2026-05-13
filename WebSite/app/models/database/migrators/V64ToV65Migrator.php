<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;
use app\interfaces\DatabaseMigratorInterface;

class V64ToV65Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $pdo->exec(<<<SQL
INSERT INTO Languages (Name, en_US, fr_FR, pl_PL) VALUES
('designer.home_settings.navbar_colors_title',  'Navbar colors',         'Couleurs de la barre de navigation', 'Kolory paska nawigacji'),
('designer.home_settings.navbar_bg_label',      'Background color',      'Couleur de fond',                    'Kolor tła'),
('designer.home_settings.navbar_ink_label',     'Ink color',             "Couleur de l'encre",                 'Kolor tekstu'),
('designer.home_settings.navbar_icon_label',    'Icon',                  'Icône',                              'Ikona'),
('filter',                                      'Filter',                'Filtrer',                            'Filtruj'),
('filters',                                     'Filters',               'Filtres',                            'Filtry'),
('media.uses.in_events',                        'Used in events',        'Utilisé dans des événements',        'Używane w wydarzeniach'),
('media.uses.no_events',                        'Not used in any event', 'Non utilisé dans aucun événement',   'Nieużywane w żadnym wydarzeniu'),
('navbar.designer.exercise',                    'Exercise',              'Exercice',                           'Ćwiczenie'),
('designer.home_settings.navbar_harmony_title', 'Color harmony',         'Harmonie des couleurs',              'Harmonia kolorów'),
('designer.home_settings.navbar_harmony_hint',  'Automatically adjusts ink and background colors to ensure contrast and visual consistency.', "Ajuste automatiquement les couleurs d'encre et de fond pour garantir le contraste et la cohérence visuelle.", 'Automatycznie dostosowuje kolory tekstu i tła, aby zapewnić kontrast i spójność wizualną.');

UPDATE Languages
SET Name = 'reset'
WHERE Name = 'visitor_insights.cross_tab.filter.reset';
SQL);

        $pdo->exec("DELETE FROM Languages WHERE Name IN (
            'ClubMembersOnly', 
            'LoginRequired', 
            'Message_UnknownUser', 
            'article.edit.created_by', 
            'article.edit.modified_on', 
            'article.edit.no_group', 
            'article.edit.on', 
            'article.edit.view', 
            'article.label.published',
            'article.label.published', 
            'article.top_articles.card_title', 
            'article.top_articles.title', 
            'chat.confirm.delete',          
            'chat.delete_image', 
            'chat.edit_modal.cancel', 
            'chat.edit_modal.delete', 
            'chat.edit_modal.message_label',        
            'chat.edit_modal.save', 
            'chat.edit_modal.title', 
            'chat.edit_modal.title', 
            'chat.error.send_failed',          
            'chat.error.update_failed', 
            'chat.no_active_users', 
            'comboSeparatorErrorPages', 
            'comboSeparatorHelp',              
            'comboSeparatorHome', 
            'comboSeparatorMessages', 
            'communication.filters.desactivated_accounts', 
            'communication.quota.daily_label',          
            'communication.quota.monthly_label', 
            'designer.home_settings.article_auto_preview_label', 
            'designer.home_settings.article_auto_status', 
            'designer.home_settings.article_featured_status',        
            'designer.home_settings.latest_count_status', 
            'designer.home_settings.latest_hidden_status', 
            'designer.home_settings.paragraphs_all', 
            'designer.home_settings.paragraphs_count',          
            'designer.home_settings.section_images', 
            'emailCredentials.invalid_email', 
            'events.calendar.welcome.agenda', 
            'events.calendar.welcome.colored_squares',           
            'events.calendar.welcome.description', 
            'events.calendar.welcome.group_name', 
            'events.calendar.welcome.group_name', 
            'events.calendar.welcome.title',          
            'visitor_insights.top_pages.title', 
            'visitor_insights.statistics.by_year', 
            'visitor_insights.statistics.by_week', 
            'visitor_insights.statistics.by_month',        
            'visitor_insights.statistics.by_month', 
            'visitor_insights.statistics.by_day', 
            'user.news.empty.since_year', 
            'user.news.empty.since_week',          
            'user.news.empty.since_signout', 
            'user.news.empty.since_signin', 
            'user.news.empty.since_quarter', 
            'user.news.empty.since_month',              
            'user.messages.title', 
            'user.messages.table.message_count', 
            'user.messages.table.last_update', 
            'user.messages.table.actions',          
            'user.messages.info.showing_since', 
            'user.messages.info.label_year', 
            'user.messages.info.label_week', 
            'user.messages.info.label_signout',        
            'user.messages.info.label_signin', 
            'user.messages.info.label_quarter', 
            'user.messages.info.label_month', 
            'loan.reservation.cancel_confirm'
        )");

        return 65;
    }
}
