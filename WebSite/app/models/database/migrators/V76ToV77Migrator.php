<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;
use app\interfaces\DatabaseMigratorInterface;

class V76ToV77Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $pdo->exec(<<<SQL
INSERT OR REPLACE INTO Languages (Name, en_US, fr_FR, pl_PL) VALUES
('Help_Messages',
'<div class="container my-5">
    <section class="mb-5">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="card-title h4 mb-4">How to post a message</h2>
                <p class="text-muted">Messages are linked to an article, an event or a group. Browse to the relevant section to start or join a conversation.</p>
                <div class="row g-4">
 
                    <div class="col-md-4">
                        <div class="d-flex align-items-start">
                            <div class="me-3" style="font-size:1.75rem; line-height:1;">📰</div>
                            <div>
                                <strong>Article</strong>
                                <ol class="text-muted small ps-3 mt-1">
                                    <li>Go to the <a href="/articles">articles list</a></li>
                                    <li>Click the green <i class="bi bi-eye"></i> button to open the article</li>
                                    <li>Click <button class="btn btn-warning btn-sm ms-2">Messages</button> in the top-right navigation bar</li>
                                </ol>
                            </div>
                        </div>
                    </div>
 
                    <div class="col-md-4">
                        <div class="d-flex align-items-start">
                            <div class="me-3" style="font-size:1.75rem; line-height:1;">📅</div>
                            <div>
                                <strong>Event</strong>
                                <ol class="text-muted small ps-3 mt-1">
                                    <li>Go to the <a href="/nextEvents">events list</a></li>
                                    <li>Use the top-right buttons to browse past events if needed</li>
                                    <li>Click the event row, then click <button class="btn btn-warning btn-sm ms-2">Messages</button></li>
                                </ol>
                            </div>
                        </div>
                    </div>
 
                    <div class="col-md-4">
                        <div class="d-flex align-items-start">
                            <div class="me-3" style="font-size:1.75rem; line-height:1;">👥</div>
                            <div>
                                <strong>Group</strong>
                                <ol class="text-muted small ps-3 mt-1">
                                    <li>Go to the <a href="/user/directory">directory</a></li>
                                    <li>Click on a group</li>
                                    <li>If you are a member, a <button class="btn btn-warning btn-sm ms-2">Messages</button> button will appear</li>
                                </ol>
                            </div>
                        </div>
                    </div>
 
                </div>
            </div>
        </div>
    </section>
</div>',
 
'<div class="container my-5">
    <section class="mb-5">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="card-title h4 mb-4">Comment créer un message</h2>
                <p class="text-muted">Les messages sont liés à un article, un événement ou un groupe. Rendez-vous dans la section concernée pour démarrer ou rejoindre une conversation.</p>
                <div class="row g-4">
 
                    <div class="col-md-4">
                        <div class="d-flex align-items-start">
                            <div class="me-3" style="font-size:1.75rem; line-height:1;">📰</div>
                            <div>
                                <strong>Article</strong>
                                <ol class="text-muted small ps-3 mt-1">
                                    <li>Rendez-vous dans la <a href="/articles">liste des articles</a></li>
                                    <li>Cliquez sur le bouton vert <i class="bi bi-eye"></i> pour ouvrir l''article</li>
                                    <li>Cliquez sur <button class="btn btn-warning btn-sm ms-2">Messages</button> dans la barre de navigation en haut à droite</li>
                                </ol>
                            </div>
                        </div>
                    </div>
 
                    <div class="col-md-4">
                        <div class="d-flex align-items-start">
                            <div class="me-3" style="font-size:1.75rem; line-height:1;">📅</div>
                            <div>
                                <strong>Événement</strong>
                                <ol class="text-muted small ps-3 mt-1">
                                    <li>Rendez-vous dans la <a href="/nextEvents">liste des événements</a></li>
                                    <li>Utilisez les boutons en haut à droite pour accéder aux événements passés si besoin</li>
                                    <li>Cliquez sur la ligne de l''événement, puis sur <button class="btn btn-warning btn-sm ms-2">Messages</button></li>
                                </ol>
                            </div>
                        </div>
                    </div>
 
                    <div class="col-md-4">
                        <div class="d-flex align-items-start">
                            <div class="me-3" style="font-size:1.75rem; line-height:1;">👥</div>
                            <div>
                                <strong>Groupe</strong>
                                <ol class="text-muted small ps-3 mt-1">
                                    <li>Rendez-vous dans le <a href="/user/directory">trombinoscope</a></li>
                                    <li>Cliquez sur un groupe</li>
                                    <li>Si vous faites partie du groupe, un bouton <button class="btn btn-warning btn-sm ms-2">Messages</button> apparaît</li>
                                </ol>
                            </div>
                        </div>
                    </div>
 
                </div>
            </div>
        </div>
    </section>
</div>',
 
'<div class="container my-5">
    <section class="mb-5">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="card-title h4 mb-4">Jak dodać wiadomość</h2>
                <p class="text-muted">Wiadomości są powiązane z artykułem, wydarzeniem lub grupą. Przejdź do odpowiedniej sekcji, aby rozpocząć lub dołączyć do rozmowy.</p>
                <div class="row g-4">
 
                    <div class="col-md-4">
                        <div class="d-flex align-items-start">
                            <div class="me-3" style="font-size:1.75rem; line-height:1;">📰</div>
                            <div>
                                <strong>Artykuł</strong>
                                <ol class="text-muted small ps-3 mt-1">
                                    <li>Przejdź do <a href="/articles">listy artykułów</a></li>
                                    <li>Kliknij zielony przycisk <i class="bi bi-eye"></i>, aby otworzyć artykuł</li>
                                    <li>Kliknij <button class="btn btn-warning btn-sm ms-2">Wiadomości</button> na pasku nawigacji w prawym górnym rogu</li>
                                </ol>
                            </div>
                        </div>
                    </div>
 
                    <div class="col-md-4">
                        <div class="d-flex align-items-start">
                            <div class="me-3" style="font-size:1.75rem; line-height:1;">📅</div>
                            <div>
                                <strong>Wydarzenie</strong>
                                <ol class="text-muted small ps-3 mt-1">
                                    <li>Przejdź do <a href="/nextEvents">listy wydarzeń</a></li>
                                    <li>Użyj przycisków w prawym górnym rogu, aby przeglądać minione wydarzenia</li>
                                    <li>Kliknij wiersz wydarzenia, a następnie <button class="btn btn-warning btn-sm ms-2">Wiadomości</button></li>
                                </ol>
                            </div>
                        </div>
                    </div>
 
                    <div class="col-md-4">
                        <div class="d-flex align-items-start">
                            <div class="me-3" style="font-size:1.75rem; line-height:1;">👥</div>
                            <div>
                                <strong>Grupa</strong>
                                <ol class="text-muted small ps-3 mt-1">
                                    <li>Przejdź do <a href="/user/directory">katalogu</a></li>
                                    <li>Kliknij na grupę</li>
                                    <li>Jeśli jesteś członkiem grupy, pojawi się przycisk <button class="btn btn-warning btn-sm ms-2">Wiadomości</button></li>
                                </ol>
                            </div>
                        </div>
                    </div>
 
                </div>
            </div>
        </div>
    </section>
</div>'
);
SQL);

        return 77;
    }
}
