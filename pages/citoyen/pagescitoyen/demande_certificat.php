<?php
global $pdo;
require "../../config/database.php";

// Configuration des documents requis
$documentsConfig = [
    'standard' => [
        ['id' => 'acteNaissance', 'label' => 'Copie d\'acte de naissance timbré et certifié', 'required' => true],
    ]
];

// Fonction de validation des documents
function validateDocuments($files) {
    $errors = [];

    if (!isset($files['acteNaissance']) || $files['acteNaissance']['error'] !== 0) {
        $errors[] = "La copie d'acte de naissance timbrée et certifiée est obligatoire.";
    }

    $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
    $maxFileSize = 5 * 1024 * 1024; // 5 MB

    foreach ($files as $key => $file) {
        if ($file['error'] === 0) {
            $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($fileExtension, $allowedExtensions)) {
                $errors[] = "Le fichier doit être au format PDF, JPG, JPEG ou PNG.";
            }
            if ($file['size'] > $maxFileSize) {
                $errors[] = "Le fichier ne doit pas dépasser 5 MB.";
            }
        }
    }

    return $errors;
}

// Traitement du formulaire
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $documentErrors = validateDocuments($_FILES);

    if (empty($documentErrors)) {
        // Récupération des données du formulaire
        $data = [
            'nom' => $_POST['nom'],
            'prenom' => $_POST['prenom'],
            'dateNaissance' => $_POST['dateNaissance'],
            'lieuNaissance' => $_POST['lieuNaissance'],
            'nomPere' => $_POST['nomPere'],
            'nomMere' => $_POST['nomMere'],
            'adresse' => $_POST['adresse'],
            'telephone' => $_POST['telephone'],
            'motif' => $_POST['motif'],
            'userId' => $_SESSION['user']['UtilisateurID']
        ];

        // Traitement des uploads de documents
        $uploadedDocs = [];
        $uploadErrors = [];

        foreach ($_FILES as $key => $file) {
            if ($file['error'] === 0) {
                $uploadDir = '../../uploads/documents_nationalite/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $fileName = uniqid() . '_' . basename($file['name']);
                $filePath = $uploadDir . $fileName;

                if (move_uploaded_file($file['tmp_name'], $filePath)) {
                    $uploadedDocs[$key] = $filePath;
                } else {
                    $uploadErrors[] = "Erreur lors de l'upload du fichier";
                }
            }
        }

        if (empty($uploadErrors)) {
            try {
                $pdo->beginTransaction();

                // Insérer la demande
                $stmtDemande = $pdo->prepare("
                    INSERT INTO demandes (UtilisateurID, TypeDemande, Statut) 
                    VALUES (:userId, 'NATIONALITE', 'Soumise')
                ");
                $stmtDemande->execute(['userId' => $data['userId']]);
                $demandeId = $pdo->lastInsertId();

                // Insérer les détails de la demande
                $stmtDetails = $pdo->prepare("
                    INSERT INTO demande_nationalite_details 
                    (DemandeID, Nom, Prenom, DateNaissance, LieuNaissance, NomPere, NomMere, Adresse, Telephone, Motif) 
                    VALUES (:demandeId, :nom, :prenom, :dateNaissance, :lieuNaissance, :nomPere, :nomMere, :adresse, :telephone, :motif)
                ");

                $stmtDetails->execute([
                    'demandeId' => $demandeId,
                    'nom' => $data['nom'],
                    'prenom' => $data['prenom'],
                    'dateNaissance' => $data['dateNaissance'],
                    'lieuNaissance' => $data['lieuNaissance'],
                    'nomPere' => $data['nomPere'],
                    'nomMere' => $data['nomMere'],
                    'adresse' => $data['adresse'],
                    'telephone' => $data['telephone'],
                    'motif' => $data['motif']
                ]);

                // Insérer les documents
                $stmtDoc = $pdo->prepare("
                    INSERT INTO documents (DemandeID, TypeDocument, CheminFichier, UtilisateurID) 
                    VALUES (:demandeId, :typeDoc, :chemin, :userId)
                ");

                foreach ($uploadedDocs as $type => $chemin) {
                    $stmtDoc->execute([
                        'demandeId' => $demandeId,
                        'typeDoc' => $type,
                        'chemin' => $chemin,
                        'userId' => $data['userId']
                    ]);
                }

                $pdo->commit();
                $message = "Votre demande de certificat de nationalité a été soumise avec succès !";
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

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demande de Certificat de Nationalité</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../Assets/css/auth.css" rel="stylesheet" type="text/css"/>
    <style>
        .form-label.required:after {
            content: " *";
            color: red;
        }
        .card {
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        }
        .card-header {
            background-color: #0d6efd !important;
        }
        .btn-primary {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }
        .btn-primary:hover {
            background-color: #0b5ed7;
            border-color: #0a58ca;
        }
        .alert {
            margin-bottom: 20px;
            animation: slideIn 0.3s ease-out;
        }
        #documentsContainer .mb-3 {
            padding: 15px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            margin-bottom: 15px !important;
            background-color: #f8f9fa;
        }
        .text-muted {
            font-size: 0.875rem;
        }
        @keyframes slideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            .card {
                margin: 10px;
            }
            .image-preview img {
                max-width: 150px;
                max-height: 150px;
            }
        }
        .image-preview {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
<div class="container mt-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h2 class="mb-0">Demande de Certificat de Nationalité</h2>
                </div>
                <div class="card-body">
                    <?php if (isset($message)) : ?>
                        <div class="alert alert-<?php echo $messageType; ?>"><?php echo $message; ?></div>
                    <?php endif; ?>

                    <form action="" method="post" enctype="multipart/form-data" id="nationaliteForm">
                        <h3 class="mt-4 mb-3">Informations personnelles</h3>

                        <div class="mb-3">
                            <label for="nom" class="form-label required">Nom</label>
                            <input type="text" class="form-control" id="nom" name="nom" required>
                        </div>

                        <div class="mb-3">
                            <label for="prenom" class="form-label required">Prénom</label>
                            <input type="text" class="form-control" id="prenom" name="prenom" required>
                        </div>

                        <div class="mb-3">
                            <label for="dateNaissance" class="form-label required">Date de naissance</label>
                            <input type="date" class="form-control" id="dateNaissance" name="dateNaissance" required>
                        </div>

                        <div class="mb-3">
                            <label for="lieuNaissance" class="form-label required">Lieu de naissance</label>
                            <input type="text" class="form-control" id="lieuNaissance" name="lieuNaissance" required>
                        </div>

                        <div class="mb-3">
                            <label for="nomPere" class="form-label required">Nom et prénom du père</label>
                            <input type="text" class="form-control" id="nomPere" name="nomPere" required>
                        </div>

                        <div class="mb-3">
                            <label for="nomMere" class="form-label required">Nom et prénom de la mère</label>
                            <input type="text" class="form-control" id="nomMere" name="nomMere" required>
                        </div>

                        <div class="mb-3">
                            <label for="adresse" class="form-label required">Adresse</label>
                            <input type="text" class="form-control" id="adresse" name="adresse" required>
                        </div>

                        <div class="mb-3">
                            <label for="telephone" class="form-label required">Téléphone</label>
                            <input type="tel" class="form-control" id="telephone" name="telephone" required>
                        </div>

                        <div class="mb-3">
                            <label for="motif" class="form-label required">Motif de la demande</label>
                            <textarea class="form-control" id="motif" name="motif" rows="3" required></textarea>
                        </div>

                        <h3 class="mt-4 mb-3">Document requis</h3>
                        <div id="documentsContainer">
                            <div class="mb-3">
                                <label for="acteNaissance" class="form-label required">Copie d'acte de naissance timbré et certifié</label>
                                <input type="file" class="form-control" id="acteNaissance" name="acteNaissance" required
                                       accept=".pdf,.jpg,.jpeg,.png">
                                <small class="text-muted">Format accepté : PDF, JPG, JPEG, PNG. Taille maximale : 5 MB</small>
                                <div id="previewContainer" class="mt-2"></div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <p class="text-muted">Frais de dossier : 1.500 FCFA</p>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Soumettre la demande</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('nationaliteForm').addEventListener('submit', function(e) {
        const maxSize = 5 * 1024 * 1024; // 5 MB
        const file = document.getElementById('acteNaissance').files[0];

        if (file) {
            if (file.size > maxSize) {
                e.preventDefault();
                alert("Le fichier ne doit pas dépasser 5 MB");
                return false;
            }

            const validTypes = ['application/pdf', 'image/jpeg', 'image/png'];
            if (!validTypes.includes(file.type)) {
                e.preventDefault();
                alert("Le fichier doit être au format PDF, JPG, JPEG ou PNG");
                return false;
            }
        }
    });

    document.getElementById('acteNaissance').addEventListener('change', function(e) {
        const file = e.target.files[0];
        const previewContainer = document.getElementById('previewContainer');
        previewContainer.innerHTML = '';

        if (file && file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                previewContainer.innerHTML = `
                    <div class="image-preview mt-2">
                        <img src="${e.target.result}" alt="Prévisualisation"
                             style="max-width: 200px; max-height: 200px; object-fit: contain;">
                        <button type="button" class="btn btn-sm btn-danger mt-1"
                                onclick="this.parentElement.remove(); document.getElementById('acteNaissance').value='';">
                            Supprimer
                        </button>
                    </div>
                `;
            }
            reader.readAsDataURL(file);
        } else if (file && file.type === 'application/pdf') {
            previewContainer.innerHTML = `
                <div class="mt-2">
                    <p class="text-success">PDF sélectionné : ${file.name}</p>
                    <button type="button" class="btn btn-sm btn-danger"
                            onclick="this.parentElement.remove(); document.getElementById('acteNaissance').value='';">
                        Supprimer
                    </button>
                </div>
            `;
        }
    });
</script>

</body>
</html>