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
    
    if ($_GET['action'] === 'approve') {
        if (approveRequest($pdo, $requestId)) {
            redirectTo('pagesadmin/gestion_demandes', ['status' => 'approved']);
        } else {
            $error = "Erreur lors de l'approbation de la demande.";
        }
    } elseif ($_GET['action'] === 'reject') {
        if (rejectRequest($pdo, $requestId)) {
            redirectTo('pagesadmin/gestion_demandes', ['status' => 'rejected']);
        } else {
            $error = "Erreur lors du rejet de la demande.";
        }
    }
}

// Récupération de toutes les demandes
$query = "SELECT DemandeID, UtilisateurID, TypeDemande, SousTypeDemande, Statut, DateSoumission, DateAchevement 
          FROM demandes ORDER BY DateSoumission DESC";
$stmt = $pdo->query($query);
$demandes = $stmt->fetchAll();
?>

    <div class="card">
        <div class="card-header">
            Gestion des Demandes
        </div>
        <div class="card-body">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if (isset($_GET['status'])): ?>
    <div class="alert <?= $_GET['status'] == 'approved' ? 'alert-success' : 'alert-info' ?>" 
         style="transition: opacity 0.5s ease-in-out; opacity: 1; 
                background-color: <?= $_GET['status'] == 'approved' ? '#d4edda' : '#cce5ff' ?>; 
                color: <?= $_GET['status'] == 'approved' ? '#155724' : '#004085' ?>; 
                padding: 15px; border-radius: 4px; margin-bottom: 20px;">
        <?php if ($_GET['status'] == 'approved'): ?>
            La demande a été approuvée avec succès.
        <?php elseif ($_GET['status'] == 'rejected'): ?>
            La demande a été rejetée avec succès.
        <?php endif; ?>
    </div>
<?php endif; ?>
            <?php if (!empty($demandes)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Utilisateur ID</th>
                            <th>Type de Demande</th>
                            <th>Sous-Type</th>
                            <th>Statut</th>
                            <th>Date de Soumission</th>
                            <th>Date d'Achèvement</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($demandes as $demande): ?>
                            <tr>
                                <td><?= htmlspecialchars($demande['DemandeID'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($demande['UtilisateurID'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($demande['TypeDemande'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($demande['SousTypeDemande'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($demande['Statut'] ?? 'En attente') ?></td>
                                <td><?= htmlspecialchars($demande['DateSoumission'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($demande['DateAchevement'] ?? 'Non achevé') ?></td>
                                <td>
                                    <a href="?page=<?= base64_encode('pagesadmin/view_request') ?>&id=<?= $demande['DemandeID'] ?>" 
                                       class="btn btn-info">
                                        Voir
                                    </a>
                                    <?php if ($demande['Statut'] !== 'Approuvee' && $demande['Statut'] !== 'Rejetee'): ?>
                                        <a href="?page=<?= base64_encode('pagesadmin/gestion_demandes') ?>&action=approve&id=<?= $demande['DemandeID'] ?>" 
                                           class="btn btn-success"
                                           onclick="return confirm('Êtes-vous sûr de vouloir approuver cette demande ?');">
                                            Approuver
                                        </a>
                                        <a href="?page=<?= base64_encode('pagesadmin/gestion_demandes') ?>&action=reject&id=<?= $demande['DemandeID'] ?>" 
                                           class="btn btn-danger"
                                           onclick="return confirm('Êtes-vous sûr de vouloir rejeter cette demande ?');">
                                            Rejeter
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="alert">Aucune demande n'a été trouvée.</div>
            <?php endif; ?>
        </div>
    </div>