<?php
require_once '../base_donnees/db_config.php';

if (isset($_GET['id'])) {
    $project_id = (int) $_GET['id'];

    try {
        // Récupérer le nom du fichier
        $stmt = $conn->prepare("SELECT FICHIER FROM project WHERE ID_PROJECT = ?");
        $stmt->execute([$project_id]);
        $project = $stmt->fetch();

        if ($project && $project['FICHIER']) {
            $filepath = '../../uploads/projects/' . $project['FICHIER'];

            if (file_exists($filepath)) {
                // Envoyer les en-têtes pour forcer le téléchargement
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . basename($filepath) . '"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($filepath));
                readfile($filepath);
                exit;
            }
        }
    } catch (PDOException $e) {
        error_log("Erreur lors du téléchargement : " . $e->getMessage());
        // Rediriger ou afficher une erreur personnalisée ici si besoin
    }
}

// Redirection si le fichier est introuvable ou en cas d'erreur
header("Location: proj-adm.php");
exit;
?>
