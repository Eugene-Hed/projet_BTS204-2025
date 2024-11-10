<?php
global $pdo;
require "../../config/database.php";

function displaySafe($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

$userId = $_SESSION['user']['UtilisateurID'];

// Récupération des documents disponibles (CNI et certificats de nationalité)
try {
    $query = $pdo->prepare("
        SELECT 
            d.DemandeID,
            d.TypeDemande,
            d.DateSoumission,
            c.NumeroCarteIdentite,
            c.CheminFichier as CheminCNI,
            c.DateEmission as DateEmissionCNI,
            cn.NumeroCertificat,
            cn.CheminPDF as CheminCertificat,
            cn.DateEmission as DateEmissionCertificat
        FROM demandes d
        LEFT JOIN cartesidentite c ON d.DemandeID = c.DemandeID
        LEFT JOIN certificatsnationalite cn ON d.DemandeID = cn.DemandeID
        WHERE d.UtilisateurID = ? 
        AND d.Statut = 'Terminee'
        AND (c.CarteID IS NOT NULL OR cn.CertificatID IS NOT NULL)
        ORDER BY d.DateSoumission DESC
    ");
    $query->execute([$userId]);
    $documents = $query->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $documents = [];
    $_SESSION['flash_message'] = "Une erreur est survenue lors de la récupération des documents.";
    $_SESSION['flash_type'] = 'danger';
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Documents</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .document-card {
            transition: transform 0.2s;
            margin-bottom: 20px;
        }
        .document-card:hover {
            transform: translateY(-5px);
        }
        .document-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }
        .card-header-custom {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card mt-5 mb-5">
                <div class="card-header text-center bg-primary text-white">
                    <h3>Mes Documents Officiels</h3>
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

                    <?php if (count($documents) > 0): ?>
                        <div class="row">
                            <?php foreach ($documents as $document): ?>
                                <?php if ($document['TypeDemande'] === 'CNI' && $document['CheminCNI']): ?>
                                    <div class="col-md-6">
                                        <div class="card document-card">
                                            <div class="card-header card-header-custom">
                                                <h5 class="card-title mb-0">Carte Nationale d'Identité</h5>
                                            </div>
                                            <div class="card-body text-center">
                                                <i class="fas fa-id-card document-icon text-primary"></i>
                                                <p class="card-text">
                                                    <strong>N° CNI:</strong> <?php echo displaySafe($document['NumeroCarteIdentite']); ?><br>
                                                    <strong>Date d'émission:</strong> <?php echo date('d/m/Y', strtotime($document['DateEmissionCNI'])); ?>
                                                </p>
                                                <a href="<?php echo displaySafe($document['CheminCNI']); ?>"
                                                   class="btn btn-primary"
                                                   download="CNI_<?php echo displaySafe($document['NumeroCarteIdentite']); ?>.pdf">
                                                    <i class="fas fa-download me-2"></i>Télécharger
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <?php if ($document['TypeDemande'] === 'CertificatNationalite' && $document['CheminCertificat']): ?>
                                    <div class="col-md-6">
                                        <div class="card document-card">
                                            <div class="card-header card-header-custom">
                                                <h5 class="card-title mb-0">Certificat de Nationalité</h5>
                                            </div>
                                            <div class="card-body text-center">
                                                <i class="fas fa-certificate document-icon text-success"></i>
                                                <p class="card-text">
                                                    <strong>N° Certificat:</strong> <?php echo displaySafe($document['NumeroCertificat']); ?><br>
                                                    <strong>Date d'émission:</strong> <?php echo date('d/m/Y', strtotime($document['DateEmissionCertificat'])); ?>
                                                </p>
                                                <a href="<?php echo displaySafe($document['CheminCertificat']); ?>"
                                                   class="btn btn-success"
                                                   download="Certificat_<?php echo displaySafe($document['NumeroCertificat']); ?>.pdf">
                                                    <i class="fas fa-download me-2"></i>Télécharger
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info text-center" role="alert">
                            <i class="fas fa-info-circle me-2"></i>
                            Aucun document n'est disponible pour le moment. Si vous avez fait une demande, veuillez attendre que celle-ci soit traitée.
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
</body>
</html>