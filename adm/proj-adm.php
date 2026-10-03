<?php
session_start();
require_once '../base_donnees/db_config.php';

// Configuration de la pagination
$projects_per_page = 12;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;
$offset = ($current_page - 1) * $projects_per_page;

// Récupérer le terme de recherche s'il existe
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';

// Récupérer le nombre total de projets (pour la pagination)
$count_query = "SELECT COUNT(*) as total FROM project p 
                JOIN student s ON p.APOGEE = s.APOGEE 
                JOIN prof pr ON p.ID_PROF = pr.ID_PROF";
$count_params = [];

// Construction de la requête principale avec pagination
$query = "SELECT p.*, s.NOM as student_nom, s.PRENOM as student_prenom, s.FILIERE, 
          pr.NOM as prof_nom, pr.PRENOM as prof_prenom 
          FROM project p 
          JOIN student s ON p.APOGEE = s.APOGEE 
          JOIN prof pr ON p.ID_PROF = pr.ID_PROF";

// Gestion des filtres
$filter_type = isset($_GET['type']) ? $_GET['type'] : '';
$filter_filiere = isset($_GET['filiere']) ? $_GET['filiere'] : '';
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';

$where_clauses = [];
$params = [];

// Ajout de la condition de recherche si elle existe
/*
if ($search_term) {
    $where_clauses[] = "p.NOM LIKE ?";
    $params[] = '%' . $search_term . '%';
    $count_query .= " WHERE p.NOM LIKE ?";
    $count_params[] = '%' . $search_term . '%';
}*/
if ($search_term) {
    $where_clauses[] = "p.NOM = ?";
    $params[] = $search_term;

    $count_query .= " WHERE p.NOM = ?";
    $count_params[] = $search_term;
}

// Exécution de la requête de count
$count_stmt = $conn->prepare($count_query);
$count_stmt->execute($count_params);
$total_projects = $count_stmt->fetch()['total'];
$total_pages = ceil($total_projects / $projects_per_page);

// Ajout des filtres s'ils sont définis et s'il n'y a pas de recherche
if (!$search_term) {
    if ($filter_type) {
        $where_clauses[] = "p.TYPE = ?";
        $params[] = $filter_type;
    }
    if ($filter_filiere) {
        $where_clauses[] = "s.FILIERE = ?";
        $params[] = $filter_filiere;
    }
    if ($filter_status) {
        $where_clauses[] = "p.STATUT_STU = ?";
        $params[] = $filter_status;
    }
}

// Construction de la clause WHERE pour la requête principale
if (!empty($where_clauses)) {
    $query .= " WHERE " . implode(' AND ', $where_clauses);
    
    // Construction de la clause WHERE pour le count si pas de recherche
    if (!$search_term) {
        $count_query .= " WHERE " . implode(' AND ', $where_clauses);
    }
}



// Vérifier que la page courante ne dépasse pas le nombre total de pages
if ($current_page > $total_pages && $total_pages > 0) {
    $current_page = $total_pages;
}

// Ajout du tri et de la pagination
$query .= " ORDER BY p.DATE_DEP DESC LIMIT ? OFFSET ?";
$params[] = $projects_per_page;
$params[] = $offset;

try {
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $projects = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des projets: " . $e->getMessage());
    die("Erreur lors de la récupération des données. Veuillez contacter l'administrateur.");
}

// Gestion du changement de statut
if (isset($_POST['change_status'])) {
    $project_id = (int) $_POST['project_id'];
    $new_status = $_POST['new_status'];

    try {
        $update_query = "UPDATE project SET STATUT_STU = ? WHERE ID_PROJECT = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->execute([$new_status, $project_id]);

        // Redirection après mise à jour
        header("Location: proj-adm.php");
        exit;
    } catch (PDOException $e) {
        error_log("Erreur lors de la mise à jour du statut : " . $e->getMessage());
        echo '<div class="alert alert-danger">Erreur lors du changement de statut.</div>';
    }
}

// Gestion du téléchargement des livrables
if (isset($_GET['download_file']) && isset($_GET['project_id'])) {
    $project_id = (int)$_GET['project_id'];
    $file_type = $_GET['download_file'];
    
    try {
        $query = "SELECT $file_type FROM project WHERE ID_PROJECT = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$project_id]);
        $project = $stmt->fetch();
        
        if ($project && $project[$file_type]) {
            $file_path = $project[$file_type];
            $file_name = basename($file_path);
            
            if (file_exists($file_path)) {
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . $file_name . '"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($file_path));
                readfile($file_path);
                exit;
            } else {
                die("Le fichier n'existe pas sur le serveur.");
            }
        } else {
            die("Aucun fichier trouvé pour ce projet.");
        }
    } catch (PDOException $e) {
        die("Erreur lors de la récupération du fichier: " . $e->getMessage());
    }
}




function limitChars($string, $limit, $suffix = '...') {
    if (strlen($string) > $limit) {
        return substr($string, 0, $limit) . $suffix;
    }
    return $string;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ENSA Projects - Tous les Projets</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Pour l'export Excel -->
    <script src="https://cdn.sheetjs.com/xlsx-0.19.3/package/dist/xlsx.full.min.js"></script>
    <!-- Pour l'export PDF -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        .nav-link {
            color: rgba(255,255,255,0.8);
            border-radius: 5px;
            margin-bottom: 5px; 
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



@media (max-width:450px) {
    .page-link{
        font-size: 12px;
    }
    
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
    .main-content {
        margin-left: 220px;
    }
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
    .main-content {
        margin-left: 80px;
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
    .main-content {
        margin-left: 0;
    }
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


.fa-chevron-down:before {
    


    content: none;
    
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


        .voir_button{
            border-color: #002c84;
            color: #002c84;
            background-color: white;


        }



        .voir_button :hover{
            border-color: #002c84;

        }

.bg_pfe {
    background-color:rgb(226, 243, 239);
    color: #383d73;
}


.bg_pfa {
    background-color: #e0f7fa;
    color: #006064;
}

.bg_obs {
    background-color: #f0f4c3;
    color: #827717;
}


.bg_module {
    background-color: #ede7f6;
    color: #4527a0;
}
.bg_proj{
            background-color: #e0f2fe;
            color: #0284c7;
        }


    </style>
</head>
<body>
    <div class="d-flex first_container">
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
                    <a class="nav-link active" href="proj-adm.php">
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


        <!-- Main Content -->
        <div class="main-content flex-grow-1">
            <button class="btn btn-ensa  mb-3 menu_button" id="sidebarToggle">
        <i class="bi bi-list"></i> Menu
    </button>



            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="mb-0">Tous les Projets Soumis</h4>
                <div>
                    <button class="btn btn-ensa me-2" id="exportExcel" style="background-color: #bfdbfe; color: #1e3a8a;">
                        <i class="bi bi-file-earmark-excel me-1"></i> Excel
                    </button>
                    <button class="btn btn-ensa me-2" id="exportPdf" style="background-color: #bfdbfe; color: #1e3a8a;">
                        <i class="bi bi-file-earmark-pdf me-1"></i> PDF
                    </button>
                    <div class="btn-group">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-filter me-1"></i> Filtres
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="proj-adm.php?status=validé<?= $search_term ? '&search='.urlencode($search_term) : '' ?>">Projets Validés</a></li>
                            <li><a class="dropdown-item" href="proj-adm.php?status=en_attente<?= $search_term ? '&search='.urlencode($search_term) : '' ?>">Projets En Attente</a></li>
                            <li><a class="dropdown-item" href="proj-adm.php?status=refusé<?= $search_term ? '&search='.urlencode($search_term) : '' ?>">Projets Refusés</a></li>

                            <li><a class="dropdown-item" href="proj-adm.php?status=rejeté<?= $search_term ? '&search='.urlencode($search_term) : '' ?>">Projets Rejetés</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="proj-adm.php">Réinitialiser les filtres</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="filter-section">
                <form method="GET" action="proj-adm.php">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label for="projectTypeFilter" class="form-label">Type de Projet</label>
                            <select id="projectTypeFilter" name="type" class="form-select">
                                <option value="">Tous les types</option>
                                <option value="stage d'observation" <?= $filter_type == 'stage d\'observation' ? 'selected' : '' ?>>Stage d'observation</option>

                                <option value="stage pfe" <?= $filter_type == 'stage pfe' ? 'selected' : '' ?>>Stage PFE</option>
                                <option value="stage pfa" <?= $filter_type == 'stage pfa' ? 'selected' : '' ?>>Stage PFA</option>
                                <option value="module" <?= $filter_type == 'module' ? 'selected' : '' ?>>Module</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="filiereFilter" class="form-label">Filière</label>
                            <select id="filiereFilter" name="filiere" class="form-select">
                                <option value="">Toutes les filières</option>
                                <option value="Génie Informatique" <?= $filter_filiere == 'Génie Informatique' ? 'selected' : '' ?>>Génie Informatique</option>
                                <option value="Génie Industriel" <?= $filter_filiere == 'Génie Industriel' ? 'selected' : '' ?>>Génie Industriel</option>
                                <option value="Génie RST" <?= $filter_filiere == 'Génie RST' ? 'selected' : '' ?>>Génie RST</option>
                                
                                <option value="Génie Electrique" <?= $filter_filiere == 'Génie Electrique' ? 'selected' : '' ?>>Génie Electrique</option>
                                <option value="Génie Mécatronique" <?= $filter_filiere == 'Génie Mécatronique' ? 'selected' : '' ?>>Génie Mécatronique</option>
                                <option value="Génie BIEE" <?= $filter_filiere == 'Génie BIEE' ? 'selected' : '' ?>>Génie BIEE</option>



                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="statusFilter" class="form-label">Statut</label>
                            <select id="statusFilter" name="status" class="form-select">
                                <option value="">Tous les statuts</option>
                                <option value="validé" <?= $filter_status == 'validé' ? 'selected' : '' ?>>Validé</option>
                                <option value="en_attente" <?= $filter_status == 'en_attente' ? 'selected' : '' ?>>En attente</option>
                                <option value="refusé" <?= $filter_status == 'refusé' ? 'selected' : '' ?>>Refusé</option>
                                <option value="rejeté" <?= $filter_status == 'rejeté' ? 'selected' : '' ?>>Rejeté</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-ensa w-100" style="background-color: #bfdbfe; color: #1e3a8a;">
                                <i class="bi bi-funnel me-1"></i> Appliquer
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Projects Table -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Liste des Projets</span>
                    <form method="GET" action="proj-adm.php" class="input-group w-auto">
                        <input type="text" name="search" class="form-control form-control-sm" placeholder="Rechercher un projet..." value="<?= htmlspecialchars($search_term) ?>">
                        <button class="btn btn-outline-secondary btn-sm" type="submit">
                            <i class="bi bi-search"></i>
                        </button>
                        <?php if ($search_term): ?>
                            <a href="proj-adm.php" class="btn btn-outline-danger btn-sm ms-2">
                                <i class="bi bi-x"></i>
                            </a>
                        <?php endif; ?>
                    </form>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Titre du Projet</th>
                                    <th>Étudiant(s)</th>
                                    <th>Type</th>
                                    <th>Filière</th>
                                    <th>Encadrant</th>
                                    <th>Date Soumission</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($projects)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <?= $search_term ? 'Aucun projet trouvé pour "' . htmlspecialchars($search_term) . '"' : 'Aucun projet disponible' ?>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($projects as $project): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold"><?= htmlspecialchars(limitChars($project['NOM'],20)) ?></div>
                                            <small class="text-muted">#PRJ-<?= $project['ID_PROJECT'] ?></small>
                                        </td>
                                        <td>
                                            <div><?= htmlspecialchars($project['student_prenom'] . ' ' . $project['student_nom']) ?></div>
                                            <small class="text-muted">Apogée: <?= $project['APOGEE'] ?></small>
                                        </td>
                                        <td>
                                            <?php 
                                            $badge_class = '';
                                            if (strpos($project['TYPE'], 'pfe') !== false) {
                                                $badge_class = 'bg_pfe';
                                            } elseif (strpos($project['TYPE'], 'module') !== false) {
                                                $badge_class = 'bg_module';
                                            } elseif (strpos($project['TYPE'], 'pfa') !== false) {
                                                $badge_class = 'bg_pfa';
                                            } elseif (strpos($project['TYPE'], 'stage') !== false) {
                                                $badge_class = 'bg_obs';
                                            } else {
                                                $badge_class = 'bg-light text-dark';
                                            }
                                            ?>
                                            <span class="badge <?= $badge_class ?> project-type-badge"><?= htmlspecialchars($project['TYPE']) ?></span>
                                        </td>
                                        <td><?= htmlspecialchars($project['FILIERE']) ?></td>
                                        <td><?= htmlspecialchars($project['prof_prenom'] . ' ' . $project['prof_nom']) ?></td>
                                        <td><?= $project['DATE_DEP'] ? date('d/m/Y', strtotime($project['DATE_DEP'])) : 'N/A' ?></td>
                                        <td>
                                            <?php 
                                            $status_badge = '';
                                            $progress_color = '';
                                            $progress_width = '0';
                                            
                                            if ($project['STATUT_STU'] == 'validé') {
                                                $status_badge = 'bg_valide';
                                                $progress_color = 'bg_valide';
                                                $progress_width = '100';
                                            } elseif ($project['STATUT_STU'] == 'en_attente') {
                                                $status_badge = 'bg_attente';
                                                $progress_color = 'bg_attente';
                                                $progress_width = '60';
                                            } elseif ($project['STATUT_STU'] == 'rejeté') {
                                                $status_badge = 'bg_refuse';
                                                $progress_color = 'bg_refuse';
                                                $progress_width = '30';
                                            } else {
                                                $status_badge = 'bg-secondary';
                                                $progress_color = 'bg-secondary';
                                                $progress_width = '0';
                                            }
                                            ?>
                                            <span class="badge <?= $status_badge ?> status-badge">
                                                <?= ucfirst(str_replace('_', ' ', $project['STATUT_STU'])) ?>
                                            </span>
                                            <div class="progress progress-thin mt-1">
                                                <div class="progress-bar <?= $progress_color ?>" style="width: <?= $progress_width ?>%"></div>
                                            </div>
                                        </td>
                                        <td style="display:flex; flex-direction:column; align-items:center;">
                                            <button class="btn btn-sm me-1" style="border-color: #002c84; color: #002c84; margin-bottom:5px;"  title="Voir" data-bs-toggle="modal" data-bs-target="#projectDetailsModal" data-id="<?= $project['ID_PROJECT'] ?>">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <button class="btn btn-sm me-1" style="border-color: gray; color: gray; margin-bottom:5px;"  title="Modifier" data-bs-toggle="modal" data-bs-target="#editProjectModal" data-id="<?= $project['ID_PROJECT'] ?>">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn btn-sm me-1" style="color: #721c24; border-color: #721c24; margin-bottom:5px;" title="Supprimer" onclick="confirmDelete(<?= $project['ID_PROJECT'] ?>)">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center flex-wrap">
                            <!-- Lien Précédent -->
                            <li class="page-item <?= $current_page <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" 
                                   href="?page=<?= $current_page - 1 ?><?= $filter_type ? '&type='.$filter_type : '' ?><?= $filter_filiere ? '&filiere='.$filter_filiere : '' ?><?= $filter_status ? '&status='.$filter_status : '' ?><?= $search_term ? '&search='.urlencode($search_term) : '' ?>" 
                                   style="<?= $current_page > 1 ? 'color: #002c84;' : '' ?>">
                                    Précédent
                                </a>
                            </li>
                            
                            <!-- Liens des pages -->
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?= $i == $current_page ? 'active' : '' ?>">
                                    <a class="page-link" 
                                       href="?page=<?= $i ?><?= $filter_type ? '&type='.$filter_type : '' ?><?= $filter_filiere ? '&filiere='.$filter_filiere : '' ?><?= $filter_status ? '&status='.$filter_status : '' ?><?= $search_term ? '&search='.urlencode($search_term) : '' ?>"
                                       style="<?= $i == $current_page ? 'background-color: #002c84; border-color: #002c84;' : 'color: #002c84;' ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <!-- Lien Suivant -->
                            <li class="page-item <?= $current_page >= $total_pages ? 'disabled' : '' ?>">
                                <a class="page-link" 
                                   href="?page=<?= $current_page + 1 ?><?= $filter_type ? '&type='.$filter_type : '' ?><?= $filter_filiere ? '&filiere='.$filter_filiere : '' ?><?= $filter_status ? '&status='.$filter_status : '' ?><?= $search_term ? '&search='.urlencode($search_term) : '' ?>"
                                   style="<?= $current_page < $total_pages ? 'color: #002c84;' : '' ?>">
                                    Suivant
                                </a>
                            </li>
                        </ul>
                    </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Project Details Modal -->
<div class="modal fade" id="projectDetailsModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Détails du Projet</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="projectDetailsContent">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Project Modal -->
<div class="modal fade" id="editProjectModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Modifier le Statut du Projet</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="proj-adm.php">
                    <div class="modal-body">
                        <input type="hidden" name="project_id" id="editProjectId">
                        <div class="mb-3">
                            <label for="new_status" class="form-label">Nouveau Statut</label>
                            <select class="form-select" id="new_status" name="new_status">
                                <option value="en_attente">En attente</option>
                                <option value="validé">Validé</option>
                                <option value="rejeté">Rejeté</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" name="change_status" class="btn" style="background-color: #e0f2fe;
            color: #0284c7;">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Script pour charger les détails du projet dans la modal
        document.getElementById('projectDetailsModal').addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const projectId = button.getAttribute('data-id');
            const modal = this;
            
            // Charger les détails via AJAX
            fetch('get_project_details.php?id=' + projectId)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('projectDetailsContent').innerHTML = data;
                });
        });
        
        // Script pour l'édition du projet
        document.getElementById('editProjectModal').addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const projectId = button.getAttribute('data-id');
            document.getElementById('editProjectId').value = projectId;
            
            // Récupérer le statut actuel du projet
            fetch('get_project_status.php?id=' + projectId)
                .then(response => response.json())
                .then(data => {
                    if (data.status) {
                        document.getElementById('new_status').value = data.status;
                    }
                });
        });
        
        // Confirmation de suppression
        function confirmDelete(projectId) {
            if (confirm('Êtes-vous sûr de vouloir supprimer ce projet ? Cette action est irréversible.')) {
                window.location.href = 'delete_project.php?id=' + projectId;
            }
        }

        /*

        // Export Excel
        document.getElementById('exportExcel').addEventListener('click', function() {
            // Sélectionner le tableau
            const table = document.querySelector('.table');
            
            // Créer un workbook Excel
            const wb = XLSX.utils.table_to_book(table);
            
            // Exporter le fichier
            XLSX.writeFile(wb, 'projets_ensa.xlsx');
        });

        // Export PDF
        document.getElementById('exportPdf').addEventListener('click', function() {
            // Initialiser jsPDF
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF('l', 'pt');
            
            // Sélectionner le tableau
            const table = document.querySelector('.table');
            
            // Options pour autoTable
            doc.autoTable({
                html: table,
                headStyles: {
                    fillColor: [44, 62, 80], // Couleur ENSA primary
                    textColor: 255
                },
                styles: {
                    cellPadding: 5,
                    fontSize: 8,
                    valign: 'middle'
                },
                margin: { top: 40 },
                didDrawPage: function(data) {
                    // Ajouter un en-tête
                    doc.setFontSize(18);
                    doc.setTextColor(40);
                    doc.text('Liste des Projets - ENSA', data.settings.margin.left, 30);
                }
            });
            
            // Sauvegarder le PDF
            doc.save('projets_ensa.pdf');
        });*/


        document.getElementById('exportExcel').addEventListener('click', function() {
    // Sélectionner le tableau
    const table = document.querySelector('.table');
    
    // Créer un workbook Excel
    const wb = XLSX.utils.table_to_book(table);
    
    // Obtenir la page actuelle (suppose que vous avez une variable currentPage)
    const currentPage = document.querySelector('.pagination .active')?.textContent || '1';
    
    // Formater la date
    const now = new Date();
    const dateStr = now.toISOString().split('T')[0]; // Format YYYY-MM-DD
    const timeStr = now.getHours() + 'h' + now.getMinutes(); // Format HHhMM
    
    // Exporter le fichier avec nouveau nom
    XLSX.writeFile(wb, `ENSA_Projets_Page${currentPage}_Export_${dateStr}_${timeStr}.xlsx`);
});


document.getElementById('exportPdf').addEventListener('click', function() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF('l', 'pt');
    
    // Sélectionner le tableau
    const table = document.querySelector('.table');
    
    // Obtenir la page actuelle
    
    const currentPage = document.querySelector('.pagination .active')?.textContent || '1';
    
    // Formater la date
    const now = new Date();
    const dateStr = now.toISOString().split('T')[0];
    const timeStr = now.getHours() + 'h' + now.getMinutes();
    
    // Options pour autoTable
    doc.autoTable({
        html: table,
        headStyles: {
            fillColor: [44, 62, 80],
            textColor: 255
        },
        styles: {
            cellPadding: 5,
            fontSize: 8,
            valign: 'middle'
        },
        margin: { top: 40 },
        /*
        didDrawPage: function(data) {
            doc.setFontSize(18);
            doc.setTextColor(40);
            doc.text(`Liste des Projets - Page ${currentPage}`, data.settings.margin.left, 30);
            // Ajouter la date dans le header
            doc.setFontSize(10);
const pageHeight = doc.internal.pageSize.height;
const marginBottom = 20; // Par exemple, 20 unités de marge en bas

doc.text(`Exporté le: ${dateStr} à ${timeStr}`, data.settings.margin.left, pageHeight - marginBottom);
        }*/

        didDrawPage: function(data) {
    const pageWidth = doc.internal.pageSize.width;
    const pageHeight = doc.internal.pageSize.height;
    const marginLeft = data.settings.margin.left;
    const marginRight = data.settings.margin.right;
    const marginBottom = 20; // marge bas
    const marginTop = 30;    // position du titre
    // Titre en haut à gauche
    doc.setFontSize(18);
    doc.setTextColor(40);
    doc.text('Liste des Projets', marginLeft, marginTop);

    // Date en bas à gauche
    doc.setFontSize(10);
    doc.text(`Exporté le: ${dateStr} à ${timeStr}`, marginLeft, pageHeight - marginBottom);

    // Numéro de page en bas à droite
    /*const pageText = `Page ${currentPage}`;
    const textWidth = doc.getTextWidth(pageText);
    doc.text(pageText, pageWidth - marginRight - textWidth, pageHeight - marginBottom );*/
    
}

    });
    
    // Sauvegarder le PDF avec nouveau nom
    doc.save(`Rapport_ENSA_P${currentPage}_${dateStr}.pdf`);
});




// Toggle sidebar on mobile
document.getElementById('sidebarToggle').addEventListener('click', function() {
    document.querySelector('.sidebar').classList.toggle('d-none');
    document.querySelector('.sidebar').classList.toggle('d-block');
});

// Make table more responsive
function adaptTableForMobile() {
    if (window.innerWidth < 1205) {
        document.querySelectorAll('table td').forEach(td => {
            const label = td.getAttribute('data-label');
            if (label) {
                td.innerHTML = `<span class="d-inline-block fw-bold" style="width: 120px;">${label}:</span> ${td.innerHTML}`;
            }
        });
    }
}

// Initial adaptation
adaptTableForMobile();

// Re-adapt on resize
window.addEventListener('resize', adaptTableForMobile);



    </script>


<script>
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
</body>
</html>