<?php
session_start();  

// Vérifiez que l'utilisateur est connecté  
if (!isset($_SESSION['APOGEE'])) {     
    header("Location: ../connexion_folder_vf/login_page.php");     
    exit(); 
}

$color = $_SESSION['AVATAR_COLOR'] ?? '#1B2631';

// Connexion à la base de données
require_once('../connexion_folder_vf/db_config.php');

if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    // Requête AJAX - renvoyer seulement le contenu des projets
    ob_start();
    foreach ($projects as $project): ?>
        <!-- Votre code HTML pour une seule carte de projet -->
    <?php endforeach;
    echo ob_get_clean();
    exit();}

// Récupérer les infos de l'étudiant pour l'image de profil
try {
    $query = "SELECT IMG, PRENOM, AVATAR FROM student WHERE APOGEE = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$_SESSION['APOGEE']]);
    $student = $stmt->fetch();
    
    // Déterminer quoi afficher
    $profile_img = !empty($student['IMG']) ? $student['IMG'] : null;
    $avatar_char = $student['AVATAR'] ?? substr($student['PRENOM'], 0, 1);
    $avatar_color = $_SESSION['AVATAR_COLOR'] ?? '#1B2631';
    
    // Stocker en session pour éviter de requêter la base à chaque page
    $_SESSION['PROFILE_IMG'] = $profile_img;
    $_SESSION['AVATAR_CHAR'] = $avatar_char;
} catch (PDOException $e) {
    // En cas d'erreur, utiliser les valeurs par défaut
    $profile_img = $_SESSION['PROFILE_IMG'] ?? null;
    $avatar_char = $_SESSION['AVATAR_CHAR'] ?? '?';
    $avatar_color = $_SESSION['AVATAR_COLOR'] ?? '#1B2631';
}

// Récupération des 4 derniers projets validés (sauf ceux de l'utilisateur connecté) pour le carousel


// Récupération des options pour les filtres
$filieres = $conn->query("SELECT DISTINCT FILIERE FROM student")->fetchAll(PDO::FETCH_COLUMN);
$types = $conn->query("SELECT DISTINCT TYPE FROM project")->fetchAll(PDO::FETCH_COLUMN);
$niveaux = $conn->query("SELECT DISTINCT NIV FROM student")->fetchAll(PDO::FETCH_COLUMN);
$categories = $conn->query("SELECT DISTINCT CATEGORIE FROM project")->fetchAll(PDO::FETCH_COLUMN);
$encadrants = $conn->query("SELECT DISTINCT CONCAT(PRENOM, ' ', NOM) AS NOM_COMPLET FROM prof")->fetchAll(PDO::FETCH_COLUMN);

// Initialisation des filtres depuis GET (tableaux pour valeurs multiples)
$filieres_selected = isset($_GET['filiere']) ? (array)$_GET['filiere'] : [];
$types_selected = isset($_GET['type']) ? (array)$_GET['type'] : [];
$niveaux_selected = isset($_GET['niveau']) ? (array)$_GET['niveau'] : [];
$categories_selected = isset($_GET['categorie']) ? (array)$_GET['categorie'] : [];
$encadrants_selected = isset($_GET['encadrant']) ? (array)$_GET['encadrant'] : [];


/*
// Construction de la requête de base pour les projets
$query = "SELECT p.*, pr.NOM AS PROF_NOM, pr.PRENOM AS PROF_PRENOM, 
                 s.NOM AS ETUD_NOM, s.PRENOM AS ETUD_PRENOM, s.FILIERE, s.NIV
          FROM project p
          JOIN prof pr ON p.ID_PROF = pr.ID_PROF
          JOIN student s ON p.APOGEE = s.APOGEE
          WHERE p.STATUT = 'validé' AND p.APOGEE = ?";

$params = [$_SESSION['APOGEE']];

// Ajout des conditions de filtrage
if (!empty($filieres_selected)) {
    $placeholders = implode(',', array_fill(0, count($filieres_selected), '?'));
    $query .= " AND s.FILIERE IN ($placeholders)";
    $params = array_merge($params, $filieres_selected);
}
    */

    // Récupération du terme de recherche
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';

// Construction de la requête de base pour les projets
/*
$query = "SELECT p.*, pr.NOM AS PROF_NOM, pr.PRENOM AS PROF_PRENOM, 
                 s.NOM AS ETUD_NOM, s.PRENOM AS ETUD_PRENOM, s.FILIERE, s.NIV
          FROM project p
          JOIN prof pr ON p.ID_PROF = pr.ID_PROF
          JOIN student s ON p.APOGEE = s.APOGEE
          WHERE p.STATUT_STU = 'validé' AND p.APOGEE = ?";*/


$query = "SELECT p.*, pr.NOM AS PROF_NOM, pr.PRENOM AS PROF_PRENOM, 
                 s.NOM AS ETUD_NOM, s.PRENOM AS ETUD_PRENOM, s.FILIERE, s.NIV
          FROM project p
          JOIN prof pr ON p.ID_PROF = pr.ID_PROF
          JOIN student s ON p.APOGEE = s.APOGEE
          WHERE  p.APOGEE = ?";

$params = [$_SESSION['APOGEE']];

// Ajout de la condition de recherche si elle existe
if (!empty($search_term)) {
    $query .= " AND p.NOM = ?";
    $params[] = $search_term;
}


// Le reste de votre code pour les autres filtres...









if (!empty($types_selected)) {
    $placeholders = implode(',', array_fill(0, count($types_selected), '?'));
    $query .= " AND p.TYPE IN ($placeholders)";
    $params = array_merge($params, $types_selected);
}

if (!empty($niveaux_selected)) {
    $placeholders = implode(',', array_fill(0, count($niveaux_selected), '?'));
    $query .= " AND s.NIV IN ($placeholders)";
    $params = array_merge($params, $niveaux_selected);
}

if (!empty($categories_selected)) {
    $placeholders = implode(',', array_fill(0, count($categories_selected), '?'));
    $query .= " AND p.CATEGORIE IN ($placeholders)";
    $params = array_merge($params, $categories_selected);
}

if (!empty($encadrants_selected)) {
    $conditions = [];
    foreach ($encadrants_selected as $encadrant) {
        $conditions[] = "CONCAT(pr.PRENOM, ' ', pr.NOM) LIKE ?";
        $params[] = "%$encadrant%";
    }
    $query .= " AND (" . implode(' OR ', $conditions) . ")";
}

// Si aucun filtre n'est appliqué, affichez les projets aléatoirement
if (empty($_GET)) {
    $query .= " ORDER BY RAND()";
} else {
    $query .= " ORDER BY p.DATE_DEP DESC";
}

// Préparation et exécution de la requête
$stmt = $conn->prepare($query);
$stmt->execute($params);
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);











// Configuration de la pagination
$projects_per_page = 3;
$total_projects = count($projects);
$total_pages = ceil($total_projects / $projects_per_page);

// Récupérer le numéro de page courant
$current_page = isset($_GET['page']) ? max(1, min((int)$_GET['page'], $total_pages)) : 1;

// Calculer l'offset
$offset = ($current_page - 1) * $projects_per_page;

// Extraire seulement les projets pour la page courante
$paginated_projects = array_slice($projects, $offset, $projects_per_page);






?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Projets | ENSA Kénitra</title>
    <!-- Liens CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap">
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.0/css/line.css">
    <link rel="stylesheet" href="style.css">



    <style>
    /* Style personnalisé pour la pagination */
    .pagination .page-item .page-link {
        color: #002C84;
        border: 1px solid #002C84;
    }
    
    .pagination .page-item.active .page-link {
        background-color: #002C84;
        border-color: #002C84;
        color: white;
    }
    
    .pagination .page-item.disabled .page-link {
        color: #6c757d;
        border-color: #dee2e6;
    }
    
    .pagination .page-item:not(.active):not(.disabled) .page-link:hover {
        background-color: rgba(0, 44, 132, 0.1);
    }
</style>
</head>
<body>
    <header class="header-wrapper bg-white shadow-sm">
        <nav class="navbar navbar-expand-lg navbar-light container">
            <a class="navbar-brand logo" href="../projets_page_folder_vf_acceuil_esp_etudiant/home_student.php">
                <img src="images/ensa_logo.png" alt="Logo ENSA">
            </a>
  
            <div class="collapse navbar-collapse justify-content-between" id="navbarNav">
                <ul class="navbar-nav nav-links">
                    <li class="nav-item"><a class="nav-link" href="../projets_page_folder_vf_acceuil_esp_etudiant/home_student.php">Accueil</a></li>
                    <li class="nav-item"><a class="nav-link" href="../soumission_folder_vf/soumission.php">Soumission</a></li>
                    <li class="nav-item"><a class="nav-link" href="../projets_historique_folder_vf_projets/projets.php">Projets</a></li>
                    <li class="nav-item"><a class="nav-link" href="../resultats_folder_vf/resultats.php">Résultats</a></li>
                    <li class="nav-item"><a class="nav-link" href="../calendrier_folder_vf/calendrier.php">Calendrier</a></li>
                </ul>       
            </div>

            <div class="d-flex align-items-center gap-3 links_container">
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center text-black text-decoration-none dropdown-toggle" id="dropdownUser" data-bs-toggle="dropdown" aria-expanded="false">
                       
                            <div class="avatar-circle" style="background-color: <?php echo htmlspecialchars($avatar_color); ?>; width:40px; height: 40px;">
                                <?php echo htmlspecialchars($avatar_char); ?>
                            </div>
                                  
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="dropdownUser">
                        <li><a class="dropdown-item d-flex align-items-center" href="../profil_vf/profil.php">
                            <i class="bi bi-person-circle me-2"></i>
                            <span>Profil</span>
                        </a></li>
                        <li><a class="dropdown-item d-flex align-items-center" href="../rapports_archive_user_folder_vf/rapports.php">
                            <i class="bi bi-file-earmark-bar-graph me-2"></i>
                            <span>Rapports</span>
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item d-flex align-items-center text-danger" href="../logout.php">
                            <i class="bi bi-box-arrow-right me-2"></i>
                            <span>Déconnexion</span>
                        </a></li>
                    </ul>
                </div>
                
                <button class="navbar-toggler toggle" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
            </div>
        </nav>
    </header>
    <!--
    <div class="input_container">
        <div class="search-box">
            <input type="text" class="form-control" placeholder="Rechercher un projet...">
            <i class="bi bi-search"></i>
        </div>
    </div>
    -->
    <div class="input_container">
    <form id="searchForm" method="GET" class="search-box">
        <input type="text" name="search" class="form-control" placeholder="Rechercher un projet..." 
               value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
        <i class="bi bi-search"></i>
    </form>
</div>



    <header>
        <nav class="navbar navbar-expand-sm bg-body-tertiary">
            <div class="container">
                <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#bsbSidebar1" aria-controls="bsbNavbar" aria-label="Toggle Navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="offcanvas offcanvas-end" tabindex="-1" id="bsbNavbar" aria-labelledby="bsbNavbarLabel">
                    <div class="offcanvas-header">
                        <h5 class="offcanvas-title" id="bsbNavbarLabel">Menu</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                    </div>
                    <div class="offcanvas-body">
                        <ul class="navbar-nav">
                            <li class="nav-item me-3">
                                <a class="nav-link" href="#!" data-bs-toggle="offcanvas" data-bs-target="#bsbSidebar1" aria-controls="bsbSidebar1">
                                    <i class="bi-filter-left fs-4 lh-1" style="color: #002C84;">Filtrer</i>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>
    </header>

<div class="container" style="margin-top:19px;">
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4" id="projects-grid">
<?php foreach ($paginated_projects as $project): ?>
        <!-- Single Card -->
        <div class="col" data-project-id="<?= $project['ID_PROJECT'] ?>">
            <div class="card h-100">
                <div class="card-image">
                    <img src="<?= !empty($project['IMG']) ? '../../uploads/images/'.$project['IMG'] : 'images/default.jpg' ?>" class="card-img-top">
                    <div class="card-tag"><?= htmlspecialchars($project['CATEGORIE']) ?></div>
                </div>

            
                
                <div class="card-body">
                    <h3 class="card-title"><?= htmlspecialchars($project['NOM']) ?></h3>
                    <p class="card-text"><?= htmlspecialchars($project['DESCRIPTION']) ?></p>
                </div>
                
                <div class="card-footer">
                    <div class="card-profile">
                        
                        <div class="card-profile-info">
                            <span class="card-profile-role">Encadré par <?= htmlspecialchars($project['PROF_PRENOM'].' '.$project['PROF_NOM']) ?></span>
                        </div>
                    </div>
                        <a href="../page_chaque_projet_folder_vf/projet<?php echo $project['ID_PROJECT']; ?>.php" class="card-button">En savoir plus</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <!-- Pagination -->
<nav aria-label="Page navigation" class="mt-4">
    <ul class="pagination justify-content-center">
        <li class="page-item <?= $current_page == 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $current_page - 1])) ?>" tabindex="-1">Précédent</a>
        </li>
        
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <li class="page-item <?= $i == $current_page ? 'active' : '' ?>">
                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
            </li>
        <?php endfor; ?>
        
        <li class="page-item <?= $current_page == $total_pages ? 'disabled' : '' ?>">
            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $current_page + 1])) ?>">Suivant</a>
        </li>
    </ul>
</nav>
</div>


    
    <aside class="offcanvas offcanvas-start sidebar" tabindex="-1" id="bsbSidebar1" aria-labelledby="bsbSidebar1Label">
        <div class="offcanvas-header sidebar-header">
            <a class="offcanvas-title sidebar-brand" id="bsbSidebar1Label" href="#!">
                <p>Filtrer les projets</p>
            </a>
            <button type="button" class="btn-close close-btn" data-bs-dismiss="offcanvas" aria-label="Fermer" onclick="closeSidebar()">×</button>
        </div>
        
        <div class="offcanvas-body sidebar-body">
            <form id="filterForm" method="GET">
                <hr class="sidebar-divider">
                <ul class="navbar-nav">
                

                    <li class="nav-item">
                        <a class="nav-link" href="#" onclick="toggleMenu('typeMenu')">
                            <span>Type de projet</span>
                            <i class="arrow">&#x25BC;</i>
                        </a>
                        <ul class="submenu" id="typeMenu">
                            <?php foreach ($types as $t): ?>
                                <li>
                                    <input type="checkbox" name="type[]" value="<?= htmlspecialchars($t) ?>" <?= in_array($t, $types_selected) ? 'checked' : '' ?>>
                                    <?= htmlspecialchars($t) ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
        
                    <li class="nav-item">
                        <a class="nav-link" href="#" onclick="toggleMenu('niveauMenu')">
                            <span>Niveau</span>
                            <i class="arrow">&#x25BC;</i>
                        </a>
                        <ul class="submenu" id="niveauMenu">
                            <?php foreach ($niveaux as $n): ?>
                                <li>
                                    <input type="checkbox" name="niveau[]" value="<?= htmlspecialchars($n) ?>" <?= in_array($n, $niveaux_selected) ? 'checked' : '' ?>>
                                    <?= htmlspecialchars($n) ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="#" onclick="toggleMenu('categorieMenu')">
                            <span>Catégorie</span>
                            <i class="arrow">&#x25BC;</i>
                        </a>
                        <ul class="submenu" id="categorieMenu">
                            <?php foreach ($categories as $c): ?>
                                <li>
                                    <input type="checkbox" name="categorie[]" value="<?= htmlspecialchars($c) ?>" <?= in_array($c, $categories_selected) ? 'checked' : '' ?>>
                                    <?= htmlspecialchars($c) ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
        
                    <li class="nav-item">
                        <a class="nav-link" href="#" onclick="toggleMenu('encadrantMenu')">
                            <span>Encadrant</span>
                            <i class="arrow">&#x25BC;</i>
                        </a>
                        <ul class="submenu" id="encadrantMenu">
                            <?php foreach ($encadrants as $e): ?>
                                <li>
                                    <input type="checkbox" name="encadrant[]" value="<?= htmlspecialchars($e) ?>" <?= in_array($e, $encadrants_selected) ? 'checked' : '' ?>>
                                    <?= htmlspecialchars($e) ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                </ul>
        
                <hr class="sidebar-divider">
                
                <div class="sidebar-footer">
                    <button type="submit" class="btn-filter">Filtrer</button>
                </div>
            </form>
        </div>
    </aside>

    <footer class="footer-wrapper">
        <div class="footer-section">
            <div class="footer-info">
                <img src="images/LOGO-ENSA.png" alt="Logo ENSA">
                <p><a href="https://ensa.uit.ac.ma/" target="_blank" style="color: white;">Visiter le site officiel</a></p>
            </div>
            <div class="footer-links">
                <h3>Liens utiles</h3>
                <ul>
                    <li><a href="../projets_espaces_folder_vf_apropos/index.html">À propos</a></li>
                    <li><a href="../reglements_page_folder_vf/index.html">Règlement</a></li>
                    <li><a href="../contact_folder_vf/index.html">Contact</a></li>
                </ul>
            </div>
            <div class="footer-contact">
                <h3>Réseaux Sociaux</h3>
                <div class="footer_icons">
                    <a target="_blank" href="https://www.instagram.com/ensak.official" class="text-dark"><i class="fab fa-instagram fa-lg"></i></a>
                    <a target="_blank" href="https://ma.linkedin.com/company/ensa-kenitra-official" class="text-dark"><i class="fab fa-linkedin fa-lg"></i></a>
                </div>
                <a class="contact_link" href="../contact_folder_vf/index.html">Contactez-nous</a>
                <p>(+212) 5 37 37 67 65</p>
                <p>Campus universitaire, BP 241, Kénitra – Maroc</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>École Nationale des Sciences Appliquées © 2025 Université Ibn Tofail. All Rights Reserved.</p>
        </div>
    </footer>
    

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
                            
 <script src="script.js"></script>
</body>
</html>