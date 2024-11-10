<?php
global $pdo;
require "../../config/database.php";

// Vérification de l'authentification et des permissions
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['RoleId'], ['1', '3'])) {
    header('HTTP/1.0 403 Forbidden');
    echo "Accès non autorisé";
    exit();
}

// Vérification de l'existence du paramètre demande
if (!isset($_GET['demande']) || !is_numeric($_GET['demande'])) {
    header('HTTP/1.0 400 Bad Request');
    echo "Paramètre invalide";
    exit();
}

$demandeId = (int)$_GET['demande'];

// Fonction pour sécuriser l'affichage
function displaySafe($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

try {
    // Récupération des informations de la demande et des documents associés
    $query = $pdo->prepare("
        SELECT 
            d.DocumentID,
            d.TypeDocument,
            d.CheminFichier,
            d.DateTelechargement,
            d.StatutValidation,
            dem.TypeDemande,
            u.Nom,
            u.Prenom
        FROM documents d
        JOIN demandes dem ON d.DemandeID = dem.DemandeID
        JOIN utilisateurs u ON dem.UtilisateurID = u.UtilisateurID
        WHERE d.DemandeID = :demandeId
    ");
    $query->execute([':demandeId' => $demandeId]);
    $documents = $query->fetchAll(PDO::FETCH_ASSOC);

    if (empty($documents)) {
        throw new Exception("Aucun document trouvé pour cette demande.");
    }

} catch (Exception $e) {
    $_SESSION['flash_message'] = $e->getMessage();
    $_SESSION['flash_type'] = 'danger';
    header('Location: traitement_demandes.php');
    exit();
}

// Traitement des actions AJAX pour les documents
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'], $_POST['action'], $_POST['documentId'])) {
    $response = ['success' => false, 'message' => ''];

    try {
        $documentId = (int)$_POST['documentId'];
        $action = $_POST['action'];
        $commentaire = $_POST['commentaire'] ?? '';
        $officierID = $_SESSION['user']['UtilisateurID'];

        // Validation des données
        if (!in_array($action, ['valider', 'rejeter'])) {
            throw new Exception("Action non valide");
        }

        $pdo->beginTransaction();

        // Mise à jour du statut du document
        $nouveauStatut = ($action === 'valider') ? 'Approuve' : 'Rejete';
        $updateDocument = $pdo->prepare("
            UPDATE documents 
            SET StatutValidation = :statut,
                DateValidation = NOW(),
                ValidePar = :officierID,
                CommentaireValidation = :commentaire
            WHERE DocumentID = :documentId
        ");
        $updateDocument->execute([
            ':statut' => $nouveauStatut,
            ':officierID' => $officierID,
            ':commentaire' => $commentaire,
            ':documentId' => $documentId
        ]);

        // Récupération des informations pour la notification
        $getDemandeInfo = $pdo->prepare("
            SELECT d.DemandeID, d.UtilisateurID, doc.TypeDocument
            FROM documents doc
            JOIN demandes d ON doc.DemandeID = d.DemandeID
            WHERE doc.DocumentID = :documentId
        ");
        $getDemandeInfo->execute([':documentId' => $documentId]);
        $demandeInfo = $getDemandeInfo->fetch(PDO::FETCH_ASSOC);

        // Création d'une notification pour l'utilisateur
        $message = ($action === 'valider')
            ? "Votre document {$demandeInfo['TypeDocument']} a été validé."
            : "Votre document {$demandeInfo['TypeDocument']} a été rejeté. Motif: $commentaire";

        $insertNotification = $pdo->prepare("
            INSERT INTO notifications (UtilisateurID, DemandeID, Contenu, TypeNotification)
            VALUES (:utilisateurId, :demandeId, :message, :type)
        ");
        $insertNotification->execute([
            ':utilisateurId' => $demandeInfo['UtilisateurID'],
            ':demandeId' => $demandeInfo['DemandeID'],
            ':message' => $message,
            ':type' => ($action === 'valider') ? 'ValidationDocument' : 'RejetDocument'
        ]);

        $pdo->commit();

        $response['success'] = true;
        $response['message'] = "Le document a été " . ($action === 'valider' ? "validé" : "rejeté") . " avec succès.";
        $response['nouveauStatut'] = $nouveauStatut;
        $response['badgeClass'] = ($action === 'valider' ? 'bg-success' : 'bg-danger');

    } catch (Exception $e) {
        $pdo->rollBack();
        $response['message'] = "Une erreur est survenue : " . $e->getMessage();
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documents de la demande #<?php echo $demandeId; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .document-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .document-header {
            background-color: #f8f9fa;
            padding: 15px;
            border-bottom: 1px solid #ddd;
            border-radius: 8px 8px 0 0;
        }
        .document-body {
            padding: 15px;
        }
        .document-preview {
            max-width: 100%;
            height: auto;
            margin: 10px 0;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Documents de la demande #<?php echo $demandeId; ?></h2>
        <a href="?page=<?php echo base64_encode('pagesofficier/traitement_demandes'); ?>" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left me-2"></i>Retour au traitement
        </a>
    </div>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['flash_type']; ?> alert-dismissible fade show" role="alert">
            <?php
            echo $_SESSION['flash_message'];
            unset($_SESSION['flash_message']);
            unset($_SESSION['flash_type']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php foreach ($documents as $document): ?>
        <div class="document-card" data-document-id="<?php echo $document['DocumentID']; ?>">
            <div class="document-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5><?php echo displaySafe($document['TypeDocument']); ?></h5>
                    <span class="status-badge bg-<?php
                    echo match($document['StatutValidation']) {
                        'Approuve' => 'success',
                        'Rejete' => 'danger',
                        default => 'warning'
                    };
                    ?>">
                            <?php echo displaySafe($document['StatutValidation']); ?>
                        </span>
                </div>
                <small>Téléchargé le: <?php echo date('d/m/Y H:i', strtotime($document['DateTelechargement'])); ?></small>
            </div>
            <div class="document-body">
                <?php
                $extension = pathinfo($document['CheminFichier'], PATHINFO_EXTENSION);
                if (in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'gif'])):
                    ?>
                    <img src="<?php echo displaySafe($document['CheminFichier']); ?>"
                         class="document-preview"
                         alt="<?php echo displaySafe($document['TypeDocument']); ?>">
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="bi bi-file-earmark-text"></i>
                        Document <?php echo strtoupper($extension); ?>
                    </div>
                <?php endif; ?>

                <div class="action-buttons mt-3">
                    <a href="<?php echo displaySafe($document['CheminFichier']); ?>"
                       class="btn btn-primary"
                       target="_blank">
                        <i class="bi bi-download"></i> Télécharger
                    </a>

                    <?php if ($document['StatutValidation'] === 'EnAttente'): ?>
                        <button class="btn btn-success" onclick="validerDocument(<?php echo $document['DocumentID']; ?>)">
                            <i class="bi bi-check-circle"></i> Valider
                        </button>
                        <button class="btn btn-danger" onclick="rejeterDocument(<?php echo $document['DocumentID']; ?>)">
                            <i class="bi bi-x-circle"></i> Rejeter
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function traiterDocument(documentId, action) {
        let commentaire = '';
        if (action === 'rejeter') {
            commentaire = prompt('Veuillez saisir le motif du rejet :');
            if (commentaire === null) return; // L'utilisateur a annulé
            if (!commentaire.trim()) {
                alert('Le motif du rejet est obligatoire');
                return;
            }
        }

        // Création de l'objet FormData
        const formData = new FormData();
        formData.append('ajax', '1');
        formData.append('action', action);
        formData.append('documentId', documentId);
        if (commentaire) {
            formData.append('commentaire', commentaire);
        }

        // Envoi de la requête AJAX
        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Mise à jour du statut dans l'interface
                    const documentCard = document.querySelector(`[data-document-id="${documentId}"]`);
                    const statusBadge = documentCard.querySelector('.status-badge');
                    const actionButtons = documentCard.querySelector('.action-buttons');

                    // Mise à jour du badge de statut
                    statusBadge.className = `status-badge ${data.badgeClass}`;
                    statusBadge.textContent = data.nouveauStatut;

                    // Suppression des boutons d'action
                    if (actionButtons) {
                        actionButtons.remove();
                    }

                    // Affichage du message de succès
                    const alertHtml = `
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        ${data.message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `;
                    document.querySelector('.container').insertAdjacentHTML('afterbegin', alertHtml);
                } else {
                    alert(data.message || "Une erreur est survenue lors du traitement du document.");
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert("Une erreur est survenue lors du traitement du document.");
            });
    }

    // Fonctions de validation et de rejet
    function validerDocument(documentId) {
        if (confirm('Êtes-vous sûr de vouloir valider ce document ?')) {
            traiterDocument(documentId, 'valider');
        }
    }

    function rejeterDocument(documentId) {
        if (confirm('Êtes-vous sûr de vouloir rejeter ce document ?')) {
            traiterDocument(documentId, 'rejeter');
        }
    }
</script>
</body>
</html>