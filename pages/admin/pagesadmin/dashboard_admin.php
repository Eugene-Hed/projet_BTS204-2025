<?php
global $pdo;
require "../../config/database.php";

// Get detailed statistics
$stats = [
    'users' => [
        'total' => $pdo->query("SELECT COUNT(*) FROM utilisateurs")->fetchColumn(),
        'active' => $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE IsActive = 1")->fetchColumn(),
        'inactive' => $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE IsActive = 0")->fetchColumn(),
        'by_role' => $pdo->query("SELECT r.role, COUNT(*) as count FROM utilisateurs u JOIN role r ON u.RoleId = r.id GROUP BY r.role")->fetchAll()
    ],
    'requests' => [
        'total' => $pdo->query("SELECT COUNT(*) FROM demandes")->fetchColumn(),
        'pending' => $pdo->query("SELECT COUNT(*) FROM demandes WHERE Statut = 'Soumise'")->fetchColumn(),
        'approved' => $pdo->query("SELECT COUNT(*) FROM demandes WHERE Statut = 'Approuvee'")->fetchColumn(),
        'rejected' => $pdo->query("SELECT COUNT(*) FROM demandes WHERE Statut = 'Rejetee'")->fetchColumn(),
        'by_type' => $pdo->query("SELECT TypeDemande, COUNT(*) as count FROM demandes GROUP BY TypeDemande")->fetchAll(),
        'monthly' => $pdo->query("SELECT DATE_FORMAT(DateSoumission, '%Y-%m') as month, COUNT(*) as count FROM demandes GROUP BY month ORDER BY month DESC LIMIT 12")->fetchAll()
    ],
    'complaints' => [
        'total' => $pdo->query("SELECT COUNT(*) FROM reclamations")->fetchColumn(),
        'open' => $pdo->query("SELECT COUNT(*) FROM reclamations WHERE Statut = 'Ouverte'")->fetchColumn(),
        'in_progress' => $pdo->query("SELECT COUNT(*) FROM reclamations WHERE Statut = 'EnCours'")->fetchColumn(),
        'closed' => $pdo->query("SELECT COUNT(*) FROM reclamations WHERE Statut = 'Fermee'")->fetchColumn()
    ]
];

// Get recent activities
$recent_activities = $pdo->query("
    SELECT h.*, d.TypeDemande, u.Nom, u.Prenom
    FROM historique_demandes h
    JOIN demandes d ON h.DemandeID = d.DemandeID
    LEFT JOIN utilisateurs u ON h.ModifiePar = u.UtilisateurID
    ORDER BY h.DateModification DESC
    LIMIT 5
")->fetchAll();

// Get latest users
$latest_users = $pdo->query("
    SELECT * FROM utilisateurs 
    ORDER BY DateCreation DESC 
    LIMIT 5
")->fetchAll();

// Add new query for system evolution
$systemEvolution = $pdo->query("
    SELECT 
        DATE_FORMAT(d.DateSoumission, '%Y-%m') as month,
        COUNT(DISTINCT d.DemandeID) as requests,
        COUNT(DISTINCT r.ReclamationID) as complaints,
        COUNT(DISTINCT u.UtilisateurID) as users
    FROM 
        (SELECT DISTINCT DATE_FORMAT(DateSoumission, '%Y-%m') as months 
         FROM demandes 
         ORDER BY months DESC LIMIT 12) as months
    LEFT JOIN demandes d ON DATE_FORMAT(d.DateSoumission, '%Y-%m') = months.months
    LEFT JOIN reclamations r ON DATE_FORMAT(r.DateCreation, '%Y-%m') = months.months
    LEFT JOIN utilisateurs u ON DATE_FORMAT(u.DateCreation, '%Y-%m') = months.months
    GROUP BY month
    ORDER BY month ASC
")->fetchAll();
?>

<!-- Add Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="main-content">
    <!-- Quick Stats Cards -->
    <div class="card">
        <div class="card-header">
            <i class="bi bi-speedometer2"></i> Vue d'ensemble
        </div>
        <div class="card-body">
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px;">
                <!-- Users Stats Card -->
                <div class="card">
                    <div class="card-body" style="text-align: center; padding: 20px;">
                        <i class="bi bi-people" style="font-size: 2rem; color: #3498db;"></i>
                        <h3><?= $stats['users']['total'] ?></h3>
                        <p>Utilisateurs</p>
                        <div class="progress" style="height: 5px;">
                            <div class="progress-bar" style="width: <?= ($stats['users']['active']/$stats['users']['total'])*100 ?>%"></div>
                        </div>
                        <small><?= $stats['users']['active'] ?> actifs</small>
                    </div>
                </div>

                <!-- Similar cards for Requests and Complaints -->
                <!-- Add more quick stat cards -->
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-graph-up"></i> Évolution des Demandes
                </div>
                <div class="card-body">
                    <canvas id="requestsChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-pie-chart"></i> Distribution des Types de Demandes
                </div>
                <div class="card-body">
                    <canvas id="requestTypesChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- System Evolution Chart -->
    <div class="card mt-4">
        <div class="card-header">
            <i class="bi bi-graph-up"></i> Évolution Générale du Système
        </div>
        <div class="card-body">
            <canvas id="systemEvolutionChart" style="height: 300px;"></canvas>
        </div>
    </div>

    <!-- Add JavaScript for charts -->
    <script>
        // Monthly Requests Line Chart
        const monthlyData = <?= json_encode(array_column($stats['requests']['monthly'], 'count')) ?>;
        const monthLabels = <?= json_encode(array_column($stats['requests']['monthly'], 'month')) ?>;
        
        new Chart(document.getElementById('requestsChart'), {
            type: 'line',
            data: {
                labels: monthLabels,
                datasets: [{
                    label: 'Demandes par mois',
                    data: monthlyData,
                    borderColor: '#3498db',
                    tension: 0.1
                }]
            }
        });

        // Request Types Pie Chart
        const requestTypes = <?= json_encode($stats['requests']['by_type']) ?>;
        new Chart(document.getElementById('requestTypesChart'), {
            type: 'pie',
            data: {
                labels: requestTypes.map(item => item.TypeDemande),
                datasets: [{
                    data: requestTypes.map(item => item.count),
                    backgroundColor: ['#3498db', '#2ecc71', '#e74c3c', '#f1c40f']
                }]
            }
        });

        // System Evolution Chart
        const systemData = <?= json_encode($systemEvolution) ?>;
        new Chart(document.getElementById('systemEvolutionChart'), {
            type: 'line',
            data: {
                labels: systemData.map(item => item.month),
                datasets: [{
                    label: 'Demandes',
                    data: systemData.map(item => item.requests),
                    borderColor: '#3498db',
                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }, {
                    label: 'Réclamations',
                    data: systemData.map(item => item.complaints),
                    borderColor: '#e74c3c',
                    backgroundColor: 'rgba(231, 76, 60, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }, {
                    label: 'Utilisateurs',
                    data: systemData.map(item => item.users),
                    borderColor: '#2ecc71',
                    backgroundColor: 'rgba(46, 204, 113, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            drawBorder: false,
                            color: 'rgba(0, 0, 0, 0.1)'
                        }
                    },
                    x: {
                        grid: {
                            drawBorder: false,
                            display: false
                        }
                    }
                },
                plugins: {
                    title: {
                        display: true,
                        text: 'Évolution sur les 12 derniers mois',
                        font: {
                            size: 16
                        }
                    },
                    legend: {
                        position: 'top'
                    }
                }
            }
        });
    </script>

    <!-- Recent Activities -->
</div>
    <div class="card mt-4">
        <div class="card-header">
            Activités Récentes
        </div>
        <div class="card-body">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Action</th>
                        <th>Type de Demande</th>
                        <th>Modifié par</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_activities as $activity): ?>
                        <tr>
                            <td><?= htmlspecialchars($activity['DateModification']) ?></td>
                            <td>
                                <span class="badge <?= $activity['NouveauStatut'] === 'Approuvee' ? 'badge-success' : 
                                    ($activity['NouveauStatut'] === 'Rejetee' ? 'badge-danger' : 'badge-info') ?>">
                                    <?= htmlspecialchars($activity['NouveauStatut']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($activity['TypeDemande']) ?></td>
                            <td><?= htmlspecialchars($activity['Nom'] . ' ' . $activity['Prenom']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Latest Users -->
    <div class="card mt-4">
        <div class="card-header">
            Derniers Utilisateurs Inscrits
        </div>
        <div class="card-body">
            <table>
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Email</th>
                        <th>Date d'inscription</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($latest_users as $user): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['Nom'] . ' ' . $user['Prenom']) ?></td>
                            <td><?= htmlspecialchars($user['Email']) ?></td>
                            <td><?= htmlspecialchars($user['DateCreation']) ?></td>
                            <td>
                                <span class="badge <?= $user['IsActive'] ? 'badge-success' : 'badge-danger' ?>">
                                    <?= $user['IsActive'] ? 'Actif' : 'Inactif' ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
