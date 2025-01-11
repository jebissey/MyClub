<?php
require_once 'includes/header.php';
require_once  __DIR__ . '/lib/Database/Tables/Person.php';
require_once  __DIR__ . '/lib/PasswordManager.php';
echo "<main>\n";


$personId=$_GET['p'] ?? 0;

//$emailReadOnly = true;


$userEmail = $_SESSION['user'] ?? '';
if($userEmail != ''){
    $id = $personFound['Id'] ?? -1;
    if($id == -1){
        header('Location:areas/SignIn/SignOut.php');
        exit();
    }

    $emojiPath = 'images/';
    $emojiFiles = glob($emojiPath . "emoji*");
    $emojis = array_map(function($path) {return basename($path);}, $emojiFiles);

    $currentAvailability = [];
    if ($personFound && !empty($personFound['Availability'])) {
        $currentAvailability = json_decode($personFound['Availability'], true);
    }
    if (empty($currentAvailability)) {
        $currentAvailability = array_fill(0, 7, ['morning' => false, 'afternoon' => false]);
    }
    $userData = $person->getById($id);
?>
    <style>
        .dropdown-menu {
            max-height: 300px;
            overflow-y: auto;
            width: 250px;
        }
        .dropdown-item img {
            width: 30px;
            height: 30px;
            margin-right: 10px;
        }
        .dropdown-item {
            display: flex;
            align-items: center;
        }
        .dropdown-toggle::after {
            display: none;
        }
    </style>
    <div class="accordion" id="accordionPerson">
        <div class="accordion-item">
            <h3 class="accordion-header">
              <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                <b>Mise à jour du profil</b>
              </button>
            </h3>
            <div id="collapseOne" class="accordion-collapse collapse" data-bs-parent="#accordionPerson">
                <div class="accordion-body">
                    <form method="POST" action="PersonUpdate.php" class="needs-validation" novalidate data-form="profil">
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($userData['Id']); ?>">
                        <input type="hidden" name="u" value="profil">
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                value="<?php echo htmlspecialchars($userData['Email']); ?>" 
                                <?php echo isset($emailReadOnly) && $emailReadOnly ? 'readonly' : 'required'; ?>>
                            <div class="invalid-feedback">
                                Veuillez saisir une adresse email valide.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Mot de passe</label>
                            <input type="password" class="form-control" id="password" name="password" 
                                placeholder="Laissez vide pour ne pas modifier">
                        </div>

                        <div class="mb-3">
                            <label for="firstName" class="form-label">Prénom</label>
                            <input type="text" class="form-control" id="firstName" name="firstName" 
                                value="<?php echo htmlspecialchars($userData['FirstName']); ?>" required>
                            <div class="invalid-feedback">
                                Le prénom est requis.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="lastName" class="form-label">Nom</label>
                            <input type="text" class="form-control" id="lastName" name="lastName" 
                                value="<?php echo htmlspecialchars($userData['LastName']); ?>" required>
                            <div class="invalid-feedback">
                                Le nom est requis.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="nickName" class="form-label">Pseudo</label>
                            <input type="text" class="form-control" id="nickName" name="nickName" 
                                value="<?php echo htmlspecialchars($userData['NickName'] ?? ''); ?>">
                        </div>

                        <div class="mb-3">
                            <div class="dropdown">
                                <button class="btn btn-outline-primary dropdown-toggle" type="button" id="emojiSelect" data-bs-toggle="dropdown" aria-expanded="false">
                                    Sélectionnez un Emoji
                                </button>
                                <ul class="dropdown-menu" id="emojiList">
                                    <?php 
                                    foreach ($emojis as $emoji): 
                                        echo '<li><a class="dropdown-item" href="#" data-img="'.$emojiPath.$emoji.'"><img src="'.$emojiPath.$emoji.'" alt="emoji"> '.$emoji.'</a></li>';
                                    endforeach; 
                                    ?>
                                </ul>
                                <input type="text" class="form-control" id="avatar" name="avatar" 
                                    value="<?php echo htmlspecialchars($userData['Avatar'] ?? ''); ?>">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">Valider</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="accordion-item">
            <h3 class="accordion-header">
              <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="true" aria-controls="collapseTwo">
                <b>Mise à jour des disponibilités</b>
              </button>
            </h3>
            <div id="collapseTwo" class="accordion-collapse collapse" data-bs-parent="#accordionPerson">
                <div class="accordion-body">
                    <form method="POST" action="PersonUpdate.php" class="needs-validation" novalidate data-form="availability">
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($userData['Id']); ?>">
                        <input type="hidden" name="u" value="availability">
                        
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>Lundi</th>
                                    <th>Mardi</th>
                                    <th>Mercredi</th>
                                    <th>Jeudi</th>
                                    <th>Vendredi</th>
                                    <th>Samedi</th>
                                    <th>Dimanche</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Matin</td>
                                    <?php for($i = 0; $i < 7; $i++): ?>
                                    <td>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" 
                                                name="availability[<?php echo $i; ?>][morning]" 
                                                id="morning_<?php echo $i; ?>"
                                                <?php echo (isset($currentAvailability[$i]['morning']) && $currentAvailability[$i]['morning']) ? 'checked' : ''; ?>>
                                        </div>
                                    </td>
                                    <?php endfor; ?>
                                </tr>
                                <tr>
                                    <td>Après-midi</td>
                                    <?php for($i = 0; $i < 7; $i++): ?>
                                    <td>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" 
                                                name="availability[<?php echo $i; ?>][afternoon]" 
                                                id="afternoon_<?php echo $i; ?>"
                                                <?php echo (isset($currentAvailability[$i]['afternoon']) && $currentAvailability[$i]['afternoon']) ? 'checked' : ''; ?>>
                                        </div>
                                    </td>
                                    <?php endfor; ?>
                                </tr>
                            </tbody>
                        </table>
                        
                        <button type="submit" class="btn btn-primary">Valider</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="accordion-item">
            <h3 class="accordion-header">
              <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="true" aria-controls="collapseThree">
                <b>Mise à jour des préférences</b>
              </button>
            </h3>
            <div id="collapseThree" class="accordion-collapse collapse" data-bs-parent="#accordionPerson">
                <div class="accordion-body">
                    <form method="POST" action="PersonUpdate.php" class="needs-validation" novalidate data-form="preferences">
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($userData['Id']); ?>">
                        <input type="hidden" name="u" value="preferences">

                        <button type="submit" class="btn btn-primary">Valider</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
$('#emojiList').on('click', '.dropdown-item', function(e) {
    e.preventDefault();
    var imageUrl = $(this).data('img');
    $('#userAvatar').attr('src', imageUrl);
    $('#avatar').attr('value', imageUrl);
});

    // submits {
    const profilForm = document.querySelector('form[data-form="profil"]');
    if (profilForm) {
        profilForm.addEventListener('submit', function(e) {
            e.preventDefault();
            this.submit();
        });
    }

    const availabilityForm = document.querySelector('form[data-form="availability"]');
    if (availabilityForm) {
        availabilityForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            alert("I am an alert box!");

            const availabilityData = [];
            for(let i = 0; i < 7; i++) {
                availabilityData[i] = {
                    morning: document.querySelector(`input[name="morning_${i}"]`).checked,
                    afternoon: document.querySelector(`input[name="afternoon_${i}"]`).checked
                };
            }
            
            // Création/mise à jour du champ caché pour les disponibilités
            let hiddenAvailability = this.querySelector('input[name="availability"]');
            if (!hiddenAvailability) {
                hiddenAvailability = document.createElement('input');
                hiddenAvailability.type = 'hidden';
                hiddenAvailability.name = 'availability';
                this.appendChild(hiddenAvailability);
            }
            hiddenAvailability.value = JSON.stringify(availabilityData);
            
            this.submit();
        });
    }

    const preferenceForm = document.querySelector('form[data-form="preference"]');
    if (preferenceForm) {
        preferenceForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const preferenceData = {
                theme: document.querySelector('input[name="theme"]:checked')?.value,
                language: document.querySelector('input[name="language"]:checked')?.value,
                // Ajoutez d'autres champs selon vos besoins
            };
            
            let hiddenPreference = this.querySelector('input[name="preference"]');
            if (!hiddenPreference) {
                hiddenPreference = document.createElement('input');
                hiddenPreference.type = 'hidden';
                hiddenPreference.name = 'preference';
                this.appendChild(hiddenPreference);
            }
            hiddenPreference.value = JSON.stringify(preferenceData);
            this.submit();
        });
    }
    // } (submits)
    </script>
<?php
} else {
   $signIn=$_GET['si'] ?? 0;
   if($signIn){
?>
    <div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="loginModalLabel">Connexion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="loginForm" action="areas/SignIn/Check.php" method="POST" novalidate>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="email" class="form-label">Adresse email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                            <div class="invalid-feedback">Veuillez entrer une adresse email valide.</div>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Mot de passe</label>
                            <input type="password" class="form-control" id="password" name="password" required minlength="6">
                            <div class="invalid-feedback">Le mot de passe doit contenir au moins 6 caractères.</div>
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-link" data-bs-toggle="modal" data-bs-target="#forgotPasswordModal" data-bs-dismiss="modal">
                                Mot de passe oublié ?
                            </button>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                        <button type="submit" class="btn btn-primary">Se connecter</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="forgotPasswordModal" tabindex="-1" aria-labelledby="forgotPasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="forgotPasswordModalLabel">Réinitialisation du mot de passe</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="forgotPasswordForm" action="areas/SignIn/ForgotPassword.php" method="POST" novalidate>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="forgotEmail" class="form-label">Adresse email</label>
                            <input type="email" class="form-control" id="forgotEmail" name="email" required>
                            <div class="invalid-feedback">Veuillez entrer une adresse email valide.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                        <button type="submit" class="btn btn-primary">Envoyer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
            loginModal.show();
        });
    </script>
<?php
   }
}

echo "</main>\n";
require_once 'includes/footer.php';
?>