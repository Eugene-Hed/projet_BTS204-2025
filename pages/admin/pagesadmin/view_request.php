<?php
global $pdo;
require "../../config/database.php";

// Fonctions d'approbation et de rejet
function approveRequest($pdo, $requestId) {
    try {
        $query = "UPDATE demandes SET Statut = 'Approuvee' WHERE DemandeID = :id";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['id' => $requestId]);
        
        if ($stmt->rowCount() > 0) {
            return true;
        }
        return false;
    } catch (PDOException $e) {
        error_log("Erreur d'approbation de la demande : " . $e->getMessage());
        return false;
    }
}

function rejectRequest($pdo, $requestId) {
    try {
        $query = "UPDATE demandes SET Statut = 'Rejetee' WHERE DemandeID = :id";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['id' => $requestId]);
        
        if ($stmt->rowCount() > 0) {
            return true;
        }
        return false;
    } catch (PDOException $e) {
        error_log("Erreur de rejet de la demande : " . $e->getMessage());
        return false;
    }
}

// Traitement des actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $requestId = intval($_GET['id']);
    
    if (!$demande) {
        redirectTo('pagesadmin/gestion_demandes', ['error' => 'demande_not_found']);
    }

    if ($_GET['action'] === 'approve') {
        if (approveRequest($pdo, $requestId)) {
            redirectTo('pagesadmin/gestion_demandes', ['status' => 'approved']);
        }
    } elseif ($_GET['action'] === 'reject') {
        if (rejectRequest($pdo, $requestId)) {
            redirectTo('pagesadmin/gestion_demandes', ['status' => 'rejected']);
        }
    }
}

// Récupération de l'ID de la demande
$demandeID = intval($_GET['id']);

// Récupération des détails de la demande
$query = "SELECT DemandeID, UtilisateurID, TypeDemande, SousTypeDemande, Statut, DateSoumission, DateAchevement 
          FROM demandes WHERE DemandeID = :demandeID";
$stmt = $pdo->prepare($query);
$stmt->bindParam(':demandeID', $demandeID, PDO::PARAM_INT);
$stmt->execute();
$demande = $stmt->fetch();

if (!$demande) {
    header("Location: ?page=" . base64_encode('pagesadmin/gestion_demandes') . "&error=demande_not_found");
    exit;
}
?>

    <div class="card">
        <div class="card-header">
            Détails de la Demande #<?= htmlspecialchars($demande['DemandeID'] ?? ''); ?>
        </div>
        <div class="card-body">
        <?php if (isset($error)): ?>
    <div class="alert alert-danger" style="transition: opacity 0.5s ease-in-out; opacity: 1; background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
        <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

            <table>
                <tr>
                    <th>ID de la Demande</th>
                    <td><?= htmlspecialchars($demande['DemandeID'] ?? 'Non spécifié') ?></td>
                </tr>
                <tr>
                    <th>ID de l'Utilisateur</th>
                    <td><?= htmlspecialchars($demande['UtilisateurID'] ?? 'Non spécifié') ?></td>
                </tr>
                <tr>
                    <th>Type de Demande</th>
                    <td><?= htmlspecialchars($demande['TypeDemande'] ?? 'Non spécifié') ?></td>
                </tr>
                <tr>
                    <th>Sous-Type</th>
                    <td><?= htmlspecialchars($demande['SousTypeDemande'] ?? 'Non spécifié') ?></td>
                </tr>
                <tr>
                    <th>Statut</th>
                    <td><?= htmlspecialchars($demande['Statut'] ?? 'Non spécifié') ?></td>
                </tr>
                <tr>
                    <th>Date de Soumission</th>
                    <td><?= htmlspecialchars($demande['DateSoumission'] ?? 'Non spécifié') ?></td>
                </tr>
                <tr>
                    <th>Date d'Achèvement</th>
                    <td><?= htmlspecialchars($demande['DateAchevement'] ?? 'Non spécifié') ?></td>
                </tr>
            </table>

            <div class="form-group">
                <?php if ($demande['Statut'] !== 'Approuvee' && $demande['Statut'] !== 'Rejetee'): ?>
                    <a href="?page=<?= base64_encode('pagesadmin/view_request') ?>&id=<?= $demande['DemandeID'] ?>&action=approve" 
                       class="btn btn-success" 
                       onclick="return confirm('Êtes-vous sûr de vouloir approuver cette demande ?');">
                        Approuver
                    </a>
                    <a href="?page=<?= base64_encode('pagesadmin/view_request') ?>&id=<?= $demande['DemandeID'] ?>&action=reject" 
                       class="btn btn-danger" 
                       onclick="return confirm('Êtes-vous sûr de vouloir rejeter cette demande ?');">
                        Rejeter
                    </a>
                <?php endif; ?>
                <a href="?page=<?= base64_encode('pagesadmin/gestion_demandes') ?>" class="btn btn-info">
                    Retour à la liste
                </a>
            </div>
        </div>
    </div>