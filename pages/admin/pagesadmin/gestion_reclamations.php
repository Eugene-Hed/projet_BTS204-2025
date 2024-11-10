<?php
global $pdo;
require "../../config/database.php";

// Update complaint status
if (isset($_GET['action']) && isset($_GET['id'])) {
    $reclamationId = intval($_GET['id']);
    $newStatus = $_GET['action'];
    
    $updateQuery = "UPDATE reclamations SET Statut = :status WHERE ReclamationID = :id";
    $updateStmt = $pdo->prepare($updateQuery);
    $updateStmt->execute(['status' => $newStatus, 'id' => $reclamationId]);
    
    redirectTo('pagesadmin/gestion_reclamations', ['status' => 'updated']);
    exit;
}

// Get all complaints with user details
$query = "SELECT r.*, u.Nom, u.Prenom, d.TypeDemande 
          FROM reclamations r
          LEFT JOIN utilisateurs u ON r.UtilisateurID = u.UtilisateurID
          LEFT JOIN demandes d ON r.DemandeID = d.DemandeID
          ORDER BY r.DateCreation DESC";
$stmt = $pdo->query($query);
$reclamations = $stmt->fetchAll();
?>
    <div class="card">
        <div class="card-header">
            Gestion des Réclamations
        </div>
        <div class="card-body">
            <?php if (isset($_GET['status']) && $_GET['status'] == 'updated'): ?>
                <div class="alert alert-success" style="transition: opacity 0.5s ease-in-out; opacity: 1; background-color: #d4edda; color: #155724; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
                    Le statut de la réclamation a été mis à jour avec succès.
                </div>
            <?php endif; ?>

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Utilisateur</th>
                        <th>Type</th>
                        <th>Demande Associée</th>
                        <th>Description</th>
                        <th>Statut</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reclamations as $reclamation): ?>
                        <tr>
                            <td><?= htmlspecialchars($reclamation['ReclamationID']) ?></td>
                            <td><?= htmlspecialchars($reclamation['Nom'] . ' ' . $reclamation['Prenom']) ?></td>
                            <td><?= htmlspecialchars($reclamation['TypeReclamation']) ?></td>
                            <td><?= htmlspecialchars($reclamation['TypeDemande'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($reclamation['Description']) ?></td>
                            <td>
                                <span class="badge <?= $reclamation['Statut'] === 'Ouverte' ? 'badge-danger' : 
                                    ($reclamation['Statut'] === 'EnCours' ? 'badge-info' : 'badge-success') ?>">
                                    <?= htmlspecialchars($reclamation['Statut']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($reclamation['DateCreation']) ?></td>
                            <td>
                                <?php if ($reclamation['Statut'] !== 'Fermee'): ?>
                                    <?php if ($reclamation['Statut'] === 'Ouverte'): ?>
                                        <a href="?page=<?= base64_encode('pagesadmin/gestion_reclamations') ?>&action=EnCours&id=<?= $reclamation['ReclamationID'] ?>" 
                                           class="btn btn-info"
                                           onclick="return confirm('Commencer le traitement de cette réclamation ?');">
                                            Traiter
                                        </a>
                                    <?php endif; ?>
                                    
                                    <a href="?page=<?= base64_encode('pagesadmin/gestion_reclamations') ?>&action=Fermee&id=<?= $reclamation['ReclamationID'] ?>" 
                                       class="btn btn-success"
                                       onclick="return confirm('Clôturer cette réclamation ?');">
                                        Clôturer
                                    </a>
                                <?php endif; ?>
                                
                                <a href="?page=<?= base64_encode('pagesadmin/view_reclamation') ?>&id=<?= $reclamation['ReclamationID'] ?>" 
                                   class="btn btn-info">
                                    Détails
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php if (empty($reclamations)): ?>
                <div class="alert">Aucune réclamation n'a été trouvée.</div>
            <?php endif; ?>
        </div>
    </div>