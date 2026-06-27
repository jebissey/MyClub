<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;
use app\interfaces\DatabaseMigratorInterface;

class V63ToV64Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {

        $pdo->exec(<<<SQL
CREATE TABLE "Exercise" (
	"Id"	INTEGER,
	"Title"	TEXT NOT NULL,
	"Detail"	TEXT NOT NULL,
	"Content"	TEXT NOT NULL,
	"CreatedBy"	INTEGER NOT NULL,
	"LastUpdate"	TEXT NOT NULL DEFAULT current_timestamp,
	"IdGroup"	INTEGER,
	"OnlyForMembers"	INTEGER NOT NULL DEFAULT 1,
	PRIMARY KEY("Id"),
	FOREIGN KEY("CreatedBy") REFERENCES "Person"("Id"),
	FOREIGN KEY("IdGroup") REFERENCES "Group"("Id")
);
SQL);

        $pdo->exec(<<<SQL
CREATE VIEW exercise_list_view AS
            SELECT 
                Exercise.Id,
                Exercise.CreatedBy,
                Exercise.Title,
                Exercise.Detail,
				Exercise.LastUpdate,
                Exercise.CreatedBy,
                Exercise.OnlyForMembers,
                Exercise.IdGroup,               
                CASE 
                    WHEN Exercise.OnlyForMembers = 1 THEN 'oui' 
                    ELSE 'non' 
                END AS ForMembers,
                CASE 
                    WHEN Person.NickName != '' THEN Person.FirstName || ' ' || Person.LastName || ' (' || Person.NickName || ')' 
                    ELSE Person.FirstName || ' ' || Person.LastName 
                END AS PersonName,
                'Group'.Name AS GroupName
            FROM Exercise
            INNER JOIN Person ON Exercise.CreatedBy = Person.Id           
            LEFT JOIN 'Group' ON 'Group'.Id = Exercise.IdGroup
SQL);

        $pdo->exec(<<<SQL
INSERT INTO Languages (Name, en_US, fr_FR, pl_PL) VALUES
('event.copy_emails.clipboard.success', 
    'Emails copied to clipboard.', 
    'Les emails ont été copiés dans le presse-papiers.', 
    'E-maile zostały skopiowane do schowka.'),
('event.copy_emails.clipboard.error', 
    'Error copying to clipboard: ', 
    'Erreur lors de la copie dans le presse-papiers : ', 
    'Błąd podczas kopiowania do schowka: '),
('event.copy_emails.title', 'List of email addresses', 'Liste des adresses email', 'Lista adresów email'),
('event.copy_emails.title.with', 'with', 'avec', 'z');
SQL);

        $pdo->exec(<<<SQL
INSERT INTO Languages (Name, en_US, fr_FR, pl_PL) VALUES
('event.get_emails.label.group', 'Group', 'Groupe', 'Grupa'),
('event.get_emails.option.all_groups', 'All groups', 'Tous les groupes', 'Wszystkie grupy'),
('event.get_emails.label.event_type', 'Event type', 'Type d''événement', 'Typ wydarzenia'),
('event.get_emails.option.choose_type', 'Choose a type', 'Choisir un type', 'Wybierz typ'),
('event.get_emails.label.day', 'Day', 'Jour', 'Dzień'),
('event.get_emails.option.choose_day', 'Choose a day', 'Choisir un jour', 'Wybierz dzień'),
('event.get_emails.label.time_of_day', 'Time of day', 'Moment de la journée', 'Pora dnia'),
('event.get_emails.option.choose_time', 'Choose a time', 'Choisir un moment', 'Wybierz porę'),
('event.get_emails.button.submit', 'Get emails', 'Obtenir les emails', 'Pobierz e-maile');
SQL);


        return 64;
    }
}
