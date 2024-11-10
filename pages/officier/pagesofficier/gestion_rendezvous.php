<?php
global $pdo;
require "../../config/database.php";

// Récupérer tous les rendez-vous
$stmt = $pdo->query("
    SELECT r.*, d.TypeDemande, u.Nom, u.Prenom
    FROM rendezvous r
    JOIN demandes d ON r.DemandeID = d.DemandeID
    JOIN utilisateurs u ON d.UtilisateurID = u.UtilisateurID
    ORDER BY r.DateRendezVous DESC
");
$rendezvous = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Traiter les actions (confirmer/annuler rendez-vous)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['id'])) {
        $id = $_POST['id'];
        $action = $_POST['action'];

        if ($action === 'confirmer') {
            $stmt = $pdo->prepare("UPDATE rendezvous SET Statut = 'Termine' WHERE RendezVousID = ?");
        } elseif ($action === 'annuler') {
            $stmt = $pdo->prepare("UPDATE rendezvous SET Statut = 'Annule' WHERE RendezVousID = ?");
        }

        $stmt->execute([$id]);
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}
?>
<div class="container mt-4">
    <h1>Gestion des Rendez-vous</h1>

    <table class="table">
        <thead>
        <tr>
            <th>Date</th>
            <th>Citoyen</th>
            <th>Type de demande</th>
            <th>Statut</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($rendezvous as $rdv): ?>
            <tr>
                <td><?php echo htmlspecialchars($rdv['DateRendezVous']); ?></td>
                <td><?php echo htmlspecialchars($rdv['Nom'] . ' ' . $rdv['Prenom']); ?></td>
                <td><?php echo htmlspecialchars($rdv['TypeDemande']); ?></td>
                <td><?php echo htmlspecialchars($rdv['Statut']); ?></td>
                <td>
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="id" value="<?php echo $rdv['RendezVousID']; ?>">
                        <?php if ($rdv['Statut'] === 'Planifie'): ?>
                            <button type="submit" name="action" value="confirmer" class="btn btn-success btn-sm">Confirmer</button>
                            <button type="submit" name="action" value="annuler" class="btn btn-danger btn-sm">Annuler</button>
                        <?php endif; ?>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>