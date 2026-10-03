<?php
require_once('../base_donnees/pdo.php');
session_start();

header('Content-Type: application/json');

// Vérifier si l'utilisateur est admin
if (!isset($_SESSION['ID_ADM'])) {
    echo json_encode(['success' => false, 'error' => 'Accès non autorisé']);
    exit;
}

// Vérifier les paramètres
if (!isset($_POST['id']) || !isset($_POST['role'])) {
    echo json_encode(['success' => false, 'error' => 'Paramètres manquants']);
    exit;
}

$id = $_POST['id'];
$role = $_POST['role'];

try {
    // Supprimer en fonction du rôle
    if ($role === 'prof') {
        $stmt = $db->prepare("DELETE FROM prof WHERE ID_PROF = ?");
    } elseif ($role === 'student') {
        $stmt = $db->prepare("DELETE FROM student WHERE APOGEE = ?");
    } elseif ($role === 'adm') {
        // Empêcher la suppression des admins (ou ajouter une vérification supplémentaire)
        echo json_encode(['success' => false, 'error' => 'Suppression des admins non autorisée']);
        exit;
    } else {
        echo json_encode(['success' => false, 'error' => 'Rôle invalide']);
        exit;
    }

    $stmt->execute([$id]);

    if ($stmt->rowCount() > 0) {
        $_SESSION['success'] = "Utilisateur supprimé avec succès";
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Utilisateur non trouvé']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Erreur de base de données: ' . $e->getMessage()]);
}
?>