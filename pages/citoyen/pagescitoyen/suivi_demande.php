<?php
global $pdo;
require "../../config/database.php";

function displaySafe($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}


$userId = $_SESSION['user']['UtilisateurID'];

// Récupération des demandes
try {
    $query = $pdo->prepare("
        SELECT * FROM demandes 
        WHERE UtilisateurID = ? 
        ORDER BY DateSoumission DESC
    ");
    $query->execute([$userId]);
    $demandes = $query->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $demandes = [];
    $_SESSION['flash_message'] = "Une erreur est survenue lors de la récupération des demandes.";
    $_SESSION['flash_type'] = 'danger';
}
?>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 500;
        }
        .status-soumise { background-color: #ffd700; color: #000; }
        .status-en-cours { background-color: #007bff; color: #fff; }
        .status-terminee { background-color: #28a745; color: #fff; }
        .status-annulee { background-color: #dc3545; color: #fff; }
    </style>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card mt-5 mb-5">
                <div class="card-header text-center bg-primary text-white">
                    <h3>Suivi de vos demandes</h3>
                </div>
            <div class="card-body">
                <?php if (isset($_SESSION['flash_message'])): ?>
<div class="alert alert-<?php echo $_SESSION['flash_type']; ?>" 
     style="transition: opacity 0.5s ease-in-out; opacity: 1; 
            background-color: <?= $_SESSION['flash_type'] == 'success' ? '#d4edda' : '#f8d7da' ?>; 
            color: <?= $_SESSION['flash_type'] == 'success' ? '#155724' : '#721c24' ?>; 
            padding: 15px; border-radius: 4px; margin-bottom: 20px;">
    <?php
    echo $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
    unset($_SESSION['flash_type']);
    ?>
</div>
<?php endif; ?>

                    <?php if (count($demandes) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                <tr>
                                    <th>N° Demande</th>
                                    <th>Type</th>
                                    <th>Sous-type</th>
                                    <th>Date de soumission</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($demandes as $demande): ?>
                                    <tr id="demande-row-<?php echo displaySafe($demande['DemandeID']); ?>">
                                        <td><?php echo displaySafe($demande['DemandeID']); ?></td>
                                        <td><?php echo displaySafe($demande['TypeDemande']); ?></td>
                                        <td><?php echo displaySafe($demande['SousTypeDemande']); ?></td>
                                        <td>
                                            <?php
                                            if (isset($demande['DateSoumission'])) {
                                                echo date('d/m/Y H:i', strtotime($demande['DateSoumission']));
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php
                                            $statut = displaySafe($demande['Statut']);
                                            $statusClass = strtolower($statut);
                                            ?>
                                            <span class="status-badge status-<?php echo $statusClass; ?>">
                                                <?php echo $statut; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="?page=<?php echo base64_encode('details_demande').'&id='.displaySafe($demande['DemandeID']); ?>"
                                                   class="btn btn-info btn-sm">
                                                    <i class="fas fa-eye me-1"></i>Détails
                                                </a>
                                                <!--<?php if ($demande['Statut'] === 'Soumise'): ?>
                                                    <button
                                                            class="btn btn-danger btn-sm annuler-demande"
                                                            data-demande-id="<?php echo $demande['DemandeID']; ?>"
                                                        <i class="fas fa-times me-1"></i>Annuler
                                                    </button>
                                                <?php endif; ?>-->
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info text-center" role="alert">
                            <i class="fas fa-info-circle me-2"></i>
                            Vous n'avez soumis aucune demande pour le moment.
                        </div>
                    <?php endif; ?>

                    <div class="d-grid gap-2 mt-4">
                        <a href="?page=<?php echo base64_encode('home'); ?>" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-left me-2"></i>Retour à l'accueil
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.annuler-demande').forEach(button => {
            button.addEventListener('click', async function(e) {
                e.preventDefault();
                const demandeId = this.getAttribute('data-demande-id');

                if (!confirm('Êtes-vous sûr de vouloir annuler cette demande ?')) {
                    return;
                }

                try {
                    // Déterminer le bon chemin en fonction de la page actuelle
                    const currentPath = window.location.pathname;
                    let annulerPath;

                    if (currentPath.includes('pagescitoyen')) {
                        annulerPath = '../annuler_demande.php';
                    } else {
                        annulerPath = '../annuler_demande.php';
                    }

                    const response = await fetch(annulerPath, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'demandeId=' + encodeURIComponent(demandeId)
                    });

                    // Vérifier si la réponse est OK
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }

                    // Vérifier le type de contenu de la réponse
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        throw new Error('La réponse du serveur n\'est pas au format JSON');
                    }

                    const data = await response.json();

                    if (data.success) {
                        // Créer l'alerte de succès
                        const alertDiv = document.createElement('div');
                        alertDiv.className = 'alert alert-success alert-dismissible fade show';
                        alertDiv.innerHTML = `
                        ${data.message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    `;

                        // Insérer l'alerte
                        const cardBody = document.querySelector('.card-body');
                        cardBody.insertBefore(alertDiv, cardBody.firstChild);

                        // Mettre à jour le statut si on est sur la page de détails
                        const statutDemande = document.getElementById('statut-demande');
                        if (statutDemande) {
                            statutDemande.innerHTML = '<span class="status-badge status-annulee">Annulée</span>';
                        }

                        // Mettre à jour la ligne du tableau si on est sur la page de suivi
                        const row = document.getElementById('demande-row-' + demandeId);
                        if (row) {
                            const statusCell = row.querySelector('td:nth-child(5)');
                            if (statusCell) {
                                statusCell.innerHTML = '<span class="status-badge status-annulee">Annulée</span>';
                            }
                        }

                        // Supprimer le bouton d'annulation
                        const cancelButton = document.querySelector(`.annuler-demande[data-demande-id="${demandeId}"]`);
                        if (cancelButton) {
                            cancelButton.remove();
                        }

                        // Rediriger si nécessaire après un délai
                        if (window.location.pathname.includes('details_demande.php')) {
                            setTimeout(() => {
                                window.location.href = 'suivi_demande.php';
                            }, 2000);
                        }
                    } else {
                        throw new Error(data.message);
                    }
                } catch (error) {
                    console.error('Error:', error);
                    // Créer une alerte d'erreur
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-danger alert-dismissible fade show';
                    alertDiv.innerHTML = `
                    Une erreur est survenue lors de l'annulation de la demande: ${error.message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                `;

                    // Insérer l'alerte
                    const cardBody = document.querySelector('.card-body');
                    cardBody.insertBefore(alertDiv, cardBody.firstChild);
                }
            });
        });
    });
</script>