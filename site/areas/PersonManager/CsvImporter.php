<?php
require_once __DIR__ . '/../../lib/Database/Tables/Person.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

class CsvImporter {
    private Person $personModel;
    private string $uploadDir;
    private array $foundEmails = [];

    public function __construct(Person $personModel) {
        $this->personModel = $personModel;
        $this->uploadDir = __DIR__ . '/tmp_imports/';
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0777, true);
        }
    }

    public function saveUploadedFile($tmpFile, $originalName): string {
        $safeName = uniqid('import_') . '_' . preg_replace('/[^a-zA-Z0-9.]/', '_', $originalName);
        $savedPath = $this->uploadDir . $safeName;
        
        if (!move_uploaded_file($tmpFile, $savedPath)) {
            throw new Exception("Erreur lors de la sauvegarde du fichier");
        }
        return $savedPath;
    }

    public function processImport(string $filepath, int $headerRow, array $mapping): array {
        if (!file_exists($filepath)) {
            throw new Exception("Fichier non trouvé : $filepath");
        }

        $result = [
            'created' => 0,
            'updated' => 0,
            'inactivated' => 0,
            'errors' => 0,
            'messages' => []
        ];

        $file = fopen($filepath, 'r');
        if (!$file) {
            throw new Exception("Impossible d'ouvrir le fichier");
        }

        for ($i = 1; $i < $headerRow; $i++) {
            fgetcsv($file);
        }
        fgetcsv($file);

        $rowNum = $headerRow;
        while (($data = fgetcsv($file)) !== false) {
            $rowNum++;
            
            $personData = [];
            foreach ($mapping as $field => $columnIndex) {
                if ($columnIndex !== '' && $columnIndex !== null) {
                    $personData[$field] = $data[$columnIndex] ?? '';
                }
            }

            if (empty($personData['Email'])) {
                $result['errors']++;
                $result['messages'][] = "Ligne $rowNum : Email manquant";
                continue;
            }
            array_push($this->foundEmails, $personData['Email']);

            try {
                $existingPerson = $this->personModel->getByEmail($personData['Email']);
                $personData['Imported'] = 1;
                $personData['Inactivated'] = 0;

                if ($existingPerson) {
                    $this->personModel->setById($existingPerson['Id'], $personData);
                    $result['updated']++;
                } else {
                    $this->personModel->set($personData);
                    $result['created']++;
                }
            } catch (Exception $e) {
                $result['errors']++;
                $result['messages'][] = "Ligne $rowNum : " . $e->getMessage();
            }
        }

        fclose($file);



require_once __DIR__ . '/../../lib/Database/Tables/Debug.php';
(new Debug())->set(json_encode($this->foundEmails));

        foreach ($this->personModel->getOrdered('Email') as $person){
            if($person['Inactivated'] == 0){


                (new Debug())->set(json_encode($person));


                if(!in_array($person['Email'], $this->foundEmails)){
                    $this->personModel->setById($person['Id'], ['Inactivated' => 1]);
                    $result['inactivated']++;
                } 
            }
        }
        return $result;
    }

    public function getHeadersFromFile(string $filepath, int $headerRow): array {
        if (!file_exists($filepath)) {
            throw new Exception("Fichier non trouvé : $filepath");
        }

        $file = fopen($filepath, 'r');
        if (!$file) {
            throw new Exception("Impossible d'ouvrir le fichier");
        }

        for ($i = 1; $i < $headerRow; $i++) {
            fgetcsv($file);
        }
        
        $headers = fgetcsv($file);
        fclose($file);

        if (!$headers) {
            throw new Exception("Impossible de lire les en-têtes du fichier");
        }

        return $headers;
    }
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $importer = new CsvImporter(new Person());
        
        // Étape 1 : Upload et affichage du mapping
        if (isset($_POST['step']) && $_POST['step'] === 'mapping' && isset($_FILES['csvFile'])) {
            if ($_FILES['csvFile']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("Erreur lors de l'upload du fichier");
            }

            $headerRow = (int)$_POST['headerRow'];
            $savedFilePath = $importer->saveUploadedFile(
                $_FILES['csvFile']['tmp_name'],
                $_FILES['csvFile']['name']
            );
            
            $headers = $importer->getHeadersFromFile($savedFilePath, $headerRow);
            ?>
            <!DOCTYPE html>
            <html lang="fr">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Import CSV - Mapping</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            </head>
            <body>
                <div class="container mt-5">
                    <h1>Import CSV - Mapping des colonnes</h1>
                    <form method="post">
                        <input type="hidden" name="step" value="import">
                        <input type="hidden" name="filepath" value="<?= htmlspecialchars($savedFilePath) ?>">
                        <input type="hidden" name="headerRow" value="<?= $headerRow ?>">
                        
                        <div class="mb-3">
                            <h3>Fichier : <?= htmlspecialchars($_FILES['csvFile']['name']) ?></h3>
                            <div class="row">
                                <div class="col-md-3">
                                    <label class="form-label">Email (obligatoire)</label>
                                    <select name="mapping[Email]" class="form-select" required>
                                        <option value="">Sélectionner</option>
                                        <?php foreach ($headers as $index => $header): ?>
                                            <option value="<?= $index ?>"><?= htmlspecialchars($header) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Prénom</label>
                                    <select name="mapping[FirstName]" class="form-select">
                                        <option value="">Sélectionner</option>
                                        <?php foreach ($headers as $index => $header): ?>
                                            <option value="<?= $index ?>"><?= htmlspecialchars($header) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Nom</label>
                                    <select name="mapping[LastName]" class="form-select">
                                        <option value="">Sélectionner</option>
                                        <?php foreach ($headers as $index => $header): ?>
                                            <option value="<?= $index ?>"><?= htmlspecialchars($header) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Téléphone</label>
                                    <select name="mapping[Phone]" class="form-select">
                                        <option value="">Sélectionner</option>
                                        <?php foreach ($headers as $index => $header): ?>
                                            <option value="<?= $index ?>"><?= htmlspecialchars($header) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Importer</button>
                    </form>
                </div>
            </body>
            </html>
            <?php
            exit;
        }
        
        // Étape 2 : Traitement de l'import
        if (isset($_POST['step']) && $_POST['step'] === 'import') {
            if (!isset($_POST['filepath']) || !file_exists($_POST['filepath'])) {
                throw new Exception("Fichier d'import non trouvé");
            }

            $mapping = array_filter($_POST['mapping'], fn($value) => $value !== '');
            $result = $importer->processImport($_POST['filepath'], (int)$_POST['headerRow'], $mapping);
            
            // Supprimer le fichier temporaire après traitement
            @unlink($_POST['filepath']);
            ?>
            <!DOCTYPE html>
            <html lang="fr">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Import CSV - Résultat</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            </head>
            <body>
                <div class="container mt-5">
                    <h1>Import CSV - Résultat</h1>
                    <?php if ($result['errors'] > 0): ?>
                        <div class="alert alert-warning">
                            <h4>Résultat de l'import :</h4>
                            <ul>
                                <li>Nouveaux enregistrements : <?= $result['created'] ?></li>
                                <li>Enregistrements mis à jour : <?= $result['updated'] ?></li>
                                <li>Enregistrements inactivé : <?= $result['inactivated'] ?></li>
                                <li>Erreurs : <?= $result['errors'] ?></li>
                            </ul>
                            <h4>Détail des erreurs :</h4>
                            <ul>
                                <?php foreach ($result['messages'] as $message): ?>
                                    <li><?= htmlspecialchars($message) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-success">
                            <h4>Import terminé avec succès :</h4>
                            <ul>
                                <li>Nouveaux enregistrements : <?= $result['created'] ?></li>
                                <li>Enregistrements mis à jour : <?= $result['updated'] ?></li>
                                <li>Enregistrements inactivés : <?= $result['inactivated'] ?></li>
                            </ul>
                        </div>
                    <?php endif; ?>
                    <a href="?" class="btn btn-primary">Nouvel import</a>
                </div>
            </body>
            </html>
            <?php
            exit;
        }
    }
} catch (Exception $e) {
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Import CSV - Erreur</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body>
        <div class="container mt-5">
            <h1>Import CSV - Erreur</h1>
            <div class="alert alert-danger">
                <?= htmlspecialchars($e->getMessage()) ?>
            </div>
            <a href="?" class="btn btn-primary">Réessayer</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import CSV - Personnes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Import CSV - Personnes</h1>
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="step" value="mapping">
            
            <div class="mb-3">
                <label for="csvFile" class="form-label">Fichier CSV</label>
                <input type="file" class="form-control" id="csvFile" name="csvFile" accept=".csv" required>
            </div>
            
            <div class="mb-3">
                <label for="headerRow" class="form-label">Numéro de la ligne d'en-tête</label>
                <input type="number" class="form-control" id="headerRow" name="headerRow" value="1" min="1" required>
            </div>
            
            <button type="submit" class="btn btn-primary">Suivant</button>
        </form>
    </div>
</body>
</html>