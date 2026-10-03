<?php
session_start();
require_once '../base_donnees/pdo.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['ID_PROF'])) {
    header("HTTP/1.1 403 Forbidden");
    exit("Accès refusé");
}

$project_id = $_POST['project_id'] ?? 0;

if (empty($project_id)) {
    header("HTTP/1.1 400 Bad Request");
    exit("Paramètre manquant");
}

try {
    // Récupérer tous les fichiers du projet
    $stmt = $db->prepare("SELECT FICHIER, FILE1, FILE2, FILE3 FROM PROJECT WHERE ID_PROJECT = ?");
    $stmt->execute([$project_id]);
    $project = $stmt->fetch();

    if (!$project) {
        header("HTTP/1.1 404 Not Found");
        exit("Projet non trouvé");
    }

    // Chemin de base
    $base_dir = realpath(dirname(__FILE__) ). '/../upload/projets/';
    $files = [];
    
    // Vérifier chaque fichier
    foreach (['FICHIER', 'FILE1', 'FILE2', 'FILE3'] as $field) {
        if (!empty($project[$field])) {
            $file_path = $base_dir . basename($project[$field]);
            if (file_exists($file_path)) {
                $files[] = $file_path;
            }
        }
    }

    if (empty($files)) {
        header("HTTP/1.1 404 Not Found");
        exit("Aucun fichier disponible pour ce projet");
    }

    // Créer une archive ZIP temporaire
    $zip = new ZipArchive();
    $zip_name = tempnam(sys_get_temp_dir(), 'projet_') . '.zip';
    
    if ($zip->open($zip_name, ZipArchive::CREATE) !== TRUE) {
        header("HTTP/1.1 500 Internal Server Error");
        exit("Impossible de créer l'archive ZIP");
    }

    // Ajouter chaque fichier à l'archive
    foreach ($files as $file) {
        $zip->addFile($file, basename($file));
    }
    
    $zip->close();

    // Envoyer l'archive
    header('Content-Description: File Transfer');
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="projet_' . $project_id . '.zip"');
    header('Content-Length: ' . filesize($zip_name));
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');

    readfile($zip_name);
    
    // Supprimer le fichier temporaire
    unlink($zip_name);
    exit;

} catch (PDOException $e) {
    error_log("Erreur DB: " . $e->getMessage());
    header("HTTP/1.1 500 Internal Server Error");
    exit("Erreur de base de données");
}
?>