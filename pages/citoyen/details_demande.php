<?php
global $pdo;
require "../../config/database.php";


// Récupérer l'ID de la demande
$demandeId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$userId = $_SESSION['user']['UtilisateurID'];

// Fonction pour sécuriser l'affichage des données
function displaySafe($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

// Récupérer les détails de la demande
$query = $pdo->prepare("
    SELECT d.*, h.DateModification, h.AncienStatut, h.NouveauStatut, h.Commentaire 
    FROM demandes d 
    LEFT JOIN historique_demandes h ON d.DemandeID = h.DemandeID 
    WHERE d.DemandeID = :DemandeID AND d.UtilisateurID = :UtilisateurID 
    ORDER BY h.DateModification DESC
");

$query->execute([
    'DemandeID' => $demandeId,
    'UtilisateurID' => $userId
]);

$demande = $query->fetch(PDO::FETCH_ASSOC);
$historique = $query->fetchAll(PDO::FETCH_ASSOC);

if (!$demande) {
    $_SESSION['flash_message'] = "Cette demande n'existe pas ou vous n'avez pas les droits pour la consulter.";
    $_SESSION['flash_type'] = 'danger';
    header("Location: pagescitoyen/suivi_demande.php");
    exit();
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
                    <h3>Détails de la demande N°<?php echo displaySafe($demandeId); ?></h3>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['flash_message'])): ?>
                        <div class="alert alert-<?php echo $_SESSION['flash_type']; ?> alert-dismissible fade show" role="alert">
                            <?php
                            echo $_SESSION['flash_message'];
                            unset($_SESSION['flash_message']);
                            unset($_SESSION['flash_type']);
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h4 class="mb-3">Informations générales</h4>
                            <table class="table table-bordered">
                                <tr>
                                    <th>Type de demande</th>
                                    <td><?php echo displaySafe($demande['TypeDemande']); ?></td>
                                </tr>
                                <tr>
                                    <th>Sous-type</th>
                                    <td><?php echo displaySafe($demande['SousTypeDemande']); ?></td>
                                </tr>
                                <tr>
                                    <th>Date de soumission</th>
                                    <td><?php echo date('d/m/Y H:i', strtotime($demande['DateSoumission'])); ?></td>
                                </tr>
                                <tr>
                                    <th>Statut actuel</th>
                                    <td id="statut-demande">
                                            <span class="status-badge status-<?php echo strtolower($demande['Statut']); ?>">
                                                <?php echo displaySafe($demande['Statut']); ?>
                                            </span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h4 class="mb-3">Description de la demande</h4>
                            <div class="card">
                                <div class="card-body">
                                    <?php echo nl2br(displaySafe($demande['DescriptionDemande'] ?? 'Aucune description disponible')); ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h4 class="mb-3">Historique des mises à jour</h4>
                            <table class="table table-bordered">
                                <thead>
                                <tr>
                                    <th>Date de mise à jour</th>
                                    <th>Ancien statut</th>
                                    <th>Nouveau statut</th>
                                    <th>Commentaire</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($historique as $historiqueItem): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y H:i', strtotime($historiqueItem['DateModification'])); ?></td>
                                        <td><?php echo displaySafe($historiqueItem['AncienStatut']); ?></td>
                                        <td><?php echo displaySafe($historiqueItem['NouveauStatut']); ?></td>
                                        <td><?php echo displaySafe($historiqueItem['Commentaire']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!--<?php if ($demande['Statut'] == 'Soumise'): ?>
                        <button
                                class="btn btn-danger btn-sm annuler-demande"
                                data-demande-id="<?php echo $demande['DemandeID']; ?>">
                            <i class="fas fa-times me-1"></i>Annuler
                        </button>
                    <?php endif; ?>-->

                    <div class="d-grid gap-2 mt-4">
                        <a href="?page=<?php echo base64_encode('pagescitoyen/suivi_demande')?>" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-left me-2"></i>Retour au suivi
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

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
                        annulerPath = '../../annuler_demande.php';
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
                                window.location.href = 'pagescitoyen/suivi_demande.php';
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>