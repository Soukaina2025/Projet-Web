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
    $projects_per_page = 8;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $projects_per_page;
    
    // Requête pour compter le nombre total de projets
    $count_stmt = $conn->prepare("SELECT COUNT(*) FROM project WHERE APOGEE = :apogee");
    $count_stmt->bindParam(':apogee', $apogee);
    $count_stmt->execute();
    $total_projects = $count_stmt->fetchColumn();
    
    // Calcul du nombre total de pages
    $total_pages = ceil($total_projects / $projects_per_page);
    
    // Requête pour récupérer les projets avec pagination
    $stmt = $conn->prepare("SELECT p.ID_PROJECT, p.*, pr.NOM as PROF_NOM, pr.PRENOM as PROF_PRENOM 
                          FROM project p 
                          JOIN prof pr ON p.ID_PROF = pr.ID_PROF 
                          WHERE p.APOGEE = :apogee
                          ORDER BY p.DATE_DEP DESC
                          LIMIT :limit OFFSET :offset");
    $stmt->bindParam(':apogee', $apogee);
    $stmt->bindParam(':limit', $projects_per_page, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    echo "Erreur de connexion : " . $e->getMessage();
}
$conn = null;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Résultats | ENSA Kénitra</title>
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
            <div class="col-lg-8" style="width:100%;">
                <div class="ensa-card mb-4">
                    <div class="card-body p-0">
                        <ul class="nav nav-tabs" id="profileTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="projects-tab" data-bs-toggle="tab" data-bs-target="#projects-tab-pane" type="button" role="tab" aria-controls="projects-tab-pane" aria-selected="true">Mes Résultats</button>
                            </li>
                        </ul>

                        <div class="tab-content p-4" id="profileTabContent">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ID PROJET</th>
                                            <th>Titre</th>
                                            <th>Type</th>
                                            <th>Catégorie</th>
                                            <th>Encadrant</th>
                                            <th>Date Soumission</th>
                                            <th>Statut</th>
                                            <th>Remarques</th>
                                            <th>Note</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($projects as $project): 
                                            // Déterminer le badge pour le type
                                            $type_badge = '';
                                            $type_text = '';
                                            if (strpos($project['TYPE'], 'module') !== false) {
                                                $type_badge = 'bg-primary-subtle text-info';
                                                $type_text = 'Module';
                                            } elseif (strpos($project['TYPE'], 'pfe') !== false) {
                                                $type_badge = 'bg-success-subtle text-success';
                                                $type_text = 'Stage PFE';
                                            } elseif (strpos($project['TYPE'], 'pfa') !== false) {
                                                $type_badge = 'bg-warning-subtle text-warning';
                                                $type_text = 'Stage PFA';
                                            } elseif (strpos($project['TYPE'], 'observation') !== false) {
                                                $type_badge = 'bg-info-subtle text-info';
                                                $type_text = "Stage d'observation";
                                            } else {
                                                $type_badge = 'bg-secondary-subtle text-secondary';
                                                $type_text = 'Stage';
                                            }
                                            
                                            // Déterminer le badge pour le statut
                                        // Déterminer le badge pour le statut
$statut_badge = '';
$statut_text = $project['STATUT_STU'];
if (strtolower($statut_text) == 'validé' || strtolower($statut_text) == 'valide') {
    $statut_badge = 'bg-success-subtle text-success';
    $statut_text = 'Validé';
} elseif (strtolower($statut_text) == 'en_attente' || strtolower($statut_text) == 'en attente') {
    $statut_badge = 'bg-warning-subtle text-warning';
    $statut_text = 'En attente';
} else {
    $statut_badge = 'bg-danger-subtle text-danger';
    $statut_text = ucfirst(strtolower($statut_text));
}
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($project['ID_PROJECT']); ?></td>
                                            <td><?php echo htmlspecialchars(limitChars($project['NOM'], 20)); ?></td>
<td><span class="badge <?php echo $type_badge; ?>"><?php echo $type_text; ?></span></td>                                            <td><?php echo htmlspecialchars($project['CATEGORIE']); ?></td>
                                            <td>Pr. <?php echo htmlspecialchars($project['PROF_NOM'] . ' ' . htmlspecialchars($project['PROF_PRENOM'])); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($project['DATE_DEP'])); ?></td>
<td><span class="badge <?php echo $statut_badge; ?>"><?php echo $statut_text; ?></span></td>                                            <td><?php echo $project['NOTE'] ? htmlspecialchars($project['NOTE']) . '/20' : '-'; ?></td>
                                            <td>
                                                <a href="../page_chaque_projet_folder_vf/projet<?php echo $project['ID_PROJECT']; ?>.php" class="btn btn-sm details_button">
                                                    <i class="bi bi-eye"></i><span> Détails</span>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <nav aria-label="Page navigation">
                                <ul class="pagination justify-content-center">
                                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?>" tabindex="-1">Précédent</a>
                                    </li>
                                    
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?>">Suivant</a>
                                    </li>
                                </ul>
                            </nav>
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
    <script src="script.js"></script>
</body>
</html>