<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;
use app\modules\Common\interfaces\DatabaseMigratorInterface;

final class V101ToV102Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion, PDO $pdoForLog): int
    {
        $pdo->exec(<<<'SQL'
-- Route help texts (Style 1: card + badges)
-- Keys: designer.routes.help.{Class}.{method}
INSERT OR REPLACE INTO Languages (Name, en_US, fr_FR, pl_PL) VALUES
('designer.routes.help.HomeController.home',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">none (isAnybody)</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Home page. Content depends on the visitor''s authorizations, especially for the navbar, sidebar and article list.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">aucune (isAnybody)</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Page d''accueil. Le contenu dépend des autorisations du visiteur notamment pour la barre de navigation (navbar), 
      le menu latéral (sidebar) et la liste des articles.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">brak (isAnybody)</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Home page. Content depends on the visitor''s authorizations, especially for the navbar, sidebar and article list.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.WebmasterController.homeAdmin',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Administration home page (Webmaster).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Page d''accueil de la zone d''administration (Webmaster).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Administration home page (Webmaster).</p>
    </div>
  </div>
</div>'),

('designer.routes.help.WebmasterController.helpAdmin',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Contextual help for the administration zone.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Aide contextuelle de la zone d''administration.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Contextual help for the administration zone.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.WebmasterController.helpWebmaster',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Contextual help for the Webmaster role.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Aide contextuelle du rôle Webmaster.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Contextual help for the Webmaster role.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.VisitorInsightsController.analytics',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">VisitorInsights</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Visitor analytics dashboard (traffic, referrers, page performance).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">VisitorInsights</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Tableau de bord des statistiques visiteurs (trafic, référents, performance des pages).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">VisitorInsights</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Visitor analytics dashboard (traffic, referrers, page performance).</p>
    </div>
  </div>
</div>'),

('designer.routes.help.VisitorInsightsController.helpPage',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">VisitorInsights</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Aide contextuelle du module.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">VisitorInsights</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Aide contextuelle du module.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">VisitorInsights</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Aide contextuelle du module.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.EventAttributeApi.createAttribute',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">EventDesigner</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Creates a new resource (createAttribute).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">EventDesigner</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Crée une nouvelle ressource (createAttribute).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">EventDesigner</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Creates a new resource (createAttribute).</p>
    </div>
  </div>
</div>'),

('designer.routes.help.EventAttributeApi.deleteAttribute',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">EventDesigner</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Deletes a resource (deleteAttribute).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">EventDesigner</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Supprime une ressource (deleteAttribute).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">EventDesigner</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Deletes a resource (deleteAttribute).</p>
    </div>
  </div>
</div>'),

('designer.routes.help.EventAttributeApi.updateAttribute',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">EventDesigner</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Updates a resource (updateAttribute).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">EventDesigner</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Met à jour une ressource (updateAttribute).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">EventDesigner</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Updates a resource (updateAttribute).</p>
    </div>
  </div>
</div>'),

('designer.routes.help.EventAttributeApi.getAttributesByEventType',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">EventDesigner</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « getAttributesByEventType ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">EventDesigner</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Affiche ou retourne les données liées à « getAttributesByEventType ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">EventDesigner</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « getAttributesByEventType ».</p>
    </div>
  </div>
</div>'),

('designer.routes.help.EventAttributeApi.getAttributes',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">EventDesigner</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « getAttributes ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">EventDesigner</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Affiche ou retourne les données liées à « getAttributes ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">EventDesigner</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « getAttributes ».</p>
    </div>
  </div>
</div>'),

('designer.routes.help.CarouselApi.getItems',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">depends on the endpoint (business role)</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « getItems ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">selon l''endpoint (rôle métier)</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Affiche ou retourne les données liées à « getItems ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">zależy od endpointu (rola biznesowa)</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « getItems ».</p>
    </div>
  </div>
</div>'),

('designer.routes.help.CarouselApi.deleteItem',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">depends on the endpoint (business role)</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Deletes a resource (deleteItem).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">selon l''endpoint (rôle métier)</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Supprime une ressource (deleteItem).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">zależy od endpointu (rola biznesowa)</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Deletes a resource (deleteItem).</p>
    </div>
  </div>
</div>'),

('designer.routes.help.CarouselApi.saveItem',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">depends on the endpoint (business role)</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Updates a resource (saveItem).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">selon l''endpoint (rôle métier)</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Met à jour une ressource (saveItem).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">zależy od endpointu (rola biznesowa)</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Updates a resource (saveItem).</p>
    </div>
  </div>
</div>'),

('designer.routes.help.ChatApi.getActiveUsers',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">depends on the endpoint (business role)</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « getActiveUsers ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">selon l''endpoint (rôle métier)</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Affiche ou retourne les données liées à « getActiveUsers ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">zależy od endpointu (rola biznesowa)</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « getActiveUsers ».</p>
    </div>
  </div>
</div>'),

('designer.routes.help.CommunicationApi.updateContactEmail',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">depends on the endpoint (business role)</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Updates a resource (updateContactEmail).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">selon l''endpoint (rôle métier)</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Met à jour une ressource (updateContactEmail).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">zależy od endpointu (rola biznesowa)</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Updates a resource (updateContactEmail).</p>
    </div>
  </div>
</div>'),

('designer.routes.help.CommunicationApi.getMembers',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">depends on the endpoint (business role)</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « getMembers ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">selon l''endpoint (rôle métier)</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Affiche ou retourne les données liées à « getMembers ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">zależy od endpointu (rola biznesowa)</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « getMembers ».</p>
    </div>
  </div>
</div>'),

('designer.routes.help.CommunicationApi.getQuota',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">depends on the endpoint (business role)</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « getQuota ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">selon l''endpoint (rôle métier)</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Affiche ou retourne les données liées à « getQuota ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">zależy od endpointu (rola biznesowa)</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « getQuota ».</p>
    </div>
  </div>
</div>'),

('designer.routes.help.CommunicationApi.sendCommunication',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">depends on the endpoint (business role)</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « sendCommunication » of controller CommunicationApi.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">selon l''endpoint (rôle métier)</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « sendCommunication » du contrôleur CommunicationApi.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">zależy od endpointu (rola biznesowa)</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « sendCommunication » of controller CommunicationApi.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.ArticleApi.designVote',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « designVote » of controller ArticleApi.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « designVote » du contrôleur ArticleApi.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « designVote » of controller ArticleApi.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.EventAvailabilitiesApi.getSlotEvents',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">depends on the endpoint (business role)</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « getSlotEvents ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">selon l''endpoint (rôle métier)</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Affiche ou retourne les données liées à « getSlotEvents ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">zależy od endpointu (rola biznesowa)</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « getSlotEvents ».</p>
    </div>
  </div>
</div>'),

('designer.routes.help.EventApi.getEvent',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">depends on the endpoint (business role)</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « getEvent ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">selon l''endpoint (rôle métier)</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Affiche ou retourne les données liées à « getEvent ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">zależy od endpointu (rola biznesowa)</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « getEvent ».</p>
    </div>
  </div>
</div>'),

('designer.routes.help.EventApi.deleteEvent',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">depends on the endpoint (business role)</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Deletes a resource (deleteEvent).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">selon l''endpoint (rôle métier)</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Supprime une ressource (deleteEvent).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">zależy od endpointu (rola biznesowa)</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Deletes a resource (deleteEvent).</p>
    </div>
  </div>
</div>'),

('designer.routes.help.EventApi.duplicateEvent',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">depends on the endpoint (business role)</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « duplicateEvent » of controller EventApi.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">selon l''endpoint (rôle métier)</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « duplicateEvent » du contrôleur EventApi.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">zależy od endpointu (rola biznesowa)</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « duplicateEvent » of controller EventApi.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.EventNeedApi.getEventNeeds',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">EventDesigner</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « getEventNeeds ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">EventDesigner</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Affiche ou retourne les données liées à « getEventNeeds ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">EventDesigner</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « getEventNeeds ».</p>
    </div>
  </div>
</div>'),

('designer.routes.help.EventApi.saveEvent',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">depends on the endpoint (business role)</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Updates a resource (saveEvent).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">selon l''endpoint (rôle métier)</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Met à jour une ressource (saveEvent).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">zależy od endpointu (rola biznesowa)</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Updates a resource (saveEvent).</p>
    </div>
  </div>
</div>'),

('designer.routes.help.EventApi.sendEmails',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">depends on the endpoint (business role)</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « sendEmails » of controller EventApi.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">selon l''endpoint (rôle métier)</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « sendEmails » du contrôleur EventApi.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">zależy od endpointu (rola biznesowa)</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « sendEmails » of controller EventApi.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.EventSupplyApi.updateSupply',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">EventDesigner</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Updates a resource (updateSupply).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">EventDesigner</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Met à jour une ressource (updateSupply).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">EventDesigner</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Updates a resource (updateSupply).</p>
    </div>
  </div>
</div>'),

('designer.routes.help.HelloAssoApi.checkout',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">depends on the endpoint (business role)</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « checkout » of controller HelloAssoApi.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">selon l''endpoint (rôle métier)</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « checkout » du contrôleur HelloAssoApi.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">zależy od endpointu (rola biznesowa)</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « checkout » of controller HelloAssoApi.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.HelloAssoApi.paymentReturn',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">depends on the endpoint (business role)</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « paymentReturn » of controller HelloAssoApi.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">selon l''endpoint (rôle métier)</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « paymentReturn » du contrôleur HelloAssoApi.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">zależy od endpointu (rola biznesowa)</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « paymentReturn » of controller HelloAssoApi.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.HelloAssoApi.webhook',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">depends on the endpoint (business role)</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « webhook » of controller HelloAssoApi.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">selon l''endpoint (rôle métier)</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « webhook » du contrôleur HelloAssoApi.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">zależy od endpointu (rola biznesowa)</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « webhook » of controller HelloAssoApi.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.ImportApi.getHeadersFromCSV',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">depends on the endpoint (business role)</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « getHeadersFromCSV ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">selon l''endpoint (rôle métier)</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Affiche ou retourne les données liées à « getHeadersFromCSV ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">zależy od endpointu (rola biznesowa)</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « getHeadersFromCSV ».</p>
    </div>
  </div>
</div>'),

('designer.routes.help.ImportApi.importOds',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">depends on the endpoint (business role)</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « importOds » of controller ImportApi.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">selon l''endpoint (rôle métier)</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « importOds » du contrôleur ImportApi.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">zależy od endpointu (rola biznesowa)</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « importOds » of controller ImportApi.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.KanbanApi.getHistory',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">depends on the endpoint (business role)</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « getHistory ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">selon l''endpoint (rôle métier)</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Affiche ou retourne les données liées à « getHistory ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">zależy od endpointu (rola biznesowa)</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « getHistory ».</p>
    </div>
  </div>
</div>'),

('designer.routes.help.KanbanApi.createCard',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">depends on the endpoint (business role)</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Creates a new resource (createCard).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">selon l''endpoint (rôle métier)</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Crée une nouvelle ressource (createCard).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">zależy od endpointu (rola biznesowa)</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Creates a new resource (createCard).</p>
    </div>
  </div>
</div>'),

('designer.routes.help.KanbanApi.deleteCard',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">depends on the endpoint (business role)</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Deletes a resource (deleteCard).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">selon l''endpoint (rôle métier)</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Supprime une ressource (deleteCard).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">zależy od endpointu (rola biznesowa)</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Deletes a resource (deleteCard).</p>
    </div>
  </div>
</div>'),

('designer.routes.help.KanbanApi.moveCard',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">depends on the endpoint (business role)</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « moveCard » of controller KanbanApi.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">selon l''endpoint (rôle métier)</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « moveCard » du contrôleur KanbanApi.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">zależy od endpointu (rola biznesowa)</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « moveCard » of controller KanbanApi.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.KanbanApi.updateCard',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">depends on the endpoint (business role)</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Updates a resource (updateCard).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">selon l''endpoint (rôle métier)</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Met à jour une ressource (updateCard).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">zależy od endpointu (rola biznesowa)</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Updates a resource (updateCard).</p>
    </div>
  </div>
</div>'),

('designer.routes.help.KanbanApi.updateCardStatus',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">depends on the endpoint (business role)</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Updates a resource (updateCardStatus).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">selon l''endpoint (rôle métier)</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Met à jour une ressource (updateCardStatus).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">zależy od endpointu (rola biznesowa)</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Updates a resource (updateCardStatus).</p>
    </div>
  </div>
</div>'),

('designer.routes.help.KanbanApi.createCardType',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">depends on the endpoint (business role)</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Creates a new resource (createCardType).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">selon l''endpoint (rôle métier)</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Crée une nouvelle ressource (createCardType).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">zależy od endpointu (rola biznesowa)</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Creates a new resource (createCardType).</p>
    </div>
  </div>
</div>'),

('designer.routes.help.KanbanApi.deleteCardType',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">depends on the endpoint (business role)</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Deletes a resource (deleteCardType).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">selon l''endpoint (rôle métier)</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Supprime une ressource (deleteCardType).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">zależy od endpointu (rola biznesowa)</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Deletes a resource (deleteCardType).</p>
    </div>
  </div>
</div>'),

('designer.routes.help.KanbanApi.updateCardType',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">depends on the endpoint (business role)</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Updates a resource (updateCardType).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">selon l''endpoint (rôle métier)</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Met à jour une ressource (updateCardType).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">zależy od endpointu (rola biznesowa)</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Updates a resource (updateCardType).</p>
    </div>
  </div>
</div>'),

('designer.routes.help.KanbanApi.getProject',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">depends on the endpoint (business role)</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « getProject ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">selon l''endpoint (rôle métier)</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Affiche ou retourne les données liées à « getProject ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">zależy od endpointu (rola biznesowa)</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « getProject ».</p>
    </div>
  </div>
</div>'),

('designer.routes.help.KanbanApi.getProjectCardTypes',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">depends on the endpoint (business role)</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « getProjectCardTypes ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">selon l''endpoint (rôle métier)</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Affiche ou retourne les données liées à « getProjectCardTypes ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">zależy od endpointu (rola biznesowa)</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « getProjectCardTypes ».</p>
    </div>
  </div>
</div>'),

('designer.routes.help.KanbanApi.getProjectCards',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">depends on the endpoint (business role)</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « getProjectCards ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">selon l''endpoint (rôle métier)</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Affiche ou retourne les données liées à « getProjectCards ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">zależy od endpointu (rola biznesowa)</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « getProjectCards ».</p>
    </div>
  </div>
</div>'),

('designer.routes.help.KanbanApi.createProject',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">depends on the endpoint (business role)</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Creates a new resource (createProject).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">selon l''endpoint (rôle métier)</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Crée une nouvelle ressource (createProject).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">zależy od endpointu (rola biznesowa)</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Creates a new resource (createProject).</p>
    </div>
  </div>
</div>'),

('designer.routes.help.KanbanApi.deleteProject',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">depends on the endpoint (business role)</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Deletes a resource (deleteProject).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">selon l''endpoint (rôle métier)</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Supprime une ressource (deleteProject).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">zależy od endpointu (rola biznesowa)</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Deletes a resource (deleteProject).</p>
    </div>
  </div>
</div>'),

('designer.routes.help.KanbanApi.updateProject',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">depends on the endpoint (business role)</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Updates a resource (updateProject).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">selon l''endpoint (rôle métier)</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Met à jour une ressource (updateProject).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">zależy od endpointu (rola biznesowa)</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Updates a resource (updateProject).</p>
    </div>
  </div>
</div>'),

('designer.routes.help.KaraokeApi.handleApiRequest',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">depends on the endpoint (business role)</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « handleApiRequest » of controller KaraokeApi.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">selon l''endpoint (rôle métier)</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « handleApiRequest » du contrôleur KaraokeApi.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">zależy od endpointu (rola biznesowa)</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « handleApiRequest » of controller KaraokeApi.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.WebmasterApi.lastVersion',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « lastVersion » of controller WebmasterApi.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « lastVersion » du contrôleur WebmasterApi.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « lastVersion » of controller WebmasterApi.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.LeapfrogApi.logMovement',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">depends on the endpoint (business role)</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « logMovement » of controller LeapfrogApi.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">selon l''endpoint (rôle métier)</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « logMovement » du contrôleur LeapfrogApi.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">zależy od endpointu (rola biznesowa)</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « logMovement » of controller LeapfrogApi.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.LoanApi.getAvailability',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">depends on the endpoint (business role)</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « getAvailability ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">selon l''endpoint (rôle métier)</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Affiche ou retourne les données liées à « getAvailability ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">zależy od endpointu (rola biznesowa)</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « getAvailability ».</p>
    </div>
  </div>
</div>'),

('designer.routes.help.LoanApi.getCalendarEvents',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">depends on the endpoint (business role)</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « getCalendarEvents ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">selon l''endpoint (rôle métier)</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Affiche ou retourne les données liées à « getCalendarEvents ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">zależy od endpointu (rola biznesowa)</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « getCalendarEvents ».</p>
    </div>
  </div>
</div>'),

('designer.routes.help.LoanApi.getItem',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">depends on the endpoint (business role)</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « getItem ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">selon l''endpoint (rôle métier)</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Affiche ou retourne les données liées à « getItem ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">zależy od endpointu (rola biznesowa)</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « getItem ».</p>
    </div>
  </div>
</div>'),

('designer.routes.help.LoanApi.deleteItem',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">depends on the endpoint (business role)</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Deletes a resource (deleteItem).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">selon l''endpoint (rôle métier)</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Supprime une ressource (deleteItem).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">zależy od endpointu (rola biznesowa)</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Deletes a resource (deleteItem).</p>
    </div>
  </div>
</div>'),

('designer.routes.help.LoanApi.saveItem',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">depends on the endpoint (business role)</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Updates a resource (saveItem).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">selon l''endpoint (rôle métier)</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Met à jour une ressource (saveItem).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">zależy od endpointu (rola biznesowa)</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Updates a resource (saveItem).</p>
    </div>
  </div>
</div>'),

('designer.routes.help.LoanApi.getItems',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">depends on the endpoint (business role)</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « getItems ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">selon l''endpoint (rôle métier)</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Affiche ou retourne les données liées à « getItems ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">zależy od endpointu (rola biznesowa)</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « getItems ».</p>
    </div>
  </div>
</div>'),

('designer.routes.help.LoanApi.getLoan',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">depends on the endpoint (business role)</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « getLoan ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">selon l''endpoint (rôle métier)</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Affiche ou retourne les données liées à « getLoan ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">zależy od endpointu (rola biznesowa)</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « getLoan ».</p>
    </div>
  </div>
</div>'),

('designer.routes.help.LoanApi.cancelLoan',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">depends on the endpoint (business role)</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « cancelLoan » of controller LoanApi.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">selon l''endpoint (rôle métier)</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « cancelLoan » du contrôleur LoanApi.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">zależy od endpointu (rola biznesowa)</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « cancelLoan » of controller LoanApi.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.LoanApi.returnLoan',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">depends on the endpoint (business role)</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « returnLoan » of controller LoanApi.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">selon l''endpoint (rôle métier)</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « returnLoan » du contrôleur LoanApi.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">zależy od endpointu (rola biznesowa)</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « returnLoan » of controller LoanApi.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.LoanApi.saveLoan',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">depends on the endpoint (business role)</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Updates a resource (saveLoan).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">selon l''endpoint (rôle métier)</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Met à jour une ressource (saveLoan).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">zależy od endpointu (rola biznesowa)</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Updates a resource (saveLoan).</p>
    </div>
  </div>
</div>'),

('designer.routes.help.LoanApi.getLoans',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">depends on the endpoint (business role)</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « getLoans ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">selon l''endpoint (rôle métier)</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Affiche ou retourne les données liées à « getLoans ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">zależy od endpointu (rola biznesowa)</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « getLoans ».</p>
    </div>
  </div>
</div>'),

('designer.routes.help.LoanApi.getReservation',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">depends on the endpoint (business role)</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « getReservation ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">selon l''endpoint (rôle métier)</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Affiche ou retourne les données liées à « getReservation ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">zależy od endpointu (rola biznesowa)</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « getReservation ».</p>
    </div>
  </div>
</div>'),

('designer.routes.help.LoanApi.cancelReservation',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">depends on the endpoint (business role)</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « cancelReservation » of controller LoanApi.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">selon l''endpoint (rôle métier)</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « cancelReservation » du contrôleur LoanApi.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">zależy od endpointu (rola biznesowa)</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « cancelReservation » of controller LoanApi.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.LoanApi.saveReservation',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">depends on the endpoint (business role)</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Updates a resource (saveReservation).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">selon l''endpoint (rôle métier)</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Met à jour une ressource (saveReservation).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">zależy od endpointu (rola biznesowa)</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Updates a resource (saveReservation).</p>
    </div>
  </div>
</div>'),

('designer.routes.help.LoanApi.getReservations',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">depends on the endpoint (business role)</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « getReservations ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">selon l''endpoint (rôle métier)</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Affiche ou retourne les données liées à « getReservations ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">zależy od endpointu (rola biznesowa)</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « getReservations ».</p>
    </div>
  </div>
</div>'),

('designer.routes.help.MakeTheTargetApi.logMovement',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">depends on the endpoint (business role)</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « logMovement » of controller MakeTheTargetApi.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">selon l''endpoint (rôle métier)</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « logMovement » du contrôleur MakeTheTargetApi.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">zależy od endpointu (rola biznesowa)</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « logMovement » of controller MakeTheTargetApi.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.MediaApi.deleteFile',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">depends on the endpoint (business role)</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Deletes a resource (deleteFile).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">selon l''endpoint (rôle métier)</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Supprime une ressource (deleteFile).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">zależy od endpointu (rola biznesowa)</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Deletes a resource (deleteFile).</p>
    </div>
  </div>
</div>'),

('designer.routes.help.MediaApi.editImage',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">depends on the endpoint (business role)</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Updates a resource (editImage).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">selon l''endpoint (rôle métier)</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Met à jour une ressource (editImage).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">zależy od endpointu (rola biznesowa)</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Updates a resource (editImage).</p>
    </div>
  </div>
</div>'),

('designer.routes.help.MediaApi.isShared',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">depends on the endpoint (business role)</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « isShared » of controller MediaApi.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">selon l''endpoint (rôle métier)</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « isShared » du contrôleur MediaApi.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">zależy od endpointu (rola biznesowa)</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « isShared » of controller MediaApi.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.MediaApi.removeFileShare',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">depends on the endpoint (business role)</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Deletes a resource (removeFileShare).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">selon l''endpoint (rôle métier)</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Supprime une ressource (removeFileShare).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">zależy od endpointu (rola biznesowa)</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Deletes a resource (removeFileShare).</p>
    </div>
  </div>
</div>'),

('designer.routes.help.MediaApi.shareFile',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">depends on the endpoint (business role)</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « shareFile » of controller MediaApi.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">selon l''endpoint (rôle métier)</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « shareFile » du contrôleur MediaApi.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">zależy od endpointu (rola biznesowa)</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « shareFile » of controller MediaApi.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.MediaApi.uploadFile',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">depends on the endpoint (business role)</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « uploadFile » of controller MediaApi.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">selon l''endpoint (rôle métier)</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « uploadFile » du contrôleur MediaApi.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">zależy od endpointu (rola biznesowa)</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « uploadFile » of controller MediaApi.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.MenuItemApi.deleteItem',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">Designer / HomeDesigner / MenuDesigner</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Deletes a resource (deleteItem).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">Designer / HomeDesigner / MenuDesigner</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Supprime une ressource (deleteItem).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">Designer / HomeDesigner / MenuDesigner</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Deletes a resource (deleteItem).</p>
    </div>
  </div>
</div>'),

('designer.routes.help.MenuItemApi.getMenuItem',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">Designer / HomeDesigner / MenuDesigner</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « getMenuItem ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">Designer / HomeDesigner / MenuDesigner</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Affiche ou retourne les données liées à « getMenuItem ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">Designer / HomeDesigner / MenuDesigner</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « getMenuItem ».</p>
    </div>
  </div>
</div>'),

('designer.routes.help.MenuItemApi.saveMenuItem',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">Designer / HomeDesigner / MenuDesigner</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Updates a resource (saveMenuItem).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">Designer / HomeDesigner / MenuDesigner</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Met à jour une ressource (saveMenuItem).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">Designer / HomeDesigner / MenuDesigner</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Updates a resource (saveMenuItem).</p>
    </div>
  </div>
</div>'),

('designer.routes.help.MenuItemApi.updatePositions',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">Designer / HomeDesigner / MenuDesigner</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Updates a resource (updatePositions).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">Designer / HomeDesigner / MenuDesigner</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Met à jour une ressource (updatePositions).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">Designer / HomeDesigner / MenuDesigner</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Updates a resource (updatePositions).</p>
    </div>
  </div>
</div>'),

('designer.routes.help.MessageApi.addMessage',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">depends on the endpoint (business role)</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Creates a new resource (addMessage).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">selon l''endpoint (rôle métier)</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Crée une nouvelle ressource (addMessage).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">zależy od endpointu (rola biznesowa)</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Creates a new resource (addMessage).</p>
    </div>
  </div>
</div>'),

('designer.routes.help.MessageApi.deleteMessage',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">depends on the endpoint (business role)</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Deletes a resource (deleteMessage).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">selon l''endpoint (rôle métier)</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Supprime une ressource (deleteMessage).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">zależy od endpointu (rola biznesowa)</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Deletes a resource (deleteMessage).</p>
    </div>
  </div>
</div>'),

('designer.routes.help.MessageApi.deleteMessageImage',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">depends on the endpoint (business role)</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Deletes a resource (deleteMessageImage).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">selon l''endpoint (rôle métier)</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Supprime une ressource (deleteMessageImage).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">zależy od endpointu (rola biznesowa)</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Deletes a resource (deleteMessageImage).</p>
    </div>
  </div>
</div>'),

('designer.routes.help.MessageApi.updateMessage',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">depends on the endpoint (business role)</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Updates a resource (updateMessage).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">selon l''endpoint (rôle métier)</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Met à jour une ressource (updateMessage).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">zależy od endpointu (rola biznesowa)</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Updates a resource (updateMessage).</p>
    </div>
  </div>
</div>'),

('designer.routes.help.EventNeedApi.deleteNeed',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">EventDesigner</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Deletes a resource (deleteNeed).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">EventDesigner</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Supprime une ressource (deleteNeed).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">EventDesigner</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Deletes a resource (deleteNeed).</p>
    </div>
  </div>
</div>'),

('designer.routes.help.EventNeedApi.saveNeed',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">EventDesigner</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Updates a resource (saveNeed).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">EventDesigner</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Met à jour une ressource (saveNeed).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">EventDesigner</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Updates a resource (saveNeed).</p>
    </div>
  </div>
</div>'),

('designer.routes.help.EventNeedTypeApi.deleteNeedType',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">EventDesigner</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Deletes a resource (deleteNeedType).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">EventDesigner</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Supprime une ressource (deleteNeedType).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">EventDesigner</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Deletes a resource (deleteNeedType).</p>
    </div>
  </div>
</div>'),

('designer.routes.help.EventNeedTypeApi.saveNeedType',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">EventDesigner</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Updates a resource (saveNeedType).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">EventDesigner</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Met à jour une ressource (saveNeedType).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">EventDesigner</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Updates a resource (saveNeedType).</p>
    </div>
  </div>
</div>'),

('designer.routes.help.EventNeedTypeApi.getNeedsByNeedType',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">EventDesigner</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « getNeedsByNeedType ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">EventDesigner</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Affiche ou retourne les données liées à « getNeedsByNeedType ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">EventDesigner</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « getNeedsByNeedType ».</p>
    </div>
  </div>
</div>'),

('designer.routes.help.WebmasterApi.sendNotification',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « sendNotification » of controller WebmasterApi.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « sendNotification » du contrôleur WebmasterApi.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « sendNotification » of controller WebmasterApi.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.WebmasterApi.notifySubscribe',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « notifySubscribe » of controller WebmasterApi.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « notifySubscribe » du contrôleur WebmasterApi.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « notifySubscribe » of controller WebmasterApi.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.ArticleApi.saveOrderReply',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Updates a resource (saveOrderReply).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Met à jour une ressource (saveOrderReply).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Updates a resource (saveOrderReply).</p>
    </div>
  </div>
</div>'),

('designer.routes.help.ArticleApi.showOrderReplyForm',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « showOrderReplyForm ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Affiche ou retourne les données liées à « showOrderReplyForm ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « showOrderReplyForm ».</p>
    </div>
  </div>
</div>'),

('designer.routes.help.EventSupplyApi.participantsSupplies',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">EventDesigner</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « participantsSupplies » of controller EventSupplyApi.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">EventDesigner</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « participantsSupplies » du contrôleur EventSupplyApi.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">EventDesigner</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « participantsSupplies » of controller EventSupplyApi.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.GroupApi.getPersonsInGroup',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">depends on the endpoint (business role)</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « getPersonsInGroup ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">selon l''endpoint (rôle métier)</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Affiche ou retourne les données liées à « getPersonsInGroup ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">zależy od endpointu (rola biznesowa)</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « getPersonsInGroup ».</p>
    </div>
  </div>
</div>'),

('designer.routes.help.NotificationApi.registerPushSubscription',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">depends on the endpoint (business role)</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « registerPushSubscription » of controller NotificationApi.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">selon l''endpoint (rôle métier)</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « registerPushSubscription » du contrôleur NotificationApi.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">zależy od endpointu (rola biznesowa)</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « registerPushSubscription » of controller NotificationApi.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.NotificationApi.deletePushSubscription',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">depends on the endpoint (business role)</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Deletes a resource (deletePushSubscription).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">selon l''endpoint (rôle métier)</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Supprime une ressource (deletePushSubscription).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">zależy od endpointu (rola biznesowa)</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Deletes a resource (deletePushSubscription).</p>
    </div>
  </div>
</div>'),

('designer.routes.help.GroupApi.addToGroup',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">depends on the endpoint (business role)</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Creates a new resource (addToGroup).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">selon l''endpoint (rôle métier)</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Crée une nouvelle ressource (addToGroup).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">zależy od endpointu (rola biznesowa)</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Creates a new resource (addToGroup).</p>
    </div>
  </div>
</div>'),

('designer.routes.help.GroupApi.removeFromGroup',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">depends on the endpoint (business role)</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Deletes a resource (removeFromGroup).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">selon l''endpoint (rôle métier)</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Supprime une ressource (removeFromGroup).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">zależy od endpointu (rola biznesowa)</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Deletes a resource (removeFromGroup).</p>
    </div>
  </div>
</div>'),

('designer.routes.help.ArticleApi.saveSurveyReply',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Updates a resource (saveSurveyReply).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Met à jour une ressource (saveSurveyReply).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Updates a resource (saveSurveyReply).</p>
    </div>
  </div>
</div>'),

('designer.routes.help.ArticleApi.showSurveyReplyForm',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « showSurveyReplyForm ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Affiche ou retourne les données liées à « showSurveyReplyForm ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « showSurveyReplyForm ».</p>
    </div>
  </div>
</div>'),

('designer.routes.help.TranslatorApi.save',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">depends on the endpoint (business role)</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Updates a resource (save).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">selon l''endpoint (rôle métier)</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Met à jour une ressource (save).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">zależy od endpointu (rola biznesowa)</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Updates a resource (save).</p>
    </div>
  </div>
</div>'),

('designer.routes.help.WebmasterApi.getVapidPublicKey',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « getVapidPublicKey ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Affiche ou retourne les données liées à « getVapidPublicKey ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « getVapidPublicKey ».</p>
    </div>
  </div>
</div>'),

('designer.routes.help.VisitorInsightsApi.getCreationTimeDistribution',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">VisitorInsights</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « getCreationTimeDistribution ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">VisitorInsights</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Affiche ou retourne les données liées à « getCreationTimeDistribution ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">VisitorInsights</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « getCreationTimeDistribution ».</p>
    </div>
  </div>
</div>'),

('designer.routes.help.VisitorInsightsApi.getCreationTimeTrend',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">VisitorInsights</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « getCreationTimeTrend ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">VisitorInsights</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Affiche ou retourne les données liées à « getCreationTimeTrend ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">JSON</span>
        <span class="badge bg-secondary">VisitorInsights</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « getCreationTimeTrend ».</p>
    </div>
  </div>
</div>'),

('designer.routes.help.ArticleController.show',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « show ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Affiche ou retourne les données liées à « show ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « show ».</p>
    </div>
  </div>
</div>'),

('designer.routes.help.ArticleController.carousel',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « carousel » of controller ArticleController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « carousel » du contrôleur ArticleController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « carousel » of controller ArticleController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.ArticleController.changeOwner',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « changeOwner » of controller ArticleController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « changeOwner » du contrôleur ArticleController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « changeOwner » of controller ArticleController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.ArticleController.showArticleChat',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « showArticleChat ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Affiche ou retourne les données liées à « showArticleChat ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « showArticleChat ».</p>
    </div>
  </div>
</div>'),

('designer.routes.help.ArticleController.create',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Creates a new resource (create).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Crée une nouvelle ressource (create).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Creates a new resource (create).</p>
    </div>
  </div>
</div>'),

('designer.routes.help.ArticleController.delete',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Deletes a resource (delete).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Supprime une ressource (delete).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Deletes a resource (delete).</p>
    </div>
  </div>
</div>'),

('designer.routes.help.ArticleController.edit',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Updates a resource (edit).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Met à jour une ressource (edit).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Updates a resource (edit).</p>
    </div>
  </div>
</div>'),

('designer.routes.help.ArticleController.update',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Updates a resource (update).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Met à jour une ressource (update).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Updates a resource (update).</p>
    </div>
  </div>
</div>'),

('designer.routes.help.ArticleListController.index',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « index » of controller ArticleListController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « index » du contrôleur ArticleListController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « index » of controller ArticleListController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.RssController.articlesRssGenerator',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « articlesRssGenerator » of controller RssController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « articlesRssGenerator » du contrôleur RssController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « articlesRssGenerator » of controller RssController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.ArticleStatisticsController.showArticleCrosstab',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « showArticleCrosstab ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Affiche ou retourne les données liées à « showArticleCrosstab ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « showArticleCrosstab ».</p>
    </div>
  </div>
</div>'),

('designer.routes.help.ArticleListController.publicIndex',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « publicIndex » of controller ArticleListController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « publicIndex » du contrôleur ArticleListController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « publicIndex » of controller ArticleListController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.ArwardsController.setArward',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « setArward » of controller ArwardsController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « setArward » du contrôleur ArwardsController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « setArward » of controller ArwardsController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.ArwardsController.seeArwards',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « seeArwards » of controller ArwardsController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « seeArwards » du contrôleur ArwardsController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « seeArwards » of controller ArwardsController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.AstronomyController.show',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">depends on the connected user</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « show ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">dépend de l''utilisateur connecté</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Affiche ou retourne les données liées à « show ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">zależy od zalogowanego użytkownika</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « show ».</p>
    </div>
  </div>
</div>'),

('designer.routes.help.AstronomyController.saveLocation',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">depends on the connected user</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Updates a resource (saveLocation).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">dépend de l''utilisateur connecté</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Met à jour une ressource (saveLocation).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">zależy od zalogowanego użytkownika</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Updates a resource (saveLocation).</p>
    </div>
  </div>
</div>'),

('designer.routes.help.EventAvailabilitiesController.showAvailabilityStats',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">depends on the connected user</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « showAvailabilityStats ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">dépend de l''utilisateur connecté</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Affiche ou retourne les données liées à « showAvailabilityStats ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">zależy od zalogowanego użytkownika</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « showAvailabilityStats ».</p>
    </div>
  </div>
</div>'),

('designer.routes.help.BackupController.showBackupForm',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">depends on the connected user</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « showBackupForm ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">dépend de l''utilisateur connecté</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Affiche ou retourne les données liées à « showBackupForm ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">zależy od zalogowanego użytkownika</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « showBackupForm ».</p>
    </div>
  </div>
</div>'),

('designer.routes.help.BackupController.download',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">depends on the connected user</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « download » of controller BackupController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">dépend de l''utilisateur connecté</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « download » du contrôleur BackupController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">zależy od zalogowanego użytkownika</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « download » of controller BackupController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.WebmasterController.clubCustomizationEdit',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « clubCustomizationEdit » of controller WebmasterController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « clubCustomizationEdit » du contrôleur WebmasterController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « clubCustomizationEdit » of controller WebmasterController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.WebmasterController.clubCustomizationSave',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « clubCustomizationSave » of controller WebmasterController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « clubCustomizationSave » du contrôleur WebmasterController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « clubCustomizationSave » of controller WebmasterController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.CommunicationController.edit',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">depends on the connected user</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Updates a resource (edit).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">dépend de l''utilisateur connecté</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Met à jour une ressource (edit).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">zależy od zalogowanego użytkownika</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Updates a resource (edit).</p>
    </div>
  </div>
</div>'),

('designer.routes.help.ContactController.contact',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">none or connected member</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « contact » of controller ContactController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">aucune ou membre connecté</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « contact » du contrôleur ContactController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">brak lub zalogowany członek</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « contact » of controller ContactController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.VisitorInsightsController.crossTab',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">VisitorInsights</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « crossTab » of controller VisitorInsightsController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">VisitorInsights</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « crossTab » du contrôleur VisitorInsightsController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">VisitorInsights</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « crossTab » of controller VisitorInsightsController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.MediaController.serveFile',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « serveFile » of controller MediaController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « serveFile » du contrôleur MediaController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « serveFile » of controller MediaController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.DbBrowserController.index',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « index » of controller DbBrowserController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « index » du contrôleur DbBrowserController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « index » of controller DbBrowserController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.DbBrowserController.showTable',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « showTable ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Affiche ou retourne les données liées à « showTable ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « showTable ».</p>
    </div>
  </div>
</div>'),

('designer.routes.help.DbBrowserController.showCreateForm',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « showCreateForm ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Affiche ou retourne les données liées à « showCreateForm ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « showCreateForm ».</p>
    </div>
  </div>
</div>'),

('designer.routes.help.DbBrowserController.createRecord',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Creates a new resource (createRecord).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Crée une nouvelle ressource (createRecord).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Creates a new resource (createRecord).</p>
    </div>
  </div>
</div>'),

('designer.routes.help.DbBrowserController.deleteRecord',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Deletes a resource (deleteRecord).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Supprime une ressource (deleteRecord).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Deletes a resource (deleteRecord).</p>
    </div>
  </div>
</div>'),

('designer.routes.help.DbBrowserController.showEditForm',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « showEditForm ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Affiche ou retourne les données liées à « showEditForm ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « showEditForm ».</p>
    </div>
  </div>
</div>'),

('designer.routes.help.DbBrowserController.updateRecord',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Updates a resource (updateRecord).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Met à jour une ressource (updateRecord).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Updates a resource (updateRecord).</p>
    </div>
  </div>
</div>'),

('designer.routes.help.DesignController.create',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Designer / HomeDesigner / MenuDesigner</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Creates a new resource (create).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Designer / HomeDesigner / MenuDesigner</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Crée une nouvelle ressource (create).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Designer / HomeDesigner / MenuDesigner</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Creates a new resource (create).</p>
    </div>
  </div>
</div>'),

('designer.routes.help.DesignController.save',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Designer / HomeDesigner / MenuDesigner</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Updates a resource (save).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Designer / HomeDesigner / MenuDesigner</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Met à jour une ressource (save).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Designer / HomeDesigner / MenuDesigner</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Updates a resource (save).</p>
    </div>
  </div>
</div>'),

('designer.routes.help.DesignerController.homeDesigner',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Designer / HomeDesigner / MenuDesigner</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Designer module home page.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Designer / HomeDesigner / MenuDesigner</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Page d''accueil du module Designer.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Designer / HomeDesigner / MenuDesigner</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Designer module home page.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.DesignerController.help',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Designer / HomeDesigner / MenuDesigner</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Contextual help page for the module.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Designer / HomeDesigner / MenuDesigner</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Page d''aide contextuelle du module.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Designer / HomeDesigner / MenuDesigner</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Contextual help page for the module.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.DesignController.index',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Designer / HomeDesigner / MenuDesigner</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « index » of controller DesignController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Designer / HomeDesigner / MenuDesigner</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « index » du contrôleur DesignController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Designer / HomeDesigner / MenuDesigner</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « index » of controller DesignController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.DesignController.help',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Designer / HomeDesigner / MenuDesigner</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Contextual help page for the module.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Designer / HomeDesigner / MenuDesigner</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Page d''aide contextuelle du module.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Designer / HomeDesigner / MenuDesigner</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Contextual help page for the module.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.EventEmailController.fetchEmails',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">depends on the connected user</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « fetchEmails » of controller EventEmailController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">dépend de l''utilisateur connecté</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « fetchEmails » du contrôleur EventEmailController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">zależy od zalogowanego użytkownika</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « fetchEmails » of controller EventEmailController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.EventEmailController.copyEmails',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">depends on the connected user</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « copyEmails » of controller EventEmailController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">dépend de l''utilisateur connecté</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « copyEmails » du contrôleur EventEmailController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">zależy od zalogowanego użytkownika</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « copyEmails » of controller EventEmailController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.ArticleController.fetchEmailsForArticle',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « fetchEmailsForArticle » of controller ArticleController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « fetchEmailsForArticle » du contrôleur ArticleController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « fetchEmailsForArticle » of controller ArticleController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.EventEmailController.help',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">according to the module</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Contextual help page for the module.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">selon le module</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Page d''aide contextuelle du module.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">zgodnie z modułem</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Contextual help page for the module.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.EventController.show',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">depends on the connected user</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « show ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">dépend de l''utilisateur connecté</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Affiche ou retourne les données liées à « show ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">zależy od zalogowanego użytkownika</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « show ».</p>
    </div>
  </div>
</div>'),

('designer.routes.help.EventController.registerSet',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">depends on the connected user</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « registerSet » of controller EventController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">dépend de l''utilisateur connecté</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « registerSet » du contrôleur EventController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">zależy od zalogowanego użytkownika</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « registerSet » of controller EventController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.EventController.registerUnset',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">depends on the connected user</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « registerUnset » of controller EventController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">dépend de l''utilisateur connecté</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « registerUnset » du contrôleur EventController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">zależy od zalogowanego użytkownika</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « registerUnset » of controller EventController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.EventController.showEventChat',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">depends on the connected user</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « showEventChat ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">dépend de l''utilisateur connecté</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Affiche ou retourne les données liées à « showEventChat ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">zależy od zalogowanego użytkownika</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « showEventChat ».</p>
    </div>
  </div>
</div>'),

('designer.routes.help.EventController.home',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">according to the module</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Home page. Content depends on the visitor''s authorizations, especially for the navbar, sidebar and article list.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">selon le module</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Page d''accueil. Le contenu dépend des autorisations du visiteur notamment pour la barre de navigation (navbar), 
      le menu latéral (sidebar) et la liste des articles.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">zgodnie z modułem</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Home page. Content depends on the visitor''s authorizations, especially for the navbar, sidebar and article list.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.EventController.help',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">according to the module</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Contextual help page for the module.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">selon le module</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Page d''aide contextuelle du module.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">zgodnie z modułem</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Contextual help page for the module.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.EventTypeController.create',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">EventDesigner</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Creates a new resource (create).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">EventDesigner</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Crée une nouvelle ressource (create).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">EventDesigner</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Creates a new resource (create).</p>
    </div>
  </div>
</div>'),

('designer.routes.help.EventTypeController.delete',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">EventDesigner</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Deletes a resource (delete).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">EventDesigner</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Supprime une ressource (delete).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">EventDesigner</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Deletes a resource (delete).</p>
    </div>
  </div>
</div>'),

('designer.routes.help.EventTypeController.edit',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">EventDesigner</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Updates a resource (edit).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">EventDesigner</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Met à jour une ressource (edit).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">EventDesigner</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Updates a resource (edit).</p>
    </div>
  </div>
</div>'),

('designer.routes.help.EventTypeController.update',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">EventDesigner</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Updates a resource (update).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">EventDesigner</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Met à jour une ressource (update).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">EventDesigner</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Updates a resource (update).</p>
    </div>
  </div>
</div>'),

('designer.routes.help.EventTypeController.index',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">EventDesigner</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « index » of controller EventTypeController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">EventDesigner</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « index » du contrôleur EventTypeController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">EventDesigner</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « index » of controller EventTypeController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.RssController.eventsRssGenerator',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « eventsRssGenerator » of controller RssController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « eventsRssGenerator » du contrôleur RssController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « eventsRssGenerator » of controller RssController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.EventController.events_crossTab_help',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">according to the module</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Help page: events_crossTab_help.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">selon le module</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Page d''aide : events_crossTab_help.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">zgodnie z modułem</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Help page: events_crossTab_help.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.EventController.showEventCrosstab',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">depends on the connected user</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « showEventCrosstab ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">dépend de l''utilisateur connecté</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Affiche ou retourne les données liées à « showEventCrosstab ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">zależy od zalogowanego użytkownika</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « showEventCrosstab ».</p>
    </div>
  </div>
</div>'),

('designer.routes.help.EventGuestController.guest',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">depends on the connected user</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « guest » of controller EventGuestController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">dépend de l''utilisateur connecté</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « guest » du contrôleur EventGuestController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">zależy od zalogowanego użytkownika</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « guest » of controller EventGuestController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.EventGuestController.guestInvite',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">depends on the connected user</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « guestInvite » of controller EventGuestController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">dépend de l''utilisateur connecté</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « guestInvite » du contrôleur EventGuestController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">zależy od zalogowanego użytkownika</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « guestInvite » of controller EventGuestController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.EventController.events_guest_help',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">according to the module</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Help page: events_guest_help.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">selon le module</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Page d''aide : events_guest_help.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">zgodnie z modułem</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Help page: events_guest_help.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.ExportController.showExportForm',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">PersonManager</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « showExportForm ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">PersonManager</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Affiche ou retourne les données liées à « showExportForm ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">PersonManager</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « showExportForm ».</p>
    </div>
  </div>
</div>'),

('designer.routes.help.ExportController.download',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">PersonManager</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « download » of controller ExportController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">PersonManager</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « download » du contrôleur ExportController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">PersonManager</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « download » of controller ExportController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.FFAController.searchMember',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">none or connected member</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « searchMember » of controller FFAController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">aucune ou membre connecté</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « searchMember » du contrôleur FFAController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">brak lub zalogowany członek</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « searchMember » of controller FFAController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.KaraokeController.play',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">depends on the connected user</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « play » of controller KaraokeController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">dépend de l''utilisateur connecté</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « play » du contrôleur KaraokeController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">zależy od zalogowanego użytkownika</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « play » of controller KaraokeController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.KaraokeController.files',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">depends on the connected user</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « files » of controller KaraokeController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">dépend de l''utilisateur connecté</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « files » du contrôleur KaraokeController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">zależy od zalogowanego użytkownika</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « files » of controller KaraokeController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.LeapfrogController.play',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">depends on the connected user</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « play » of controller LeapfrogController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">dépend de l''utilisateur connecté</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « play » du contrôleur LeapfrogController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">zależy od zalogowanego użytkownika</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « play » of controller LeapfrogController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.LeapfrogController.statistics',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">depends on the connected user</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « statistics » of controller LeapfrogController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">dépend de l''utilisateur connecté</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « statistics » du contrôleur LeapfrogController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">zależy od zalogowanego użytkownika</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « statistics » of controller LeapfrogController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.MakeTheTargetController.play',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">depends on the connected user</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « play » of controller MakeTheTargetController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">dépend de l''utilisateur connecté</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « play » du contrôleur MakeTheTargetController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">zależy od zalogowanego użytkownika</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « play » of controller MakeTheTargetController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.MakeTheTargetController.statistics',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">depends on the connected user</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « statistics » of controller MakeTheTargetController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">dépend de l''utilisateur connecté</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « statistics » du contrôleur MakeTheTargetController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">zależy od zalogowanego użytkownika</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « statistics » of controller MakeTheTargetController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.GameManagerController.games',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">depends on the connected user</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « games » of controller GameManagerController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">dépend de l''utilisateur connecté</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « games » du contrôleur GameManagerController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">zależy od zalogowanego użytkownika</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « games » of controller GameManagerController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.GameManagerController.help',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">according to the module</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Contextual help page for the module.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">selon le module</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Page d''aide contextuelle du module.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">zgodnie z modułem</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Contextual help page for the module.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.GroupController.showGroupChat',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">PersonManager</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « showGroupChat ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">PersonManager</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Affiche ou retourne les données liées à « showGroupChat ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">PersonManager</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « showGroupChat ».</p>
    </div>
  </div>
</div>'),

('designer.routes.help.GroupController.groupCreate',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">PersonManager</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « groupCreate » of controller GroupController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">PersonManager</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « groupCreate » du contrôleur GroupController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">PersonManager</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « groupCreate » of controller GroupController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.GroupController.groupCreateSave',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">PersonManager</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « groupCreateSave » of controller GroupController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">PersonManager</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « groupCreateSave » du contrôleur GroupController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">PersonManager</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « groupCreateSave » of controller GroupController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.GroupController.groupDelete',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">PersonManager</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « groupDelete » of controller GroupController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">PersonManager</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « groupDelete » du contrôleur GroupController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">PersonManager</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « groupDelete » of controller GroupController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.GroupController.groupEdit',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">PersonManager</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « groupEdit » of controller GroupController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">PersonManager</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « groupEdit » du contrôleur GroupController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">PersonManager</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « groupEdit » of controller GroupController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.GroupController.groupEditSave',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">PersonManager</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « groupEditSave » of controller GroupController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">PersonManager</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « groupEditSave » du contrôleur GroupController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">PersonManager</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « groupEditSave » of controller GroupController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.GroupController.groupIndex',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">PersonManager</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « groupIndex » of controller GroupController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">PersonManager</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « groupIndex » du contrôleur GroupController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">PersonManager</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « groupIndex » of controller GroupController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.WebmasterController.helloassoCredentialsEdit',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « helloassoCredentialsEdit » of controller WebmasterController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « helloassoCredentialsEdit » du contrôleur WebmasterController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « helloassoCredentialsEdit » of controller WebmasterController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.WebmasterController.helloassoCredentialsSave',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « helloassoCredentialsSave » of controller WebmasterController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « helloassoCredentialsSave » du contrôleur WebmasterController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « helloassoCredentialsSave » of controller WebmasterController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.HomeController.help',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">none (isAnybody)</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Contextual help page for the module.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">aucune (isAnybody)</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Page d''aide contextuelle du module.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">brak (isAnybody)</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Contextual help page for the module.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.CommunicationController.help',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">according to the module</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Contextual help page for the module.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">selon le module</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Page d''aide contextuelle du module.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">zgodnie z modułem</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Contextual help page for the module.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.ImportController.showImportForm',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">PersonManager</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « showImportForm ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">PersonManager</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Affiche ou retourne les données liées à « showImportForm ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">PersonManager</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « showImportForm ».</p>
    </div>
  </div>
</div>'),

('designer.routes.help.ImportController.processImport',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">PersonManager</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « processImport » of controller ImportController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">PersonManager</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « processImport » du contrôleur ImportController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">PersonManager</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « processImport » of controller ImportController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.WebmasterController.showInstallations',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « showInstallations ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Affiche ou retourne les données liées à « showInstallations ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « showInstallations ».</p>
    </div>
  </div>
</div>'),

('designer.routes.help.KanbanController.board',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">depends on the connected user</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « board » of controller KanbanController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">dépend de l''utilisateur connecté</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « board » du contrôleur KanbanController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">zależy od zalogowanego użytkownika</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « board » of controller KanbanController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.KanbanController.help',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">according to the module</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Contextual help page for the module.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">selon le module</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Page d''aide contextuelle du module.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">zgodnie z modułem</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Contextual help page for the module.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.VisitorInsightsController.showLastVisits',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">VisitorInsights</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « showLastVisits ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">VisitorInsights</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Affiche ou retourne les données liées à « showLastVisits ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">VisitorInsights</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « showLastVisits ».</p>
    </div>
  </div>
</div>'),

('designer.routes.help.HomeController.legalNotice',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">none or connected member</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « legalNotice » of controller HomeController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">aucune ou membre connecté</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « legalNotice » du contrôleur HomeController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">brak lub zalogowany członek</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « legalNotice » of controller HomeController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.LoanController.designer',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">depends on the connected user</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « designer » of controller LoanController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">dépend de l''utilisateur connecté</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « designer » du contrôleur LoanController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">zależy od zalogowanego użytkownika</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « designer » of controller LoanController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.LoanController.calendar',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">depends on the connected user</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « calendar » of controller LoanController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">dépend de l''utilisateur connecté</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « calendar » du contrôleur LoanController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">zależy od zalogowanego użytkownika</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « calendar » of controller LoanController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.LoanController.designerHelp',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">according to the module</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Contextual help for designer.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">selon le module</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Aide contextuelle pour designer.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">zgodnie z modułem</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Contextual help for designer.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.LoanController.manager',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">depends on the connected user</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « manager » of controller LoanController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">dépend de l''utilisateur connecté</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « manager » du contrôleur LoanController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">zależy od zalogowanego użytkownika</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « manager » of controller LoanController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.LoanController.reservations',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">depends on the connected user</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « reservations » of controller LoanController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">dépend de l''utilisateur connecté</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « reservations » du contrôleur LoanController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">zależy od zalogowanego użytkownika</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « reservations » of controller LoanController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.VisitorInsightsController.index',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">VisitorInsights</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « index » of controller VisitorInsightsController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">VisitorInsights</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « index » du contrôleur VisitorInsightsController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">VisitorInsights</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « index » of controller VisitorInsightsController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.MaintenanceController.maintenance',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « maintenance » of controller MaintenanceController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « maintenance » du contrôleur MaintenanceController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « maintenance » of controller MaintenanceController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.MaintenanceController.setSiteUnderMaintenance',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « setSiteUnderMaintenance » of controller MaintenanceController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « setSiteUnderMaintenance » du contrôleur MaintenanceController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « setSiteUnderMaintenance » of controller MaintenanceController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.MaintenanceController.setSiteOnline',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « setSiteOnline » of controller MaintenanceController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « setSiteOnline » du contrôleur MaintenanceController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « setSiteOnline » of controller MaintenanceController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.PwaController.manifest',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">depends on the connected user</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « manifest » of controller PwaController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">dépend de l''utilisateur connecté</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « manifest » du contrôleur PwaController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">zależy od zalogowanego użytkownika</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « manifest » of controller PwaController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.MediaController.viewFileForRedactor',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « viewFileForRedactor » of controller MediaController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « viewFileForRedactor » du contrôleur MediaController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « viewFileForRedactor » of controller MediaController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.MediaController.gpxViewer',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « gpxViewer » of controller MediaController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « gpxViewer » du contrôleur MediaController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « gpxViewer » of controller MediaController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.MediaController.isShared',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « isShared » of controller MediaController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « isShared » du contrôleur MediaController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « isShared » of controller MediaController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.MediaController.listFiles',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « listFiles ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Affiche ou retourne les données liées à « listFiles ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « listFiles ».</p>
    </div>
  </div>
</div>'),

('designer.routes.help.MediaController.help',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Contextual help page for the module.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Page d''aide contextuelle du module.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Contextual help page for the module.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.MediaController.getSharedFile',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « getSharedFile ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Affiche ou retourne les données liées à « getSharedFile ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « getSharedFile ».</p>
    </div>
  </div>
</div>'),

('designer.routes.help.MediaController.showUploadForm',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « showUploadForm ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Affiche ou retourne les données liées à « showUploadForm ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « showUploadForm ».</p>
    </div>
  </div>
</div>'),

('designer.routes.help.MediaController.showUsesInArticles',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « showUsesInArticles ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Affiche ou retourne les données liées à « showUsesInArticles ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « showUsesInArticles ».</p>
    </div>
  </div>
</div>'),

('designer.routes.help.MediaController.showUsesInMessages',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « showUsesInMessages ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Affiche ou retourne les données liées à « showUsesInMessages ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « showUsesInMessages ».</p>
    </div>
  </div>
</div>'),

('designer.routes.help.MemberFieldController.edit',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">PersonManager</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Updates a resource (edit).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">PersonManager</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Met à jour une ressource (edit).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">PersonManager</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Updates a resource (edit).</p>
    </div>
  </div>
</div>'),

('designer.routes.help.MemberFieldController.save',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">PersonManager</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Updates a resource (save).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">PersonManager</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Met à jour une ressource (save).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">PersonManager</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Updates a resource (save).</p>
    </div>
  </div>
</div>'),

('designer.routes.help.VisitorInsightsController.membersAlerts',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">VisitorInsights</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « membersAlerts » of controller VisitorInsightsController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">VisitorInsights</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « membersAlerts » du contrôleur VisitorInsightsController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">VisitorInsights</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « membersAlerts » of controller VisitorInsightsController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.PersonController.membershipSettingsEdit',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">PersonManager</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « membershipSettingsEdit » of controller PersonController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">PersonManager</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « membershipSettingsEdit » du contrôleur PersonController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">PersonManager</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « membershipSettingsEdit » of controller PersonController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.PersonController.membershipSettingsSave',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">PersonManager</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « membershipSettingsSave » of controller PersonController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">PersonManager</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « membershipSettingsSave » du contrôleur PersonController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">PersonManager</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « membershipSettingsSave » of controller PersonController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.MenuItemController.index',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Designer / HomeDesigner / MenuDesigner</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « index » of controller MenuItemController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Designer / HomeDesigner / MenuDesigner</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « index » du contrôleur MenuItemController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Designer / HomeDesigner / MenuDesigner</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « index » of controller MenuItemController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.MenuItemController.showArticle',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Designer / HomeDesigner / MenuDesigner</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « showArticle ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Designer / HomeDesigner / MenuDesigner</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Affiche ou retourne les données liées à « showArticle ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Designer / HomeDesigner / MenuDesigner</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « showArticle ».</p>
    </div>
  </div>
</div>'),

('designer.routes.help.MenuItemController.showArwards',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Designer / HomeDesigner / MenuDesigner</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « showArwards ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Designer / HomeDesigner / MenuDesigner</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Affiche ou retourne les données liées à « showArwards ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Designer / HomeDesigner / MenuDesigner</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « showArwards ».</p>
    </div>
  </div>
</div>'),

('designer.routes.help.EventNeedController.needs',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">EventDesigner</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « needs » of controller EventNeedController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">EventDesigner</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « needs » du contrôleur EventNeedController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">EventDesigner</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « needs » of controller EventNeedController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.EventController.nextEvents',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">depends on the connected user</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « nextEvents » of controller EventController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">dépend de l''utilisateur connecté</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « nextEvents » du contrôleur EventController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">zależy od zalogowanego użytkownika</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « nextEvents » of controller EventController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.EventController.nextEventsHelp',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">according to the module</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Contextual help for nextEvents.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">selon le module</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Aide contextuelle pour nextEvents.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">zgodnie z modułem</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Contextual help for nextEvents.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.WebmasterController.notifications',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « notifications » of controller WebmasterController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « notifications » du contrôleur WebmasterController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « notifications » of controller WebmasterController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.OrderController.add',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Creates a new resource (add).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Crée une nouvelle ressource (add).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Creates a new resource (add).</p>
    </div>
  </div>
</div>'),

('designer.routes.help.OrderController.createOrUpdate',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Creates a new resource (createOrUpdate).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Crée une nouvelle ressource (createOrUpdate).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Creates a new resource (createOrUpdate).</p>
    </div>
  </div>
</div>'),

('designer.routes.help.OrderController.viewResults',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « viewResults » of controller OrderController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « viewResults » du contrôleur OrderController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « viewResults » of controller OrderController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.PersonController.activate',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">PersonManager</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « activate » of controller PersonController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">PersonManager</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « activate » du contrôleur PersonController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">PersonManager</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « activate » of controller PersonController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.PersonController.create',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">PersonManager</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Creates a new resource (create).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">PersonManager</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Crée une nouvelle ressource (create).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">PersonManager</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Creates a new resource (create).</p>
    </div>
  </div>
</div>'),

('designer.routes.help.PersonController.delete',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">PersonManager</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Deletes a resource (delete).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">PersonManager</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Supprime une ressource (delete).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">PersonManager</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Deletes a resource (delete).</p>
    </div>
  </div>
</div>'),

('designer.routes.help.PersonController.edit',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">PersonManager</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Updates a resource (edit).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">PersonManager</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Met à jour une ressource (edit).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">PersonManager</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Updates a resource (edit).</p>
    </div>
  </div>
</div>'),

('designer.routes.help.PersonController.editSave',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">PersonManager</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Updates a resource (editSave).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">PersonManager</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Met à jour une ressource (editSave).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">PersonManager</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Updates a resource (editSave).</p>
    </div>
  </div>
</div>'),

('designer.routes.help.PersonController.home',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">PersonManager</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Home page. Content depends on the visitor''s authorizations, especially for the navbar, sidebar and article list.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">PersonManager</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Page d''accueil. Le contenu dépend des autorisations du visiteur notamment pour la barre de navigation (navbar), 
      le menu latéral (sidebar) et la liste des articles.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">PersonManager</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Home page. Content depends on the visitor''s authorizations, especially for the navbar, sidebar and article list.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.PersonController.help',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">PersonManager</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Contextual help page for the module.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">PersonManager</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Page d''aide contextuelle du module.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">PersonManager</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Contextual help page for the module.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.PersonController.index',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">PersonManager</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « index » of controller PersonController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">PersonManager</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « index » du contrôleur PersonController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">PersonManager</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « index » of controller PersonController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.ArticleController.publish',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Editor</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « publish » of controller ArticleController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Editor</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « publish » du contrôleur ArticleController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Editor</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « publish » of controller ArticleController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.ArticleController.home',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Home page. Content depends on the visitor''s authorizations,
      especially for the navbar, sidebar and article list.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Page d''accueil. Le contenu dépend des autorisations du visiteur notamment pour la barre de navigation (navbar),
      le menu latéral (sidebar) et la liste des articles.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Home page. Content depends on the visitor''s authorizations, especially for the navbar, sidebar and article list.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.ArticleController.help',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Contextual help page for the module.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Page d''aide contextuelle du module.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Contextual help page for the module.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.VisitorInsightsController.referents',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">VisitorInsights</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « referents » of controller VisitorInsightsController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">VisitorInsights</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « referents » du contrôleur VisitorInsightsController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">VisitorInsights</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « referents » of controller VisitorInsightsController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.RegistrationController.index',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">PersonManager</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « index » of controller RegistrationController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">PersonManager</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « index » du contrôleur RegistrationController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">PersonManager</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « index » of controller RegistrationController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.RegistrationController.getPersonGroups',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">PersonManager</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « getPersonGroups ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">PersonManager</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Affiche ou retourne les données liées à « getPersonGroups ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">PersonManager</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « getPersonGroups ».</p>
    </div>
  </div>
</div>'),

('designer.routes.help.DesignerController.showRoutes',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Designer / HomeDesigner / MenuDesigner</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « showRoutes ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Designer / HomeDesigner / MenuDesigner</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Affiche ou retourne les données liées à « showRoutes ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Designer / HomeDesigner / MenuDesigner</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « showRoutes ».</p>
    </div>
  </div>
</div>'),

('designer.routes.help.WebmasterController.sendEmailCredentialsEdit',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « sendEmailCredentialsEdit » of controller WebmasterController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « sendEmailCredentialsEdit » du contrôleur WebmasterController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « sendEmailCredentialsEdit » of controller WebmasterController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.WebmasterController.sendEmailCredentialsSave',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « sendEmailCredentialsSave » of controller WebmasterController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « sendEmailCredentialsSave » du contrôleur WebmasterController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « sendEmailCredentialsSave » of controller WebmasterController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.WebappSettingsController.editSettings',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Designer / HomeDesigner / MenuDesigner</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Updates a resource (editSettings).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Designer / HomeDesigner / MenuDesigner</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Met à jour une ressource (editSettings).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Designer / HomeDesigner / MenuDesigner</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Updates a resource (editSettings).</p>
    </div>
  </div>
</div>'),

('designer.routes.help.WebappSettingsController.saveSettings',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Designer / HomeDesigner / MenuDesigner</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Updates a resource (saveSettings).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Designer / HomeDesigner / MenuDesigner</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Met à jour une ressource (saveSettings).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Designer / HomeDesigner / MenuDesigner</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Updates a resource (saveSettings).</p>
    </div>
  </div>
</div>'),

('designer.routes.help.WebappSettingsController.saveLanguage',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Designer / HomeDesigner / MenuDesigner</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Updates a resource (saveLanguage).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Designer / HomeDesigner / MenuDesigner</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Met à jour une ressource (saveLanguage).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Designer / HomeDesigner / MenuDesigner</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Updates a resource (saveLanguage).</p>
    </div>
  </div>
</div>'),

('designer.routes.help.PwaController.handleShare',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">depends on the connected user</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « handleShare » of controller PwaController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">dépend de l''utilisateur connecté</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « handleShare » du contrôleur PwaController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">zależy od zalogowanego użytkownika</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « handleShare » of controller PwaController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.HomeController.signpost',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">none or connected member</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « signpost » of controller HomeController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">aucune ou membre connecté</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « signpost » du contrôleur HomeController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">brak lub zalogowany członek</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « signpost » of controller HomeController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.WebmasterController.sitemapGenerator',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « sitemapGenerator » of controller WebmasterController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « sitemapGenerator » du contrôleur WebmasterController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « sitemapGenerator » of controller WebmasterController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.SurveyController.add',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Creates a new resource (add).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Crée une nouvelle ressource (add).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Creates a new resource (add).</p>
    </div>
  </div>
</div>'),

('designer.routes.help.SurveyController.createOrUpdate',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Creates a new resource (createOrUpdate).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Crée une nouvelle ressource (createOrUpdate).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Creates a new resource (createOrUpdate).</p>
    </div>
  </div>
</div>'),

('designer.routes.help.SurveyController.viewResults',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « viewResults » of controller SurveyController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « viewResults » du contrôleur SurveyController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « viewResults » of controller SurveyController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.ArticleStatisticsController.topArticlesByPeriod',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « topArticlesByPeriod » of controller ArticleStatisticsController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « topArticlesByPeriod » du contrôleur ArticleStatisticsController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Redactor / Editor</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « topArticlesByPeriod » of controller ArticleStatisticsController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.VisitorInsightsController.topPagesByPeriod',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">VisitorInsights</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « topPagesByPeriod » of controller VisitorInsightsController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">VisitorInsights</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « topPagesByPeriod » du contrôleur VisitorInsightsController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">VisitorInsights</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « topPagesByPeriod » of controller VisitorInsightsController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.TranslatorController.index',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">depends on the connected user</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « index » of controller TranslatorController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">dépend de l''utilisateur connecté</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « index » du contrôleur TranslatorController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">zależy od zalogowanego użytkownika</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « index » of controller TranslatorController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.WebmasterController.turnstileCredentialsEdit',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « turnstileCredentialsEdit » of controller WebmasterController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « turnstileCredentialsEdit » du contrôleur WebmasterController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « turnstileCredentialsEdit » of controller WebmasterController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.WebmasterController.turnstileCredentialsSave',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « turnstileCredentialsSave » of controller WebmasterController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « turnstileCredentialsSave » du contrôleur WebmasterController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « turnstileCredentialsSave » of controller WebmasterController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.UserDashboardController.user',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">none or connected member</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « user » of controller UserDashboardController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">aucune ou membre connecté</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « user » du contrôleur UserDashboardController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">brak lub zalogowany członek</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « user » of controller UserDashboardController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.UserAccountController.account',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">none or connected member</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « account » of controller UserAccountController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">aucune ou membre connecté</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « account » du contrôleur UserAccountController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">brak lub zalogowany członek</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « account » of controller UserAccountController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.UserAccountController.accountSave',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">none or connected member</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « accountSave » of controller UserAccountController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">aucune ou membre connecté</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « accountSave » du contrôleur UserAccountController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">brak lub zalogowany członek</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « accountSave » of controller UserAccountController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.UserAvailabilitiesController.availabilities',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">none or connected member</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « availabilities » of controller UserAvailabilitiesController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">aucune ou membre connecté</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « availabilities » du contrôleur UserAvailabilitiesController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">brak lub zalogowany członek</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « availabilities » of controller UserAvailabilitiesController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.UserAvailabilitiesController.availabilitiesSave',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">none or connected member</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « availabilitiesSave » of controller UserAvailabilitiesController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">aucune ou membre connecté</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « availabilitiesSave » du contrôleur UserAvailabilitiesController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">brak lub zalogowany członek</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « availabilitiesSave » of controller UserAvailabilitiesController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.UserConnectionsController.showConnectionsOfConnectedUser',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">none or connected member</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « showConnectionsOfConnectedUser ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">aucune ou membre connecté</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Affiche ou retourne les données liées à « showConnectionsOfConnectedUser ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">brak lub zalogowany członek</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « showConnectionsOfConnectedUser ».</p>
    </div>
  </div>
</div>'),

('designer.routes.help.UserConnectionsController.showConnections',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">none or connected member</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « showConnections ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">aucune ou membre connecté</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Affiche ou retourne les données liées à « showConnections ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">brak lub zalogowany członek</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « showConnections ».</p>
    </div>
  </div>
</div>'),

('designer.routes.help.UserDirectoryController.showDirectory',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">none or connected member</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « showDirectory ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">aucune ou membre connecté</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Affiche ou retourne les données liées à « showDirectory ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">brak lub zalogowany członek</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « showDirectory ».</p>
    </div>
  </div>
</div>'),

('designer.routes.help.UserDirectoryController.showMap',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">none or connected member</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « showMap ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">aucune ou membre connecté</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Affiche ou retourne les données liées à « showMap ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">brak lub zalogowany członek</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « showMap ».</p>
    </div>
  </div>
</div>'),

('designer.routes.help.UserDirectoryController.showPublicMap',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">none or connected member</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « showPublicMap ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">aucune ou membre connecté</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Affiche ou retourne les données liées à « showPublicMap ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">brak lub zalogowany członek</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « showPublicMap ».</p>
    </div>
  </div>
</div>'),

('designer.routes.help.UserController.forgotPassword',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">none or connected member</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « forgotPassword » of controller UserController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">aucune ou membre connecté</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « forgotPassword » du contrôleur UserController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">brak lub zalogowany członek</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « forgotPassword » of controller UserController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.UserGroupsController.groups',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">none or connected member</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « groups » of controller UserGroupsController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">aucune ou membre connecté</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « groups » du contrôleur UserGroupsController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">brak lub zalogowany członek</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « groups » of controller UserGroupsController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.UserGroupsController.groupsSave',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">none or connected member</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « groupsSave » of controller UserGroupsController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">aucune ou membre connecté</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « groupsSave » du contrôleur UserGroupsController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">brak lub zalogowany członek</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « groupsSave » of controller UserGroupsController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.UserDashboardController.help',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">none (isAnybody)</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Contextual help page for the module.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">aucune (isAnybody)</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Page d''aide contextuelle du module.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">brak (isAnybody)</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Contextual help page for the module.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.MembershipController.index',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">depends on the connected user</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « index » of controller MembershipController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">dépend de l''utilisateur connecté</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « index » du contrôleur MembershipController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">zależy od zalogowanego użytkownika</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « index » of controller MembershipController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.UserMessagesController.showMessages',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">none or connected member</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « showMessages ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">aucune ou membre connecté</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Affiche ou retourne les données liées à « showMessages ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">brak lub zalogowany członek</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « showMessages ».</p>
    </div>
  </div>
</div>'),

('designer.routes.help.UserNewsController.showNews',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">none or connected member</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « showNews ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">aucune ou membre connecté</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Affiche ou retourne les données liées à « showNews ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">brak lub zalogowany członek</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « showNews ».</p>
    </div>
  </div>
</div>'),

('designer.routes.help.UserNotepadController.editNotepad',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">none or connected member</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Updates a resource (editNotepad).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">aucune ou membre connecté</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Met à jour une ressource (editNotepad).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">brak lub zalogowany członek</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Updates a resource (editNotepad).</p>
    </div>
  </div>
</div>'),

('designer.routes.help.UserNotepadController.saveNotepad',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">none or connected member</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Updates a resource (saveNotepad).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">aucune ou membre connecté</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Met à jour une ressource (saveNotepad).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">brak lub zalogowany członek</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Updates a resource (saveNotepad).</p>
    </div>
  </div>
</div>'),

('designer.routes.help.UserNotificationsController.notifications',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">none or connected member</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « notifications » of controller UserNotificationsController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">aucune ou membre connecté</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « notifications » du contrôleur UserNotificationsController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">brak lub zalogowany członek</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « notifications » of controller UserNotificationsController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.UserNotificationsController.notificationsSave',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">none or connected member</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « notificationsSave » of controller UserNotificationsController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">aucune ou membre connecté</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « notificationsSave » du contrôleur UserNotificationsController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">brak lub zalogowany członek</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « notificationsSave » of controller UserNotificationsController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.UserPreferencesController.preferences',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">none or connected member</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « preferences » of controller UserPreferencesController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">aucune ou membre connecté</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « preferences » du contrôleur UserPreferencesController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">brak lub zalogowany członek</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « preferences » of controller UserPreferencesController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.UserPreferencesController.preferencesSave',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">none or connected member</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « preferencesSave » of controller UserPreferencesController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">aucune ou membre connecté</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « preferencesSave » du contrôleur UserPreferencesController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">brak lub zalogowany członek</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « preferencesSave » of controller UserPreferencesController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.UserPresentationController.showPresentation',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">none or connected member</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « showPresentation ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">aucune ou membre connecté</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Affiche ou retourne les données liées à « showPresentation ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">brak lub zalogowany członek</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « showPresentation ».</p>
    </div>
  </div>
</div>'),

('designer.routes.help.UserPresentationController.editPresentation',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">none or connected member</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Updates a resource (editPresentation).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">aucune ou membre connecté</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Met à jour une ressource (editPresentation).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">brak lub zalogowany członek</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Updates a resource (editPresentation).</p>
    </div>
  </div>
</div>'),

('designer.routes.help.UserPresentationController.savePresentation',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">none or connected member</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Updates a resource (savePresentation).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">aucune ou membre connecté</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Met à jour une ressource (savePresentation).</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">brak lub zalogowany członek</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Updates a resource (savePresentation).</p>
    </div>
  </div>
</div>'),

('designer.routes.help.UserController.setPassword',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">none or connected member</span>
        <span class="badge bg-success">update</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « setPassword » of controller UserController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">aucune ou membre connecté</span>
        <span class="badge bg-success">mise à jour</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « setPassword » du contrôleur UserController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">brak lub zalogowany członek</span>
        <span class="badge bg-success">aktualizacja</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « setPassword » of controller UserController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.UserController.signIn',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">none or connected member</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « signIn » of controller UserController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">aucune ou membre connecté</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « signIn » du contrôleur UserController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">brak lub zalogowany członek</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « signIn » of controller UserController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.UserController.signOut',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">none or connected member</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « signOut » of controller UserController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">aucune ou membre connecté</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « signOut » du contrôleur UserController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">brak lub zalogowany członek</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « signOut » of controller UserController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.UserStatisticsController.showStatistics',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">none or connected member</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « showStatistics ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">aucune ou membre connecté</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Affiche ou retourne les données liées à « showStatistics ».</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">brak lub zalogowany członek</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Displays or returns data related to « showStatistics ».</p>
    </div>
  </div>
</div>'),

('designer.routes.help.VisitorInsightsController.visitorInsights',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">VisitorInsights</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « visitorInsights » of controller VisitorInsightsController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">VisitorInsights</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « visitorInsights » du contrôleur VisitorInsightsController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">VisitorInsights</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « visitorInsights » of controller VisitorInsightsController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.VisitorInsightsController.visitorsGraf',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">VisitorInsights</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « visitorsGraf » of controller VisitorInsightsController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">VisitorInsights</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « visitorsGraf » du contrôleur VisitorInsightsController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">VisitorInsights</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « visitorsGraf » of controller VisitorInsightsController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.WebmasterController.homeWebmaster',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Webmaster module home page.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Page d''accueil du module Webmaster.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">Webmaster</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Webmaster module home page.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.EventController.weekEvents',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">depends on the connected user</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « weekEvents » of controller EventController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">dépend de l''utilisateur connecté</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « weekEvents » du contrôleur EventController.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">zależy od zalogowanego użytkownika</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Action « weekEvents » of controller EventController.</p>
    </div>
  </div>
</div>'),

('designer.routes.help.EventController.weekEventsHelp',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">according to the module</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Help for the weekly events view.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">selon le module</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Aide pour la vue des événements de la semaine.</p>
    </div>
  </div>
</div>',
 '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">zgodnie z modułem</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Help for the weekly events view.</p>
    </div>
  </div>
</div>');
SQL);

        return 102;
    }
}
