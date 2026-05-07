<?php
declare(strict_types=1);
namespace app\models\database\migrators;

use PDO;
use app\interfaces\DatabaseMigratorInterface;

class V62ToV63Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        // Les exercices sont stockés dans Article.Content (JSON).
        // On ajoute juste les autorisations et les traductions.

        $pdo->exec(<<<SQL
INSERT INTO Authorization (Id, Name) VALUES (15, 'ExerciseDesigner');
SQL);

        $pdo->exec(<<<SQL
INSERT INTO Languages (Name, en_US, fr_FR, pl_PL) VALUES
('exercise.nav.designer',  'Exercise Designer', 'Concepteur d''exercices', 'Projektant ćwiczeń'),
('exercise.nav.player',    'Play',              'Lancer',                  'Uruchom'),
('exercise.title',         'Exercises',         'Exercices',               'Ćwiczenia'),
('exercise.add',           'New exercise set',  'Nouvel ensemble',         'Nowy zestaw'),
('exercise.prep.title',    'Preparation title', 'Titre de préparation',    'Tytuł przygotowania'),
('exercise.prep.text',     'Instructions',      'Instructions',            'Instrukcje'),
('exercise.prep.image',    'Image (optional)',  'Image (optionnelle)',      'Obraz (opcjonalny)'),
('exercise.prep.sound',    'Sound (optional)',  'Son (optionnel)',          'Dźwięk (opcjonalny)'),
('exercise.prep.duration', 'Prep duration (s, 0=tap)', 'Durée prép. (s, 0=toucher)', 'Czas przyg. (s, 0=dotknij)'),
('exercise.ex.duration',   'Exercise duration (s)',    'Durée exercice (s)',          'Czas ćwiczenia (s)'),
('exercise.save',          'Save',              'Enregistrer',             'Zapisz'),
('exercise.msg.saved',     'Saved.',            'Enregistré.',             'Zapisano.'),
('exercise.msg.error',     'Error.',            'Erreur.',                 'Błąd.');
SQL);

        return 63;
    }
}