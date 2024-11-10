<?php 
global $pdo; 
require "../../config/database.php"; 

if (isset($_GET['id'])) { 
    $requestId = intval($_GET['id']); 
    $query = "UPDATE demandes SET Statut = 'Approuvee' WHERE DemandeID = :id"; 
    $stmt = $pdo->prepare($query); 
    $stmt->execute(['id' => $requestId]); 

    if ($stmt->rowCount() > 0) { 
        header("Location: gestion_demande.php?status=approved"); 
    } else { 
        echo "Erreur : La demande n'existe pas ou n'a pas pu être approuvée."; 
    } 
} else { 
    echo "ID de la demande manquant."; 
} 
?>