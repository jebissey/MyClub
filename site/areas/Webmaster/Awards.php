<?php
require_once __DIR__ . '/../../lib/Database/Tables/Counter.php';

$counter = new Counter();
$counterNames = $counter->getAllNames();
$results = $counter->getAwards();

$data = [];
foreach ($results as $row) {
    $personId = $row['Id'];
    if (!isset($data[$personId])) {
        $data[$personId] = [
            'name' => trim(sprintf('%s %s %s', 
                $row['FirstName'],
                $row['LastName'],
                $row['NickName'] ? "({$row['NickName']})" : ''
            )),
            'counters' => array_fill_keys($counterNames, 0),
            'total' => $row['Total']
        ];
    }
    if ($row['CounterName']) {
        $data[$personId]['counters'][$row['CounterName']] = $row['CounterValue'];
    }
}
?>
    <div class="container mt-4">
        <h2>Tableau des compteurs par personne</h2>
        <div class="table-responsive">
            <table class="table table-striped table-hover" id="dataTable">
                <thead class="table-dark">
                    <tr>
                        <th class="sortable">Nom de la personne <span class="sort-icon"></span></th>
                        <?php foreach ($counterNames as $name): ?>
                            <th class="sortable text-center">
                                <?= htmlspecialchars($name) ?> <span class="sort-icon"></span>
                            </th>
                        <?php endforeach; ?>
                        <th class="sortable text-center">Total <span class="sort-icon"></span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $personData): ?>
                        <tr>
                            <td><?= htmlspecialchars($personData['name']) ?></td>
                            <?php foreach ($counterNames as $name): ?>
                                <td class="text-center" data-value="<?= $personData['counters'][$name] ?>">
                                    <?= number_format($personData['counters'][$name], 0, ',', ' ') ?>
                                </td>
                            <?php endforeach; ?>
                            <td class="text-center fw-bold" data-value="<?= $personData['total'] ?>">
                                <?= number_format($personData['total'], 0, ',', ' ') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <p>(1 bonus (404, gravatar, 403...) = 1pt,  1 bug = 2pt, 1 suggestion = 3pt, 1 visio = 1pt)</p>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const table = document.getElementById('dataTable');
        const headers = table.querySelectorAll('th.sortable');
        let currentSortCol = -1;
        let ascending = true;

        headers.forEach((header, index) => {
            header.addEventListener('click', () => {
                headers.forEach(h => h.querySelector('.sort-icon').textContent = '');
                
                if (currentSortCol === index) {
                    ascending = !ascending;
                } else {
                    ascending = true;
                    currentSortCol = index;
                }

                header.querySelector('.sort-icon').textContent = ascending ? '↑' : '↓';

                const rows = Array.from(table.querySelectorAll('tbody tr'));
                rows.sort((a, b) => {
                    let aVal = a.cells[index].textContent.trim();
                    let bVal = b.cells[index].textContent.trim();
                    
                    if (a.cells[index].hasAttribute('data-value')) {
                        aVal = parseFloat(a.cells[index].getAttribute('data-value'));
                        bVal = parseFloat(b.cells[index].getAttribute('data-value'));
                    }

                    if (typeof aVal === 'number') {
                        return ascending ? aVal - bVal : bVal - aVal;
                    } else {
                        return ascending ? 
                            aVal.localeCompare(bVal, 'fr', {sensitivity: 'base'}) : 
                            bVal.localeCompare(aVal, 'fr', {sensitivity: 'base'});
                    }
                });

                const tbody = table.querySelector('tbody');
                rows.forEach(row => tbody.appendChild(row));
            });
        });
    });
    </script>
