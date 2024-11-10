<?php
global $pdo;
require "../../config/database.php";

// Récupérer toutes les réclamations
$stmt = $pdo->query("
    SELECT r.*, u.Nom, u.Prenom
    FROM reclamations r
    JOIN utilisateurs u ON r.UtilisateurID = u.UtilisateurID
    ORDER BY r.DateCreation DESC
");
$reclamations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Traiter la réponse à une réclamation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['id'])) {
        $id = $_POST['id'];
        $reponse = $_POST['reponse'] ?? '';

        $stmt = $pdo->prepare("
            UPDATE reclamations 
            SET Statut = 'Fermee', 
                DateMiseAJour = CURRENT_TIMESTAMP,
                Description = CONCAT(Description, '\n\nRéponse: ', ?)
            WHERE ReclamationID = ?
        ");
        $stmt->execute([$reponse, $id]);

        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}
?>
<div class="container mt-4">
    <h1>Gestion des Réclamations</h1>

    <div class="row">
        <?php foreach ($reclamations as $reclamation): ?>
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title"><?php echo htmlspecialchars($reclamation['TypeReclamation']); ?></h5>
                        <h6 class="card-subtitle mb-2 text-muted">
                            Par <?php echo htmlspecialchars($reclamation['Nom'] . ' ' . $reclamation['Prenom']); ?>
                            le <?php echo htmlspecialchars($reclamation['DateCreation']); ?>
                        </h6>
                    </div>
                    <div class="card-body">
                        <p class="card-text"><?php echo nl2br(htmlspecialchars($reclamation['Description'])); ?></p>

                        <?php if ($reclamation['Statut'] !== 'Fermee'): ?>
                            <form method="post">
                                <input type="hidden" name="id" value="<?php echo $reclamation['ReclamationID']; ?>">
                                <div class="form-group">
                                    <label for="reponse">Réponse :</label>
                                    <textarea class="form-control" name="reponse" rows="3" required></textarea>
                                </div>
                                <button type="submit" name="action" value="repondre" class="btn btn-primary">Répondre</button>
                            </form>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer">
                        Statut : <span class="badge badge-<?php echo $reclamation['Statut'] === 'Fermee' ? 'success' : 'warning'; ?>">
                        <?php echo htmlspecialchars($reclamation['Statut']); ?>
                    </span>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>