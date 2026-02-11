document.getElementById('submit-vote').addEventListener('click', function () {
    const designId = this.dataset.designId;
    const voteOption = document.querySelector('input[name="voteOption"]:checked').value;

    fetch('/api/design/vote', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            designId: designId,
            vote: voteOption
        })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('voteModal')).hide();
                alert('Votre vote a été enregistré !');
                location.reload();
            } else {
                alert('Erreur lors de l\'enregistrement du vote : ' + data.message);
            }
        })
        .catch(error => {
            alert('Une erreur est survenue lors de l\'envoi du vote :' + error.message);
        });
});

document.querySelectorAll('.design-row').forEach(row => {
    row.addEventListener('click', function () {
        const designId = this.dataset.id;
        const designName = this.dataset.name;
        const designDetail = this.dataset.detail;

        document.getElementById('design-name').textContent = designName;
        document.getElementById('design-detail').textContent = designDetail;

        if (userVotes[designId] !== undefined) {
            const vote = userVotes[designId];
            if (vote === 1) {
                document.getElementById('voteUp').checked = true;
            } else if (vote === -1) {
                document.getElementById('voteDown').checked = true;
            } else {
                document.getElementById('voteNeutral').checked = true;
            }
        } else {
            document.getElementById('voteNeutral').checked = true;
        }

        document.getElementById('submit-vote').dataset.designId = designId;
        const voteModal = new bootstrap.Modal(document.getElementById('voteModal'));
        voteModal.show();
    })
});