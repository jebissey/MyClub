<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;
use app\modules\Common\interfaces\DatabaseMigratorInterface;

final class V96ToV97Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion, PDO $pdoForLog): int
    {
        $pdo->exec(<<<'SQL'
-- Make the Target game translations
INSERT OR REPLACE INTO Languages (Name, en_US, fr_FR, pl_PL) VALUES
('makethetarget.title',
 'Make the Target',
 'Le Compte est bon',
 'Traf cel'),

('makethetarget.rules',
 'Rules',
 'Règles',
 'Zasady'),

('makethetarget.rule1',
 'Draw 6 number tiles and a target to reach.',
 'Tirez 6 plaques et un total à atteindre.',
 'Wylosuj 6 płytek i cel do osiągnięcia.'),

('makethetarget.rule2',
 'Select 2 numbers then one operation (+ − × ÷).',
 'Sélectionnez 2 nombres puis une opération (+ − × ÷).',
 'Wybierz 2 liczby, a następnie jedno działanie (+ − × ÷).'),

('makethetarget.rule3',
 'The result becomes a new usable tile.',
 'Le résultat devient une nouvelle plaque utilisable.',
 'Wynik staje się nową użyteczną płytką.'),

('makethetarget.rule4',
 'Used tiles are crossed out.',
 'Les plaques utilisées sont barrées.',
 'Użyte płytki są przekreślane.'),

('makethetarget.rule5',
 'You win when you reach the target exactly.',
 'Vous gagnez si vous obtenez exactement le total.',
 'Wygrywasz, gdy osiągniesz dokładnie cel.'),

('makethetarget.draw_tiles',
 'Draw tiles',
 'Tirer les plaques',
 'Wylosuj płytki'),

('makethetarget.solution',
 'Solution',
 'Solution',
 'Rozwiązanie'),

('makethetarget.restart',
 'Restart',
 'Recommencer',
 'Od nowa'),

('makethetarget.target',
 'Target',
 'Total à obtenir',
 'Cel'),

('makethetarget.tiles',
 'Tiles',
 'Plaques',
 'Płytki'),

('makethetarget.operation',
 'Operation',
 'Opération',
 'Działanie'),

('makethetarget.history',
 'Operation history',
 'Historique des opérations',
 'Historia działań'),

('makethetarget.no_operation_yet',
 'No operation yet.',
 'Aucune opération pour le moment.',
 'Brak działań.'),

('makethetarget.operation_count',
 '%d operation',
 '%d opération',
 '%d działanie'),

('makethetarget.operations_count',
 '%d operations',
 '%d opérations',
 '%d działań'),

('makethetarget.target_reached',
 'Target reached!',
 'Le compte est bon !',
 'Cel osiągnięty!'),

('makethetarget.obtained_in',
 'You obtained %1 in %2 operation(s).',
 'Vous avez obtenu %1 en %2 opération(s).',
 'Uzyskałeś %1 w %2 działaniach.'),

('makethetarget.new_game',
 'New game',
 'Nouvelle partie',
 'Nowa gra'),

('makethetarget.solution_found',
 'Solution found:',
 'Solution trouvée :',
 'Znalezione rozwiązanie:'),

('makethetarget.best_result',
 'Best possible result:',
 'Meilleur résultat possible :',
 'Najlepszy możliwy wynik:'),

('makethetarget.no_combination',
 'No interesting combination found.',
 'Aucune combinaison intéressante.',
 'Nie znaleziono interesującej kombinacji.'),

('makethetarget.statistics_title',
 'Statistics – Make the Target',
 'Statistiques – Le Compte est bon',
 'Statystyki – Traf cel'),

('makethetarget.filter.date',
 'Date',
 'Date',
 'Data'),

('makethetarget.filter.who',
 'Who',
 'Qui',
 'Kto'),

('makethetarget.filter.game',
 'Game',
 'Jeu',
 'Gra'),

('makethetarget.filter.operations',
 'Operations',
 'Opérations',
 'Działania'),

('makethetarget.filter.result',
 'Result',
 'Résultat',
 'Wynik'),

('makethetarget.column.date',
 'Date',
 'Date',
 'Data'),

('makethetarget.column.who',
 'Who',
 'Qui',
 'Kto'),

('makethetarget.column.game',
 'Game',
 'Jeu',
 'Gra'),

('makethetarget.column.operations',
 'Operations',
 'Opérations',
 'Działania'),

('makethetarget.column.result',
 'Result',
 'Résultat',
 'Wynik');
SQL);

        return 97;
    }
}
