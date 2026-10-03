<?php
session_start();
require_once '../base_donnees/pdo.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['ID_PROF'])) {
    header("HTTP/1.1 403 Forbidden");
    exit("Accès refusé");
}

// Récupérer les paramètres
$project_id = $_GET['project_id'] ?? 0;
$file_field = $_GET['field'] ?? '';

// Liste des champs de fichier autorisés
$allowed_fields = ['FICHIER', 'FILE1', 'FILE2', 'FILE3'];

if (empty($project_id) || !in_array($file_field, $allowed_fields)) {
    header("HTTP/1.1 400 Bad Request");
    exit("Paramètres invalides");
}

try {
    // Récupérer le nom du fichier depuis la base de données
    $stmt = $db->prepare("SELECT $file_field FROM PROJECT WHERE ID_PROJECT = ?");
    $stmt->execute([$project_id]);
    $file_name = $stmt->fetchColumn();

    if (empty($file_name)) {
        header("HTTP/1.1 404 Not Found");
        exit("Aucun fichier enregistré pour ce champ");
    }

    // Chemin complet du fichier - MODIFIEZ CE CHEMIN SELON VOTRE STRUCTURE
    $base_dir = realpath(dirname(__FILE__) ). '/../upload/projets/';
    $file_path = $base_dir . basename($file_name);

    // DEBUG - Afficher le chemin recherché
    error_log("Recherche du fichier: " . $file_path);

    if (!file_exists($file_path)) {
        // Essayer avec juste le nom du fichier si le chemin complet est stocké en base
        $file_path = $base_dir . basename($file_name);
        error_log("Essai avec chemin simplifié: " . $file_path);
        
        if (!file_exists($file_path)) {
            header("HTTP/1.1 404 Not Found");
            error_log("Fichier introuvable: " . $file_path);
            exit("Fichier non trouvé sur le serveur");
        }
    }

    // Désactiver la mise en mémoire tampon
    if (ob_get_level()) ob_end_clean();

    // En-têtes pour le téléchargement
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
    header('Content-Length: ' . filesize($file_path));
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');

    // Lire et envoyer le fichier
    readfile($file_path);
    exit;

} catch (PDOException $e) {
    error_log("Erreur DB: " . $e->getMessage());
    header("HTTP/1.1 500 Internal Server Error");
    exit("Erreur de base de données");
}
?>