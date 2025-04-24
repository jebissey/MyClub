document.addEventListener('DOMContentLoaded', function () {
    // Initialiser le modal avec les valeurs par défaut pour une nouvelle alerte
    $('.new-alert').on('click', function () {
        // Réinitialiser le formulaire
        $('#alertModal form')[0].reset();

        // Effacer l'ID (pour indiquer qu'il s'agit d'une création)
        $('#alertId').val('');

        // Définir les dates par défaut (aujourd'hui et dans un mois)
        var today = new Date();
        var nextMonth = new Date();
        nextMonth.setMonth(today.getMonth() + 1);

        $('#startDate').val(today.toISOString().substring(0, 10));
        $('#endDate').val(nextMonth.toISOString().substring(0, 10));

        // Afficher le sélecteur de groupe (nécessaire pour la création)
        $('.group-selector').show();

        // Changer le titre du modal
        $('#alertModalLabel').text('Créer une nouvelle alerte');
    });

    // Initialiser le modal avec les données de l'alerte à modifier
    $('.edit-alert').on('click', function () {
        var id = $(this).data('id');
        var message = $(this).data('message');
        var type = $(this).data('type');
        var start = $(this).data('start').substring(0, 10); // Format YYYY-MM-DD
        var end = $(this).data('end').substring(0, 10); // Format YYYY-MM-DD
        var groupId = $(this).data('groupid');
        var members = $(this).data('members');

        $('#alertId').val(id);
        $('#message').val(message);
        $('#type').val(type);
        $('#startDate').val(start);
        $('#endDate').val(end);
        $('#groupId').val(groupId);
        $('#onlyForMembers').prop('checked', members == 1);

        // Masquer le sélecteur de groupe (non modifiable en édition)
        $('.group-selector').hide();

        // Changer le titre du modal
        $('#alertModalLabel').text('Modifier l\'alerte');
    });
});