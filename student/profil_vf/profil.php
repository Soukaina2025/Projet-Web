<?php
session_start();  

// Vérifiez que l'utilisateur est connecté  
if (!isset($_SESSION['APOGEE'])) {     
    // Redirige vers la page de connexion si non connecté     
    header("Location: /ensa_project/web_projet_vf/connexion_folder_vf/login_page.php");     
    exit(); 
}

// Connexion à la base de données
require_once('../connexion_folder_vf/db_config.php');

// Récupérer les informations de l'étudiant
$apogee = $_SESSION['APOGEE'];
try {
    $query = "SELECT * FROM student WHERE APOGEE = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$apogee]);
    $student = $stmt->fetch();
    
    if (!$student) {
        throw new Exception("Étudiant non trouvé");
    }
} catch (PDOException $e) {
    error_log("Erreur de base de données: " . $e->getMessage());
    die("Une erreur est survenue lors de la récupération des données.");
} catch (Exception $e) {
    die($e->getMessage());
}

// Traitement de l'upload d'image
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_image'])) {
    $target_dir = "../../uploads/profiles/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_extension = pathinfo($_FILES["profile_image"]["name"], PATHINFO_EXTENSION);
    $new_filename = "profile_" . $apogee . "." . $file_extension;
    $target_file = $target_dir . $new_filename;
    
    // Vérifier et déplacer le fichier uploadé
    if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
        try {
            // Mettre à jour la base de données
            $update_query = "UPDATE student SET IMG = ? WHERE APOGEE = ?";
            $update_stmt = $conn->prepare($update_query);
            $img_path = "uploads/profiles/" . $new_filename;
            $update_stmt->execute([$img_path, $apogee]);
            
            // Mettre à jour la session
            $_SESSION['PROFILE_IMG'] = $img_path;
            
            // Rediriger pour éviter le rechargement du formulaire
            header("Location: profil.php");
            exit();
        } catch (PDOException $e) {
            error_log("Erreur lors de la mise à jour de l'image: " . $e->getMessage());
            // Vous pourriez ajouter un message d'erreur à afficher à l'utilisateur
        }
    }
}

// Déterminer quelle image afficher (IMG si existe, sinon avatar)
$profile_img = isset($student['IMG']) && !empty($student['IMG']) ? 
    $student['IMG'] : null;
    
$avatar_color = $_SESSION['AVATAR_COLOR'] ?? '#1B2631';
$avatar_char = $student['AVATAR'] ?? substr($student['PRENOM'], 0, 1);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil | ENSA Kénitra</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
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

    <?php
// Fonction pour limiter le nombre de caractères
function limitChars($string, $limit, $suffix = '...') {
    if (strlen($string) > $limit) {
        return substr($string, 0, $limit) . $suffix;
    }
    return $string;
}
?>

    <!-- Main Content -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <!-- Sidebar -->
                <div class="col-lg-4 mb-4 mb-lg-0">
                    <div class="ensa-card mb-4">
                        <div class="ensa-card-header p-3">
                            <h5 class="mb-0 profil_title">Profil Étudiant</h5>
                        </div>
                        <div class="card-body text-center">
                            <div class="text-center mb-3">
                                <div class="profile-img-container position-relative mb-5" style="margin-bottom: 70px;">
                                 
                                        <div class="avatar-circle profile-img mb-2 big_profile_img" style="background-color: <?php echo htmlspecialchars($avatar_color); ?>; width:120px; height:120px; font-size:48px; line-height:120px;">
                                            <?php echo htmlspecialchars($avatar_char); ?>
                                        </div>
                                   
                                    
                                
                                </div>
                            </div>

                            <h4 class="mb-1"><?php echo htmlspecialchars($student['PRENOM'] . ' ' . $student['NOM']); ?></h4>
                            <p class="text-muted mb-3"><?php echo htmlspecialchars($student['NIV']); ?> - <?php echo htmlspecialchars($student['FILIERE']); ?></p>
                            
                            <div class="buttons_container d-grid gap-2 mb-4">
                                <a href="../soumission_folder_vf/soumission.php" class="btn ensa-btn-primary">
                                    <i class="bi bi-plus-circle me-2"></i>Soumettre un projet
                                </a>
                                <a href="../projets_historique_folder_vf_projets/projets.php" class="btn ensa-btn-secondary">
                                    <i class="bi bi-folder me-2"></i>Voir mes projets
                                </a>
                            </div>
                            
                            <hr>
                            
                            <div class="info_container row text-start">
                                <div class="col-12 mb-4">
                                    <h6 class="mb-1">
                                        <i class="bi bi-person-badge me-2"></i>
                                        Numéro d'apogée
                                    </h6>
                                    <span class="text-muted"><?php echo htmlspecialchars($student['APOGEE']); ?></span>
                                </div>
                                <div class="col-12 mb-4">
                                    <h6 class="mb-1">
                                        <i class="bi bi-envelope me-2"></i>
                                        Email
                                    </h6>
                                    <span class="text-muted"><?php echo htmlspecialchars($student['EMAIL_INST']); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="ensa-card mb-4">
                        <div class="ensa-card-header p-3">
                            <h5 class="mb-0">Statistiques</h5>
                        </div>
                        <div class="card-body">
                            <?php
                            try {
                                // Récupérer les statistiques des projets
                                $stats_query = "SELECT 
                                    COUNT(*) as total,
                                    SUM(CASE WHEN STATUT_STU = 'validé' THEN 1 ELSE 0 END) as accepted,
                                    SUM(CASE WHEN STATUT_STU = 'refusé' THEN 1 ELSE 0 END) as rejected,
                                    SUM(CASE WHEN STATUT_STU = 'en_attente' OR STATUT_STU IS NULL THEN 1 ELSE 0 END) as pending
                                    FROM project WHERE APOGEE = ?";
                                $stats_stmt = $conn->prepare($stats_query);
                                $stats_stmt->execute([$apogee]);
                                $stats = $stats_stmt->fetch();
                            } catch (PDOException $e) {
                                error_log("Erreur lors de la récupération des statistiques: " . $e->getMessage());
                                $stats = ['total' => 0, 'accepted' => 0, 'rejected' => 0, 'pending' => 0];
                            }
                            ?>
                            <div class="row">
                                <div class="col-6 mb-3">
                                    <div class="stat-card">
                                        <div class="stat-number"><?php echo htmlspecialchars($stats['total']); ?></div>
                                        <div class="stat-label">Projets soumis</div>
                                    </div>
                                </div>
                                <div class="col-6 mb-3">
                                    <div class="stat-card">
                                        <div class="stat-number"><?php echo htmlspecialchars($stats['accepted']); ?></div>
                                        <div class="stat-label">Projets validés</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="stat-card">
                                        <div class="stat-number"><?php echo htmlspecialchars($stats['rejected']); ?></div>
                                        <div class="stat-label">Projets refusés</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="stat-card">
                                        <div class="stat-number"><?php echo htmlspecialchars($stats['pending']); ?></div>
                                        <div class="stat-label">En attente</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Main Content -->
                <div class="col-lg-8">
                    <div class="ensa-card mb-4">
                        <div class="card-body p-0">
                            <ul class="nav nav-tabs" id="profileTab" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview-tab-pane" type="button" role="tab" aria-controls="overview-tab-pane" aria-selected="true">Aperçu</button>
                                </li>
                            </ul>
                            <div class="tab-content p-4" id="profileTabContent">
                                <!-- Overview Tab -->
                                <div class="tab-pane fade show active" id="overview-tab-pane" role="tabpanel" aria-labelledby="overview-tab" tabindex="0">
                                    <h5 class="mb-4">Informations Personnelles</h5>
                                    <div class="row mb-4">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Nom</label>
                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($student['NOM']); ?>" readonly>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Prénom</label>
                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($student['PRENOM']); ?>" readonly>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Numéro d'apogée</label>
                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($student['APOGEE']); ?>" readonly>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Email</label>
                                            <input type="email" class="form-control" value="<?php echo htmlspecialchars($student['EMAIL_INST']); ?>" readonly>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Niveau</label>
                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($student['NIV']); ?>" readonly>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Filière</label>
                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($student['FILIERE']); ?>" readonly>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="ensa-card">
                        <div class="ensa-card-header p-3">
                            <h5 class="mb-0">Dernières Activités</h5>
                        </div>
                        <div class="card-body">
                            <div class="list-group list-group-flush">
                                <?php
                                try {
                                    // Récupérer les dernières activités (projets récents)
                                    $activities_query = "SELECT * FROM project WHERE APOGEE = ? ORDER BY DATE_DEP DESC LIMIT 3";
                                    $activities_stmt = $conn->prepare($activities_query);
                                    $activities_stmt->execute([$apogee]);
                                    
                                    if ($activities_stmt->rowCount() > 0) {
                                        while ($activity = $activities_stmt->fetch()) {
                                            $icon = '';
                                            $text_class = 'text-muted';
                                            $date = $activity['DATE_DEP'] ? date('d F Y', strtotime($activity['DATE_DEP'])) : 'Date inconnue';
                                            
                                            if ($activity['STATUT_STU'] === 'validé') {
                                                $icon = 'bi-file-earmark-check text-primary';
                                                $status_text = 'Projet accepté';
                                            } elseif ($activity['STATUT_STU'] === 'refusé') {
                                                $icon = 'bi-file-earmark-excel text-danger';
                                                $status_text = 'Projet refusé';
                                                $text_class = 'text-danger';
                                            } else {
                                                $icon = 'bi-clock-history text-warning';
                                                $status_text = 'Projet en attente';
                                            }
                                            ?>
                                            <div class="list-group-item border-0 px-0 py-3">
                                                <div class="d-flex">
                                                    <div class="p-2 me-3">
                                                        <i class="bi <?php echo $icon; ?>"></i>
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-1 <?php echo $text_class; ?>"><?php echo $status_text; ?></h6>
                                                        <p class="mb-0 text-muted small"><?php echo htmlspecialchars(limitChars($activity['NOM'],20)); ?>: <?php echo htmlspecialchars(substr($activity['DESCRIPTION'], 0, 200)); ?>...</p>
                                                        <span class="text-muted small"><?php echo $date; ?></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php
                                        }
                                    } else {
                                        echo '<p class="text-muted">Aucune activité récente</p>';
                                    }
                                } catch (PDOException $e) {
                                    error_log("Erreur lors de la récupération des activités: " . $e->getMessage());
                                    echo '<p class="text-muted">Erreur lors du chargement des activités</p>';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer-wrapper">
        <div class="footer-section">
            <div class="footer-info">
                <img src="images/LOGO-ENSA.png" alt="Logo ENSA">
                <p><a href="https://ensa.uit.ac.ma/" target="_blank" style="color: white; ">Visiter le site officiel</a></p>
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
</body>
</html>