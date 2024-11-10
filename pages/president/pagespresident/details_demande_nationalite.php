<?php
// Vérification de l'accès direct au script
//if (!defined('ACCESS_AUTHORIZED')) {
  //  die('Accès non autorisé');
//}
require "../../config/database.php";
global $pdo;

if (!isset($_GET['demande']) || !is_numeric($_GET['demande'])) {
    die("ID de demande invalide.");
}

$demandeId = $_GET['demande'];

// Récupération des détails de la demande
$sql = "SELECT d.DemandeID, d.DateSoumission, d.Statut, 
               u.Nom, u.Prenom, u.Email, u.NumeroTelephone,
               dnd.DateNaissance, dnd.LieuNaissance, dnd.NomPere, dnd.NomMere, dnd.Adresse, dnd.Motif
        FROM demandes d
        JOIN utilisateurs u ON d.UtilisateurID = u.UtilisateurID
        JOIN demande_nationalite_details dnd ON d.DemandeID = dnd.DemandeID
        WHERE d.DemandeID = :demandeId AND d.TypeDemande = 'NATIONALITE'";

$stmt = $pdo->prepare($sql);
$stmt->execute(['demandeId' => $demandeId]);
$demande = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$demande) {
    die("Demande non trouvée.");
}

// Récupération des documents associés
$sqlDocuments = "SELECT TypeDocument, CheminFichier FROM documents WHERE DemandeID = :demandeId";
$stmtDocuments = $pdo->prepare($sqlDocuments);
$stmtDocuments->execute(['demandeId' => $demandeId]);
$documents = $stmtDocuments->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-5">
    <h1>Détails de la demande de certificat de nationalité</h1>
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Informations générales</h5>
            <p><strong>ID de la demande:</strong> <?= htmlspecialchars($demande['DemandeID']) ?></p>
            <p><strong>Date de soumission:</strong> <?= htmlspecialchars($demande['DateSoumission']) ?></p>
            <p><strong>Statut:</strong> <?= htmlspecialchars($demande['Statut']) ?></p>

            <h5 class="mt-4">Informations du demandeur</h5>
            <p><strong>Nom:</strong> <?= htmlspecialchars($demande['Nom']) ?></p>
            <p><strong>Prénom:</strong> <?= htmlspecialchars($demande['Prenom']) ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($demande['Email']) ?></p>
            <p><strong>Téléphone:</strong> <?= htmlspecialchars($demande['NumeroTelephone']) ?></p>
            <p><strong>Date de naissance:</strong> <?= htmlspecialchars($demande['DateNaissance']) ?></p>
            <p><strong>Lieu de naissance:</strong> <?= htmlspecialchars($demande['LieuNaissance']) ?></p>
            <p><strong>Nom du père:</strong> <?= htmlspecialchars($demande['NomPere']) ?></p>
            <p><strong>Nom de la mère:</strong> <?= htmlspecialchars($demande['NomMere']) ?></p>
            <p><strong>Adresse:</strong> <?= htmlspecialchars($demande['Adresse']) ?></p>
            <p><strong>Motif de la demande:</strong> <?= htmlspecialchars($demande['Motif']) ?></p>

            <h5 class="mt-4">Documents fournis</h5>
            <ul>
                <?php foreach ($documents as $document): ?>
                    <li>
                        <?= htmlspecialchars($document['TypeDocument']) ?>:
                        <a href="<?= htmlspecialchars($document['CheminFichier']) ?>" target="_blank">Voir le document</a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <a href="?page=<?= base64_encode('pagespresident/voir_demandes') ?>" class="btn btn-primary mt-3">Retour à la liste</a>
</div>