<?php
global $pdo;
require "../../config/database.php";

// Get complaint details with user and request information
$reclamationId = intval($_GET['id']);
$query = "SELECT r.*, u.Nom, u.Prenom, u.Email, u.NumeroTelephone, 
          d.TypeDemande, d.SousTypeDemande, d.Statut as StatutDemande
          FROM reclamations r
          LEFT JOIN utilisateurs u ON r.UtilisateurID = u.UtilisateurID
          LEFT JOIN demandes d ON r.DemandeID = d.DemandeID
          WHERE r.ReclamationID = :id";
$stmt = $pdo->prepare($query);
$stmt->execute(['id' => $reclamationId]);
$reclamation = $stmt->fetch();

if (!$reclamation) {
    redirectTo('pagesadmin/gestion_reclamations', ['error' => 'reclamation_not_found']);
}

// Handle status update
if (isset($_GET['action'])) {
    $newStatus = $_GET['action'];
    $updateQuery = "UPDATE reclamations SET Statut = :status WHERE ReclamationID = :id";
    $updateStmt = $pdo->prepare($updateQuery);
    $updateStmt->execute(['status' => $newStatus, 'id' => $reclamationId]);
    redirectTo('pagesadmin/view_reclamation', ['id' => $reclamationId, 'status' => 'updated']);
}
?>

<div class="main-content">
    <div class="card">
        <div class="card-header">
            Détails de la Réclamation #<?= htmlspecialchars($reclamation['ReclamationID']) ?>
        </div>
        <div class="card-body">
            <?php if (isset($_GET['status']) && $_GET['status'] == 'updated'): ?>
                <div class="alert alert-success" style="transition: opacity 0.5s ease-in-out; opacity: 1; background-color: #d4edda; color: #155724; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
                    Le statut de la réclamation a été mis à jour avec succès.
                </div>
            <?php endif; ?>

            <div class="form-group">
                <h4>Informations de la Réclamation</h4>
                <table>
                    <tr>
                        <th>Type de Réclamation</th>
                        <td><?= htmlspecialchars($reclamation['TypeReclamation']) ?></td>
                    </tr>
                    <tr>
                        <th>Description</th>
                        <td><?= htmlspecialchars($reclamation['Description']) ?></td>
                    </tr>
                    <tr>
                        <th>Statut</th>
                        <td>
                            <span class="badge <?= $reclamation['Statut'] === 'Ouverte' ? 'badge-danger' : 
                                ($reclamation['Statut'] === 'EnCours' ? 'badge-info' : 'badge-success') ?>">
                                <?= htmlspecialchars($reclamation['Statut']) ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th>Date de Création</th>
                        <td><?= htmlspecialchars($reclamation['DateCreation']) ?></td>
                    </tr>
                </table>
            </div>

            <div class="form-group mt-4">
                <h4>Informations de l'Utilisateur</h4>
                <table>
                    <tr>
                        <th>Nom Complet</th>
                        <td><?= htmlspecialchars($reclamation['Nom'] . ' ' . $reclamation['Prenom']) ?></td>
                    </tr>
                    <tr>
                        <th>Email</th>
                        <td><?= htmlspecialchars($reclamation['Email']) ?></td>
                    </tr>
                    <tr>
                        <th>Téléphone</th>
                        <td><?= htmlspecialchars($reclamation['NumeroTelephone']) ?></td>
                    </tr>
                </table>
            </div>

            <?php if ($reclamation['DemandeID']): ?>
            <div class="form-group mt-4">
                <h4>Demande Associée</h4>
                <table>
                    <tr>
                        <th>Type de Demande</th>
                        <td><?= htmlspecialchars($reclamation['TypeDemande']) ?></td>
                    </tr>
                    <tr>
                        <th>Sous-Type</th>
                        <td><?= htmlspecialchars($reclamation['SousTypeDemande']) ?></td>
                    </tr>
                    <tr>
                        <th>Statut de la Demande</th>
                        <td><?= htmlspecialchars($reclamation['StatutDemande']) ?></td>
                    </tr>
                </table>
            </div>
            <?php endif; ?>

            <div class="form-group mt-4">
                <?php if ($reclamation['Statut'] !== 'Fermee'): ?>
                    <?php if ($reclamation['Statut'] === 'Ouverte'): ?>
                        <a href="?page=<?= base64_encode('pagesadmin/view_reclamation') ?>&id=<?= $reclamation['ReclamationID'] ?>&action=EnCours" 
                           class="btn btn-info"
                           onclick="return confirm('Commencer le traitement de cette réclamation ?');">
                            Traiter
                        </a>
                    <?php endif; ?>
                    
                    <a href="?page=<?= base64_encode('pagesadmin/view_reclamation') ?>&id=<?= $reclamation['ReclamationID'] ?>&action=Fermee" 
                       class="btn btn-success"
                       onclick="return confirm('Clôturer cette réclamation ?');">
                        Clôturer
                    </a>
                <?php endif; ?>
                
                <a href="?page=<?= base64_encode('pagesadmin/gestion_reclamations') ?>" class="btn btn-info">
                    Retour à la liste
                </a>
            </div>
        </div>
    </div>
</div>
