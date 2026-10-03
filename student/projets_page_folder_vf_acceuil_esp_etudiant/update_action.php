<?php
session_start();
require_once('../connexion_folder_vf/db_config.php');

header('Content-Type: application/json');

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['APOGEE'])) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit();
}

// Récupérer les données POST
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['projectId'], $data['actionType'], $data['newCount'])) {
    echo json_encode(['success' => false, 'message' => 'Données manquantes']);
    exit();
}

$projectId = (int)$data['projectId'];
$actionType = $data['actionType'];
$newCount = (int)$data['newCount'];

// Valider le type d'action
$validActions = ['aimer', 'clap', 'support'];
if (!in_array($actionType, $validActions)) {
    echo json_encode(['success' => false, 'message' => 'Type d\'action invalide']);
    exit();
}

// Mettre à jour la base de données
try {
    $columnName = strtoupper($actionType); // AIMER, CLAP ou SUPPORT
    
    $query = "UPDATE project SET $columnName = ? WHERE ID_PROJECT = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$newCount, $projectId]);
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données: ' . $e->getMessage()]);
}