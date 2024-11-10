<?php
global $pdo;
require "../../config/database.php";

// Fonction pour approuver une demande
function approuverDemande($demandeId) {
    global $pdo;
    $sql = "UPDATE demandes SET Statut = 'Approuvee' WHERE DemandeID = :demandeId";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['demandeId' => $demandeId]);

    // Ajouter une entrée dans l'historique
    $sqlHistorique = "INSERT INTO historique_demandes (DemandeID, AncienStatut, NouveauStatut, Commentaire) 
                      VALUES (:demandeId, 'EnCours', 'Approuvee', 'Demande approuvée par le président')";
    $stmtHistorique = $pdo->prepare($sqlHistorique);
    $stmtHistorique->execute(['demandeId' => $demandeId]);

    // Ajouter une notification
    //$sqlNotif = "INSERT INTO notifications (UtilisateurID, DemandeID, Contenu, TypeNotification)
      //           SELECT UtilisateurID, :demandeId, 'Votre demande de certificat de nationalité a été approuvée.', 'Approbation'
      //           FROM demandes WHERE DemandeID = :demandeId";
    //$stmtNotif = $pdo->prepare($sqlNotif);
    //$stmtNotif->execute(['demandeId' => $demandeId]);
}

// Fonction pour rejeter une demande
function rejeterDemande($demandeId, $motifRejet) {
    global $pdo;
    $sql = "UPDATE demandes SET Statut = 'Rejetee' WHERE DemandeID = :demandeId";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['demandeId' => $demandeId]);

    // Ajouter une entrée dans l'historique
    $sqlHistorique = "INSERT INTO historique_demandes (DemandeID, AncienStatut, NouveauStatut, Commentaire) 
                      VALUES (:demandeId, 'EnCours', 'Rejetee', :motifRejet)";
    $stmtHistorique = $pdo->prepare($sqlHistorique);
    $stmtHistorique->execute(['demandeId' => $demandeId, 'motifRejet' => $motifRejet]);

    // Ajouter une notification
    //$sqlNotif = "INSERT INTO notifications (UtilisateurID, DemandeID, Contenu, TypeNotification)
        //     SELECT UtilisateurID, :demandeId, 'Votre demande de certificat de nationalité a été approuvée.', 'Approbation'
      //       FROM demandes WHERE DemandeID = :demandeId";
    //$stmtNotif = $pdo->prepare($sqlNotif);
    //$stmtNotif->execute(['demandeId' => $demandeId, 'demandeId' => $demandeId]);
}

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['demandeId'])) {
        $demandeId = $_POST['demandeId'];

        if ($_POST['action'] === 'approuver') {
            approuverDemande($demandeId);
            // Rediriger ou afficher un message de succès
            header("Location: ?page=" . base64_encode('pagespresident/voir_demandes') . "&success=1");
            exit();
        } elseif ($_POST['action'] === 'rejeter') {
            $motifRejet = $_POST['motifRejet'] ?? 'Aucun motif spécifié';
            rejeterDemande($demandeId, $motifRejet);
            // Rediriger ou afficher un message de succès
            header("Location: ?page=" . base64_encode('pagespresident/voir_demandes') . "&success=2");
            exit();
        }
    }
}

// Récupération des demandes de certificat de nationalité
$sql = "SELECT d.DemandeID, d.DateSoumission, d.Statut, u.Nom, u.Prenom 
        FROM demandes d
        JOIN utilisateurs u ON d.UtilisateurID = u.UtilisateurID
        WHERE d.TypeDemande = 'NATIONALITE'
        ORDER BY d.DateSoumission DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$demandes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-5">
    <h1>Demandes de certificat de nationalité</h1>
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $_GET['success'] == 1 ? "La demande a été approuvée avec succès." : "La demande a été rejetée avec succès."; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <table class="table table-striped">
        <thead>
        <tr>
            <th>ID</th>
            <th>Nom</th>
            <th>Prénom</th>
            <th>Date de soumission</th>
            <th>Statut</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($demandes as $demande): ?>
            <tr>
                <td><?= htmlspecialchars($demande['DemandeID']) ?></td>
                <td><?= htmlspecialchars($demande['Nom']) ?></td>
                <td><?= htmlspecialchars($demande['Prenom']) ?></td>
                <td><?= htmlspecialchars($demande['DateSoumission']) ?></td>
                <td><?= htmlspecialchars($demande['Statut']) ?></td>
                <td>
                    <a href="?page=<?php echo base64_encode('pagespresident/details_demande_nationalite').'&demande='.htmlspecialchars($demande['DemandeID']); ?>" class="btn btn-info btn-sm">
                        Détails
                    </a>
                    <?php if ($demande['Statut'] === 'EnCours'): ?>
                        <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#approuverModal<?= $demande['DemandeID'] ?>">
                            Approuver
                        </button>
                        <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#rejeterModal<?= $demande['DemandeID'] ?>">
                            Rejeter
                        </button>
                    <?php endif; ?>
                </td>
            </tr>

            <!-- Modal Approuver -->
            <div class="modal fade" id="approuverModal<?= $demande['DemandeID'] ?>" tabindex="-1" aria-labelledby="approuverModalLabel<?= $demande['DemandeID'] ?>" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="approuverModalLabel<?= $demande['DemandeID'] ?>">Confirmer l'approbation</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            Êtes-vous sûr de vouloir approuver cette demande ?
                        </div>
                        <div class="modal-footer">
                            <form action="" method="post">
                                <input type="hidden" name="demandeId" value="<?= $demande['DemandeID'] ?>">
                                <input type="hidden" name="action" value="approuver">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                <button type="submit" class="btn btn-success">Approuver</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Rejeter -->
            <div class="modal fade" id="rejeterModal<?= $demande['DemandeID'] ?>" tabindex="-1" aria-labelledby="rejeterModalLabel<?= $demande['DemandeID'] ?>" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="rejeterModalLabel<?= $demande['DemandeID'] ?>">Confirmer le rejet</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form action="" method="post">
                                <input type="hidden" name="demandeId" value="<?= $demande['DemandeID'] ?>">
                                <input type="hidden" name="action" value="rejeter">
                                <div class="form-group">
                                    <label for="motifRejet<?= $demande['DemandeID'] ?>">Motif du rejet :</label>
                                    <textarea class="form-control" id="motifRejet<?= $demande['DemandeID'] ?>" name="motifRejet" rows="3" required></textarea>
                                </div>
                                <div class="mt-3">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                    <button type="submit" class="btn btn-danger">Rejeter</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>