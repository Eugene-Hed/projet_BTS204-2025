<?php
require_once '../../vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

if (!isset($_GET['demande'])) {
    exit('Demande non spécifiée');
}

$demandeID = $_GET['demande'];
$cniData = preparerCNI($demandeID);

if (!$cniData) {
    exit('Erreur lors de la génération de la CNI');
}

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isPhpEnabled', true);

$dompdf = new Dompdf($options);

$html = '
<html>
<head>
    <style>
        .cni-container {
            width: 85.6mm;
            height: 54mm;
            border: 1px solid #000;
            padding: 10px;
            position: relative;
            font-family: Arial, sans-serif;
        }
        .header {
            text-align: center;
            margin-bottom: 10px;
        }
        .photo {
            width: 35mm;
            position: absolute;
            right: 10px;
            top: 30px;
        }
        .qr-code {
            width: 20mm;
            position: absolute;
            bottom: 10px;
            right: 10px;
        }
        .info-block {
            margin-left: 5px;
            font-size: 8pt;
        }
    </style>
</head>
<body>
    <div class="cni-container">
        <div class="header">
            <h2>RÉPUBLIQUE DU CAMEROUN</h2>
            <h3>CARTE NATIONALE D\'IDENTITÉ</h3>
        </div>
        
        <img src="'.$cniData['info']['PhotoPath'].'" class="photo">
        <img src="'.$cniData['qrImagePath'].'" class="qr-code">
        
        <div class="info-block">
            <p>N°: '.$cniData['numeroCNI'].'</p>
            <p>Nom: '.$cniData['info']['Nom'].'</p>
            <p>Prénom: '.$cniData['info']['Prenom'].'</p>
            <p>Né(e) le: '.$cniData['info']['DateNaissance'].'</p>
            <p>À: '.$cniData['info']['LieuNaissance'].'</p>
            <p>Profession: '.$cniData['info']['Profession'].'</p>
            <p>Taille: '.$cniData['info']['Taille'].' cm</p>
        </div>
        
        <div class="footer">
            <p>Date d\'émission: '.$cniData['dateEmission'].'</p>
            <p>Date d\'expiration: '.$cniData['dateExpiration'].'</p>
        </div>
    </div>
</body>
</html>';

$dompdf->loadHtml($html);
$dompdf->setPaper([0, 0, 85.6, 54], 'landscape');
$dompdf->render();
$dompdf->stream("CNI_".$cniData['numeroCNI'].".pdf", ["Attachment" => false]);