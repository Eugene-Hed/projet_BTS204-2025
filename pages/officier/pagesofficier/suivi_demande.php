<?php
global $pdo;
require_once '../../config/database.php';
require_once '../../vendor/autoload.php';

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

// Récupérer les demandes en attente
$stmt = $pdo->prepare("
    SELECT d.DemandeID, d.DateSoumission, u.Nom, u.Prenom, u.DateNaissance, 
           dc.Adresse, dc.Profession, dc.Taille, u.NumeroTelephone, dc.LieuNaissance,
           doc.CheminFichier as PhotoPath
    FROM demandes d
    JOIN demande_cni_details dc ON d.DemandeID = dc.DemandeID
    JOIN utilisateurs u ON d.UtilisateurID = u.UtilisateurID
    JOIN documents doc ON d.DemandeID = doc.DemandeID
    WHERE d.Statut = 'EnCours' AND d.TypeDemande = 'CNI'
    AND doc.TypeDocument = 'Photo'
");
$stmt->execute();
$demandes = $stmt->fetchAll(PDO::FETCH_ASSOC);
  function preparerCNI($demandeID) {
      global $pdo;
      try {
          // Get information with additional fields
          $stmt = $pdo->prepare("
              SELECT 
                  d.DemandeID, 
                  u.Nom, 
                  u.Prenom, 
                  u.DateNaissance,
                  u.Sexe, 
                  dc.LieuNaissance,
                  dc.Adresse, 
                  dc.Profession, 
                  dc.Taille, 
                  dc.NationalitePere,
                  dc.NationaliteMere,
                  u.NumeroTelephone,
                  doc.CheminFichier as PhotoPath
              FROM demandes d
              JOIN demande_cni_details dc ON d.DemandeID = dc.DemandeID
              JOIN utilisateurs u ON d.UtilisateurID = u.UtilisateurID
              JOIN documents doc ON d.DemandeID = doc.DemandeID
              WHERE d.DemandeID = ? AND doc.TypeDocument = 'Photo'
          ");
          $stmt->execute([$demandeID]);
          $info = $stmt->fetch(PDO::FETCH_ASSOC);

          if (!$info) return false;

          // Generate unique CNI number with checksum
          $numeroCNI = 'CMR' . date('Y') . str_pad($demandeID, 8, '0', STR_PAD_LEFT);
        
          // Set dates
          $dateEmission = date('Y-m-d');
          $dateExpiration = date('Y-m-d', strtotime('+10 years'));

          // Enhanced QR data structure
          $qrData = json_encode([
              'numeroCNI' => $numeroCNI,
              'identite' => [
                  'nom' => $info['Nom'],
                  'prenom' => $info['Prenom'],
                  'sexe' => $info['Sexe'],
                  'dateNaissance' => $info['DateNaissance'],
                  'lieuNaissance' => $info['LieuNaissance']
              ],
              'filiation' => [
                  'nationalitePere' => $info['NationalitePere'],
                  'nationaliteMere' => $info['NationaliteMere']
              ],
              'informations' => [
                  'adresse' => $info['Adresse'],
                  'profession' => $info['Profession'],
                  'taille' => $info['Taille'],
                  'telephone' => $info['NumeroTelephone']
              ],
              'validite' => [
                  'dateEmission' => $dateEmission,
                  'dateExpiration' => $dateExpiration,
                  'autoriteEmettrice' => 'République du Cameroun'
              ]
          ]);

          // Generate QR code with enhanced options
          $qrOptions = new QROptions([
              'version' => QRCode::VERSION_AUTO,
              'outputType' => QRCode::OUTPUT_IMAGE_PNG,
              'eccLevel' => QRCode::ECC_H,
              'scale' => 8,
              'imageBase64' => false,
              'imageTransparent' => false,
              'drawLightModules' => true,
              'keepAsSquare' => true,
              'addQuietzone' => true,
          ]);

          $qrCode = new QRCode($qrOptions);
          $qrImagePath = '../../uploads/qrcodes/' . $numeroCNI . '_qr.png';
          $qrCode->render($qrData, $qrImagePath);

          // Update demand status and create CNI record
          $pdo->beginTransaction();

          $stmt = $pdo->prepare("UPDATE demandes SET Statut = 'Terminee' WHERE DemandeID = ?");
          $stmt->execute([$demandeID]);

          $stmt = $pdo->prepare("
              INSERT INTO cartesidentite (
                  DemandeID, 
                  NumeroCarteIdentite, 
                  DateEmission, 
                  DateExpiration, 
                  CodeQR, 
                  Statut
              ) VALUES (?, ?, ?, ?, ?, 'Active')
          ");
          $stmt->execute([$demandeID, $numeroCNI, $dateEmission, $dateExpiration, $qrImagePath]);

          $pdo->commit();

          return [
              'info' => $info,
              'numeroCNI' => $numeroCNI,
              'qrImagePath' => $qrImagePath,
              'dateEmission' => $dateEmission,
              'dateExpiration' => $dateExpiration
          ];

      } catch (Exception $e) {
          if ($pdo->inTransaction()) $pdo->rollBack();
          error_log("Erreur préparation CNI: " . $e->getMessage());
          return false;
      }
  }
function genererCNIHtml($cniData) {
    return '
        <!DOCTYPE html>
        <html>
        <head>
            <title>CNI ' . $cniData['numeroCNI'] . '</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
            <style>
                .cni-container { 
                    width: 85.6mm;
                    height: 54mm;
                    border: 1px solid #000;
                    margin: 20px auto;
                    position: relative;
                    padding: 10px;
                }
                .photo-zone {
                    position: absolute;
                    right: 10px;
                    top: 30px;
                    width: 35mm;
                    height: 45mm;
                }
                .qr-zone {
                    position: absolute;
                    bottom: 10px;
                    right: 10px;
                    width: 20mm;
                }
                @media print {
                    .no-print { display: none; }
                    body { padding: 0; margin: 0; }
                    .cni-container { page-break-inside: avoid; }
                }
            </style>
        </head>
        <body>
            <div class="container">
                <button onclick="window.print()" class="btn btn-primary no-print mb-3">Imprimer</button>
                <div class="cni-container">
                    <div class="header text-center">
                        <h6>RÉPUBLIQUE DU CAMEROUN</h6>
                        <small>CARTE NATIONALE D\'IDENTITÉ</small>
                    </div>
                    <img src="' . $cniData['info']['PhotoPath'] . '" class="photo-zone">
                    <img src="' . $cniData['qrImagePath'] . '" class="qr-zone">
                    <div class="info-section">
                        <p class="mb-1">N°: ' . $cniData['numeroCNI'] . '</p>
                        <p class="mb-1">Nom: ' . $cniData['info']['Nom'] . '</p>
                        <p class="mb-1">Prénom: ' . $cniData['info']['Prenom'] . '</p>
                        <p class="mb-1">Né(e) le: ' . $cniData['info']['DateNaissance'] . '</p>
                        <p class="mb-1">À: ' . $cniData['info']['LieuNaissance'] . '</p>
                        <p class="mb-1">Taille: ' . $cniData['info']['Taille'] . ' cm</p>
                        <p class="mb-1">Profession: ' . $cniData['info']['Profession'] . '</p>
                    </div>
                    <div class="footer">
                        <small>Émise le: ' . $cniData['dateEmission'] . '</small><br>
                        <small>Expire le: ' . $cniData['dateExpiration'] . '</small>
                    </div>
                </div>
            </div>
        </body>
        </html>
    ';
}

// Traitement de la génération de CNI
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['genererCNI'])) {
    $demandeID = $_POST['demandeID'];
    
    // Add error logging
    error_log("Attempting to generate CNI for demand ID: " . $demandeID);
    
    $resultatPreparation = preparerCNI($demandeID);
    
    if ($resultatPreparation) {
        $cniHtml = genererCNIHtml($resultatPreparation);
        echo "<script>
            const printWindow = window.open('', '_blank');
            printWindow.document.write(" . json_encode($cniHtml) . ");
            printWindow.document.close();
        </script>";
    } else {
        // Enhanced error message
        error_log("CNI preparation failed for demand ID: " . $demandeID);
        $erreur = "Une erreur est survenue lors de la préparation de la CNI. Vérifiez les logs pour plus de détails.";
    }
}?>
<div class="container mt-5">
    <h1>Suivi des demandes de CNI</h1>

    <?php if (isset($erreur)): ?>
        <div class="alert alert-danger"><?php echo $erreur; ?></div>
    <?php endif; ?>

    <table class="table">
        <thead>
        <tr>
            <th>ID</th>
            <th>Nom</th>
            <th>Prénom</th>
            <th>Date de naissance</th>
            <th>Date de soumission</th>
            <th>Action</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($demandes as $demande): ?>
            <tr>
                <td><?php echo $demande['DemandeID']; ?></td>
                <td><?php echo $demande['Nom']; ?></td>
                <td><?php echo $demande['Prenom']; ?></td>
                <td><?php echo $demande['DateNaissance']; ?></td>
                <td><?php echo $demande['DateSoumission']; ?></td>
                <td>
                    <button type="button" class="btn btn-primary" data-toggle="modal"
                            data-target="#previewModal<?php echo $demande['DemandeID']; ?>">
                        Prévisualiser CNI
                    </button>
                </td>
            </tr>

            <!-- Modal de prévisualisation -->
            <div class="modal fade" id="previewModal<?php echo $demande['DemandeID']; ?>" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Prévisualisation CNI</h5>
                            <button type="button" class="close" data-dismiss="modal">
                                <span>&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <img src="<?php echo $demande['PhotoPath']; ?>"
                                                 class="img-fluid" alt="Photo d'identité">
                                        </div>
                                        <div class="col-md-8">
                                            <h5>Informations personnelles</h5>
                                            <p><strong>Nom:</strong> <?php echo $demande['Nom']; ?></p>
                                            <p><strong>Prénom:</strong> <?php echo $demande['Prenom']; ?></p>
                                            <p><strong>Date de naissance:</strong> <?php echo $demande['DateNaissance']; ?></p>
                                            <p><strong>Adresse:</strong> <?php echo $demande['Adresse']; ?></p>
                                            <p><strong>Profession:</strong> <?php echo $demande['Profession']; ?></p>
                                            <p><strong>Taille:</strong> <?php echo $demande['Taille']; ?> cm</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
                            <form method="post">
                                <input type="hidden" name="demandeID" value="<?php echo $demande['DemandeID']; ?>">
                                <button type="submit" name="genererCNI" class="btn btn-primary">Élaborer la CNI</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>