


<?php
session_start();

if (!isset($_SESSION['APOGEE'])) {
    header("Location: ../connexion_folder_vf/login_page.php");
    exit();
}

require_once '../connexion_folder_vf/db_config.php';



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
// Dans la fonction generateProjectPage(), modifiez la requête SQL dans le template généré :
$stmt = $conn->prepare("SELECT p.*, pr.NOM AS PROF_NOM, pr.PRENOM AS PROF_PRENOM, 
s.NOM AS STUDENT_NOM, s.PRENOM AS STUDENT_PRENOM, 
s.FILIERE AS STUDENT_FILIERE, s.NIV AS STUDENT_NIVEAU
FROM project p
JOIN prof pr ON p.ID_PROF = pr.ID_PROF
JOIN student s ON p.APOGEE = s.APOGEE
WHERE p.ID_PROJECT = ? AND p.APOGEE = ?");
    $stmt->execute([81, $_SESSION['APOGEE']]);
    $projet = $stmt->fetch(PDO::FETCH_ASSOC);
/*    
    if (!$projet) {
        header("Location: ../projets_historique_folder_vf_projets/projets.php");
        exit();
    }
    */
} catch (PDOException $e) {
    header("Location: ../projets_historique_folder_vf_projets/projets.php");
    exit();
}

$color = $_SESSION['AVATAR_COLOR'] ?? '#1B2631';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Étude ergonomique d'un poste de travail | ENSA Kénitra</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../calendrier_folder_vf/style.css">
      <style>
:root {
  /* Couleurs principales */
  --ensa-blue: #002C84;
  --ensa-light-blue: #e9f0f7;
  --ensa-dark: #343a40;
  --main-bg: #f8f9fa;
  --primary-color: #002C84;
  --secondary-color: #54595F;
  --text-color: #333;
  --card-bg: #fff;
  --box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
  --shadow-hover: 0 8px 25px rgba(0, 44, 132, 0.15);
}

/* Structure de base */
body {
  font-family: 'Roboto', Tahoma, Geneva, Verdana, sans-serif;
  background-color: var(--main-bg);
  color: var(--text-color);
  margin: 0;
  padding: 0;
}

/* En-tête */






/* Contenu principal */
.project-header {
  background-color: white;
  border-radius: 10px;
  box-shadow: var(--box-shadow);
  padding: 2rem;
  margin-bottom: 2rem;
  transition: all 0.3s ease;
  border-left: 4px solid var(--primary-color);
}

.project-header:hover {
  box-shadow: var(--shadow-hover);
  transform: translateY(-2px);
}

.project-header h1 {
  color: var(--primary-color);
  font-weight: 700;
  position: relative;
  display: inline-block;
}

.project-header h1::after {
  content: '';
  position: absolute;
  bottom: -5px;
  left: 0;
  width: 60px;
  height: 3px;
  background: var(--primary-color);
  transition: width 0.3s ease;
}

.project-header:hover h1::after {
  width: 120px;
}

/* Badges et statuts */
.badge {
  font-weight: 500;
  padding: 0.5rem 1rem;
  border-radius: 50px;
  transition: all 0.3s ease;
}

.badge:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.status-badge {
  display: inline-flex;
  align-items: center;
  padding: 0.75rem 1.5rem;
  border-radius: 50px;
  font-weight: 600;
  margin-bottom: 1rem;
  transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
}

.status-badge i {
  font-size: 1.2rem;
  transition: transform 0.3s ease;
}

.status-badge:hover i {
  transform: scale(1.2);
}

.status-badge.accepted {
  background-color: rgba(40, 167, 69, 0.2);
  color: #28a745;
  box-shadow: 0 2px 5px rgba(40, 167, 69, 0.2);
}

.status-badge.pending {
  background-color: rgba(255, 193, 7, 0.2);
  color: #ffc107;
  box-shadow: 0 2px 5px rgba(255, 193, 7, 0.2);
}

.status-badge.rejected {
  background-color: rgba(220, 53, 69, 0.2);
  color: #dc3545;
  box-shadow: 0 2px 5px rgba(220, 53, 69, 0.2);
}

/* Image du projet */
.project-image {
  border-radius: 10px;
  overflow: hidden;
  box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
  transition: all 0.5s cubic-bezier(0.25, 0.8, 0.25, 1);
  margin-bottom: 18px;
}

.project-image:hover {
  box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
  transform: translateY(-5px);
}

.project-image img {
  transition: transform 0.5s ease;
  width: 100%;
}

.project-image:hover img {
  transform: scale(1.03);
}

/* Livrables */
.deliverables{
    display: flex;
    flex-direction: column;
    gap: 1rem;
}


.deliverable-item {
  display: flex;
  align-items: center;
  padding: 1rem;
  background-color: var(--ensa-light-blue);
  border-radius: 8px;
  transition: all 0.3s ease;
  border-left: 3px solid transparent;
}

.deliverable-item:hover {
  background-color: white;
  box-shadow: 0 5px 15px rgba(0, 44, 132, 0.1);
  transform: translateX(5px);
  border-left: 3px solid var(--primary-color);
}

.deliverable-item i {
  font-size: 1.5rem;
  transition: transform 0.3s ease;
}

.deliverable-item:hover i {
  transform: scale(1.2);
}

/* Détails du projet */
.detail-item {
  display: flex;
  gap: 1rem;
  padding: 1rem;
  border-radius: 8px;
  transition: all 0.3s ease;
}

.detail-item:hover {
  background-color: var(--ensa-light-blue);
  transform: translateX(5px);
}

.detail-item i {
  font-size: 1.25rem;
  color: var(--primary-color);
  margin-top: 3px;
  transition: transform 0.3s ease;
}

.detail-item:hover i {
  transform: scale(1.2);
}

/* Boutons */
.btn-back{
  background: var(--primary-color);
  color: white;
  border: 2px solid var(--primary-color);
  padding: 0.5rem 1rem;
  border-radius: 5px;
  transition: all 0.3s ease;
  display: inline-flex;
  align-items: center;
  text-decoration: none;
  width: fit-content;
  margin-bottom: 18px;
}

.btn-back:hover, .download_bouton:hover {
  background-color: white;
  color: var(--primary-color);
  transform: translateY(-2px);
  border: 2px solid #002C84;
  box-shadow: 0 4px 8px rgba(0, 44, 132, 0.2);
}



.download_bouton {
  color: #002C84;
  
  border:2px solid #002C84 ;
  position: relative;
  overflow: hidden;
  transition: all 0.3s ease;
  z-index: 1000;
  width: fit-content;
  border: 2px solid var(--primary-color);
  padding: 0.5rem 1rem;
  border-radius: 5px;
  transition: all 0.3s ease;
  display: inline-flex;
  align-items: center;
  text-decoration: none;
  width: fit-content;
  background-color: white;
}

.download_bouton::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 0;
  height: 100%;
  background-color: var(--primary-color);
  transition: width 0.3s ease;
  z-index: -1000;
}

.download_bouton:hover {
  color: white;
  border-color: #002C84;
}

.download_bouton:hover::before {
  width: 100%;
}

.download_bouton i {
  transition: transform 0.3s ease;
}

.download_bouton:hover i {
  transform: translateY(-3px);
}




.progress-bar{
  background-color: #002C84;
}

.progress{
  margin-bottom: 29px;
}

.evaluation-comment{
  background-color: rgba(0, 0, 0, 0.08);
  padding: 10px 5px 10px 10px;
  border-radius: 5px;
}

.evaluation-comment h6{
  color: #002C84;
  font-weight: bold;
  padding-bottom: 2px;
  border-bottom: 2px solid #002C84;
  width: fit-content;
}









@media (max-width: 768px){
    .project-header h1::after {
    left: 50%;
    transform: translateX(-50%);
  }
    
}

@media (max-width: 510px){
    .project-header h1::after {
    left: 50%;
    transform: translateX(-50%);
  }
  
  .deliverable-item span{
    font-size: 13px;

  }

  .download_bouton{
    font-size: 11px;
    padding: 0.3rem 0.5rem;
  }
  .download_bouton i{
    font-size: 13px;
  }
    
    
}

@media (max-width: 390px){
    .deliverable-item{
        padding: 0.8rem 0.4rem;
    }
    .deliverable-item span{
    font-size: 12px;

  }
    
  .download_bouton{
    font-size: 9px;
    padding: 0.3rem 0.5rem;
  }
  .download_bouton i{
    font-size: 13px;
  }
    
    
}





/*
@media (max-width: 768px) {

  
  .project-header .d-flex {
    /*justify-content: center;
  }
  
  .project-status {
    margin-top: 1.5rem;
    display: flex;
    flex-direction: column;
    align-items: center;
  }
  
  /*
  .project-header h1::after {
    left: 50%;
    transform: translateX(-50%);
  }
  
 
}*/
</style>
</head>
<body>
    <header class="header-wrapper bg-white shadow-sm">
        <nav class="navbar navbar-expand-lg navbar-light container">
            <a class="navbar-brand logo" href="../projets_page_folder_vf_acceuil_esp_etudiant/home_student.php">
                <img src="../projets_page_folder_vf_acceuil_esp_etudiant/images/ensa_logo.png" alt="Logo ENSA">
            </a>
  
            <div class="collapse navbar-collapse justify-content-between" id="navbarNav">
                <ul class="navbar-nav nav-links">
                    <li class="nav-item"><a class="nav-link" href="../projets_page_folder_vf_acceuil_esp_etudiant/home_student.php">Accueil</a></li>
                    <li class="nav-item"><a class="nav-link" href="../soumission_folder_vf/soumission.php">Soumission</a></li>
                    <li class="nav-item"><a class="nav-link active" href="../projets_historique_folder_vf_projets/projets.php">Projets</a></li>
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

    <main class="container my-5">
        <div class="project-header">
            <a href="../projets_page_folder_vf_acceuil_esp_etudiant/home_student.php" class="btn btn-back">
                <i class="bi bi-arrow-left me-2"></i>Retour aux projets
            </a>
            
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="d-flex align-items-center mb-3">
                        <span class="badge bg-primary me-2">iot</span>
                        <span class="badge bg-secondary ">module</span>
                    </div>
                    <h1 class="mb-3">Étude ergonomique d'un poste de travail</h1>
                    <div class="d-flex flex-wrap gap-3 mb-3">
                    
                        <div class="d-flex align-items-center">
                            <i class="bi bi-calendar-event me-2"></i>
                            <span>Soumis le: 02/06/2025</span>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
        
        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-body">
                        <h3 class="card-title mb-4"><i class="bi bi-image me-2"></i>Présentation du Projet</h3>
                        <div class="project-image">
                            <img src="../../uploads/images/683d1f084d417_C4-1.png" alt="Image du projet" class="img-fluid">
                        </div>
                        <div class="project-description">
                            <h4 class="mb-3">Description</h4>
                            <p>Évaluation des risques et propositions d'améliorations.
</p>
                        </div>
                    </div>
                </div>
                
                <div class="card mb-4">
                    <div class="card-body">
                        <h3 class="card-title mb-4"><i class="bi bi-file-earmark-arrow-down me-2"></i>Livrables</h3>
                        <div class="deliverables">
                            <div class="deliverable-item">
<i class="bi bi-file-zip-fill text-warning me-2"></i>
                                <span>Archive du projet (ZIP)</span>
                                <a href="../../uploads/projets/683d1f084c495_documents de travailEXAM2022-2023.zip" class="btn btn-sm download_bouton ms-auto" download>
                                    <i class="bi bi-download"></i> Télécharger
                                </a>
                            </div>
                                                        <div class="deliverable-item">
                                <i class="bi bi-file-earmark-pdf-fill text-danger me-2"></i>
                                <span>Fichier supplémentaire (PDF)</span>
                                <a href="../../uploads/projets/683d1f084cbf0_TP-4 (2).pdf" class="btn btn-sm download_bouton ms-auto" download>
                                    <i class="bi bi-download"></i> Télécharger
                                </a>
                            </div>                            <div class="deliverable-item">
                                <i class="bi bi-file-earmark-pdf-fill text-danger me-2"></i>
                                <span>Fichier supplémentaire (PDF)</span>
                                <a href="../../uploads/projets/683d1f084d04f_Rapport_ENSA_P_                                    _                                        1                                    _                                _2025-06-01.pdf" class="btn btn-sm download_bouton ms-auto" download>
                                    <i class="bi bi-download"></i> Télécharger
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-body">
                        <h3 class="card-title mb-4"><i class="bi bi-info-circle me-2"></i>Détails du Projet</h3>
                        <div class="project-details">
                            <div class="detail-item">
                                <i class="bi bi-file-earmark-fill me-2"></i>
                                <div>
                                    <h6>Type de Projet</h6>
                                    <p>Projet de module</p>
                                </div>
                            </div>
                            <div class="detail-item">
                                <i class="bi bi-person-fill me-2"></i>
                                <div>
                                    <h6>Étudiant</h6>
                                     <p>Hind Rhayour</p>
                                </div>
                            </div>
                            <div class="detail-item">
                                <i class="bi bi-building me-2"></i>
                                <div>
                                    <h6>Filière</h6>
        <p>Génie Informatique</p>
                                </div>
                            </div>
                            <div class="detail-item">
                                <i class="bi bi-award-fill me-2"></i>
                                <div>
                                    <h6>Niveau</h6>
        <p>CI1</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                

            </div>
        </div>
    </main>

    <footer class="footer-wrapper">
        <div class="footer-section">
            <div class="footer-info">
                <img src="../projets_page_folder_vf_acceuil_esp_etudiant/images/LOGO-ENSA.png" alt="Logo ENSA">
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
                    <a target="_blank" href="https://www.instagram.com/ensak.official" class="text-white"><i class="fab fa-instagram fa-lg"></i></a>
                    <a target="_blank" href="https://ma.linkedin.com/company/ensa-kenitra-official" class="text-white"><i class="fab fa-linkedin fa-lg"></i></a>
                </div>
                <a class="contact_link text-white" href="../contact_folder_vf/index.html">Contactez-nous</a>
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