<?php
global $pdo;
require "../../config/database.php";

// Fonction pour sécuriser l'affichage
function displaySafe($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

// Fonction pour gérer les erreurs de manière uniforme
function handleError($message) {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = 'danger';
    return false;
}

// Fonction pour traiter une demande
function traiterDemande($demandeId, $action, $commentaire, $officierID) {
    global $pdo;

    try {
        $pdo->beginTransaction();

        // Vérifier si la demande existe et est toujours en statut 'Soumise'
        $verificationDemande = $pdo->prepare("
            SELECT d.*, u.UtilisateurID 
            FROM demandes d
            JOIN utilisateurs u ON d.UtilisateurID = u.UtilisateurID
            WHERE d.DemandeID = :demandeId AND d.Statut = 'Soumise'
        ");
        $verificationDemande->execute([':demandeId' => $demandeId]);
        $demande = $verificationDemande->fetch(PDO::FETCH_ASSOC);

        if (!$demande) {
            throw new Exception("La demande n'existe pas ou a déjà été traitée.");
        }

        // Définir le nouveau statut
        $newStatus = ($action === 'approuver') ? 'EnCours' : 'Rejetee';

        // Mettre à jour le statut de la demande
        $updateDemande = $pdo->prepare("
            UPDATE demandes 
            SET Statut = :statut,
                DateAchevement = CURRENT_TIMESTAMP
            WHERE DemandeID = :demandeId
        ");
        $updateDemande->execute([
            ':statut' => $newStatus,
            ':demandeId' => $demandeId
        ]);

        // Enregistrer dans l'historique
        $insertHistorique = $pdo->prepare("
            INSERT INTO historique_demandes 
            (DemandeID, AncienStatut, NouveauStatut, Commentaire, ModifiePar) 
            VALUES (:demandeId, 'Soumise', :nouveauStatut, :commentaire, :officierID)
        ");
        $insertHistorique->execute([
            ':demandeId' => $demandeId,
            ':nouveauStatut' => $newStatus,
            ':commentaire' => $commentaire,
            ':officierID' => $officierID
        ]);

        // Créer une notification
        $message = ($action === 'approuver')
            ? "Votre demande de CNI a été approuvée et est en cours de traitement."
            : "Votre demande de CNI a été rejetée. Motif : " . $commentaire;

        $insertNotification = $pdo->prepare("
            INSERT INTO notifications 
            (UtilisateurID, DemandeID, Contenu, TypeNotification) 
            VALUES (:utilisateurId, :demandeId, :message, :type)
        ");
        $insertNotification->execute([
            ':utilisateurId' => $demande['UtilisateurID'],
            ':demandeId' => $demandeId,
            ':message' => $message,
            ':type' => ($action === 'approuver') ? 'Approbation' : 'Rejet'
        ]);

        $pdo->commit();
        return true;

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

// Récupération des demandes CNI en attente
$query = $pdo->prepare("
    SELECT 
        d.DemandeID,
        d.DateSoumission,
        d.Statut,
        u.Nom,
        u.Prenom,
        MAX(dc.DetailID) as DetailID,
        MAX(dc.DateNaissance) as DateNaissance,
        MAX(dc.LieuNaissance) as LieuNaissance,
        MAX(dc.Profession) as Profession,
        GROUP_CONCAT(DISTINCT doc.TypeDocument) as Documents
    FROM demandes d
    JOIN utilisateurs u ON d.UtilisateurID = u.UtilisateurID
    JOIN demande_cni_details dc ON d.DemandeID = dc.DemandeID
    LEFT JOIN documents doc ON d.DemandeID = doc.DemandeID
    WHERE d.TypeDemande = 'CNI' 
    AND d.Statut = 'Soumise'
    GROUP BY d.DemandeID, d.DateSoumission, d.Statut, u.Nom, u.Prenom
    ORDER BY d.DateSoumission DESC
");
$query->execute();
$demandes = $query->fetchAll(PDO::FETCH_ASSOC);

// Traitement des actions (Approuver/Rejeter)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['demandeId'])) {
    try {
        $demandeId = filter_var($_POST['demandeId'], FILTER_VALIDATE_INT);
        $action = $_POST['action'];
        $commentaire = trim($_POST['commentaire'] ?? '');
        $officierID = $_SESSION['user']['UtilisateurID'] ?? null;

        if (!$demandeId || !$officierID) {
            throw new Exception("Données invalides pour le traitement de la demande.");
        }

        if ($action === 'rejeter' && empty($commentaire)) {
            throw new Exception("Un commentaire est requis pour rejeter une demande.");
        }

        if (traiterDemande($demandeId, $action, $commentaire, $officierID)) {
            $_SESSION['flash_message'] = "La demande a été " . ($action === 'approuver' ? "approuvée" : "rejetée") . " avec succès.";
            $_SESSION['flash_type'] = 'success';
        }

    } catch (Exception $e) {
        $_SESSION['flash_message'] = "Erreur : " . $e->getMessage();
        $_SESSION['flash_type'] = 'danger';
    }

    // Redirection pour éviter la soumission multiple
    header("Location: " . $_SERVER['PHP_SELF'] . '?page=' . ($_GET['page'] ?? ''));
    exit;
}
?>

<div class="container mt-4">
    <h2 class="mb-4">Traitement des Demandes de CNI</h2>

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

    <?php if (empty($demandes)): ?>
        <div class="alert alert-info">Aucune demande de CNI en attente de traitement.</div>
    <?php else: ?>
        <?php foreach ($demandes as $demande): ?>
            <div class="demande-card">
                <div class="demande-header">
                    <h5>Demande #<?php echo displaySafe($demande['DemandeID']); ?></h5>
                    <small>Soumise le : <?php echo date('d/m/Y H:i', strtotime($demande['DateSoumission'])); ?></small>
                </div>
                <div class="demande-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Informations personnelles</h6>
                            <p>
                                <strong>Nom :</strong> <?php echo displaySafe($demande['Nom']); ?><br>
                                <strong>Prénom :</strong> <?php echo displaySafe($demande['Prenom']); ?><br>
                                <strong>Date de naissance :</strong> <?php echo displaySafe($demande['DateNaissance']); ?><br>
                                <strong>Lieu de naissance :</strong> <?php echo displaySafe($demande['LieuNaissance']); ?><br>
                                <strong>Profession :</strong> <?php echo displaySafe($demande['Profession']); ?>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h6>Documents fournis</h6>
                            <ul class="documents-list">
                                <?php
                                $documents = explode(',', $demande['Documents']);
                                foreach ($documents as $document):
                                    if (!empty(trim($document))):
                                        ?>
                                        <li><i class="fas fa-file-alt"></i> <?php echo displaySafe($document); ?></li>
                                    <?php
                                    endif;
                                endforeach;
                                ?>
                            </ul>
                        </div>
                    </div>

                    <div class="action-buttons">
                        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#approuverModal<?php echo $demande['DemandeID']; ?>">
                            Approuver
                        </button>
                        <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejeterModal<?php echo $demande['DemandeID']; ?>">
                            Rejeter
                        </button>
                        <a href="?page=<?php echo base64_encode('pagesofficier/voir_documents').'&demande='.displaySafe($demande['DemandeID']); ?>" class="btn btn-info">
                            Voir les documents
                        </a>
                    </div>
                </div>
            </div>

            <!-- Modal Approuver -->
            <div class="modal fade" id="approuverModal<?php echo $demande['DemandeID']; ?>" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="approuverModalLabel<?php echo $demande['DemandeID']; ?>" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="approuverModalLabel<?php echo $demande['DemandeID']; ?>">Approuver la demande</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form method="POST">
                            <div class="modal-body">
                                <p>Êtes-vous sûr de vouloir approuver cette demande ?</p>
                                <input type="hidden" name="demandeId" value="<?php echo $demande['DemandeID']; ?>">
                                <input type="hidden" name="action" value="approuver">
                                <div class="mb-3">
                                    <label for="commentaire" class="form-label">Commentaire (optionnel) :</label>
                                    <textarea id="commentaire" name="commentaire" class="form-control"></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                <button type="submit" class="btn btn-success">Confirmer</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Modal Rejeter -->
            <div class="modal fade" id="rejeterModal<?php echo $demande['DemandeID']; ?>" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="rejeterModalLabel<?php echo $demande['DemandeID']; ?>" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="rejeterModalLabel<?php echo $demande['DemandeID']; ?>">Rejeter la demande</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form method="POST">
                            <div class="modal-body">
                                <input type="hidden" name="demandeId" value="<?php echo $demande['DemandeID']; ?>">
                                <input type="hidden" name="action" value="rejeter">
                                <div class="mb-3">
                                    <label for="commentaire" class="form-label">Motif du rejet (obligatoire) :</label>
                                    <textarea id="commentaire" name="commentaire" class="form-control" required></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                <button type="submit" class="btn btn-danger">Confirmer</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<style>
    .demande-card {
        border: 1px solid #ddd;
        border-radius: 8px;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .demande-header {
        background-color: #f8f9fa;
        padding: 15px;
        border-bottom: 1px solid #ddd;
        border-radius: 8px 8px 0 0;
    }
    .demande-body {
        padding: 15px;
    }
    .documents-list {
        list-style: none;
        padding: 0;
    }
    .documents-list li {
        margin-bottom: 5px;
    }
    .action-buttons {
        margin-top: 15px;
    }
</style>