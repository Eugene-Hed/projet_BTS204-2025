<?php
global $pdo;
require "../../config/database.php";

// Requêtes SQL pour récupérer les données spécifiques à l'officier
$demandesData = $pdo->query('SELECT MONTH(DateSoumission) AS mois, COUNT(DemandeID) AS total FROM demandes WHERE Statut <> "Approuvee" AND TypeDemande = "CNI" GROUP BY mois ORDER BY mois')->fetchAll(PDO::FETCH_ASSOC);
$rendezVousData = $pdo->query('SELECT MONTH(DateRendezVous) AS mois, COUNT(RendezVousID) AS total FROM rendezvous WHERE Statut <> "Termine" GROUP BY mois ORDER BY mois')->fetchAll(PDO::FETCH_ASSOC);
$reclamationsData = $pdo->query('SELECT MONTH(DateCreation) AS mois, COUNT(ReclamationID) AS total FROM reclamations WHERE Statut <> "Fermee" GROUP BY mois ORDER BY mois')->fetchAll(PDO::FETCH_ASSOC);

$activites = $pdo->query('SELECT DateHeure, UtilisateurID, TypeActivite FROM journalactivites WHERE TypeActivite LIKE "Officier%" ORDER BY DateHeure DESC LIMIT 10')->fetchAll(PDO::FETCH_ASSOC);

$nbRendezVous = $pdo->query('SELECT COUNT(RendezVousID) AS total FROM rendezvous WHERE Statut <> "Termine"')->fetchColumn();
$nbDemandes = $pdo->query('SELECT COUNT(DemandeID) AS total FROM demandes WHERE Statut <> "Approuvee" AND TypeDemande = "CNI"')->fetchColumn();
$nbReclamations = $pdo->query('SELECT COUNT(ReclamationID) AS total FROM reclamations WHERE Statut <> "Fermee"')->fetchColumn();
$nbDemandesEnAttente = $pdo->query('SELECT COUNT(DemandeID) AS total FROM demandes WHERE Statut = "EnCours" AND TypeDemande = "CNI"')->fetchColumn();

// Fonction pour préparer les données pour Chart.js
function prepareChartData($data) {
    $months = range(1, 12);
    $prepared = array_fill_keys($months, 0);
    foreach ($data as $row) {
        $prepared[$row['mois']] = (float)$row['total'];
    }
    return array_values($prepared);
}

$demandesChartData = prepareChartData($demandesData);
$rendezVousChartData = prepareChartData($rendezVousData);
$reclamationsChartData = prepareChartData($reclamationsData);

?>
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
                    <h5 class="card-title">Rendez-vous</h5>
                    <p class="card-text">Nombre de rendez-vous en attente : <?php echo $nbRendezVous; ?></p>
                    <a href="?page=<?php echo base64_encode('pagesofficier/gestion_rendezvous')?>" class="btn btn-primary">Gérer les rendez-vous</a>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Demandes</h5>
                    <p class="card-text">Nombre de demandes en cours : <?php echo $nbDemandes; ?></p>
                    <a href="?page=<?php echo base64_encode('pagesofficier/traitement_demandes')?>" class="btn btn-primary">Traiter les demandes</a>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Réclamations</h5>
                    <p class="card-text">Nombre de réclamations ouvertes : <?php echo $nbReclamations; ?></p>
                    <a href="?page=<?php echo base64_encode('pagesofficier/gestion_reclamation')?>" class="btn btn-primary">Gérer les réclamations</a>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Demandes en attente</h5>
                    <p class="card-text">Nombre de demandes en attente : <?php echo $nbDemandesEnAttente; ?></p>
                    <a href="?page=<?php echo base64_encode('pagesofficier/suivi_demande')?>" class="btn btn-primary">Voir les demandes en attente</a>
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
                    label: 'Demandes',
                    data: <?php echo json_encode($demandesChartData); ?>,
                    borderColor: 'rgba(255, 99, 132, 1)',
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    tension: 0.1
                },
                {
                    label: 'Rendez-vous',
                    data: <?php echo json_encode($rendezVousChartData); ?>,
                    borderColor: 'rgba(54, 162, 235, 1)',
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    tension: 0.1
                },
                {
                    label: 'Réclamations',
                    data: <?php echo json_encode($reclamationsChartData); ?>,
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