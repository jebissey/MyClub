<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;
use app\modules\Common\interfaces\DatabaseMigratorInterface;

final class V109ToV110Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion, PDO $pdoForLog): int
    {
        $pdo->exec(<<<'SQL'
-- Arwards page moved to the games layout (Other / Awards): sidebar entry and previously hard-coded texts
INSERT OR REPLACE INTO Languages (Name, en_US, fr_FR, pl_PL) VALUES
('gameManager.sidebar.other.arwards', 'Awards', 'Récompenses', 'Nagrody'),
('arwards.title', 'Counters per person', 'Tableau des compteurs par personne', 'Liczniki według osób'),
('arwards.add_counter', 'Add a counter', 'Ajouter un compteur', 'Dodaj licznik'),
('arwards.column.person', 'Person name', 'Nom de la personne', 'Imię i nazwisko'),
('arwards.column.total', 'Total', 'Total', 'Razem'),
('arwards.scoring_legend',
    '(video call = 1 pt, action = 1 pt, bug = 2 pts, suggestion = 3 pts, bonus = 5 pts, content = 10 pts)', 
    '(visio = 1pt, action = 1pt, bug = 2pts, suggestion = 3pts, bonus = 5pts, contenu = 10pts)', 
    '(wideokonferencja = 1 pkt, akcja = 1 pkt, błąd = 2 pkt, sugestia = 3 pkt, bonus = 5 pkt, treść = 10 pkt)'),
('arwards.form.counter_name', 'Counter name', 'Nom du compteur', 'Nazwa licznika'),
('arwards.form.choose_counter_name', 'Choose a counter name', 'Choisir un nom de compteur', 'Wybierz nazwę licznika'),
('arwards.form.new_name', 'New name...', 'Nouveau nom...', 'Nowa nazwa...'),
('arwards.form.new_name_placeholder', 'Enter a new name', 'Entrez un nouveau nom', 'Wpisz nową nazwę'),
('arwards.form.detail', 'Detail', 'Détail', 'Szczegóły'),
('arwards.form.value', 'Value', 'Valeur', 'Wartość'),
('arwards.form.group', 'Group', 'Groupe', 'Grupa'),
('arwards.form.choose_group', 'Choose a group', 'Choisir un groupe', 'Wybierz grupę'),
('arwards.form.person', 'Person', 'Personne', 'Osoba'),
('arwards.form.choose_person', 'Choose a person', 'Choisir une personne', 'Wybierz osobę'),
('arwards.form.cancel', 'Cancel', 'Annuler', 'Anuluj'),
('arwards.form.save', 'Save', 'Enregistrer', 'Zapisz'),
('arwards.js.network_error', 'Network or server error', 'Erreur réseau ou serveur', 'Błąd sieci lub serwera'),
('arwards.js.load_persons_error', 
    'Error while loading persons:', 
    'Erreur lors du chargement des personnes :', 
    'Błąd podczas ładowania osób:'),
('arwards.js.name_required', 'Please enter a name for the counter', 'Veuillez entrer un nom pour le compteur', 'Wpisz nazwę licznika');
SQL);

        return 110;
    }
}
