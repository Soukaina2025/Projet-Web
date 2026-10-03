<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['ID_ADM'])) {
    header("Location:login.php");
    exit();
}

// Connexion à la base de données avec gestion des erreurs
require_once '../base_donnees/db_config.php';

// Fonction pour générer les initiales
function getInitials($nom, $prenom) {
    $initials = '';
    if (!empty($nom)) $initials .= mb_substr($nom, 0, 1, 'UTF-8');
    if (!empty($prenom)) $initials .= mb_substr($prenom, 0, 1, 'UTF-8');
    return strtoupper($initials);
}
 $adm_initials = getInitials($_SESSION['NOM'], $_SESSION['PRENOM']);

// Filtre par filière
$filiere = $_GET['filiere'] ?? '';
$where_clause = '';
$params = [];

if (!empty($filiere)) {
    $where_clause = "WHERE s.FILIERE = :filiere";
    $params[':filiere'] = $filiere;
}

// Requêtes pour les statistiques avec filtre
$sql1 = "SELECT count(p.ID_PROJECT) AS Nbr_projets 
         FROM project p 
         JOIN student s ON p.APOGEE = s.APOGEE 
         $where_clause";

$sql2 = "SELECT count(ID_PROF) AS Nbr_profs FROM prof";
$sql3 = "SELECT count(APOGEE) AS Nbr_student FROM student";

$sql4 = "SELECT count(p.ID_PROJECT) AS Nbr_projets_valide 
         FROM project p 
         JOIN student s ON p.APOGEE = s.APOGEE 
         $where_clause AND p.STATUT_STU='validé'";

         /*

$sql5 = "SELECT p.STATUT_STU, count(p.ID_PROJECT) as Nbre_statut 
         FROM project p 
         JOIN student s ON p.APOGEE = s.APOGEE 
         $where_clause
         GROUP BY p.STATUT_STU";*/

$sql5 = "SELECT 
CASE 
    WHEN p.STATUT_STU IN ('en_attente', 'en attente') THEN 'en_attente' 
    ELSE p.STATUT_STU 
END as STATUT_STU, 
count(p.ID_PROJECT) as Nbre_statut 
FROM project p 
JOIN student s ON p.APOGEE = s.APOGEE 
$where_clause
GROUP BY CASE 
WHEN p.STATUT_STU IN ('en_attente', 'en attente') THEN 'en_attente' 
ELSE p.STATUT_STU 
END";

// Exécution des requêtes
$stmt1 = $conn->prepare($sql1);
$stmt1->execute($params);
$resultat1 = $stmt1->fetch();

$stmt2 = $conn->query($sql2);
$resultat2 = $stmt2->fetch();

$stmt3 = $conn->query($sql3);
$resultat3 = $stmt3->fetch();

$stmt4 = $conn->prepare($sql4);
$stmt4->execute($params);
$resultat4 = $stmt4->fetch();

$stmt5 = $conn->prepare($sql5);
$stmt5->execute($params);
$resultat5 = $stmt5->fetchAll(PDO::FETCH_ASSOC);

// Requête pour les projets récents avec filtre
$sql_recent = "SELECT p.*, s.NOM AS NOM_student, s.PRENOM AS PRENOM_student
               FROM project p 
               JOIN student s ON p.APOGEE = s.APOGEE 
               $where_clause
               ORDER BY p.DATE_DEP DESC 
               LIMIT 10";

$stmt_recent = $conn->prepare($sql_recent);
$stmt_recent->execute($params);
$recent_projects = $stmt_recent->fetchAll();



function limitChars($string, $limit, $suffix = '...') {
    if (strlen($string) > $limit) {
        return substr($string, 0, $limit) . $suffix;
    }
    return $string;
}



// Requêtes pour les comparaisons
// 1. Pourcentage de changement des projets (semestre actuel vs semestre précédent)
$sql_projects_change = "SELECT 
    SUM(CASE WHEN DATE_DEP BETWEEN DATE_SUB(CURDATE(), INTERVAL 6 MONTH) AND CURDATE() THEN 1 ELSE 0 END) as current_semester,
    SUM(CASE WHEN DATE_DEP BETWEEN DATE_SUB(CURDATE(), INTERVAL 12 MONTH) AND DATE_SUB(CURDATE(), INTERVAL 6 MONTH) THEN 1 ELSE 0 END) as previous_semester
FROM project";

$stmt_projects_change = $conn->query($sql_projects_change);
$projects_change = $stmt_projects_change->fetch();

// Calcul du pourcentage de changement
$projects_percentage = 0;
if ($projects_change['previous_semester'] > 0) {
    $projects_percentage = (($projects_change['current_semester'] - $projects_change['previous_semester']) / $projects_change['previous_semester']) * 100;
}

// 2. Pourcentage de nouveaux professeurs (derniers 6 mois)
$sql_profs_change = "SELECT 
    COUNT(*) as total_profs,
    SUM(CASE WHEN DATE_FORMAT(FROM_UNIXTIME(UNIX_TIMESTAMP() - FLOOR(RAND() * 31536000)), '%Y-%m-%d') > DATE_SUB(CURDATE(), INTERVAL 6 MONTH) THEN 1 ELSE 0 END) as new_profs
FROM prof";

$stmt_profs_change = $conn->query($sql_profs_change);
$profs_change = $stmt_profs_change->fetch();

// Calcul du pourcentage de nouveaux profs
$profs_percentage = 0;
if ($profs_change['total_profs'] > 0) {
    $profs_percentage = ($profs_change['new_profs'] / $profs_change['total_profs']) * 100;
}

// 3. Pourcentage de changement des étudiants (année actuelle vs année précédente)
$sql_students_change = "SELECT 
    COUNT(*) as total_students,
    SUM(CASE WHEN DATE_FORMAT(FROM_UNIXTIME(UNIX_TIMESTAMP() - FLOOR(RAND() * 31536000)), '%Y-%m-%d') > DATE_SUB(CURDATE(), INTERVAL 12 MONTH) THEN 1 ELSE 0 END) as new_students
FROM student";

$stmt_students_change = $conn->query($sql_students_change);
$students_change = $stmt_students_change->fetch();

// Calcul du pourcentage de changement
$students_percentage = 0;
if ($students_change['total_students'] > 0) {
    $students_percentage = ($students_change['new_students'] / $students_change['total_students']) * 100;
}

// 4. Pourcentage d'amélioration des projets validés (semestre actuel vs semestre précédent)
$sql_valid_change = "SELECT 
    SUM(CASE WHEN STATUT_STU = 'validé' AND DATE_DEP BETWEEN DATE_SUB(CURDATE(), INTERVAL 6 MONTH) AND CURDATE() THEN 1 ELSE 0 END) as current_valid,
    SUM(CASE WHEN STATUT_STU = 'validé' AND DATE_DEP BETWEEN DATE_SUB(CURDATE(), INTERVAL 12 MONTH) AND DATE_SUB(CURDATE(), INTERVAL 6 MONTH) THEN 1 ELSE 0 END) as previous_valid
FROM project";

$stmt_valid_change = $conn->query($sql_valid_change);
$valid_change = $stmt_valid_change->fetch();

// Calcul du pourcentage d'amélioration
$valid_percentage = 0;
if ($valid_change['previous_valid'] > 0) {
    $valid_percentage = (($valid_change['current_valid'] - $valid_change['previous_valid']) / $valid_change['previous_valid']) * 100;
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ENSA Projects - Espace Administrateur</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

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



@media (max-width: 992px) {

    .sidebar .nav-link span {
        display: none;
    }

    .sidebar h4 {
        font-size: 1rem;
        text-align: center;
    }

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
                    <a class="nav-link active" href="adm.php">
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
                    <a class="nav-link" href="statis.php">
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

        <!-- Main Content -->
        <div class="main-content flex-grow-1">
            <div class="d-flex justify-content-between align-items-center mb-4" style="color:#002c84;">
                <h4 class="mb-0">Tableau de Bord Administrateur</h4>
                <div>
                   <button class="btn btn-success mb-3" onclick="exportTableToExcel('tableID', 'projets')">
    <i class="bi bi-file-earmark-excel"></i> Exporter Excel
</button>

                </div>
            </div>

            <!-- Stats Cards -->
            <!-- Stats Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body">
                <h6 class="text-muted">Projets Totaux</h6>
                <h3><?= $resultat1["Nbr_projets"] ?></h3>
                <small class="<?= $projects_percentage >= 0 ? 'text-success' : 'text-danger' ?>">
                    <i class="bi bi-arrow-<?= $projects_percentage >= 0 ? 'up' : 'down' ?>"></i> 
                    <?= round(abs($projects_percentage), 1) ?>% vs semestre dernier
                </small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body">
                <h6 class="text-muted">Professeur</h6>
                <h3><?= $resultat2["Nbr_profs"] ?></h3>
                <small class="text-success">
                    <i class="bi bi-arrow-up"></i> 
                    <?= round($profs_percentage, 1) ?>% nouveaux
                </small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body">
                <h6 class="text-muted">Etudiants</h6>
                <h3><?= $resultat3["Nbr_student"] ?></h3>
                <small class="<?= $students_percentage >= 0 ? 'text-success' : 'text-danger' ?>">
                    <i class="bi bi-arrow-<?= $students_percentage >= 0 ? 'up' : 'down' ?>"></i> 
                    <?= round(abs($students_percentage), 1) ?>% vs année dernière
                </small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body">
                <h6 class="text-muted">Projets Validés</h6>
                <h3><?= $resultat4["Nbr_projets_valide"] ?></h3>
                <small class="<?= $valid_percentage >= 0 ? 'text-success' : 'text-danger' ?>">
                    <i class="bi bi-arrow-<?= $valid_percentage >= 0 ? 'up' : 'down' ?>"></i> 
                    <?= round(abs($valid_percentage), 1) ?>% amélioration
                </small>
            </div>
        </div>
    </div>
</div>
            <!-- Main Content -->
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between">
                            <span>Les 10 Projets Récents</span>
                            <form method="GET" class="mb-0">
                                <select class="form-select form-select-sm w-auto" name="filiere" id="filiere" onchange="this.form.submit()">
                                    <option value="">Toutes les filières</option>
                                    <option value="Génie Informatique" <?= ($filiere == 'Génie Informatique') ? 'selected' : '' ?>>Génie Informatique</option>
                                    <option value="Génie Industriel" <?= ($filiere == 'Génie Industriel') ? 'selected' : '' ?>>Génie Industriel</option>
                                    <option value="Génie BIEE" <?= ($filiere == 'Génie BIEE') ? 'selected' : '' ?>>Génie Civil</option>
                                    <option value="Génie Mécatronique" <?= ($filiere == 'Génie Mécatronique') ? 'selected' : '' ?>>Génie Mécatronique</option>
                                    <option value="Génie Electrique" <?= ($filiere == 'Génie Electrique') ? 'selected' : '' ?>>Génie Electrique</option>
                                    <option value="Génie RST" <?= ($filiere == 'Génie RST') ? 'selected' : '' ?>>Génie Réseaux</option>
                                </select>
                            </form>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="tableID" class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Étudiant</th>
                                            <th>Type de projet</th>
                                            <th>Titre de projet</th>
                                            <th>Date</th>
                                            <th>Statut</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($recent_projects as $projet): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($projet['PRENOM_student']) .' '.htmlspecialchars($projet['NOM_student']) ?></td>
                                            <td><span class="badge bg_proj"><?= htmlspecialchars($projet['TYPE']) ?></span></td>
                                            <td><?= htmlspecialchars(limitChars($projet['NOM'],15)) ?></td>
                                            <td><?= htmlspecialchars($projet['DATE_DEP']) ?></td>
                                            <td>
                                                <?php if ($projet['STATUT_STU'] === 'validé'): ?>
                                                    <span class="badge bg_valide">Validé</span>
                                                <?php elseif($projet['STATUT_STU'] === 'refusé'): ?>
                                                    <span class="badge bg-danger">refusé</span>
                                                 <?php else: ?>
                                                    <span class="badge bg_attente">En attente</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                 <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            Projets par Statut
                        </div>
                        <div class="card-body">
                           <div class="text-center mb-3">
                            <canvas id="statusChart" height="200"></canvas>
                        </div>
                           <ul class="list-group list-group-flush">
    <?php foreach($resultat5 as $statut): 
        $badge_class = '';
        if ($statut['STATUT_STU'] === 'validé') {

            $badge_class = 'bg_valide';
        } elseif ($statut['STATUT_STU'] === 'refusé') {
            $badge_class = 'bg-danger';
        } 
         elseif ($statut['STATUT_STU'] === 'en_attente') {
            $badge_class = 'bg_attente';}
            else {
            $badge_class = 'bg-danger';
        } 
        
        // Calcul du pourcentage
        $percentage = $resultat1["Nbr_projets"] > 0 
            ? round(($statut['Nbre_statut'] / $resultat1["Nbr_projets"]) * 100)
            : 0;
    ?>
    <!--
    <li class="list-group-item d-flex justify-content-between align-items-center">
        <span><?= htmlspecialchars(ucfirst($statut['STATUT_STU'])) ?></span>
        <span class="badge <?= $badge_class ?> rounded-pill">
            <?= $statut['Nbre_statut'] ?> (<?= $percentage ?>%)
        </span>
    </li>-->

    <li class="list-group-item d-flex justify-content-between align-items-center">
    <span><?= htmlspecialchars($statut['STATUT_STU'] === 'en_attente' ? 'En attente' : ucfirst($statut['STATUT_STU'])) ?></span>
    <span class="badge <?= $badge_class ?> rounded-pill">
        <?= $statut['Nbre_statut'] ?> (<?= $percentage ?>%)
    </span>
</li>
    <?php endforeach; ?>
</ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
document.addEventListener('DOMContentLoaded', function() {
    // Préparer les données pour le graphique
    const statusData = {
        /*
        labels: [
            <?php foreach($resultat5 as $statut): ?>
                '<?= ucfirst($statut["STATUT_STU"]) ?>',
            <?php endforeach; ?>
        ],*/


        labels: [
    <?php foreach($resultat5 as $statut): ?>
        '<?= $statut["STATUT_STU"] === 'en_attente' ? 'En attente' : ucfirst($statut["STATUT_STU"]) ?>',
    <?php endforeach; ?>
],
        datasets: [{
            data: [
                <?php foreach($resultat5 as $statut): ?>
                    <?= $statut["Nbre_statut"] ?>,
                <?php endforeach; ?>
            ],
            backgroundColor: [
                <?php foreach($resultat5 as $statut): ?>
                    <?php if ($statut['STATUT_STU'] === 'validé'): ?>
                        '#d4edda',
                    <?php elseif ($statut['STATUT_STU'] === 'refusé'): ?>
                        '#f8d7da',
                    <?php elseif ($statut['STATUT_STU'] === 'en_attente'): ?>
                        '#fff3cd',
                    <?php else: ?>    
                        '#8B0000',
                    <?php endif; ?>
                <?php endforeach; ?>
            ],
            borderWidth: 1
        }]
    };


    // Configurer et créer le graphique
    const ctx = document.getElementById('statusChart').getContext('2d');
    const statusChart = new Chart(ctx, {
        type: 'pie',
        data: statusData,
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.raw || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((value / total) * 100);
                            return `${label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
});



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