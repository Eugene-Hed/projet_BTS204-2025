<?php
global $pdo;
require "../../config/database.php";

// Requêtes SQL pour récupérer les données spécifiques au président
$paiementsData = $pdo->query('SELECT MONTH(DatePaiement) AS mois, SUM(Montant) AS total FROM paiements GROUP BY mois ORDER BY mois')->fetchAll(PDO::FETCH_ASSOC);
$utilisateursData = $pdo->query('SELECT MONTH(DateCreation) AS mois, COUNT(UtilisateurID) AS total FROM utilisateurs WHERE RoleId <> 2 GROUP BY mois ORDER BY mois')->fetchAll(PDO::FETCH_ASSOC);
$demandesData = $pdo->query('SELECT MONTH(DateSoumission) AS mois, COUNT(DemandeID) AS total FROM demandes WHERE Statut = "Approuvee" GROUP BY mois ORDER BY mois')->fetchAll(PDO::FETCH_ASSOC);

$activites = $pdo->query('SELECT DateHeure, UtilisateurID, TypeActivite FROM journalactivites ORDER BY DateHeure DESC LIMIT 10')->fetchAll(PDO::FETCH_ASSOC);

$nbDemandes = $pdo->query('SELECT COUNT(DemandeID) AS total FROM demandes WHERE TypeDemande = "NATIONALITE"')->fetchColumn();
$nbUtilisateurs = $pdo->query('SELECT COUNT(UtilisateurID) AS total FROM utilisateurs WHERE RoleId = 2')->fetchColumn();
$montantTotal = $pdo->query('SELECT SUM(Montant) AS total FROM paiements')->fetchColumn();
$nbDemandesApprouvees = $pdo->query('SELECT COUNT(DemandeID) AS total FROM demandes WHERE Statut = "Approuvee"')->fetchColumn();

// Fonction pour préparer les données pour Chart.js
function prepareChartData($data) {
    $months = range(1, 12);
    $prepared = array_fill_keys($months, 0);
    foreach ($data as $row) {
        $prepared[$row['mois']] = (float)$row['total'];
    }
    return array_values($prepared);
}

$paiementsChartData = prepareChartData($paiementsData);
$utilisateursChartData = prepareChartData($utilisateursData);
$demandesChartData = prepareChartData($demandesData);

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord du président</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.2.1.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="container">
    <h1 class="mt-4 mb-4">Tableau de bord</h1>
    <div class="row">
        <div class="col-md-3 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Demandes</h5>
                    <p class="card-text">Nombre total de demandes : <?php echo $nbDemandes; ?></p>
                    <a href="?page=<?php echo base64_encode('pagespresident/voir_demandes')?>" class="btn btn-primary">Voir les demandes</a>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Utilisateurs</h5>
                    <p class="card-text">Nombre d'utilisateurs citoyens : <?php echo $nbUtilisateurs; ?></p>
                    <a href="?page=<?php echo base64_encode('pagespresident/gestion_utilisateurs')?>" class="btn btn-primary">Gérer les utilisateurs</a>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Paiements</h5>
                    <p class="card-text">Montant total des paiements : <?php echo number_format($montantTotal, 0, ',', ' '); ?> FCFA</p>
                    <a href="?page=<?php echo base64_encode('pagespresident/voir_paiements')?>" class="btn btn-primary">Voir les paiements</a>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Demandes approuvées</h5>
                    <p class="card-text">Nombre de demandes approuvées : <?php echo $nbDemandesApprouvees; ?></p>
                    <a href="?page=<?php echo base64_encode('pagespresident/voir_demandes_approuvees')?>" class="btn btn-primary">Voir les demandes approuvées</a>
                </div>
            </div>
        </div>
    </div>
    <div class="row mb-4">
        <div class="col-md-12">
            <h2>Activités récentes</h2>
            <table class="table table-striped">
                <thead>
                <tr>
                    <th>Date</th>
                    <th>Utilisateur</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($activites as $activite) : ?>
                    <tr>
                        <td><?php echo $activite['DateHeure']; ?></td>
                        <td><?php echo $activite['UtilisateurID']; ?></td>
                        <td><?php echo $activite['TypeActivite']; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <h2>Statistiques mensuelles</h2>
            <canvas id="chart" width="400" height="200"></canvas>
        </div>
    </div>
</div>
<script>
    const ctx = document.getElementById('chart').getContext('2d');
    const chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'],
            datasets: [
                {
                    label: 'Paiements',
                    data: <?php echo json_encode($paiementsChartData); ?>,
                    borderColor: 'rgba(255, 99, 132, 1)',
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    tension: 0.1
                },
                {
                    label: 'Utilisateurs',
                    data: <?php echo json_encode($utilisateursChartData); ?>,
                    borderColor: 'rgba(54, 162, 235, 1)',
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    tension: 0.1
                },
                {
                    label: 'Demandes approuvées',
                    data: <?php echo json_encode($demandesChartData); ?>,
                    borderColor: 'rgba(255, 206, 86, 1)',
                    backgroundColor: 'rgba(255, 206, 86, 0.2)',
                    tension: 0.1
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
</script>
</body>
</html>