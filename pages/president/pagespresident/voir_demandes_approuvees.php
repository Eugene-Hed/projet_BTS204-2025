<?php
global $pdo;
require_once "../../config/database.php";
require_once "../../vendor/autoload.php";

use FPDF\FPDF;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

// Fonction pour générer et enregistrer le certificat
function genererCertificat($pdo, $demandeId) {
    // Récupérer les informations de la demande et de l'utilisateur
    $sql = "SELECT d.*, u.Nom, u.Prenom, u.DateNaissance, u.Adresse 
            FROM demandes d
            JOIN utilisateurs u ON d.UtilisateurID = u.UtilisateurID
            WHERE d.DemandeID = :demandeId AND d.TypeDemande = 'NATIONALITE' AND d.Statut = 'Approuvee'";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['demandeId' => $demandeId]);
    $demande = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$demande) {
        return false;
    }

    // Générer le contenu du certificat
    $pdf = new FPDF();
    $pdf->AddPage();

    // Ajouter un filigrane
    $pdf->SetFont('Arial', 'B', 50);
    $pdf->SetTextColor(255, 192, 203);
    $pdf->RotatedText(35, 190, 'Republique du Cameroun', 45);

    $pdf->SetFont('Arial', 'B', 16);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(0, 10, 'Certificat de Nationalité', 0, 1, 'C');
    $pdf->Ln(10);

    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 10, 'Nous, soussignés, certifions que :', 0, 1);
    $pdf->Cell(0, 10, $demande['Nom'] . ' ' . $demande['Prenom'], 0, 1);
    $pdf->Cell(0, 10, 'Né(e) le ' . $demande['DateNaissance'], 0, 1);
    $pdf->Cell(0, 10, 'Domicilié(e) à ' . $demande['Adresse'], 0, 1);
    $pdf->Cell(0, 10, 'Est de nationalité camerounaise.', 0, 1);
    $pdf->Ln(10);
    $pdf->Cell(0, 10, 'Fait à Yaoundé, le ' . date('d/m/Y'), 0, 1);
    $pdf->Cell(0, 10, 'Signature et cachet', 0, 1);

    // Générer un QR code pour l'authentification
    $qrCode = QrCode::create('https://verification.gov.cm/certificat/' . $demandeId);
    $writer = new PngWriter();
    $result = $writer->write($qrCode);
    $qrCodePath = '../../uploads/qrcodes/qr_' . $demandeId . '.png';
    $result->saveToFile($qrCodePath);

    // Ajouter le QR code au PDF
    $pdf->Image($qrCodePath, 10, 200, 30, 30);

    // Générer un nom de fichier unique
    $fileName = 'certificat_nationalite_' . $demandeId . '_' . date('YmdHis') . '.pdf';
    $uploadDir = '../../uploads/certificats/';

    // Créer le répertoire s'il n'existe pas
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $filePath = $uploadDir . $fileName;

    // Sauvegarder le PDF
    $pdf->Output('F', $filePath);

    // Enregistrer le certificat dans la base de données
    $sqlInsert = "INSERT INTO certificatsnationalite (DemandeID, NumeroCertificat, DateEmission, CheminPDF) 
                   VALUES (:demandeId, :numeroCertificat, NOW(), :cheminPDF)";
    $stmtInsert = $pdo->prepare($sqlInsert);
    $numeroCertificat = 'CN-' . str_pad($demandeId, 6, '0', STR_PAD_LEFT);
    $stmtInsert->execute([
        'demandeId' => $demandeId,
        'numeroCertificat' => $numeroCertificat,
        'cheminPDF' => $filePath
    ]);

    // Mettre à jour le statut de la demande
    $sqlUpdate = "UPDATE demandes SET Statut = 'Terminee' WHERE DemandeID = :demandeId";
    $stmtUpdate = $pdo->prepare($sqlUpdate);
    $stmtUpdate->execute(['demandeId' => $demandeId]);

    return $filePath;
}

// Traitement de la demande d'impression
if (isset($_GET['action']) && $_GET['action'] == 'imprimer' && isset($_GET['id'])) {
    $demandeId = intval($_GET['id']);
    $filePath = genererCertificat($pdo, $demandeId);

    if ($filePath) {
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . basename($filePath) . '"');
        readfile($filePath);
        exit();
    } else {
        echo "Erreur lors de la génération du certificat.";
        exit();
    }
}

// Récupération des demandes approuvées de certificat de nationalité
$sql = "SELECT d.DemandeID, d.DateSoumission, d.DateAchevement, u.Nom, u.Prenom 
        FROM demandes d
        JOIN utilisateurs u ON d.UtilisateurID = u.UtilisateurID
        WHERE d.TypeDemande = 'NATIONALITE' AND d.Statut IN ('Approuvee', 'Terminee')
        ORDER BY d.DateAchevement DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$demandes_approuvees = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-5">
    <h1>Demandes approuvées de certificat de nationalité</h1>
    <table class="table table-striped">
        <thead>
        <tr>
            <th>ID Demande</th>
            <th>Nom</th>
            <th>Prénom</th>
            <th>Date de soumission</th>
            <th>Date d'approbation</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($demandes_approuvees as $demande): ?>
            <tr>
                <td><?= htmlspecialchars($demande['DemandeID']) ?></td>
                <td><?= htmlspecialchars($demande['Nom']) ?></td>
                <td><?= htmlspecialchars($demande['Prenom']) ?></td>
                <td><?= htmlspecialchars($demande['DateSoumission']) ?></td>
                <td><?= htmlspecialchars($demande['DateAchevement']) ?></td>
                <td>
                    <a href="?action=imprimer&id=<?= $demande['DemandeID'] ?>" class="btn btn-success btn-sm" target="_blank">Imprimer Certificat</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>