<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;
use app\interfaces\DatabaseMigratorInterface;

class V73ToV74Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        // Membership table: one row per person per season
        $pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS Membership (
	Id            INTEGER PRIMARY KEY AUTOINCREMENT,
	PersonId      INTEGER NOT NULL,
	Season        TEXT    NOT NULL,                          -- e.g. '2024-2025'
	Amount        INTEGER NOT NULL DEFAULT 0,               -- in euro-cents
	Status        TEXT    NOT NULL DEFAULT 'pending'
	              CHECK(Status IN ('pending','paid','cancelled')),
	HelloAssoOrderId  TEXT    NOT NULL DEFAULT '',
	HelloAssoCheckoutIntentId TEXT NOT NULL DEFAULT '',
	PaidAt        TEXT,
	CreatedAt     TEXT    NOT NULL DEFAULT (datetime('now')),
	UpdatedAt     TEXT    NOT NULL DEFAULT (datetime('now')),
	FOREIGN KEY (PersonId) REFERENCES Person(Id)
);
SQL);

        // Translations
        $pdo->exec(<<<SQL
INSERT OR IGNORE INTO Languages (Name, en_US, fr_FR, pl_PL) VALUES
('user.filter.info.label_signout',
 '(last sign-out)',
 '(dernière déconnexion)',
 '(ostatnie wylogowanie)'),
('user.filter.info.label_signin',
 '(last sign-in)',
 '(dernière connexion)',
 '(ostatnie logowanie)'),
('user.filter.info.label_week',
 '(1 week)',
 '(1 semaine)',
 '(1 tydzień)'),
('user.filter.info.label_month',
 '(1 month)',
 '(1 mois)',
 '(1 miesiąc)'),
('user.filter.info.label_quarter',
 '(1 quarter)',
 '(1 trimestre)',
 '(1 kwartał)'),
('user.filter.info.label_year',
 '(1 year)',
 '(1 an)',
 '(1 rok)'),

('helloasso.title',                              
	'HelloAsso API credentials',              
	'Identifiants API HelloAsso',                    
	'Dane API HelloAsso'),
('helloasso.alert.not_configured',               
	'HelloAsso is not configured yet',        
	"HelloAsso n'est pas encore configuré",          
	'HelloAsso nie jest jeszcze skonfigurowany'),
('helloasso.info.get_keys',                      
	'Get your API keys from',                 
	'Obtenez vos clés API depuis',                   
	'Pobierz klucze API z'),
('helloasso.info.sandbox',                       
	'Use sandbox credentials for testing',    
	'Utilisez les identifiants sandbox pour tester', 
	'Użyj danych sandbox do testów'),
('helloasso.field.client_id',                    
	'Client ID',                              
	'Client ID',                                     
	'Client ID'),
('helloasso.field.client_id.public',             
	'public',                                 
	'public',                                        
	'publiczny'),
('helloasso.field.client_id.hint',               
	'Your HelloAsso application client ID',   
	"L'identifiant client de votre application HelloAsso", 
	'Identyfikator klienta aplikacji HelloAsso'),
('helloasso.field.client_secret',                
	'Client Secret',                          
	'Client Secret',                                 
	'Client Secret'),
('helloasso.field.client_secret.private',        
	'private',                                
	'privé',                                         
	'prywatny'),
('helloasso.field.client_secret.hint',           
	'Leave blank to keep the current secret', 
	'Laisser vide pour conserver le secret actuel',  
	'Pozostaw puste, aby zachować obecny sekret'),
('helloasso.field.client_secret.not_configured', 
	'Not configured',                         
	'Non configuré',                                 
	'Nieskonfigurowany'),

('membership.nav.my',        'My Membership',      'Mon adhésion',          'Moje członkostwo'),
('membership.title',         'Membership renewal', 'Renouvellement adhésion','Odnowienie członkostwa'),
('membership.season',        'Season',             'Saison',                'Sezon'),
('membership.status',        'Status',             'Statut',                'Status'),
('membership.amount',        'Amount',             'Montant',               'Kwota'),
('membership.pay',           'Pay now',            'Payer maintenant',      'Zapłać teraz'),
('membership.status.pending','Pending',            'En attente',            'Oczekujące'),
('membership.status.paid',   'Paid',               'Réglée',                'Opłacone'),
('membership.status.cancelled','Cancelled',        'Annulée',               'Anulowane'),
('membership.already_paid',  
	'Your membership for this season is already paid.',
	'Votre adhésion pour cette saison est déjà réglée.',
	'Twoje składki na ten sezon są już opłacone.'),
('membership.no_membership', 
	'No membership found for this season.',
	'Aucune adhésion trouvée pour cette saison.',
	'Nie znaleziono członkostwa na ten sezon.'),
('membership.payment_success',
	'Payment confirmed. Welcome!',
	'Paiement confirmé. Bienvenue !',
	'Płatność potwierdzona. Witaj!'),
('membership.payment_error', 
	'Payment failed or cancelled.',
	'Paiement échoué ou annulé.',
	'Płatność nie powiodła się lub została anulowana.'),

('personManager.membershipSettings.title',       
	'Membership Settings',                      
	'Paramètres d''adhésion',
	'Ustawienia członkostwa'),
('personManager.membershipSettings.description', 
	'Configure the membership fee and season.', 
	'Configurez le montant de l''adhésion et la saison sportive.', 
	'Skonfiguruj składkę członkowską i sezon sportowy.'),
('personManager.membershipSettings.amount',      
	'Membership Fee',                           
	'Montant de l''adhésion',                      
	'Składka członkowska'),
('personManager.membershipSettings.amountHint',  
	'Amount in euros, e.g. 12.50',              
	'Montant en euros, ex : 12,50',                
	'Kwota w euro, np. 12,50'),
('personManager.membershipSettings.season',      'Season',     'Saison',        'Sezon'),
('personManager.membershipSettings.seasonStart', 'Start date', 'Date de début', 'Data rozpoczęcia'),
('personManager.membershipSettings.seasonEnd',   'End date',   'Date de fin',   'Data zakończenia'),
('personManager.membershipSettings.seasonHint',  
	'Leave empty to use the current season automatically.', 	
	'Laisser vide pour utiliser la saison en cours automatiquement.', 
	'Pozostaw puste, aby automatycznie użyć bieżącego sezonu.'),

('media.upload.error',                       'Upload error',        'Erreur de téléversement',  'Błąd przesyłania pliku'),
('membership',                               'Membership',          'Adhésion',                 'Członkostwo'),
('navbar.person_manager.membershipSettings', 'Membership settings', 'Paramètres des adhésions', 'Ustawienia członkostwa'),
('navbar.webmaster.helloasso',               'HelloAsso',           'HelloAsso',                'HelloAsso');
SQL);

        return 74;
    }
}
