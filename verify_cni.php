<?php

global $pdo;
require_once 'config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET' || !isset($_GET['cni'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Numéro CNI manquant']);
    exit;
}

$numeroCNI = $_GET['cni'];

try {
    $stmt = $pdo->prepare("
        SELECT ci.NumeroCarteIdentite, ci.DateEmission, ci.DateExpiration,
               u.Nom, u.Prenom, u.DateNaissance, u.NumeroTelephone,
               dc.LieuNaissance, dc.Profession
        FROM cartesidentite ci
        JOIN demandes d ON ci.DemandeID = d.DemandeID
        JOIN utilisateurs u ON d.UtilisateurID = u.UtilisateurID
        JOIN demande_cni_details dc ON d.DemandeID = dc.DemandeID
        WHERE ci.NumeroCarteIdentite = ?
    ");
    $stmt->execute([$numeroCNI]);
    $info = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$info) {
        http_response_code(404);
        echo json_encode(['error' => 'CNI non trouvée']);
        exit;
    }

    // Vérifier si la CNI est expirée
    $isExpired = strtotime($info['DateExpiration']) < time();

    $response = [
        'valid' => !$isExpired,
        'numero' => $info['NumeroCarteIdentite'],
        'nom' => $info['Nom'],
        'prenom' => $info['Prenom'],
        'dateNaissance' => $info['DateNaissance'],
        'lieuNaissance' => $info['LieuNaissance'],
        'profession' => $info['Profession'],
        'dateEmission' => $info['DateEmission'],
        'dateExpiration' => $info['DateExpiration']
    ];

    echo json_encode($response);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur serveur']);
    error_log($e->getMessage());
}