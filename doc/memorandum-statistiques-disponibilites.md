# Mémorandum — Statistiques de disponibilités

## Objet et périmètre

Cette évolution ajoute une vue synthétique des disponibilités renseignées par
les membres. Elle est destinée aux responsables d’événements : elle ne révèle
pas les disponibilités individuelles et ne modifie pas les disponibilités
elles-mêmes, qui restent saisies depuis `/user/availabilities`.

La page est disponible à l’URL `GET /availabilities`. Son accès est protégé
par l’autorisation `isEventManager()` ; un utilisateur non autorisé est traité
par le mécanisme standard du contrôleur abstrait.

## Résultat visible

Depuis le menu « gestion des événements », l’icône 🕒 ouvre un écran qui
contient :

- un indicateur de complétude : membres actifs ayant renseigné au moins une
  valeur / nombre total de membres actifs ;
- un graphique à barres empilées, du lundi au dimanche ;
- trois séries : matin, après-midi et soir. Chaque valeur est le pourcentage
  des membres ayant effectivement renseigné leurs disponibilités.

Les infobulles et les étiquettes du graphique présentent des pourcentages.
Une valeur nulle ne reçoit pas d’étiquette dans la barre.

## Données et règle de calcul

La source est `Member.Availabilities`. Cette colonne contient le JSON produit
par le formulaire de l’utilisateur, par exemple :

```json
[
  {"morning":"on", "afternoon":"on"},
  {},
  {"evening":"on"},
  {}, {}, {}, {}
]
```

Les index `0` à `6` correspondent respectivement à lundi à dimanche. Le
lecteur accepte aussi l’ancienne/variante forme objet, par exemple
`{"0":{"morning":"on"},"6":{"evening":"on"}}`. Un JSON invalide,
une valeur qui n’est pas un tableau, ou un jour/créneau absent est interprété
comme une absence de disponibilité ; l’écran reste donc affichable avec des
données historiques imparfaites.

`AvailabilityDataHelper::getAvailabilityStats()` applique les règles suivantes :

| Mesure | Calcul |
| --- | --- |
| Membres actifs | `COUNT(*)` sur `Member` avec `Inactivated = 0` |
| Membres répondants | membres actifs dont `Availabilities` n’est ni `NULL` ni une chaîne vide |
| Taux de complétude | répondants / membres actifs × 100 |
| Taux d’un créneau | répondants ayant la valeur stricte `"on"` pour ce créneau / répondants × 100 |

Le dénominateur du graphique est donc le nombre de répondants, pas l’ensemble
des membres actifs. C’est intentionnel : le graphique décrit les préférences
exprimées, tandis que la barre supérieure rend visible la couverture de ces
données. En l’absence de répondant, le dénominateur est protégé à `1` et tous
les taux sont à `0`.

## Architecture mise en place

| Couche | Fichier | Responsabilité |
| --- | --- | --- |
| Enregistrement | `app/config/Routes.php` et `app/config/routes/EventAvailabilities.php` | déclare `GET /availabilities` |
| Construction | `app/config/ControllerFactory.php` | instancie le contrôleur et son helper de données |
| Autorisation/orchestration | `app/modules/Event/EventAvailabilitiesController.php` | vérifie le rôle, construit les libellés localisés et les données du graphique |
| Agrégation | `app/models/AvailabilityDataHelper.php` | lit et normalise les JSON, puis calcule les statistiques |
| Modèle de vue | `app/modules/Event/viewModels/AvailabilityStatsViewModel.php` | porte statistiques, données de graphique et paramètres de mise en page |
| Vue | `app/modules/Event/views/availability_stats.latte` | affiche la barre de progression, le canevas et initialise les données JavaScript |
| Graphique | `app/modules/Event/js/availability_stats.js` | rend le graphique Chart.js empilé et ses étiquettes |
| Navigation | `app/modules/Event/views/navbar/eventManager.latte` | ajoute l’entrée 🕒 et devient le fragment de barre de navigation du module Event |

Le fragment de navigation a été déplacé de
`app/modules/Webmaster/views/navbar/eventManager.latte` vers le module Event.
`app/modules/Event/views/eventManager.latte` référence désormais ce nouvel
emplacement. Toute future page du contexte « gestion des événements » doit
utiliser ce fragment afin de conserver la nouvelle entrée.

Le contrôleur fournit les jours par les clés existantes `day.monday` à
`day.sunday`. Les clés dédiées aux créneaux sont injectées dans `window.i18n`,
puis consommées par `window.t()` dans le module JavaScript.

Le contrôleur transmet également le nom de la page courante et le layout actif
au modèle de vue. L’entrée 🕒 est donc marquée active sur `/availabilities` et
la page utilise le layout correspondant à la barre de navigation conservée en
session.

## Livraison des traductions et migration

Les libellés de la fonctionnalité ont été livrés par
`app/models/database/migrators/V81ToV82Migrator.php`. Cette migration est
idempotente pour les clés concernées grâce à `INSERT OR REPLACE` et renseigne
les trois langues prises en charge (`en_US`, `fr_FR`, `pl_PL`). Elle couvre :

- le titre de la page, la carte de taux de réponse et le graphique ;
- les créneaux matin, après-midi et soir ;
- l’infobulle de l’entrée « Disponibilités » ;
- les sept jours de la semaine, afin que les libellés du graphique soient
  disponibles même sur une base plus ancienne.

La constante `Database::DB_VERSION` est maintenant à `82`. Le modèle de base
initial `app/models/database/MyClub.sqlite` et son export
`app/models/database/MyClub.sqlite.sql` incluent également ces traductions,
afin qu’une nouvelle installation soit directement au niveau attendu.

Pour une évolution de données ultérieure, créer une nouvelle migration à partir
de `V82ToV83Migrator` : une migration déjà livrée ne doit pas être modifiée.

## Dépendances côté navigateur

Le graphique utilise les versions CDN suivantes :

- Chart.js `3.9.1` ;
- `chartjs-plugin-datalabels` `2.2.0`.

Le module JavaScript importe Chart.js et le plugin au format ESM depuis jsDelivr.
La vue charge aussi Chart.js depuis cdnjs. Ce deuxième chargement est
actuellement redondant avec l’import ESM ; si cette page est rationalisée, il
peut être retiré après vérification dans les navigateurs pris en charge.

## Guide pour les extensions futures

Pour ajouter un créneau (par exemple « nuit »), l’évolution doit être faite de
bout en bout :

1. ajouter la case au formulaire utilisateur et préserver le format JSON ;
2. ajouter le créneau à la normalisation et au comptage de
   `AvailabilityDataHelper` ;
3. transmettre la série depuis le contrôleur ;
4. créer le jeu de données et la légende dans `availability_stats.js` ;
5. ajouter les traductions dans une nouvelle migration et dans le modèle de
   base initial ;
6. compléter les tests de calcul et vérifier l’affichage sur une base réelle.

Pour changer la population étudiée (groupe, type d’événement, période ou
statut), modifier d’abord les requêtes de `AvailabilityDataHelper` et documenter
explicitement le nouveau dénominateur. Ne jamais transmettre le détail des
disponibilités nominatives dans les données JavaScript sans revoir l’autorisation
et les exigences de confidentialité.

Si les statistiques deviennent lourdes sur un grand nombre de membres, la
première optimisation à envisager est une agrégation SQL ou un cache invalidé
lors de l’enregistrement de `/user/availabilities`. La logique actuelle lit et
décode chaque JSON de membre à chaque consultation : elle privilégie la
tolérance des formats et la simplicité.

## Vérification et couverture de tests

Les contrôles suivants ont été exécutés avec succès après les corrections de
qualité :

```text
./vendor/bin/phpunit tests/models/AvailabilityDataHelperTest.php \
  tests/models/AvailabilityDataHelperNormalizationTest.php
OK (12 tests, 45 assertions)

./vendor/bin/phpstan analyse --no-progress
[OK] No errors
```

La couverture ajoutée se répartit ainsi :

| Fichier | Vérifie |
| --- | --- |
| `tests/models/AvailabilityDataHelperTest.php` | présence des colonnes utilisées et validité des deux requêtes SQL sur une copie jetable de la base modèle |
| `tests/models/AvailabilityDataHelperNormalizationTest.php` | normalisation des tableaux et objets JSON, rejet des jours hors plage et des JSON invalides, ainsi que filtrage des valeurs de créneaux non textuelles |

Les tests actuels valident donc le contrat de stockage et la robustesse de la
normalisation. Pour faire évoluer le calcul lui-même, ajouter des scénarios
d’intégration qui insèrent des membres actifs/inactifs et vérifient les quatre
mesures retournées par `getAvailabilityStats()`. Un test de route/contrôleur
reste également pertinent pour confirmer que `/availabilities` est accessible
à un responsable d’événements et refusé aux autres rôles.
