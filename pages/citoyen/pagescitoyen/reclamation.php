<?php
global $pdo;
require_once '../../config/database.php';

$userId = $_SESSION['user']['UtilisateurID'];
$successMessage = $errorMessage = "";

try {
    // Récupérer les demandes de l'utilisateur pour le menu déroulant
    $stmt = $pdo->prepare("SELECT DemandeID, TypeDemande FROM demandes WHERE UtilisateurID = ?");
    $stmt->execute([$userId]);
    $demandes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $demandeId = isset($_POST['demande_id']) ? $_POST['demande_id'] : null;
        $typeReclamation = $_POST['type_reclamation'];
        $description = $_POST['description'];

        if (empty($demandeId) || empty($typeReclamation) || empty($description)) {
            $errorMessage = "Tous les champs sont obligatoires.";
        } else {
            // Insérer la réclamation dans la base de données
            $stmt = $pdo->prepare("INSERT INTO reclamations (UtilisateurID, DemandeID, TypeReclamation, Description) 
                                  VALUES (?, ?, ?, ?)");

            if ($stmt->execute([$userId, $demandeId, $typeReclamation, $description])) {
                $successMessage = "Votre réclamation a été enregistrée avec succès.";
            } else {
                $errorMessage = "Une erreur est survenue lors de l'enregistrement de votre réclamation.";
            }
        }
    }

    // Récupérer les réclamations existantes de l'utilisateur
    $stmt = $pdo->prepare("SELECT r.*, d.TypeDemande 
                           FROM reclamations r 
                           LEFT JOIN demandes d ON r.DemandeID = d.DemandeID 
                           WHERE r.UtilisateurID = ? 
                           ORDER BY r.DateCreation DESC");
    $stmt->execute([$userId]);
    $reclamations = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $errorMessage = "Erreur de base de données : " . $e->getMessage();
}
?>
    <style>
        .reclamation-form {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .reclamation-history {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .alert-success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }
        .alert-danger {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
    </style>
<div class="container">
    <div class="card-header text-center bg-primary text-white">
    <h2 class="mb-4">Soumettre une réclamation</h2>
    </div>
    <div class="card-body">
    <?php if ($successMessage): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($successMessage); ?></div>
    <?php endif; ?>

    <?php if ($errorMessage): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($errorMessage); ?></div>
    <?php endif; ?>

    <div class="reclamation-form">
        <form method="POST" action="">
            <div class="mb-3">
                <label for="demande_id" class="form-label">Demande concernée</label>
                <select class="form-control" id="demande_id" name="demande_id" required>
                    <option value="">Sélectionnez une demande</option>
                    <?php foreach ($demandes as $demande): ?>
                        <option value="<?php echo htmlspecialchars($demande['DemandeID']); ?>">
                            Demande #<?php echo htmlspecialchars($demande['DemandeID']); ?> -
                            <?php echo htmlspecialchars($demande['TypeDemande']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="type_reclamation" class="form-label">Type de réclamation</label>
                <select class="form-control" id="type_reclamation" name="type_reclamation" required>
                    <option value="">Sélectionnez un type</option>
                    <option value="Delai">Délai de traitement</option>
                    <option value="Document">Problème de document</option>
                    <option value="Information">Demande d'information</option>
                    <option value="Autre">Autre</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description de la réclamation</label>
                <textarea class="form-control" id="description" name="description" rows="4" required></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Soumettre la réclamation</button>
        </form>
    </div>
        <div class="card-header text-center bg-primary text-white">
    <h3 class="mb-4">Historique des réclamations</h3>
        </div>
        <div class="card-body">
    <div class="reclamation-history">
        <?php if (empty($reclamations)): ?>
            <p>Vous n'avez pas encore soumis de réclamation.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                    <tr>
                        <th>Date</th>
                        <th>Demande</th>
                        <th>Type</th>
                        <th>Description</th>
                        <th>Statut</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($reclamations as $reclamation): ?>
                        <tr>
                            <td><?php echo date('d/m/Y H:i', strtotime($reclamation['DateCreation'])); ?></td>
                            <td>
                                Demande #<?php echo htmlspecialchars($reclamation['DemandeID']); ?> -
                                <?php echo htmlspecialchars($reclamation['TypeDemande']); ?>
                            </td>
                            <td><?php echo htmlspecialchars($reclamation['TypeReclamation']); ?></td>
                            <td><?php echo htmlspecialchars($reclamation['Description']); ?></td>
                            <td ><?php echo htmlspecialchars($reclamation['Statut']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>