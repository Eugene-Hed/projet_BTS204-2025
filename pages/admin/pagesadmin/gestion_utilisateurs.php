<?php
global $pdo;
require "../../config/database.php";

// Récupération de tous les utilisateurs avec leur rôle
$query = "SELECT u.*, r.role as role_name 
          FROM utilisateurs u 
          LEFT JOIN role r ON u.RoleId = r.id 
          ORDER BY u.DateCreation DESC";
$stmt = $pdo->query($query);
$utilisateurs = $stmt->fetchAll();

// Fonction pour activer/désactiver un utilisateur
if (isset($_GET['action']) && isset($_GET['id'])) {
    $userId = intval($_GET['id']);
    $newStatus = ($_GET['action'] === 'activate') ? 1 : 0;
    
    $updateQuery = "UPDATE utilisateurs SET IsActive = :status WHERE UtilisateurID = :id";
    $updateStmt = $pdo->prepare($updateQuery);
    $updateStmt->execute(['status' => $newStatus, 'id' => $userId]);
    
    redirectTo('pagesadmin/gestion_utilisateurs', ['status' => 'updated']);
    exit;
}
?>
    <div class="card">
        <div class="card-header">
            Gestion des Utilisateurs
        </div>
        <div class="card-body">
            <?php if (isset($_GET['status']) && $_GET['status'] == 'updated'): ?>
                <div class="alert alert-success" style="transition: opacity 0.5s ease-in-out; opacity: 1; background-color: #d4edda; color: #155724; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
                    Le statut de l'utilisateur a été mis à jour avec succès.
                </div>
            <?php endif; ?>

            <table>
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Nom</th>
                        <th>Prénom</th>
                        <th>Email</th>
                        <th>Téléphone</th>
                        <th>Rôle</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($utilisateurs as $user): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['Codeutilisateur']) ?></td>
                            <td><?= htmlspecialchars($user['Nom']) ?></td>
                            <td><?= htmlspecialchars($user['Prenom']) ?></td>
                            <td><?= htmlspecialchars($user['Email']) ?></td>
                            <td><?= htmlspecialchars($user['NumeroTelephone']) ?></td>
                            <td>
                                <span class="badge badge-info">
                                    <?= htmlspecialchars($user['role_name']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($user['IsActive']): ?>
                                    <span class="badge badge-success">Actif</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Inactif</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($user['IsActive']): ?>
                                    <a href="?page=<?= base64_encode('pagesadmin/gestion_utilisateurs') ?>&action=deactivate&id=<?= $user['UtilisateurID'] ?>" 
                                       class="btn btn-danger"
                                       onclick="return confirm('Êtes-vous sûr de vouloir désactiver cet utilisateur ?');">
                                        Désactiver
                                    </a>
                                <?php else: ?>
                                    <a href="?page=<?= base64_encode('pagesadmin/gestion_utilisateurs') ?>&action=activate&id=<?= $user['UtilisateurID'] ?>" 
                                       class="btn btn-success"
                                       onclick="return confirm('Êtes-vous sûr de vouloir activer cet utilisateur ?');">
                                        Activer
                                    </a>
                                <?php endif; ?>
                                <a href="?page=<?= base64_encode('pagesadmin/edit_user') ?>&id=<?= $user['UtilisateurID'] ?>" 
                                   class="btn btn-info">
                                    Modifier
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>