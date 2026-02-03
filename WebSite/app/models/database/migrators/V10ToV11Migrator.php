<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;

use app\interfaces\DatabaseMigratorInterface;

class V10ToV11Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $pdo->exec(<<<'SQL'
UPDATE Languages
SET 
    en_US = '<div class="container my-3">
    <section class="mb-3">
        <div class="card shadow-sm">
            <div class="card-body p-3 p-md-5">
                <h2 class="fw-bold text-primary">Administration Area Tools</h2>
                <p class="lead text-muted mb-4">
                    This page provides access to administration tools based on your permissions.
                </p>

                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3 flex-shrink-0">
                                <i class="bi bi-key-fill fs-4"></i>
                            </div>
                            <div>
                                <h3 class="h5 fw-bold mb-2">Access and Permissions</h3>
                                <ul class="list-unstyled text-muted small">
                                    <li class="mb-2">
                                        <strong>Visibility:</strong> The yellow key only appears in the top bar for members with the appropriate permissions.
                                    </li>
                                    <li>
                                        <strong>Smart navigation:</strong>
                                        <ul class="mt-2 list-unstyled ms-3">
                                            <li class="mb-1">→ Multiple areas → selection menu</li>
                                            <li>→ Single area → automatic redirection (time saver 😊)</li>
                                        </ul>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3 flex-shrink-0">
                                <i class="bi bi-phone fs-4"></i>
                            </div>
                            <div>
                                <h3 class="h5 fw-bold mb-2">Mobile Optimization</h3>
                                <p class="text-muted small">
                                    Shortcuts are also displayed directly here — no need to open the ☰ menu on smaller screens.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section>
        <h2 class="h4 mb-3 fw-bold">Key Takeaways</h2>
        <div class="list-group">
            <div class="list-group-item d-flex align-items-center">
                <i class="bi bi-key-fill fs-4 text-warning me-3"></i>
                <span>
                    The <strong>yellow key</strong> is only visible to members with permissions.
                </span>
            </div>
            <div class="list-group-item d-flex align-items-center">
                <i class="bi bi-arrow-right-circle fs-4 text-primary me-3"></i>
                <span>
                    A single administration area → automatic redirection.
                </span>
            </div>
            <div class="list-group-item d-flex align-items-center">
                <i class="bi bi-phone fs-4 text-success me-3"></i>
                <span>
                    On mobile: direct shortcuts on this page.
                </span>
            </div>
            <div class="list-group-item d-flex align-items-center bg-light">
                <i class="bi bi-question-circle-fill fs-4 text-warning me-3"></i>
                <span>
                    <strong>Contextual help:</strong> available in each module via the help icon.
                </span>
            </div>
        </div>
    </section>
</div>',
    fr_FR = '<div class="container my-3">
    <section class="mb-3">
        <div class="card shadow-sm">
            <div class="card-body p-3 p-md-5">
                <h2 class="fw-bold text-primary">Les outils des zones d’administration</h2>
                <p class="lead text-muted mb-4">
                    Cette page permet d’accéder aux outils d''administration selon vos droits.
                </p>

                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3 flex-shrink-0">
                                <i class="bi bi-key-fill fs-4"></i>
                            </div>
                            <div>
                                <h3 class="h5 fw-bold mb-2">Accès et Permissions</h3>
                                <ul class="list-unstyled text-muted small">
                                    <li class="mb-2">
                                        <strong>Visibilité :</strong> La clé jaune n’apparaît dans la barre supérieure que pour les membres ayant des autorisations.
                                    </li>
                                    <li>
                                        <strong>Navigation intelligente :</strong>
                                        <ul class="mt-2 list-unstyled ms-3">
                                            <li class="mb-1">→ Plusieurs zones → menu de sélection</li>
                                            <li>→ Une seule zone → redirection automatique (gain de temps 😊)</li>
                                        </ul>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3 flex-shrink-0">
                                <i class="bi bi-phone fs-4"></i>
                            </div>
                            <div>
                                <h3 class="h5 fw-bold mb-2">Optimisation Mobile</h3>
                                <p class="text-muted small">
                                    Les raccourcis apparaissent aussi directement ici — plus besoin d’ouvrir le menu ☰ sur les écrans étroits.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section>
        <h2 class="h4 mb-3 fw-bold">Ce qu’il faut retenir</h2>
        <div class="list-group">
            <div class="list-group-item d-flex align-items-center">
                <i class="bi bi-key-fill fs-4 text-warning me-3"></i>
                <span>
                    La <strong>clé jaune</strong> n’est visible que pour les membres ayant des autorisations.
                </span>
            </div>
            <div class="list-group-item d-flex align-items-center">
                <i class="bi bi-arrow-right-circle fs-4 text-primary me-3"></i>
                <span>
                    Une seule zone d’administration → redirection automatique.
                </span>
            </div>
            <div class="list-group-item d-flex align-items-center">
                <i class="bi bi-phone fs-4 text-success me-3"></i>
                <span>
                    Sur mobile : raccourcis directs sur cette page.
                </span>
            </div>
            <div class="list-group-item d-flex align-items-center bg-light">
                <i class="bi bi-question-circle-fill fs-4 text-warning me-3"></i>
                <span>
                    <strong>Aide contextuelle :</strong> disponible dans chaque module via l’icône d’aide.
                </span>
            </div>
        </div>
    </section>
</div>'
WHERE Name = 'Help_Admin';
SQL);

        $pdo->exec(
            '
            CREATE TABLE "Order" (
                "Id"	INTEGER,
                "Question"	TEXT NOT NULL,
                "Options"	TEXT NOT NULL,
                "IdArticle"	INTEGER NOT NULL,
                "ClosingDate"	TEXT NOT NULL,
                "Visibility"	TEXT NOT NULL,
                PRIMARY KEY("Id"),
                FOREIGN KEY("IdArticle") REFERENCES "Article"("Id")
            )'
        );
        $pdo->exec(
            '
            CREATE TABLE "OrderReply" (
                "Id"	INTEGER,
                "IdPerson"	INTEGER NOT NULL,
                "IdOrder"	INTEGER NOT NULL,
                "Answers"	TEXT NOT NULL,
                "LastUpdate"	TEXT NOT NULL,
                PRIMARY KEY("Id"),
                FOREIGN KEY("IdOrder") REFERENCES "Order"("Id"),
                FOREIGN KEY("IdPerson") REFERENCES "Person"("Id")
            )'
        );

        return 11;
    }
}
