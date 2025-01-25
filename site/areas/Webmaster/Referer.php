<?php
require_once __DIR__ . '/../../lib/Database/Tables/Log.php';

$log = new Log();
$currentParams = $_GET;
$period = $currentParams['period'] ?? 'day';
$currentDate = $currentParams['date'] ?? date('Y-m-d');
if (!strtotime($currentDate)) {
    $currentDate = date('Y-m-d');
}

$nav = $log->getRefererNavigation($period, $currentDate);
$externalRefs = $log->getExternalRefererStats($period, $currentDate);

function buildUrl($newParams) {
    $params = array_merge($_GET, $newParams);
    return '?' . http_build_query($params);
}
?>

<div class="row mb-4">
    <div class="col">
        <div class="btn-group">
            <a href="<?= buildUrl(['period' => 'day']) ?>" 
               class="btn btn-outline-primary <?= $period === 'day' ? 'active' : '' ?>">Jour</a>
            <a href="<?= buildUrl(['period' => 'week']) ?>" 
               class="btn btn-outline-primary <?= $period === 'week' ? 'active' : '' ?>">Semaine</a>
            <a href="<?= buildUrl(['period' => 'month']) ?>" 
               class="btn btn-outline-primary <?= $period === 'month' ? 'active' : '' ?>">Mois</a>
            <a href="<?= buildUrl(['period' => 'year']) ?>" 
               class="btn btn-outline-primary <?= $period === 'year' ? 'active' : '' ?>">Année</a>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col">
        <div class="btn-group">
            <a href="<?= buildUrl(['date' => $nav['first']]) ?>" 
               class="btn btn-outline-secondary">&lt;&lt;</a>
            <a href="<?= buildUrl(['date' => $nav['prev']]) ?>" 
               class="btn btn-outline-secondary">&lt;</a>
            <span class="btn btn-secondary"><?= $nav['current'] ?></span>
            <a href="<?= buildUrl(['date' => $nav['next']]) ?>" 
               class="btn btn-outline-secondary">&gt;</a>
            <a href="<?= buildUrl(['date' => $nav['last']]) ?>" 
               class="btn btn-outline-secondary">&gt;&gt;</a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Source</th>
                    <th class="text-end">Visites</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($log->getRefererStats($period, $currentDate, 'https://myclub.alwaysdata.net') as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['source'] ?? '') ?></td>
                    <td class="text-end"><?= number_format($row['count'], 0, ',', ' ') ?></td>
                </tr>
            <?php endforeach; ?>

            <?php if (!empty($externalRefs)): ?>
                <tr><td colspan="2" class="border-top"></td></tr>
                <?php foreach ($externalRefs as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['source']) ?></td>
                        <td class="text-end"><?= number_format($row['count'], 0, ',', ' ') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>