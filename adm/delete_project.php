<?php
require_once '../base_donnees/db_config.php';

if (isset($_GET['id'])) {
    $project_id = (int) $_GET['id'];

    try {
        // Récupérer les informations du projet
        $stmt = $conn->prepare("SELECT FICHIER, FILE1, FILE2, FILE3, IMG FROM project WHERE ID_PROJECT = ?");
        $stmt->execute([$project_id]);
        $project = $stmt->fetch();

        if ($project) {
            // Supprimer les fichiers associés
            $files = [$project['FICHIER'], $project['FILE1'], $project['FILE2'], $project['FILE3'], $project['IMG']];
            foreach ($files as $file) {
                $filePath = '../../uploads/projects/' . $file;
                if ($file && file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            // Supprimer le projet de la base de données
            $delete_stmt = $conn->prepare("DELETE FROM project WHERE ID_PROJECT = ?");
            $delete_stmt->execute([$project_id]);
        }

    } catch (PDOException $e) {
        /*error_log("Erreur lors de la suppression du projet : " . $e->getMessage());
        // Optionnel : rediriger vers une page d'erreur ou afficher un message*/
    }
}

// Rediriger après traitement
header("Location: proj-adm.php");
exit;
?>
