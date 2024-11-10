<?php
global $pdo;
require "../../config/database.php";

// Configuration des documents requis pour chaque type de demande
$documentsConfig = [
    'premiere' => [
        ['id' => 'photo', 'label' => 'Photo d\'identité', 'required' => true],
        ['id' => 'acteNaissance', 'label' => 'Copie certifiée conforme d\'acte de naissance', 'required' => true],
        ['id' => 'certificatNationalite', 'label' => 'Certificat de nationalité', 'required' => true],
        ['id' => 'justificatifProfession', 'label' => 'Pièce justificative de la profession', 'required' => false]
    ],
    'renouvellement' => [
        ['id' => 'photo', 'label' => 'Photo d\'identité', 'required' => true],
        ['id' => 'acteNaissance', 'label' => 'Copie certifiée conforme d\'acte de naissance', 'required' => true],
        ['id' => 'ancienneCNI', 'label' => 'Ancienne carte nationale d\'identité', 'required' => true],
        ['id' => 'justificatifProfession', 'label' => 'Pièce justificative de la profession', 'required' => false]
    ],
    'perte' => [
        ['id' => 'photo', 'label' => 'Photo d\'identité', 'required' => true],
        ['id' => 'acteNaissance', 'label' => 'Copie certifiée conforme d\'acte de naissance', 'required' => true],
        ['id' => 'certificatNationalite', 'label' => 'Certificat de nationalité', 'required' => true],
        ['id' => 'justificatifProfession', 'label' => 'Pièce justificative de la profession', 'required' => false]
    ],
    'naturalisation' => [
        ['id' => 'photo', 'label' => 'Photo d\'identité', 'required' => true],
        ['id' => 'acteNaissance', 'label' => 'Copie certifiée conforme d\'acte de naissance', 'required' => true],
        ['id' => 'decretNaturalisation', 'label' => 'Copie du décret de naturalisation', 'required' => true],
        ['id' => 'casierJudiciaire', 'label' => 'Bulletin n°3 du casier judiciaire spécial', 'required' => true],
        ['id' => 'certificatNationalite', 'label' => 'Certificat de nationalité', 'required' => true],
        ['id' => 'justificatifProfession', 'label' => 'Pièce justificative de la profession', 'required' => false]
    ]
];

// Fonction de validation des documents
function validateCNIDocuments($typeDemande, $files) {
    global $documentsConfig;
    $errors = [];

    if (!isset($documentsConfig[$typeDemande])) {
        return ['Type de demande invalide'];
    }

    // Vérification des documents requis pour le type de demande
    foreach ($documentsConfig[$typeDemande] as $doc) {
        if ($doc['required'] && (!isset($files[$doc['id']]) || $files[$doc['id']]['error'] !== 0)) {
            $errors[] = "Le document '" . $doc['label'] . "' est obligatoire.";
        }
    }

    // Validation des types de fichiers et tailles
    $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
    $maxFileSize = 5 * 1024 * 1024; // 5 MB

    foreach ($files as $key => $file) {
        if ($file['error'] === 0) {
            $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($fileExtension, $allowedExtensions)) {
                $errors[] = "Le fichier '" . getDocumentLabel($key) . "' doit être au format PDF, JPG, JPEG ou PNG.";
            }
            if ($file['size'] > $maxFileSize) {
                $errors[] = "Le fichier '" . getDocumentLabel($key) . "' ne doit pas dépasser 5 MB.";
            }
        }
    }

    return $errors;
}

function getDocumentLabel($key) {
    global $documentsConfig;
    foreach ($documentsConfig as $type) {
        foreach ($type as $doc) {
            if ($doc['id'] === $key) {
                return $doc['label'];
            }
        }
    }
    return $key;
}

// Traitement du formulaire
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validation des documents
    $documentErrors = validateCNIDocuments($_POST['typeDemande'], $_FILES);

    if (empty($documentErrors)) {
        // Récupération des données de base
        $typeDemande = $_POST['typeDemande'];
        $nom = $_POST['nom'];
        $prenom = $_POST['prenom'];
        $dateNaissance = $_POST['dateNaissance'];
        $lieuNaissance = $_POST['lieuNaissance'];
        $adresse = $_POST['adresse'];
        $sexe = $_POST['sexe'];
        $taille = $_POST['taille'];
        $profession = $_POST['profession'];

        // Traitement des uploads de documents
        $uploadedDocs = [];
        $uploadErrors = [];

        foreach ($_FILES as $key => $file) {
            if ($file['error'] === 0) {
                $uploadDir = '../../uploads/documents/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $fileName = uniqid() . '_' . basename($file['name']);
                $filePath = $uploadDir . $fileName;

                if (move_uploaded_file($file['tmp_name'], $filePath)) {
                    $uploadedDocs[$key] = $filePath;
                } else {
                    $uploadErrors[] = "Erreur lors de l'upload du fichier " . getDocumentLabel($key);
                }
            }
        }

        if (empty($uploadErrors)) {
            try {
                $pdo->beginTransaction();

                // Insérer la demande
                $stmtDemande = $pdo->prepare("INSERT INTO demandes (UtilisateurID, TypeDemande, SousTypeDemande, Statut) VALUES (:userId, 'CNI', :typeDemande, 'Soumise')");
                $stmtDemande->execute([
                    ':userId' => $_SESSION['user']['UtilisateurID'],
                    ':typeDemande' => $typeDemande
                ]);
                $demandeId = $pdo->lastInsertId();

                // Insérer les détails de la demande
                $stmtDetails = $pdo->prepare("INSERT INTO demande_cni_details (DemandeID, Nom, Prenom, DateNaissance, LieuNaissance, Adresse, Sexe, Taille, Profession) VALUES (:demandeId, :nom, :prenom, :dateNaissance, :lieuNaissance, :adresse, :sexe, :taille, :profession)");
                $stmtDetails->execute([
                    ':demandeId' => $demandeId,
                    ':nom' => $nom,
                    ':prenom' => $prenom,
                    ':dateNaissance' => $dateNaissance,
                    ':lieuNaissance' => $lieuNaissance,
                    ':adresse' => $adresse,
                    ':sexe' => $sexe,
                    ':taille' => $taille,
                    ':profession' => $profession
                ]);

                // Insérer les documents
                $stmtDoc = $pdo->prepare("INSERT INTO documents (DemandeID, TypeDocument, CheminFichier, UtilisateurID) VALUES (:demandeId, :typeDoc, :chemin, :userId)");
                foreach ($uploadedDocs as $type => $chemin) {
                    $stmtDoc->execute([
                        ':demandeId' => $demandeId,
                        ':typeDoc' => $type,
                        ':chemin' => $chemin,
                        ':userId' => $_SESSION['user']['UtilisateurID']
                    ]);
                }

                $pdo->commit();
                $message = "Votre demande de CNI a été soumise avec succès !";
                $messageType = "success";
            } catch (PDOException $e) {
                $pdo->rollBack();
                $message = "Erreur lors de la soumission de la demande : " . $e->getMessage();
                $messageType = "danger";
            }
        } else {
            $message = implode("<br>", $uploadErrors);
            $messageType = "danger";
        }
    } else {
         $message = implode("<br>", $documentErrors);
         $messageType = "danger";
     }
}
?>
<?php if (isset($message)): ?>
     <div class="alert alert-<?php echo $messageType; ?>" 
          style="transition: opacity 0.5s ease-in-out; opacity: 1; 
                 background-color: <?= $messageType == 'success' ? '#d4edda' : '#f8d7da' ?>; 
                 color: <?= $messageType == 'success' ? '#155724' : '#721c24' ?>; 
                 padding: 15px; border-radius: 4px; margin-bottom: 20px;">
         <?php echo $message; ?>
     </div>
<?php endif; ?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demande de Carte Nationale d'Identité</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../Assets/css/auth.css" rel="stylesheet" type="text/css"/>
    <style>
    .progress-steps {
        display: flex;
        justify-content: space-between;
        margin-bottom: 2rem;
    }

    .step {
        flex: 1;
        text-align: center;
        padding: 1rem;
        background: #f8f9fa;
        border-radius: 4px;
        margin: 0 5px;
        position: relative;
    }

    .step.active {
        background: #0d6efd;
        color: white;
    }

    .preview-container {
        position: relative;
        display: inline-block;
        margin: 10px 0;
    }

    .preview-image {
        max-width: 200px;
        max-height: 200px;
        border-radius: 4px;
    }

    .preview-overlay {
        position: absolute;
        top: 5px;
        right: 5px;
        display: flex;
        gap: 5px;
    }

    .form-section {
        transition: all 0.3s ease;
    }

    .form-section.hidden {
        display: none;
    }

    .upload-progress {
        height: 4px;
        margin-top: 5px;
        background: #e9ecef;
        border-radius: 4px;
        overflow: hidden;
    }

    .upload-progress-bar {
        height: 100%;
        background: #0d6efd;
        width: 0;
        transition: width 0.3s ease;
    }
</style>
</head>
<body>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h2 class="mb-0">Demande de Carte Nationale d'Identité</h2>
                </div>
                <div class="card-body">
                    <!-- Progress Steps -->
                    <div class="progress-steps">
                        <div class="step active">1. Type de demande</div>
                        <div class="step">2. Informations personnelles</div>
                        <div class="step">3. Documents</div>
                    </div>

                    <!-- Alert Messages -->
                    <?php if (isset($message)): ?>
                        <div class="alert alert-<?php echo $messageType; ?>" 
                             style="transition: opacity 0.5s ease-in-out; opacity: 1;">
                            <?php echo $message; ?>
                        </div>
                    <?php endif; ?>

                    <form id="cniForm" action="" method="post" enctype="multipart/form-data">
                        <!-- Type de demande section -->
                        <div class="form-section" id="section1">
                            <div class="mb-3">
                                <label for="typeDemande" class="form-label">Type de demande</label>
                                <select class="form-select" id="typeDemande" name="typeDemande" required>
                                    <option value="">Choisissez...</option>
                                    <option value="premiere">Première demande</option>
                                    <option value="renouvellement">Renouvellement</option>
                                    <option value="perte">Perte/Vol</option>
                                </select>
                                <div class="invalid-feedback">Veuillez sélectionner un type de demande</div>
                            </div>
                            <button type="button" class="btn btn-primary" onclick="nextSection(1)">Suivant</button>
                        </div>

                        <!-- Informations personnelles -->
                        <div class="form-section hidden" id="section2">
                            <h3 class="mt-4">Informations personnelles</h3>
                            <div class="mb-3">
                                <label for="nom" class="form-label">Nom</label>
                                <input type="text" class="form-control" id="nom" name="nom" required>
                            </div>
                            <div class="mb-3">
                                <label for="prenom" class="form-label">Prénom</label>
                                <input type="text" class="form-control" id="prenom" name="prenom" required>
                            </div>
                            <div class="mb-3">
                                <label for="dateNaissance" class="form-label">Date de naissance</label>
                                <input type="date" class="form-control" id="dateNaissance" name="dateNaissance" required>
                            </div>
                            <div class="mb-3">
                                <label for="lieuNaissance" class="form-label">Lieu de naissance</label>
                                <input type="text" class="form-control" id="lieuNaissance" name="lieuNaissance" required>
                            </div>
                            <div class="mb-3">
                                <label for="adresse" class="form-label">Adresse</label>
                                <input type="text" class="form-control" id="adresse" name="adresse" required>
                            </div>
                            <div class="mb-3">
                                <label for="sexe" class="form-label">Sexe</label>
                                <select class="form-select" id="sexe" name="sexe" required>
                                    <option value="">Choisissez...</option>
                                    <option value="M">Masculin</option>
                                    <option value="F">Féminin</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="taille" class="form-label">Taille (en cm)</label>
                                <input type="number" class="form-control" id="taille" name="taille" required min="0" max="300">
                            </div>
                            <div class="mb-3">
                                <label for="profession" class="form-label">Profession</label>
                                <input type="text" class="form-control" id="profession" name="profession">
                            </div>
                            <button type="button" class="btn btn-secondary" onclick="previousSection(2)">Précédent</button>
                            <button type="button" class="btn btn-primary" onclick="nextSection(2)">Suivant</button>
                        </div>

                        <!-- Documents -->
                        <div class="form-section hidden" id="section3">
                            <h3 class="mt-4">Documents à fournir</h3>
                            <div id="documentsContainer">
                                <!-- Les documents seront injectés ici dynamiquement -->
                            </div>

                            <div class="mb-3">
                                <p class="text-muted">Frais de dossier : 2.800 FCFA</p>
                            </div>

                            <button type="button" class="btn btn-secondary" onclick="previousSection(3)">Précédent</button>
                            <button type="submit" class="btn btn-primary">Soumettre la demande</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Form validation and progression
let currentSection = 1;

function nextSection(section) {
    const currentDiv = document.getElementById('section' + section);
    const nextDiv = document.getElementById('section' + (section + 1));
    const steps = document.querySelectorAll('.step');

    if (validateSection(section)) {
        currentDiv.classList.add('hidden');
        nextDiv.classList.remove('hidden');
        steps[section].classList.add('active');
        currentSection = section + 1;
        saveFormData();
    }
}

function previousSection(section) {
    const currentDiv = document.getElementById('section' + section);
    const prevDiv = document.getElementById('section' + (section - 1));
    const steps = document.querySelectorAll('.step');

    currentDiv.classList.add('hidden');
    prevDiv.classList.remove('hidden');
    steps[section - 1].classList.remove('active');
    currentSection = section - 1;
}

function validateSection(section) {
    const currentDiv = document.getElementById('section' + section);
    const inputs = currentDiv.querySelectorAll('input[required], select[required]');
    let valid = true;

    inputs.forEach(input => {
        if (!input.value) {
            input.classList.add('is-invalid');
            valid = false;
        } else {
            input.classList.remove('is-invalid');
        }
    });

    return valid;
}

// Document preview and upload
function handleFileSelect(input) {
    const file = input.files[0];
    const previewDiv = input.parentElement.querySelector('.preview-container');
    
    if (file) {
        createImagePreview(file, previewDiv);
        const progressBar = input.parentElement.querySelector('.upload-progress-bar');
        updateUploadProgress(file, progressBar);
    }
}

function createImagePreview(file, previewDiv) {
    const reader = new FileReader();
    reader.onload = function(e) {
        previewDiv.innerHTML = `
            <div class="preview-container">
                <img src="${e.target.result}" alt="Prévisualisation" class="preview-image">
                <div class="preview-overlay">
                    <button type="button" class="btn btn-sm btn-light" onclick="rotateImage(this)">
                        <i class="bi bi-arrow-clockwise"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-danger" onclick="removePreview(this)">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        `;
    }
    reader.readAsDataURL(file);
}

// Form autosave
function saveFormData() {
    const formData = new FormData(document.getElementById('cniForm'));
    const data = {};
    formData.forEach((value, key) => {
        data[key] = value;
    });
    localStorage.setItem('cniFormData', JSON.stringify(data));
}

// Load saved form data
document.addEventListener('DOMContentLoaded', function() {
    const savedData = localStorage.getItem('cniFormData');
    if (savedData) {
        const data = JSON.parse(savedData);
        Object.keys(data).forEach(key => {
            const input = document.querySelector(`[name="${key}"]`);
            if (input) input.value = data[key];
        });
    }
});

// Configuration des documents (même structure que côté PHP)
const documentsConfig = <?php echo json_encode($documentsConfig); ?>;

document.getElementById('typeDemande').addEventListener('change', function() {
    const container = document.getElementById('documentsContainer');
    container.innerHTML = ''; // Vider le conteneur

    const selectedType = this.value;
    if (!selectedType || !documentsConfig[selectedType]) return;

    const documents = documentsConfig[selectedType];
    documents.forEach(doc => {
        const div = document.createElement('div');
        div.className = 'mb-3';
        div.innerHTML = `
            <label for="${doc.id}" class="form-label">
                ${doc.label}${doc.required ? ' *' : ''}
            </label>
            <input type="file" class="form-control" id="${doc.id}" name="${doc.id}"
                   accept=".pdf,.jpg,.jpeg,.png" ${doc.required ? 'required' : ''} onchange="handleFileSelect(this)">
            <small class="text-muted">Formats acceptés : PDF, JPG, JPEG, PNG. Taille max : 5 MB</small>
            <div class="preview-container"></div>
            <div class="upload-progress">
                <div class="upload-progress-bar"></div>
            </div>
        `;
        container.appendChild(div);
    });
});

// Validation côté client de la taille des fichiers
document.getElementById('cniForm').addEventListener('submit', function(e) {
    const maxSize = 5 * 1024 * 1024; // 5 MB
    const files = this.querySelectorAll('input[type="file"]');
    let hasError = false;

    files.forEach(file => {
        if (file.files.length > 0) {
            if (file.files[0].size > maxSize) {
                hasError = true;
                alert(`Le fichier "${file.files[0].name}" dépasse la taille maximale autorisée de 5 MB`);
            }

            const validTypes = ['application/pdf', 'image/jpeg', 'image/png'];
            if (!validTypes.includes(file.files[0].type)) {
                hasError = true;
                alert(`Le fichier "${file.files[0].name}" n'est pas dans un format valide (PDF, JPG, JPEG, PNG)`);
            }
        }
    });

    if (hasError) {
        e.preventDefault();
        return false;
    }
});

function removePreview(button) {
    const previewDiv = button.closest('.preview-container');
    const fileInput = previewDiv.parentElement.querySelector('input[type="file"]');
    fileInput.value = ''; // Réinitialiser l'input file
    previewDiv.innerHTML = '';
}

function rotateImage(button) {
    const img = button.closest('.preview-container').querySelector('img');
    const currentRotation = img.style.transform ? parseInt(img.style.transform.replace('rotate(', '').replace('deg)', '')) || 0 : 0;
    img.style.transform = `rotate(${(currentRotation + 90) % 360}deg)`;
}

function updateUploadProgress(file, progressBar) {
    // Simuler un téléchargement
    let progress = 0;
    const interval = setInterval(() => {
        progress += 10;
        progressBar.style.width = `${progress}%`;
        if (progress >= 100) {
            clearInterval(interval);
        }
    }, 200);
}
</script>

</body>
</html>
