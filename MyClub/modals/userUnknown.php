<div class="modal fade" id="popUpModal" data-bs-backdrop="static" tabindex="-1" aria-labelledby="popUpModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="popUpModalLabel">Utilisateur inconnu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>L'adresse email ne correspond à aucun utilisateur</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var modal = new bootstrap.Modal(document.getElementById('popUpModal'));
        modal.show();
    });

    document.getElementById('popUpModal').addEventListener('hidden.bs.modal', function (event) {
        window.location.href = '../../Page.php';
    });
</script>