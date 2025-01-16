<?php
require 'includes/header.php';
require_once  __DIR__ . '/lib/Database/Tables/Event.php';
echo "<main>\n";

  
// Tableau de traduction des jours en français
$joursFr = [
  'Mon' => 'Lun',
  'Tue' => 'Mar',
  'Wed' => 'Mer',
  'Thu' => 'Jeu',
  'Fri' => 'Ven',
  'Sat' => 'Sam',
  'Sun' => 'Dim'
];
  // Obtenir la date courante et calculer les dates de la semaine
  $currentDate = new DateTime();
  $startDate = isset($_GET['start']) ? new DateTime($_GET['start']) : $currentDate;
  $dates = [];
  for ($i = 0; $i < 7; $i++) {
      $date = clone $startDate;
      $date->modify("+$i day");
      $dates[] = $date;
  }
  ?>
    <div class="container mt-4">
        <div class="row align-items-center mb-3">
            <div class="col-auto">
                <button type="button" class="btn btn-secondary move-week" data-days="-7"><<</button>
                <button type="button" class="btn btn-secondary move-day" data-days="-1"><</button>
            </div>
            
            <div class="col">
                <div class="table-responsive">
                    <table class="table table-bordered text-center">
                        <thead>
                            <tr>
                                <?php foreach ($dates as $date): ?>
                                    <th class="weekday date-cell" data-date="<?= $date->format('Y-m-d') ?>">
                                        <?= $joursFr[$date->format('D')] ?> <?= $date->format('d/m') ?>
                                    </th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <?php foreach ($dates as $date): ?>
                                    <?php 
                                    $events = (new Event())->getEventsForDay($date->format('Y-m-d'), $userEmail);
                                    $count = count($events);
                                    ?>
                                    <td class="event-cell" data-date="<?= $date->format('Y-m-d') ?>">
                                        <?php if ($count > 0): ?>
                                            <button class="btn btn-primary show-events" 
                                                    data-date="<?= $date->format('Y-m-d') ?>">
                                                <?= $count ?>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="col-auto">
                <button type="button" class="btn btn-secondary move-day" data-days="1">></button>
                <button type="button" class="btn btn-secondary move-week" data-days="7">>></button>
            </div>
        </div>
        
        <div id="events-detail" class="mt-4"></div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        const joursFr = {
            'Mon': 'Lun',
            'Tue': 'Mar',
            'Wed': 'Mer',
            'Thu': 'Jeu',
            'Fri': 'Ven',
            'Sat': 'Sam',
            'Sun': 'Dim'
        };
        
        const currentDate = new Date();
        currentDate.setHours(0, 0, 0, 0);
        let startDate = new Date($('.weekday').first().data('date'));

        function formatDate(date) {
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            return `${day}/${month}`;
        }

        function updateCalendar() {
            // Désactiver les boutons si on est à la date courante
            $('.move-week, .move-day').prop('disabled', false);
            if (startDate <= currentDate) {
                $('.move-week[data-days="-7"], .move-day[data-days="-1"]').prop('disabled', true);
                startDate = new Date(currentDate);
            }

            // Mettre à jour les dates et les événements
            $('.weekday').each(function(index) {
                const cellDate = new Date(startDate);
                cellDate.setDate(startDate.getDate() + index);
                
                const dateStr = cellDate.toISOString().split('T')[0];
                $(this).data('date', dateStr);
                
                const dayName = cellDate.toLocaleDateString('en-US', { weekday: 'short' });
                const formattedDate = formatDate(cellDate);
                $(this).text(`${joursFr[dayName]} ${formattedDate}`);
                
                // Mettre à jour la cellule des événements
                const eventCell = $('.event-cell').eq(index);
                eventCell.data('date', dateStr);
                
                // Charger les événements pour cette date
                $.get('get_event_count.php', { 
                    date: dateStr, 
                    userEmail: '<?= $userEmail ?>' 
                }, function(count) {
                    eventCell.empty();
                    if (count > 0) {
                        eventCell.html(`
                            <button class="btn btn-primary show-events" data-date="${dateStr}">
                                ${count}
                            </button>
                        `);
                    }
                });
            });
        }

        // Gestionnaire pour les boutons de navigation
        $('.move-day, .move-week').click(function() {
            const days = parseInt($(this).data('days'));
            startDate.setDate(startDate.getDate() + days);
            updateCalendar();
        });

        // Gestionnaire pour l'affichage des événements
        $(document).on('click', '.show-events', function() {
            const date = $(this).data('date');
            $.ajax({
                url: 'get_events.php',
                data: { date: date, userEmail: '<?= $userEmail ?>' },
                success: function(response) {
                    $('#events-detail').html(response);
                }
            });
        });
        
        // Gestionnaire pour l'affichage des détails d'un événement
        $(document).on('click', '.event-row', function() {
            const eventId = $(this).data('event-id');
            $.ajax({
                url: 'get_event_detail.php',
                data: { eventId: eventId, userEmail: '<?= $userEmail ?>' },
                success: function(response) {
                    $('#event-' + eventId + '-detail').html(response);
                    $('#event-' + eventId + '-detail').collapse('toggle');
                }
            });
        });
        
        // Gestionnaire pour l'inscription à un événement
        $(document).on('click', '.register-event', function() {
            const eventId = $(this).data('event-id');
            $.ajax({
                url: 'register_event.php',
                method: 'POST',
                data: { eventId: eventId, userEmail: '<?= $userEmail ?>' },
                success: function(response) {
                    // Recharger la liste des événements
                    const date = $('.show-events.active').data('date');
                    if (date) {
                        $.ajax({
                            url: 'get_events.php',
                            data: { date: date, userEmail: '<?= $userEmail ?>' },
                            success: function(response) {
                                $('#events-detail').html(response);
                            }
                        });
                    }
                },
                error: function(xhr) {
                    alert('Erreur lors de l\'inscription à l\'événement');
                }
            });
        });
        
        // Gestionnaire pour la désinscription d'un événement
        $(document).on('click', '.unregister-event', function() {
            const eventId = $(this).data('event-id');
            if (confirm('Êtes-vous sûr de vouloir vous désinscrire de cet événement ?')) {
                $.ajax({
                    url: 'unregister_event.php',
                    method: 'POST',
                    data: { eventId: eventId, userEmail: '<?= $userEmail ?>' },
                    success: function(response) {
                        // Recharger la liste des événements
                        const date = $('.show-events.active').data('date');
                        if (date) {
                            $.ajax({
                                url: 'get_events.php',
                                data: { date: date, userEmail: '<?= $userEmail ?>' },
                                success: function(response) {
                                    $('#events-detail').html(response);
                                }
                            });
                        }
                    },
                    error: function(xhr) {
                        alert('Erreur lors de la désinscription de l\'événement');
                    }
                });
            }
        });

        // Ajouter une classe active au bouton d'événements cliqué
        $(document).on('click', '.show-events', function() {
            $('.show-events').removeClass('active');
            $(this).addClass('active');
        });

        // Initialiser le calendrier au chargement
        updateCalendar();
</script>
</body>
</html>
    </script>
<?php
echo "</main>\n";
require 'includes/footer.php';
?>
