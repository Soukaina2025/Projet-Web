<?php
session_start();
require_once '../base_donnees/db_config.php';

if (!isset($_GET['id'])) {
    die("ID du projet non spécifié");
}

$project_id = (int)$_GET['id'];

try {
    // Récupérer les détails du projet avec les informations des étudiants et des professeurs
    $query = "SELECT p.*, s.NOM as student_nom, s.PRENOM as student_prenom, s.FILIERE, 
              pr.NOM as prof_nom, pr.PRENOM as prof_prenom 
              FROM project p 
              JOIN student s ON p.APOGEE = s.APOGEE 
              JOIN prof pr ON p.ID_PROF = pr.ID_PROF 
              WHERE p.ID_PROJECT = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$project_id]);
    $project = $stmt->fetch();
    
    if (!$project) {
        die("Projet non trouvé");
    }


    function limitChars($string, $limit, $suffix = '...') {
    if (strlen($string) > $limit) {
        return substr($string, 0, $limit) . $suffix;
    }
    return $string;
}
    
    // Afficher les détails du projet
    echo '<div class="row">';
    echo '<div class="col-md-6">';
    echo '<h5>Informations de base</h5>';
    echo '<table class="table table-sm">';
    echo '<tr><th>Titre</th><td>' . htmlspecialchars(limitChars($project['NOM'],20)) . '</td></tr>';
    echo '<tr><th>Type</th><td>' . htmlspecialchars($project['TYPE']) . '</td></tr>';
    echo '<tr><th>Date de soumission</th><td>' . date('d/m/Y', strtotime($project['DATE_DEP'])) . '</td></tr>';
    echo '</table>';
    echo '</div>';
    
    echo '<div class="col-md-6">';
    echo '<h5>Participants</h5>';
    echo '<table class="table table-sm">';
    echo '<tr><th>Étudiant</th><td>' . htmlspecialchars($project['student_prenom']) . ' ' . htmlspecialchars($project['student_nom']) . '</td></tr>';
    echo '<tr><th>Apogée</th><td>' . htmlspecialchars($project['APOGEE']) . '</td></tr>';
    echo '<tr><th>Filière</th><td>' . htmlspecialchars($project['FILIERE']) . '</td></tr>';
    echo '<tr><th>Encadrant</th><td>' . htmlspecialchars($project['prof_prenom'] . ' ' . $project['prof_nom']) . '</td></tr>';
    echo '</table>';
    echo '</div>';
    echo '</div>';

    /*
    
    // Afficher les livrables disponibles
    echo '<div class="mt-4">';
    echo '<h5>Livrables</h5>';
    echo '<div class="d-flex flex-wrap">';
    
    // Liste des champs de fichiers dans la table project
    $file_fields = [
        'RAPPORT' => 'Rapport final',
        'PRESENTATION' => 'Présentation',
        'SOURCES' => 'Code source',
        'AUTRE_FICHIER' => 'Autre fichier'
    ];
    
    $has_files = false;
    
    foreach ($file_fields as $field => $label) {
        if (!empty($project[$field])) {
            $has_files = true;
            $file_name = basename($project[$field]);
            echo '<a href="proj-adm.php?download_file=' . $field . '&project_id=' . $project_id . '" class="file-download-btn">';
            echo '<i class="bi bi-file-earmark-arrow-down"></i> ' . $label;
            echo '</a>';
        }
    }
    
    if (!$has_files) {
        echo '<p class="text-muted">Aucun livrable disponible pour ce projet.</p>';
    }*/
    
    echo '</div>';
    echo '</div>';
    
} catch (PDOException $e) {
    echo '<div class="alert alert-danger">Erreur lors de la récupération des détails du projet: ' . htmlspecialchars($e->getMessage()) . '</div>';
}
?>