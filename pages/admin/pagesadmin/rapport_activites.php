<?php
global $pdo;
require "../../config/database.php";

// Statistiques générales
$stats = [
    'total_users' => $pdo->query("SELECT COUNT(*) FROM utilisateurs")->fetchColumn(),
    'active_users' => $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE IsActive = 1")->fetchColumn(),
    'total_requests' => $pdo->query("SELECT COUNT(*) FROM demandes")->fetchColumn(),
    'pending_requests' => $pdo->query("SELECT COUNT(*) FROM demandes WHERE Statut = 'Soumise'")->fetchColumn()
];

// Dernières demandes
$recent_requests = $pdo->query("
    SELECT d.*, u.Nom, u.Prenom 
    FROM demandes d
    JOIN utilisateurs u ON d.UtilisateurID = u.UtilisateurID
    ORDER BY d.DateSoumission DESC 
    LIMIT 5
")->fetchAll();

// Historique des activités
$activities = $pdo->query("
    SELECT h.*, d.TypeDemande, u.Nom, u.Prenom
    FROM historique_demandes h
    JOIN demandes d ON h.DemandeID = d.DemandeID
    LEFT JOIN utilisateurs u ON h.ModifiePar = u.UtilisateurID
    ORDER BY h.DateModification DESC
    LIMIT 10
")->fetchAll();

$pageTitle = "Activités";
?>
    <!-- Statistiques -->
    <div class="card">
        <div class="card-header">
            Statistiques Générales
        </div>
        <div class="card-body">
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px;">
                <div class="card">
                    <div class="card-body">
                        <h3><?= $stats['total_users'] ?></h3>
                        <p>Utilisateurs Total</p>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body">
                        <h3><?= $stats['active_users'] ?></h3>
                        <p>Utilisateurs Actifs</p>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body">
                        <h3><?= $stats['total_requests'] ?></h3>
                        <p>Demandes Total</p>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body">
                        <h3><?= $stats['pending_requests'] ?></h3>
                        <p>Demandes en Attente</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Dernières demandes -->
    <div class="card mt-4">
        <div class="card-header">
            Dernières Demandes
        </div>
        <div class="card-body">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Demandeur</th>
                        <th>Type</th>
                        <th>Statut</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_requests as $request): ?>
                        <tr>
                            <td><?= htmlspecialchars($request['DemandeID']) ?></td>
                            <td><?= htmlspecialchars($request['Nom'] . ' ' . $request['Prenom']) ?></td>
                            <td><?= htmlspecialchars($request['TypeDemande']) ?></td>
                            <td>
                                <span class="badge badge-info">
                                    <?= htmlspecialchars($request['Statut']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($request['DateSoumission']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Historique des activités -->
    <div class="card mt-4">
        <div class="card-header">
            Historique des Activités
        </div>
        <div class="card-body">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Action</th>
                        <th>Demande</th>
                        <th>Modifié par</th>
                        <th>Commentaire</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($activities as $activity): ?>
                        <tr>
                            <td><?= htmlspecialchars($activity['DateModification']) ?></td>
                            <td>
                                <?= htmlspecialchars($activity['AncienStatut']) ?> → 
                                <?= htmlspecialchars($activity['NouveauStatut']) ?>
                            </td>
                            <td><?= htmlspecialchars($activity['TypeDemande']) ?></td>
                            <td><?= htmlspecialchars($activity['Nom'] . ' ' . $activity['Prenom']) ?></td>
                            <td><?= htmlspecialchars($activity['Commentaire']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>