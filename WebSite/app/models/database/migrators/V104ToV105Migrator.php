<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;
use app\modules\Common\interfaces\DatabaseMigratorInterface;

/**
 * Adds the translation keys used by the Event form JavaScript (EventFormManager.js).
 *
 * The form was previously filled with hard-coded French strings for the modal
 * title and the submit button, depending on whether the modal was opened in
 * "create" or "update" mode. These strings are now translatable.
 *
 * Existing keys reused by the JS:
 *   - events.form.create   (already in the Languages table, used by the Latte partial)
 *
 * New keys added here:
 *   - events.form.create_title  ("Create event")
 *   - events.form.update_title  ("Update event")
 *   - events.form.update        ("Update" button label, parallel to events.form.create)
 *   - events.js.error           ("Error" alert prefix)
 *   - events.js.unknown_error   ("Unknown error" alert fallback)
 */
final class V104ToV105Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion, PDO $pdoForLog): int
    {
        $pdo->exec(<<<'SQL'
-- Event form: modal titles and button labels used dynamically by EventFormManager.js.
-- Reuses the existing "events.form.create" key for the create button.
INSERT OR REPLACE INTO Languages (Name, en_US, fr_FR, pl_PL) VALUES
('events.form.create_title',
 'Create event',
 'Créer un événement',
 'Utwórz wydarzenie'),

('events.form.update_title',
 'Update event',
 'Mettre à jour l''événement',
 'Aktualizuj wydarzenie'),

('events.form.update',
 'Update',
 'Mettre à jour',
 'Aktualizuj'),

-- Event form: alert messages shown only from JavaScript.
('events.js.error',
 'Error',
 'Erreur',
 'Błąd'),

('events.js.unknown_error',
 'Unknown error',
 'Erreur inconnue',
 'Nieznany błąd');
SQL);

        return 105;
    }
}
