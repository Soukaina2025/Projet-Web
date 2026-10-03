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






try {
    $apogee = $_SESSION['APOGEE'];
    
    // Pagination
    $livrables_per_page = 6;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $livrables_per_page;
    
    // Recherche et filtres
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
    
    // Requête de base avec conditions
    $base_query = "FROM project 
                  WHERE APOGEE = :apogee 
                  AND (FICHIER IS NOT NULL OR FILE1 IS NOT NULL OR FILE2 IS NOT NULL OR FILE3 IS NOT NULL)";
    
    // Ajout des conditions de recherche et filtre
    $params = [':apogee' => $apogee];
    $conditions = [];
    if (!empty($search)) {
    $conditions[] = "(CATEGORIE = :search_categorie)";
    $params[':search_categorie'] = $search;
}

/*    
    if (!empty($search)) {
        $conditions[] = "(CATEGORIE LIKE :search_categorie)";
        $params[':search_categorie'] = "%$search%";
    }*/
    
    if ($filter !== 'all') {
    if ($filter === 'pdf') {
        $conditions[] = "(
            (FICHIER LIKE '%.pdf') OR 
            (FILE1 LIKE '%.pdf') OR 
            (FILE2 LIKE '%.pdf') OR 
            (FILE3 LIKE '%.pdf')
        )";
    } elseif ($filter === 'ppt') {
        $conditions[] = "(
            (FICHIER LIKE '%.ppt' OR FICHIER LIKE '%.pptx') OR
            (FILE1 LIKE '%.ppt' OR FILE1 LIKE '%.pptx') OR
            (FILE2 LIKE '%.ppt' OR FILE2 LIKE '%.pptx') OR
            (FILE3 LIKE '%.ppt' OR FILE3 LIKE '%.pptx')
        )";
    } elseif ($filter === 'code') {
        $conditions[] = "(
            (FICHIER LIKE '%.php' OR FICHIER LIKE '%.js' OR FICHIER LIKE '%.html' OR 
             FICHIER LIKE '%.css' OR FICHIER LIKE '%.py' OR FICHIER LIKE '%.java' OR 
             FICHIER LIKE '%.c' OR FICHIER LIKE '%.cpp' OR FICHIER LIKE '%.sql') OR
            (FILE1 LIKE '%.php' OR FILE1 LIKE '%.js' OR FILE1 LIKE '%.html' OR 
             FILE1 LIKE '%.css' OR FILE1 LIKE '%.py' OR FILE1 LIKE '%.java' OR 
             FILE1 LIKE '%.c' OR FILE1 LIKE '%.cpp' OR FILE1 LIKE '%.sql') OR
            (FILE2 LIKE '%.php' OR FILE2 LIKE '%.js' OR FILE2 LIKE '%.html' OR 
             FILE2 LIKE '%.css' OR FILE2 LIKE '%.py' OR FILE2 LIKE '%.java' OR 
             FILE2 LIKE '%.c' OR FILE2 LIKE '%.cpp' OR FILE2 LIKE '%.sql') OR
            (FILE3 LIKE '%.php' OR FILE3 LIKE '%.js' OR FILE3 LIKE '%.html' OR 
             FILE3 LIKE '%.css' OR FILE3 LIKE '%.py' OR FILE3 LIKE '%.java' OR 
             FILE3 LIKE '%.c' OR FILE3 LIKE '%.cpp' OR FILE3 LIKE '%.sql')
        )";
    } elseif ($filter === 'archive') {
        $conditions[] = "(
            (FICHIER LIKE '%.zip' OR FICHIER LIKE '%.rar' OR FICHIER LIKE '%.7z') OR
            (FILE1 LIKE '%.zip' OR FILE1 LIKE '%.rar' OR FILE1 LIKE '%.7z') OR
            (FILE2 LIKE '%.zip' OR FILE2 LIKE '%.rar' OR FILE2 LIKE '%.7z') OR
            (FILE3 LIKE '%.zip' OR FILE3 LIKE '%.rar' OR FILE3 LIKE '%.7z')
        )";
    }
}
    
    if (!empty($conditions)) {
        $base_query .= " AND " . implode(" AND ", $conditions);
    }
    
    // Requête pour compter le nombre total de livrables
    $count_query = "SELECT COUNT(*) " . $base_query;
    $count_stmt = $conn->prepare($count_query);
    foreach ($params as $key => &$value) {
        $count_stmt->bindParam($key, $value);
    }
    $count_stmt->execute();
    $total_livrables = $count_stmt->fetchColumn();
    
    // Calcul du nombre total de pages
    $total_pages = ceil($total_livrables / $livrables_per_page);
    
    // Requête pour récupérer les livrables avec pagination
    $select_query = "SELECT ID_PROJECT, NOM, TYPE, CATEGORIE, FICHIER, FILE1, FILE2, FILE3, DATE_DEP " . 
                   $base_query . " ORDER BY DATE_DEP DESC LIMIT :limit OFFSET :offset";
    
    $stmt = $conn->prepare($select_query);
    foreach ($params as $key => &$value) {
        $stmt->bindParam($key, $value);
    }
    
    // Bind des paramètres de pagination
    $stmt->bindParam(':limit', $livrables_per_page, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $livrables = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    echo "Erreur de connexion : " . $e->getMessage();
    exit(); // Ajouté pour arrêter l'exécution en cas d'erreur
}
$conn = null;

// Fonction pour déterminer le type de fichier
function getFileType($filename) {
    if (!$filename) return null;
    
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    if ($extension === 'pdf') return 'pdf';
    if (in_array($extension, ['ppt', 'pptx'])) return 'ppt';
    if (in_array($extension, ['zip', 'rar', '7z'])) return 'archive';
    if (in_array($extension, ['php', 'js', 'html', 'css', 'py', 'java', 'c', 'cpp', 'sql'])) return 'code';
    if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) return 'image';
    if (in_array($extension, ['doc', 'docx'])) return 'word';
    
    return 'other';
}

// Fonction pour nettoyer le nom du fichier
function cleanFileName($filename) {
    // Supprimer l'ID unique au début du nom de fichier
    $filename = preg_replace('/^[a-f0-9]+_/', '', $filename);
    return $filename;
}



// Fonction pour limiter le nombre de caractères
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
    <title>Archive des Livrables | ENSA Kénitra</title>

    <!-- Liens CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap">
    <link rel="stylesheet" href="style.css">
    <style>
        :root {
            --primary-blue: #002C84;
            --secondary-blue: #1A4B8C;
            --light-blue: #E6F0FF;
            --dark-blue: #001A4B;
        }
        
        .title-text {
            color: var(--primary-blue);
            font-weight: 700;
        }
        
        /*.search-box {
            position: relative;
            width: 300px;
        }
        
        .search-box .form-control {
            padding-left: 40px;
            border-radius: 20px;
            border: 1px solid #ddd;
        }
        
        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary-blue);
        }*/
        
        .filter-buttons .btn-outline-primary {
            border-color: var(--primary-blue);
            color: var(--primary-blue);
        }
        
        .filter-buttons .btn-outline-primary.active {
            background-color: var(--primary-blue);
            color: white;
        }
        
        .filter-buttons .btn-outline-primary:hover {
            background-color: var(--light-blue);
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 44, 132, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 44, 132, 0.15);
        }
        
        .card-title {
            color: var(--dark-blue);
            font-weight: 600;
        }
        /*
        .badge.bg-primary-blue {
            background-color: var(--primary-blue) !important;
        }*/
        
        .page-item.active .page-link {
            background-color: var(--primary-blue);
            border-color: var(--primary-blue);
        }
        
        .page-link {
            color: var(--primary-blue);
        }
        
        .file-icon {
            font-size: 1.5rem;
            margin-right: 10px;
            color: var(--primary-blue);
        }
        
        .download-btn {
            background-color: var(--primary-blue);
            color: white;
            border: none;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .download-btn:hover {
            background-color: var(--secondary-blue);
            color: white;
        }


        /* Styles pour les badges de type de fichier */
.badge-pdf {
    background-color: #FF5252 !important; /* Rouge pour PDF */
    color: white;
}

.badge-archive {
    background-color: #795548 !important; /* Marron pour archives */
    color: white;
}

.badge-ppt {
    background-color: #FF9100 !important; /* Orange pour PPT */
    color: white;
}

.badge-code {
    background-color: #4CAF50 !important; /* Vert pour code */
    color: white;
}

.badge-word {
    background-color: #2196F3 !important; /* Bleu pour Word */
    color: white;
}

.badge-image {
    background-color: #9C27B0 !important; /* Violet pour images */
    color: white;
}

.badge-other {
    background-color: #607D8B !important; /* Gris pour autres */
    color: white;
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
                    <li class="nav-item"><a class="nav-link active" href="../resultats_folder_vf/resultats.php">Résultats</a></li>
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

    <main class="container my-5">
        <div class="row mb-4">
            <div class="col-12">
                <h1 class="mb-4 title-text">Archives des Livrables</h1>
                <form method="GET" action="" class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div class="search-box">
                        <input type="text" name="search" class="form-control" placeholder="Rechercher une catégorie ..." value="<?php echo htmlspecialchars($search); ?>">
                        <i class="bi bi-search"></i>
                    </div>
                    <div class="filter-buttons">
                        <div class="btn-group" role="group">
                            <button type="submit" name="filter" value="all" class="btn filter-button btn-outline-primary <?php echo $filter === 'all' ? 'active' : ''; ?>">Tous</button>
                            <button type="submit" name="filter" value="pdf" class="btn filter-button btn-outline-primary <?php echo $filter === 'pdf' ? 'active' : ''; ?>">PDF</button>
                            <button type="submit" name="filter" value="ppt" class="btn filter-button btn-outline-primary <?php echo $filter === 'ppt' ? 'active' : ''; ?>">PPT</button>
                            <button type="submit" name="filter" value="code" class="btn filter-button btn-outline-primary <?php echo $filter === 'code' ? 'active' : ''; ?>">Code</button>
                            <button type="submit" name="filter" value="archive" class="btn filter-button btn-outline-primary <?php echo $filter === 'archive' ? 'active' : ''; ?>">Archives</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>




        <?php if (empty($livrables)): ?>
            <div class="alert alert-info">Aucun livrable trouvé.</div>
        <?php else: ?>
            <div class="row g-4" id="documents-container">
                <?php foreach ($livrables as $livrable): 
                    // Récupérer tous les fichiers du projet
                    $fichiers = [];
                    if ($livrable['FICHIER']) $fichiers[] = ['name' => $livrable['FICHIER'], 'field' => 'FICHIER'];
                    if ($livrable['FILE1']) $fichiers[] = ['name' => $livrable['FILE1'], 'field' => 'FILE1'];
                    if ($livrable['FILE2']) $fichiers[] = ['name' => $livrable['FILE2'], 'field' => 'FILE2'];
                    if ($livrable['FILE3']) $fichiers[] = ['name' => $livrable['FILE3'], 'field' => 'FILE3'];
                    
                    foreach ($fichiers as $fichier):
                        $fileType = getFileType($fichier['name']);
                        if (!$fileType) continue;
                        
                        // Vérifier si le filtre correspond au type de fichier
                        if ($filter !== 'all' && $filter !== $fileType) continue;
                        
                        $icon = '';
                        //$badgeClass = 'bg-primary-blue';
                        $fileIcon = '';
                        
                        switch ($fileType) {
                            case 'pdf':
                                $icon = 'bi-file-earmark-pdf-fill';
                                $fileIcon = '<i class="bi bi-filetype-pdf file-icon text-danger"></i>';
                                break;
                            case 'ppt':
                                $icon = 'bi-file-earmark-ppt-fill';
                                $fileIcon = '<i class="bi bi-filetype-ppt file-icon text-warning"></i>';
                                break;
                            case 'code':
                                $icon = 'bi-file-earmark-code-fill';
                                $fileIcon = '<i class="bi bi-filetype-js file-icon text-info"></i>';
                                break;
                            case 'archive':
                                $icon = 'bi-file-earmark-zip-fill';
                                $fileIcon = '<i class="bi bi-file-earmark-zip file-icon text-secondary"></i>';
                                break;
                            case 'image':
                                $icon = 'bi-file-earmark-image-fill';
                                $fileIcon = '<i class="bi bi-filetype-jpg file-icon text-success"></i>';
                                break;
                            case 'word':
                                $icon = 'bi-file-earmark-word-fill';
                                $fileIcon = '<i class="bi bi-filetype-docx file-icon text-primary"></i>';
                                break;
                            default:
                                $icon = 'bi-file-earmark-fill';
                                $fileIcon = '<i class="bi bi-file-earmark file-icon text-dark"></i>';
                        }
                ?>
                <div class="col-md-6 col-lg-4 document-item" data-type="<?php echo $fileType; ?>">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
<span class="badge badge-<?php echo $fileType; ?>"><?php echo strtoupper($fileType); ?></span>                                
<small class="text-muted">
                                    <?php echo $livrable['DATE_DEP'] ? date('d/m/Y', strtotime($livrable['DATE_DEP'])) : 'Non validé'; ?>
                                </small>
                            </div>
                            <h5 class="card-title"><?php echo htmlspecialchars(limitChars($livrable['NOM'],20)); ?></h5>
                            <p class="card-text text-muted">
                                <span class="badge bg-light text-dark"><?php echo htmlspecialchars($livrable['TYPE']); ?></span>
                                <span class="badge bg-light text-dark"><?php echo htmlspecialchars($livrable['CATEGORIE']); ?></span>
                            </p>
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <div class="d-flex align-items-center">
                                    <?php echo $fileIcon; ?>
                                    <small class="text-truncate" style="max-width: 180px;"><?php echo htmlspecialchars(cleanFileName(basename($fichier['name']))); ?></small>
                                </div>
                                <a href="../../uploads/projets/<?php echo htmlspecialchars($fichier['name']); ?>" class="download-btn" download>
                                    <i class="bi bi-download"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; endforeach; ?>
            </div>

            <!-- Pagination -->
            <nav aria-label="Page navigation" class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" 
                           href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&filter=<?php echo urlencode($filter); ?>" 
                           tabindex="-1">Précédent</a>
                    </li>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                            <a class="page-link" 
                               href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&filter=<?php echo urlencode($filter); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    
                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                        <a class="page-link" 
                           href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&filter=<?php echo urlencode($filter); ?>">
                            Suivant
                        </a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </main>

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

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>