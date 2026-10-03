<?php
require_once('../base_donnees/db_config.php');
session_start();

// Initialiser les variables pour éviter les erreurs
$periodedates = $_POST['annee_scolaire'] ?? '2024-09-18|2025-09-17';
$date_debut = null;
$date_fin = null;

$periode = $_POST['periode'] ?? "all";
$filiere = $_POST['filiere'] ?? "all";
$type_projet = $_POST['type_projet'] ?? "all";

// Traitement de l'année scolaire
if (!empty($_POST['annee_scolaire'])) {
    $periode_dates = explode('|', $_POST['annee_scolaire']);
    $date_debut = $periode_dates[0]; 
    $date_fin = $periode_dates[1];  
}

// Construction de la requête SQL
$rqt = "SELECT * FROM project p
        JOIN student s ON s.APOGEE=p.APOGEE  
        WHERE 1=1";
$params = [];

if ($filiere !== "all") {
    $rqt .= " AND FILIERE = :fil";
    $params[':fil'] = $filiere;
}

if (!empty($date_debut) && !empty($date_fin)) {
    $rqt .= " AND DATE_DEP BETWEEN :debut AND :fin";
    $params[':debut'] = $date_debut;
    $params[':fin'] = $date_fin;
}

if ($periode !== "all") {
    $rqt .= " AND SEMESTRE = :sem";
    $params[':sem'] = $periode;
}

if ($type_projet !== "all") {
    $rqt .= " AND TYPE = :typ";
    $params[':typ'] = $type_projet;
}

// Exécution de la requête
$rstmt = $conn->prepare($rqt);
$rstmt->execute($params);
$result = $rstmt->fetchAll(PDO::FETCH_ASSOC);

// Préparation des données pour Chart.js
$projects_by_month = array_fill_keys(['Sept', 'Oct', 'Nov', 'Déc', 'Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin'], 0);
$projects_by_filiere = ['Génie Informatique' => 0, 'Génie Industriel' => 0, 'Génie BIEE' => 0, 'Génie Electrique' => 0, 'Génie Mécatronique' => 0, 'Génie RST' => 0];
$projects_by_type = ['stage pfe' => 0, 'stage pfa' => 0, 'stage d\'observation' => 0, 'module' => 0];


foreach ($result as $row) {
    $mois = date('M', strtotime($row['DATE_DEP']));
    $mois_fr = [
        'Jan' => 'Jan', 'Feb' => 'Fév', 'Mar' => 'Mar', 'Apr' => 'Avr',
        'May' => 'Mai', 'Jun' => 'Juin', 'Sep' => 'Sept', 'Oct' => 'Oct',
        'Nov' => 'Nov', 'Dec' => 'Déc'
    ];
    $mois_francais = $mois_fr[$mois] ?? null;
    if ($mois_francais && isset($projects_by_month[$mois_francais])) {
        $projects_by_month[$mois_francais]++;
    }

    if (isset($projects_by_filiere[$row['FILIERE']])) {
        $projects_by_filiere[$row['FILIERE']]++;
    }

    if (isset($projects_by_type[$row['TYPE']])) {
        $projects_by_type[$row['TYPE']]++;
    }
}

// Calcul des pourcentages par filière
$total_projects = array_sum($projects_by_filiere);

if ($total_projects != 0) {
    $pourcent_info = round(($projects_by_filiere['Génie Informatique'] / $total_projects) * 100);
    $pourcent_mec = round(($projects_by_filiere['Génie Mécatronique'] / $total_projects) * 100);
    $pourcent_elec = round(($projects_by_filiere['Génie Electrique'] / $total_projects) * 100);
    $pourcent_rst = round(($projects_by_filiere['Génie RST'] / $total_projects) * 100);
    $pourcent_indus = round(($projects_by_filiere['Génie Industriel'] / $total_projects) * 100);
    $pourcent_civil = round(($projects_by_filiere['Génie BIEE'] / $total_projects) * 100);
} else {
    $pourcent_info = $pourcent_indus = $pourcent_civil = $pourcent_rst = $pourcent_elec = $pourcent_mec = 0;
}

// Données pour le tableau des encadrants
$RQTN = "SELECT 
    e.NOM AS encadrant,
    COUNT(p.ID_PROJECT) AS total_projets,
    COUNT(CASE WHEN p.STATUT_STU = 'validé' THEN 1 END) AS projets_valides,
    ROUND(COUNT(CASE WHEN p.STATUT_STU = 'validé' THEN 1 END) / COUNT(p.ID_PROJECT) * 100, 2) AS taux_validation,
    ROUND(AVG(p.NOTE), 2) AS moyenne_evaluation
FROM 
    project p
JOIN 
    prof e ON p.ID_PROF = e.ID_PROF
GROUP BY 
    e.ID_PROF, e.NOM
ORDER BY 
    total_projets DESC";
$stmtn = $conn->prepare($RQTN);
$stmtn->execute();
$rsn = $stmtn->fetchAll(PDO::FETCH_ASSOC);

// Données pour les types de projets
$pr = "SELECT 
    p.TYPE,
    COUNT(p.ID_PROJECT) AS total_projets
FROM 
    project p
GROUP BY 
    p.TYPE
ORDER BY 
    total_projets DESC";
$stmtpr = $conn->prepare($pr);
$stmtpr->execute();
$prs = $stmtpr->fetchAll(PDO::FETCH_ASSOC);

// Données pour l'évolution mensuelle par encadrant
$req_mensuelle_encadrant = "
SELECT 
    e.NOM AS encadrant,
    DATE_FORMAT(p.DATE_DEP, '%Y-%m') AS mois,
    COUNT(*) AS nb_projets
FROM 
    project p
JOIN 
    prof e ON p.ID_PROF = e.ID_PROF
WHERE 
    p.DATE_DEP BETWEEN :debut AND :fin
GROUP BY 
    e.ID_PROF, mois
ORDER BY 
    mois ASC";
$stmt_mensuel = $conn->prepare($req_mensuelle_encadrant);
$stmt_mensuel->execute([':debut' => $date_debut, ':fin' => $date_fin]);
$encadrants_data = $stmt_mensuel->fetchAll(PDO::FETCH_ASSOC);

$mois_labels = [];
$encadrants_projets = [];

foreach ($encadrants_data as $row) {
    $mois = $row['mois'];
    $nom = $row['encadrant'];
    $nb = $row['nb_projets'];

    if (!in_array($mois, $mois_labels)) {
        $mois_labels[] = $mois;
    }

    if (!isset($encadrants_projets[$nom])) {
        $encadrants_projets[$nom] = [];
    }

    $encadrants_projets[$nom][$mois] = $nb;
}

sort($mois_labels);

// Préparer les datasets pour Chart.js
$datasets = [];
$colors = [
    '#FFB3BA', '#FFDFBA', '#FFFFBA', '#BAFFC9', 
    '#BAE1FF', '#D0BAFF', '#FFBAF2', '#B5EAD7', '#C7CEEA'
];
$i = 0;

foreach ($encadrants_projets as $encadrant => $mois_data) {
    $data_points = [];
    foreach ($mois_labels as $mois) {
        $data_points[] = $mois_data[$mois] ?? 0;
    }

    $datasets[] = [
        'label' => $encadrant,
        'data' => $data_points,
        'borderColor' => $colors[$i % count($colors)],
        'fill' => false
    ];
    $i++;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ENSA Projects - Statistiques</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --ensa-primary: #2C3E50;
            --ensa-secondary: #E74C3C;
            --ensa-light: #ECF0F1;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
        }
        .sidebar {
            background-color: var(--ensa-primary);
            color: white;
            height: 100vh;
            position: fixed;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            border: none;
        }
        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0,0,0,0.1);
            font-weight: 600;
        }
        .stat-card {
            border-left: 4px solid var(--ensa-secondary);
        }
        .nav-link {
            color: rgba(255,255,255,0.8);
        }
        .nav-link:hover, .nav-link.active {
            color: white;
            background-color: rgba(255,255,255,0.1);
        }
        .btn-ensa {
            background-color: var(--ensa-secondary);
            color: white;
        }
        .btn-ensa:hover {
            background-color: #c0392b;
            color: white;
        }
        .table th {
            background-color: var(--ensa-light);
        }
        .chart-container {
            position: relative;
            height: 259px;
            width: 100%;

        }
        @media (max-width:768px) {
            .donut_chart {
                    justify-content: center;
                    display: flex;
                    
            }
            
            
        }
        .chart-tab {
            display: none;
        }
        .chart-tab.active {
            display: block;
        }
        .btn-group .btn.active {
            background-color: #bfdbfe;
            color: #1e3a8a;
        }


        
        .avatar-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 18px;
}

/* Style pour le dropdown */
.dropdown-toggle {
    cursor: pointer;
    transition: all 0.3s ease;
}

.dropdown-toggle:hover {
    opacity: 0.8;
}

.dropdown-menu {
    min-width: 200px;
    border: none;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.dropdown-item {
    padding: 8px 15px;
    transition: all 0.2s;
}

.dropdown-item:hover {
    background-color: #f8f9fa;
    color: #3f51b5;
}


.sidebar{
    padding-right: 2rem !important;
    height: 100%;
}


        .bg_proj{
            background-color: #bfdbfe;
            color: #1e3a8a;
        }

        .fa-chevron-down:before {
    


    content: none;
    
}




    </style>




<style>
        :root {
            --ensa-primary: #0b2341;
            --ensa-secondary: #E74C3C;
            --ensa-light: #ECF0F1;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
        }
        .sidebar {
            background-color: var(--ensa-primary);
            color: white;
            height: 100vh;
            position: fixed;
        }
        /* Style pour les initiales quand pas d'image */
        .initials-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #002c84;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 5px;
            cursor: pointer;
            border: 2px solid #002c84;
        }

        .avatar-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 18px;
}

/* Style pour le dropdown */
.dropdown-toggle {
    cursor: pointer;
    transition: all 0.3s ease;
}

.dropdown-toggle:hover {
    opacity: 0.8;
}

.dropdown-menu {
    min-width: 200px;
    border: none;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.dropdown-item {
    padding: 8px 15px;
    transition: all 0.2s;
}

.dropdown-item:hover {
    background-color: #f8f9fa;
    color: #3f51b5;
}


.sidebar{
    padding-right: 2rem !important;
    height: 100%;
    background-color: #2C3E50;
}

        .sidebar {
            color: white;
            height: 100vh;
            position: fixed;
            width: 250px;
            padding: 20px;
            transition: all 0.3s;
        }
/*
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            border: none;
        }*/
        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0,0,0,0.1);
            font-weight: 600;
        }
        .stat-card {
            border-left: 4px solid rgb(248, 175, 108);
        }
        .nav-link {
            color: rgba(255,255,255,0.8);
            border-radius: 5px;
            margin-bottom: 5px;        }
        .nav-link:hover, .nav-link.active {
            color: white;
            background-color: rgba(255,255,255,0.1);
        }
        .btn-ensa {
            background-color: var(--ensa-secondary);
            color: white;
        }
        .btn-ensa:hover {
            background-color: #c0392b;
            color: white;
        }
        .table th {
            background-color: var(--ensa-light);
        }


        .bg_attente{
            background-color: #fff3cd;
            color: #856404;

        }

        .bg_valide{
            background-color: #d4edda;
            color: #155724;

        }


        .bg_refuse{
            background-color: #f8d7da;
            color: #721c24;

        }


            .bg_proj{
            background-color: #bfdbfe;
            color: #1e3a8a;
        }
/*
        .bg_proj{
            background-color: #e0f2fe;
            color: #0284c7;
        }*/

.fa-chevron-down:before {
    


    content: none;
    
}























/* Styles pour les cartes avec animation au hover */
.stat-card {
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1);
}


/* Styles responsives pour toute la page */
@media (max-width: 1199.98px) {
    /*
    .sidebar {
        width: 220px;
    }*/
    
    .main-content {
        margin-left: 220px;
    }
}

@media (max-width: 991.98px) {
    /*
    .sidebar {
        width: 70px;
        overflow: hidden;
        padding-right: 0.5rem !important;
    }
    .sidebar h4, .sidebar hr, .sidebar .nav-link span {
        display: none;
    }
    .sidebar .nav-link {
        text-align: center;
        padding: 0.75rem 0.25rem;
    }
    .sidebar .nav-link i {
        margin-right: 0;
        font-size: 1.25rem;
    }*/

        .sidebar .nav-link span {
        display: none;
    }
    .main-content {
        margin-left: 70px;
    }
    .avatar-circle {
        width: 30px;
        height: 30px;
        font-size: 14px;
    }
}

@media (max-width: 767.98px) {

    .bar_chart{
        display: flex;
        justify-content: center;
    }

    .text_container{

    display: flex;
    flex-direction: column;
    align-items: center;
}
    .sidebar {
        display: none;
    }
    .main-content {
        margin-left: 0;
    }
    .row {
        flex-direction: column;
    }
    .col-md-8, .col-md-4 {
        width: 100%;
    }
    .card {
        margin-bottom: 15px;
    }
    .stat-card .card-body h3 {
        font-size: 1.5rem;
    }
    .table-responsive {
        overflow-x: auto;
    }
    table {
        font-size: 0.9rem;
    }
    .dropdown-menu {
        position: absolute !important;
    }
}

@media (max-width: 575.98px) {
    /*
    .sidebar {
        display: none;
    }*/
    /*
    .main-content {
        margin-left: 0;
        padding: 15px;
    }*/
    .d-flex.justify-content-between.align-items-center.mb-4 {
        flex-direction: column;
        align-items: flex-start;
    }
    .d-flex.justify-content-between.align-items-center.mb-4 > div {
        margin-top: 10px;
    }
    .stat-card .card-body {
        padding: 1rem;
    }
    .stat-card .card-body h6 {
        font-size: 0.9rem;
    }
    .stat-card .card-body h3 {
        font-size: 1.3rem;
    }
    .card-header {
        font-size: 1rem;
    }
    .dropdown-toggle > div:last-child {
        display: none;
    }
}



@media (max-width: 1200px) {
    .first_container {
        display: block !important; /* ou display: initial; selon le besoin */
    }
}


@media (min-width: 1200px) {
    .menu_button {
        display: none !important; /* ou display: initial; selon le besoin */
    }
}

@media (max-width: 363px) {
    .btn-ensa,.btn-group {
        font-size: 13px;
    }
}








/* Améliorations globales pour les cartes */

.card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    /*border: none;*/
    border-radius: 10px;
    overflow: hidden;
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1);
}

.card-header {
    background-color: white;
    border-bottom: 1px solid rgba(0,0,0,0.1);
    font-weight: 600;
    padding: 1rem 1.25rem;
}

.card-body {
    padding: 1.25rem;
}*/

/* Amélioration de la lisibilité des tableaux */
.table th {
    white-space: nowrap;
    background-color: #f8f9fa;
}

.table td {
    vertical-align: middle;
}

/* Style pour les badges */
.badge {
    font-weight: 500;
    padding: 0.35em 0.65em;
    font-size: 0.85em;
}



@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Amélioration du formulaire de filtre */
.form-select {
    border-radius: 20px;
    border: 1px solid #dee2e6;
    padding: 0.375rem 1.75rem 0.375rem 0.75rem;
    cursor: pointer;
}

/* Style pour le bouton d'export */
.btn-success {

    transition: all 0.3s ease;
}

.btn-success:hover {
    background-color: #218838;
    border-color: #1e7e34;
    transform: translateY(-2px);
}

















        .sidebar {
            color: white;
            height: 100vh;
            position: fixed;
        }
        .main-content {
        margin-left: 110px;
            padding: 20px;
        }
        /*
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            border: none;
        }*/
        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0,0,0,0.1);
            font-weight: 600;
        }
        .nav-link {
            color: rgba(255,255,255,0.8);
        }
        .nav-link:hover, .nav-link.active {
            color: white;
            background-color: rgba(255,255,255,0.1);
        }
        .btn-ensa {
            background-color: var(--ensa-secondary);
            color: white;
        }
        .btn-ensa:hover {
            background-color: #c0392b;
            color: white;
        }
        .table th {
            background-color: var(--ensa-light);
        }
        .project-type-badge {
            font-size: 0.75rem;
            padding: 0.35em 0.65em;
        }
        .filter-section {
            background-color: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .status-badge {
            font-size: 0.75rem;
            padding: 0.5em 0.75em;
            border-radius: 12px;
        }
        .progress-thin {
            height: 6px;
        }
        #exportExcel:hover, #exportPdf:hover {
            transform: translateY(-2px);
            transition: transform 0.2s ease;
        }
        .file-download-btn {
            display: inline-block;
            margin: 5px;
            padding: 8px 15px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            color: #495057;
            text-decoration: none;
            transition: all 0.2s;
        }
        .file-download-btn:hover {
            background-color: #e9ecef;
            color: #212529;
        }
        .file-download-btn i {
            margin-right: 5px;
        }

        .pagination .page-item.active .page-link {
            background-color: #002c84;
            border-color: #002c84;
            color: white;
        }
        .pagination .page-link {
            color: #002c84;
        }
        .pagination .page-link:hover {
            color: #002c84;
            background-color: #e9ecef;
        }






        /* Style pour l'avatar circulaire */
.avatar-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 18px;
}

/* Style pour le dropdown */
.dropdown-toggle {
    cursor: pointer;
    transition: all 0.3s ease;
}

.dropdown-toggle:hover {
    opacity: 0.8;
}

.dropdown-menu {
    min-width: 200px;
    border: none;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.dropdown-item {
    padding: 8px 15px;
    transition: all 0.2s;
}

.dropdown-item:hover {
    background-color: #f8f9fa;
    color: #3f51b5;
}


.sidebar{
    padding-right: 2rem !important;
    height: 100%;
}







/* Responsive Design */
@media (max-width: 1200px) {
    /*.sidebar {
        width: 220px;
    }*/
    /*
    .main-content {
        margin-left: 220px;
    }*/
}

@media (max-width: 992px) {
    /*
    .sidebar {
        /*width: 80px;*/
        /*overflow: hidden;*/
        /*padding-right: 0.5rem !important;
    }
    .sidebar .nav-link {
        /*padding: 0.5rem;
        text-align: center;
    }*/
    .sidebar .nav-link span {
        display: none;
    }
    /*
    .sidebar .nav-link i {
        /*margin-right: 0;
        font-size: 1.2rem;
    }*/
    .sidebar h4 {
        font-size: 1rem;
        text-align: center;
    }
    /*
    .main-content {
        margin-left: 80px;
    }*/
    .filter-section .row {
        flex-direction: column;
    }
    .filter-section .col-md-3 {
        width: 100%;
        margin-bottom: 10px;
    }
}

@media (max-width: 768px) {
    .sidebar {
        display: none;
    }

    .stat-card {
    height: 170px;}
    /*
    .main-content {
        margin-left: 0;
    }*/
    /*
    .table-responsive {
        overflow-x: auto;
    }*/
    .table th, .table td {
        white-space: nowrap;
    }
    .card-header {
        flex-direction: column;
        align-items: flex-start;
    }
    .card-header form {
        width: 100%;
        margin-top: 10px;
    }
    .d-flex.justify-content-between.align-items-center.mb-4 {
        flex-direction: column;
        align-items: flex-start;
    }
    .d-flex.justify-content-between.align-items-center.mb-4 > div {
        margin-top: 10px;
    }
}

@media (max-width: 576px) {
    .pagination {
        flex-wrap: wrap;
    }
    .page-item {
        margin-bottom: 5px;
    }
    .modal-dialog {
        margin: 0.5rem;
    }
    .avatar-circle {
        width: 30px;
        height: 30px;
        font-size: 14px;
    }
    .dropdown-menu {
        min-width: 180px;
    }
}



.table-responsive {
    overflow-x: auto;

  scrollbar-width: auto; /* Firefox */
}

.table-responsive::-webkit-scrollbar {
  display: block;  /* Chrome, Safari */
  height: 8px;
}

.table-responsive::-webkit-scrollbar-thumb {
  background-color: #888;
  border-radius: 4px;
}

.table-responsive::-webkit-scrollbar-track {
  background-color: #f1f1f1;
}





@media (max-width: 1205px) {
    .sidebar {
        position: fixed;
        left: -250px;
        top: 0;
        bottom: 0;
        z-index: 1000;
        transition: left 0.3s ease;
        display: block !important; /* S'assurer qu'il est toujours en DOM */
    }
    .sidebar.show {
        left: 0;
    }
    .main-content {
        margin-left: 0;
    }
    .menu_button{
        display:block;
    }
    /*
    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }*/
    .table th, .table td {
        white-space: nowrap;
    }
}


@media (max-width: 1200px) {
    .first_container {
        display: block !important; /* ou display: initial; selon le besoin */
    }
}


@media (min-width: 1200px) {
    .menu_button {
        display: none !important; /* ou display: initial; selon le besoin */
    }
}

@media (max-width: 363px) {
    .btn-ensa,.btn-group {
        font-size: 13px;
    }
}






    </style>
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <div class="sidebar w-250 px-3 py-4">
            <h4 class="text-center mb-4 " style="display: flex; flex-direction:column; align-items:center;">
                <img src="../images/LOGO-ENSA.png" alt="ENSA LOGO" width="140px" style="margin-bottom:19px; ">
                 ENSA Projects
            </h4>
            <hr>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link " href="adm.php">
                        <i class="bi bi-speedometer2 me-2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="gestion-user.php">
                        <i class="bi bi-people me-2"></i> Gestion Utilisateurs
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="proj-adm.php">
                        <i class="bi bi-folder me-2"></i> Tous les Projets
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="statis.php">
                        <i class="bi bi-bar-chart me-2"></i> Statistiques
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="parametre.php">
                        <i class="bi bi-gear me-2"></i> Paramètres
                    </a>
                </li>
            </ul>
                <div class="position-absolute bottom-0 mb-4 ">
    <div class="dropdown">
        <div class="d-flex align-items-center dropdown-toggle" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
            <div class="avatar-circle bg-primary text-white me-2" style="background-color: #3f51b5;">
                <?php 
                $initial = strtoupper(substr($_SESSION['PRENOM'], 0, 1));
                echo $initial;
                ?>
            </div>
            <div>
                <div class="fw-bold"><?php echo $_SESSION['NOM'] . ' ' . $_SESSION['PRENOM']; ?></div>
                <small>Administrateur</small>
            </div>
            <i class="fas fa-chevron-down ms-2"></i>
        </div>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton">
            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Déconnexion</a></li>
        </ul>
            </div>
        </div>
        </div>


            <div class="main-content flex-grow-1">
            <button class="btn btn-ensa  mb-3 menu_button" id="sidebarToggle">
        <i class="bi bi-list"></i> Menu
    </button>
        <!-- Main Content - Page Statistiques -->
        <div class="main-content flex-grow-1">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="mb-0"><i class="bi bi-bar-chart me-2"></i> Tableau de Statistiques</h4>
                <div>
                    <form action="#" method="post">
                        <select class="form-select form-select-sm me-2 d-inline-block w-auto" name="annee_scolaire">
                            <option value="2024-09-18|2025-09-17" <?=$periodedates=="2024-09-18|2025-09-17" ? 'selected' : ''?>>Année 2024-2025</option>
                            <option value="2023-09-18|2024-09-17" <?=$periodedates=="2023-09-18|2024-09-17" ? 'selected' : ''?>>Année 2023-2024</option>
                            <option value="2022-09-18|2023-09-17" <?=$periodedates=="2022-09-18|2023-09-17" ? 'selected' : ''?>>Année 2022-2023</option>
                            <option value="2021-09-18|2022-09-17" <?=$periodedates=="2021-09-18|2022-09-17" ? 'selected' : ''?>>Année 2021-2022</option>
                        </select>
                </div>
            </div>

            <!-- Filtres -->
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Filière</label>
                    <select class="form-select" name="filiere">
                        <option value="all">Toutes les filières</option>
                        <option value="Génie Informatique" <?= $filiere === 'Génie Informatique' ? 'selected' : '' ?>>Génie Informatique</option>
                        <option value="Génie Industriel" <?= $filiere === 'Génie Industriel' ? 'selected' : '' ?>>Génie Industriel</option>
                        <option value="Génie BIEE" <?= $filiere === 'Génie BIEE' ? 'selected' : '' ?>>Génie Civil</option>
                        <option value="Génie Electrique" <?= $filiere === 'Génie Electrique' ? 'selected' : '' ?>>Génie Electrique</option>
                        <option value="Génie RST" <?= $filiere === 'Génie RST' ? 'selected' : '' ?>>Génie Réseaux et Telecom</option>
                        <option value="Génie Mécatronique" <?= $filiere === 'Génie Mécatronique' ? 'selected' : '' ?>>Génie Mécatronique</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Type de projet</label>
                    <select class="form-select" name="type_projet">
                        <option value="all">Tous les types</option>
                        <option value="stage pfe" <?= $type_projet=='stage pfe' ? 'selected' : '' ?>>Stage PFE</option>
                        <option value="stage pfa" <?=$type_projet=='stage pfa' ? "selected" : '' ?>>Stage PFA</option>
                        <option value="stage d'observation" <?=$type_projet=='stage d\'observation' ? "selected" : '' ?>>Stage d'observation</option>
                        <option value="module" <?=$type_projet=='module' ? "selected" : '' ?>>Projet de module</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Période</label>
                    <select class="form-select" name="periode">
                        <option value="all" <?=$periode=='all' ? "selected" : '' ?>>Toute l'année</option>
                        <option value="s1" <?=$periode=='s1' ? "selected" : '' ?>>Semestre 1</option>
                        <option value="s2" <?=$periode=='s2' ? "selected" : '' ?>>Semestre 2</option>
                        <option value="s3" <?=$periode=='s3' ? "selected" : '' ?>>Semestre 3</option>
                        <option value="s4" <?=$periode=='s4' ? "selected" : '' ?>>Semestre 4</option>
                        <option value="s5" <?=$periode=='s5' ? "selected" : '' ?>>Semestre 5</option>
                        <option value="s6" <?=$periode=='s6' ? "selected" : '' ?>>Semestre 6</option>
                        <option value="s7" <?=$periode=='s7' ? "selected" : '' ?>>Semestre 7</option>
                        <option value="s8" <?=$periode=='s8' ? "selected" : '' ?>>Semestre 8</option>
                        <option value="s9" <?=$periode=='s9' ? "selected" : '' ?>>Semestre 9</option>
                        <option value="s10" <?=$periode=='s10' ? "selected" : '' ?>>Semestre 10</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-ensa w-100" style="background-color: #bfdbfe;
            color: #1e3a8a;">
                        <i class="bi bi-funnel me-1"></i> Appliquer
                    </button>
                </div>
            </div>
            </form>

            <!-- Principales statistiques -->
            <div class="row mb-4 mt-4">
                <div class="col-md-8">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span>Évolution des projets</span>
                            <div class="btn-group btn-group-sm" id="chartToggle">
                                <button class="btn btn-outline-secondary active" data-chart="projects">Projets</button>
                                <button class="btn btn-outline-secondary" data-chart="validation">Validations</button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <div id="projectsChartTab" class="chart-tab active">
                                    <canvas id="projectsChart"></canvas>
                                </div>
                                <div id="validationChartTab" class="chart-tab">
                                    <canvas id="validationChart"></canvas>
                                </div>
                                <div id="encadrantsChartTab" class="chart-tab">
                                    <canvas id="encadrantsChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-header">
                            Répartition par filière
                        </div>
                        <div class="card-body">
                            <div class="chart-container donut_chart">
                                <canvas id="departmentsChart"></canvas>
                            </div>
                            <div class="mt-5">
                                <table class="table table-sm">
                                    <tbody>
                                        <tr>
                                            <td>Génie Informatique</td>
                                            <td class="text-end"><?= $pourcent_info ?>%</td>
                                            <td class="text-end"><?= $projects_by_filiere['Génie Informatique'] ?></td>
                                        </tr>
                                        <tr>
                                            <td>Génie Industriel</td>
                                            <td class="text-end"><?= $pourcent_indus ?>%</td>
                                            <td class="text-end"><?= $projects_by_filiere['Génie Industriel'] ?></td>
                                        </tr>
                                        <tr>
                                            <td>Génie Civil</td>
                                            <td class="text-end"><?= $pourcent_civil ?>%</td>
                                            <td class="text-end"><?= $projects_by_filiere['Génie BIEE'] ?></td>
                                        </tr>
                                        <tr>
                                            <td>Génie Electrique</td>
                                            <td class="text-end"><?= $pourcent_elec ?>%</td>
                                            <td class="text-end"><?= $projects_by_filiere['Génie Electrique'] ?></td>
                                        </tr>
                                        <tr>
                                            <td>Génie Réseaux et Telecom</td>
                                            <td class="text-end"><?= $pourcent_rst ?>%</td>
                                            <td class="text-end"><?= $projects_by_filiere['Génie RST'] ?></td>
                                        </tr>
                                        <tr>
                                            <td>Génie Mécatronique</td>
                                            <td class="text-end"><?= $pourcent_mec ?>%</td>
                                            <td class="text-end"><?= $projects_by_filiere['Génie Mécatronique'] ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistiques détaillées -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            Taux de validation par encadrant
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Encadrant</th>
                                            <th class="text-end">Projets</th>
                                            <th class="text-end">Taux validation</th>
                                            <th class="text-end">Moy. évaluation</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($rsn as $rs): ?>
                                        <tr>
                                            <td><?=$rs['encadrant']?></td>
                                            <td class="text-end"><?=$rs['total_projets']?></td>
                                            <td class="text-end"><?=$rs['taux_validation']?>%</td>
                                            <td class="text-end"><?=$rs['moyenne_evaluation']?>/20</td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            Types de projets
                        </div>
                        <div class="card-body">
                            <div class="chart-container bar_chart" style="height: 200px;">
                                <canvas id="typesChart"></canvas>
                            </div>
                            <div class="row text-center mt-3 text_container">
                                <?php foreach ($prs as $row): ?>
                                <div class="col-4">
                                    <h5 class="mb-0"><?= htmlspecialchars($row['total_projets']) ?></h5>
                                    <small class="text-muted"><?= htmlspecialchars($row['TYPE']) ?></small>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>


    <script>
        const pastelColors = [
    '#FFB3BA', // Pastel rouge
    '#FFDFBA', // Pastel orange
    '#FFFFBA', // Pastel jaune
    '#BAFFC9', // Pastel vert
    '#BAE1FF', // Pastel bleu
    '#D0BAFF', // Pastel violet
    '#FFBAF2', // Pastel rose
    '#B5EAD7', // Pastel menthe
    '#C7CEEA', // Pastel lavande
];
        // Données PHP encodées en JSON
        const dataProjectsByMonth = <?= json_encode(array_values($projects_by_month)) ?>;
        const labelsProjectsByMonth = <?= json_encode(array_keys($projects_by_month)) ?>;

        const dataProjectsByFiliere = <?= json_encode(array_values($projects_by_filiere)) ?>;
        const labelsProjectsByFiliere = ["Génie Informatique", "Génie Industriel", "Génie Civil", "Génie Electrique", "Génie Mécatronique", "Génie Réseaux et Telecom"];

        const dataProjectsByType = <?= json_encode(array_values($projects_by_type)) ?>;
        const labelsProjectsByType = ["Stage PFE", "Stage PFA", "Stage d'observation", "Projet de Module"];

        // Variables pour stocker les instances de graphiques
        let projectsChart, validationChart, encadrantsChart;

        // Fonction pour initialiser les graphiques
        function initCharts() {
            // Graphique des projets par mois
            
            projectsChart = new Chart(document.getElementById('projectsChart'), {
    type: 'line',
    data: {
        labels: labelsProjectsByMonth,
        datasets: [{
            label: 'Projets déposés',
            data: dataProjectsByMonth,
            fill: false,
            borderColor: '#B5EAD7', // Couleur pastel
            backgroundColor: '#B5EAD7', // Couleur pastel
            tension: 0.1
        }]
    },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1,
                                callback: value => Number.isInteger(value) ? value : null
                            }
                        }
                    }
                }
            });

            // Graphique de répartition par filière
            
            new Chart(document.getElementById('departmentsChart'), {
    type: 'doughnut',
    data: {
        labels: labelsProjectsByFiliere,
        datasets: [{
            label: 'Répartition des projets',
            data: dataProjectsByFiliere,
            backgroundColor: pastelColors.slice(0, dataProjectsByFiliere.length), // Utilisation des couleurs pastel
            borderWidth: 1
        }]
    },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });

            // Graphique des types de projets
            new Chart(document.getElementById('typesChart'), {
    type: 'bar',
    data: {
        labels: labelsProjectsByType,
        datasets: [{
            label: 'Nombre de projets',
            data: dataProjectsByType,
            backgroundColor: pastelColors.slice(0, dataProjectsByType.length) // Utilisation des couleurs pastel
        }]
    },
                options: {
                    responsive: true,
                    indexAxis: 'y',
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: { stepSize: 1 }
                        }
                    }
                }
            });

            

            // Graphique des encadrants
            const moisLabels = <?= json_encode($mois_labels) ?>;
            const datasets = <?= json_encode($datasets) ?>;
            
            encadrantsChart = new Chart(document.getElementById("encadrantsChart"), {
                type: 'line',
                data: {
                    labels: moisLabels,
                    datasets: datasets
                },
                options: {
                    responsive: true,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Nombre de projets'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Mois'
                            }
                        }
                    }
                }
            });

            // Graphique de validation (exemple, à adapter)
            validationChart = new Chart(document.getElementById('validationChart'), {
    type: 'bar',
    data: {
        labels: labelsProjectsByMonth,
        datasets: [{
            label: 'Projets validés',
            data: dataProjectsByMonth.map(v => Math.round(v * 0.8)),
            backgroundColor: '#BAFFC9' // Couleur pastel verte
        }]
    },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }

        // Gestion des onglets
        document.getElementById('chartToggle').addEventListener('click', function(e) {
            if (e.target.tagName === 'BUTTON') {
                // Mettre à jour les boutons actifs
                document.querySelectorAll('#chartToggle .btn').forEach(btn => {
                    btn.classList.remove('active');
                });
                e.target.classList.add('active');
                
                // Afficher le bon graphique
                const chartType = e.target.dataset.chart;
                document.querySelectorAll('.chart-tab').forEach(tab => {
                    tab.classList.remove('active');
                });
                document.getElementById(chartType + 'ChartTab').classList.add('active');
            }
        });

        // Initialiser les graphiques au chargement
        window.addEventListener('DOMContentLoaded', initCharts);




        
// Toggle sidebar on mobile
document.getElementById('sidebarToggle').addEventListener('click', function() {
    document.querySelector('.sidebar').classList.toggle('d-none');
    document.querySelector('.sidebar').classList.toggle('d-block');
});


// Toggle sidebar on mobile
document.getElementById('sidebarToggle').addEventListener('click', function() {
    document.querySelector('.sidebar').classList.toggle('show');
});

// Fermer le sidebar quand on clique à l'extérieur
document.addEventListener('click', function(event) {
    const sidebar = document.querySelector('.sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    
    if (!sidebar.contains(event.target) && event.target !== sidebarToggle && !sidebarToggle.contains(event.target)) {
        sidebar.classList.remove('show');
    }
});
</script>
<script>
function exportTableToExcel(tableID, filename = '') {
    var wb = XLSX.utils.book_new();
    var table = document.getElementById(tableID);
    var ws = XLSX.utils.table_to_sheet(table);
    XLSX.utils.book_append_sheet(wb, ws, "Feuille1");
    XLSX.writeFile(wb, filename ? filename + '.xlsx' : 'tableau.xlsx');
}

    </script>
</body>
</html>