<div class="modal fade" id="reservationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nouvelle réservation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="process.php" id="dateTimeForm">
                    <div class="mb-3">
                        <label for="start_datetime" class="form-label">Date et heure de début:</label>
                        <input type="text" class="form-control" id="start_datetime" name="start_datetime" required>
                    </div>

                    <div class="mb-3">
                        <label for="end_datetime" class="form-label">Date et heure de fin:</label>
                        <input type="text" class="form-control" id="end_datetime" name="end_datetime" required>
                    </div>

                    <div id="duration_display" class="text-primary fw-bold mb-3"></div>

                    <button type="submit" class="btn <?php echo $buttonClass; ?>">Enregistrer</button>
                </form>
            </div>
        </div>
    </div>
</div>