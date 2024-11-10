<?php
global $pdo;
require "../../config/database.php";

// Récupération des utilisateurs (citoyens)
$sql = "SELECT UtilisateurID, Codeutilisateur, Nom, Prenom, Email, NumeroTelephone, DateCreation, IsActive 
        FROM utilisateurs 
        WHERE RoleId = 2 
        ORDER BY DateCreation DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$utilisateurs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fonction pour activer/désactiver un utilisateur
if (isset($_POST['toggle_active'])) {
    $userId = $_POST['user_id'];
    $newStatus = $_POST['new_status'];
    $updateSql = "UPDATE utilisateurs SET IsActive = ? WHERE UtilisateurID = ?";
    $updateStmt = $pdo->prepare($updateSql);
    $updateStmt->execute([$newStatus, $userId]);
    header("Location: ?page=<?php echo base64_encode('pagespresident/gestion_utilisateurs')?>");
    exit();
}
?>
<div class="container mt-5">
    <h1>Gestion des utilisateurs</h1>
    <table class="table table-striped">
        <thead>
        <tr>
            <th>ID</th>
            <th>Code utilisateur</th>
            <th>Nom</th>
            <th>Prénom</th>
            <th>Email</th>
            <th>Téléphone</th>
            <th>Date d'inscription</th>
            <!--<th>Statut</th>
            <th>Actions</th>-->
        </tr>
        </thead>
        <tbody>
        <?php foreach ($utilisateurs as $utilisateur): ?>
            <tr>
                <td><?= htmlspecialchars($utilisateur['UtilisateurID']) ?></td>
                <td><?= htmlspecialchars($utilisateur['Codeutilisateur']) ?></td>
                <td><?= htmlspecialchars($utilisateur['Nom']) ?></td>
                <td><?= htmlspecialchars($utilisateur['Prenom']) ?></td>
                <td><?= htmlspecialchars($utilisateur['Email']) ?></td>
                <td><?= htmlspecialchars($utilisateur['NumeroTelephone']) ?></td>
                <td><?= htmlspecialchars($utilisateur['DateCreation']) ?></td>
                <!--<td><?= $utilisateur['IsActive'] ? 'Actif' : 'Inactif' ?></td>
                <td>
                    <form method="POST">
                        <input type="hidden" name="user_id" value="<?= $utilisateur['UtilisateurID'] ?>">
                        <input type="hidden" name="new_status" value="<?= $utilisateur['IsActive'] ? '0' : '1' ?>">
                        <button type="submit" name="toggle_active" class="btn btn-<?= $utilisateur['IsActive'] ? 'danger' : 'success' ?> btn-sm">
                            <?= $utilisateur['IsActive'] ? 'Désactiver' : 'Activer' ?>
                        </button>
                    </form>
                </td>-->
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>