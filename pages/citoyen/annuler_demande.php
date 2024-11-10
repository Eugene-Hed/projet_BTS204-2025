<?php
// Démarrer la session si elle n'est pas déjà démarrée
global $pdo;
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Désactiver l'affichage des erreurs pour éviter de casser la réponse JSON
ini_set('display_errors', 0);
error_reporting(0);

// Définir l'en-tête JSON dès le début
header('Content-Type: application/json');

try {
    // Vérifier si le fichier de configuration existe
    if (!file_exists("../../config/database.php")) {
        throw new Exception("Erreur de configuration : fichier database.php introuvable");
    }

    require_once "../../config/database.php";

    if (!isset($_SESSION['user'])) {
        throw new Exception("Session utilisateur non valide");
    }

    if (!isset($_POST['demandeId'])) {
        throw new Exception("ID de demande manquant");
    }

    $userId = $_SESSION['user']['UtilisateurID'];
    $demandeId = intval($_POST['demandeId']);

    if (!$pdo) {
        throw new Exception("Erreur de connexion à la base de données");
    }

    $pdo->beginTransaction();

    // Vérification de la demande
    $checkQuery = $pdo->prepare("
        SELECT * FROM demandes 
        WHERE DemandeID = ? 
        AND UtilisateurID = ? 
        AND Statut = 'Soumise'
    ");
    $checkQuery->execute([$demandeId, $userId]);
    $demande = $checkQuery->fetch(PDO::FETCH_ASSOC);

    if (!$demande) {
        throw new Exception("La demande n'existe pas ou ne peut pas être annulée.");
    }

    // Mise à jour du statut
    $updateQuery = $pdo->prepare("
        UPDATE demandes 
        SET Statut = 'Annulee', 
            DateAchevement = NOW() 
        WHERE DemandeID = ?
    ");
    $updateSuccess = $updateQuery->execute([$demandeId]);

    // Ajout historique
    $historyQuery = $pdo->prepare("
        INSERT INTO historique_demandes 
        (DemandeID, AncienStatut, NouveauStatut, Commentaire, ModifiePar, DateModification) 
        VALUES (?, 'Soumise', 'Annulee', 'Demande annulée par l\'utilisateur', ?, NOW())
    ");
    $historySuccess = $historyQuery->execute([$demandeId, $userId]);

    // Ajout notification
    $notifQuery = $pdo->prepare("
        INSERT INTO notifications 
        (UtilisateurID, Contenu, TypeNotification, DateCreation) 
        VALUES (?, ?, 'Annulation', NOW())
    ");
    $notifSuccess = $notifQuery->execute([
        $userId,
        "Votre demande N°$demandeId a été annulée avec succès."
    ]);

    if ($updateSuccess && $historySuccess && $notifSuccess) {
        $pdo->commit();
        echo json_encode([
            'success' => true,
            'message' => "La demande N°$demandeId a été annulée avec succès."
        ]);
    } else {
        throw new Exception("Échec d'une des opérations");
    }
} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}