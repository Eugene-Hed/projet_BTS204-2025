<?php
global $pdo;
require "../../config/database.php";

// Récupération des paiements liés aux demandes de certificat de nationalité
$sql = "SELECT p.PaiementID, p.Montant, p.DatePaiement, p.StatutPaiement, d.DemandeID, u.Nom, u.Prenom 
        FROM paiements p
        JOIN demandes d ON p.DemandeID = d.DemandeID
        JOIN utilisateurs u ON d.UtilisateurID = u.UtilisateurID
        WHERE d.TypeDemande = 'NATIONALITE'
        ORDER BY p.DatePaiement DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$paiements = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voir les paiements des certificats de nationalité</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <h1>Paiements des certificats de nationalité</h1>
    <table class="table table-striped">
        <thead>
        <tr>
            <th>ID Paiement</th>
            <th>ID Demande</th>
            <th>Nom</th>
            <th>Prénom</th>
            <th>Montant</th>
            <th>Date de paiement</th>
            <th>Statut</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($paiements as $paiement): ?>
            <tr>
                <td><?= htmlspecialchars($paiement['PaiementID']) ?></td>
                <td><?= htmlspecialchars($paiement['DemandeID']) ?></td>
                <td><?= htmlspecialchars($paiement['Nom']) ?></td>
                <td><?= htmlspecialchars($paiement['Prenom']) ?></td>
                <td><?= htmlspecialchars($paiement['Montant']) ?> FCFA</td>
                <td><?= htmlspecialchars($paiement['DatePaiement']) ?></td>
                <td><?= htmlspecialchars($paiement['StatutPaiement']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>