<?php
session_start();  

if (!isset($_SESSION['APOGEE'])) {
    header("Location: ../connexion_folder_vf/login_page.php");
    exit();
}

if (empty($_GET['id'])) {
    header("Location: ../soumission_folder_vf/soumission.php");
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
    // Récupérer les données du projet
    $stmt = $conn->prepare("SELECT * FROM project WHERE ID_PROJECT = ? AND APOGEE = ?");
    $stmt->execute([$_GET['id'], $_SESSION['APOGEE']]);
    $projet = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$projet) {
        header("Location: ../soumission_folder_vf/soumission.php");
        exit();
    }
    
    // Récupérer la liste des professeurs
    $stmt = $conn->prepare("SELECT ID_PROF, NOM, PRENOM FROM PROF");
    $stmt->execute();
    $professeurs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur de base de données: " . $e->getMessage();
    header("Location: ../soumission_folder_vf/soumission.php");
    exit();
}

$color = $_SESSION['AVATAR_COLOR'] ?? '#1B2631';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modification | ENSA Kénitra</title>
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

    <main class="main-content">
        <div class="form-container">
            <h1 class="form-title">
                VALIDATION DE PROJET
                <span style="font-size: 1rem; color: var(--dark-gray); font-weight: normal;">ID: PRJ-<?= $projet['ID_PROJECT'] ?></span>
            </h1>
            
            <!-- Image du projet -->
            <div class="project-image-container">
                <label>Image du projet</label>
                <div class="project-image-wrapper">
                    <img src="../../uploads/images/<?= $projet['IMG'] ?>" 
                         alt="Image du projet" 
                         class="preview-image"
                         id="projectImagePreview">
                </div>
            </div>
            
            <form id="projectForm" action="traitement_modification.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="projectId" value="<?= $projet['ID_PROJECT'] ?>">
                
                <div class="form-group">
                    <label for="projectName">Nom du projet *</label>
                    <input type="text" id="projectName" name="projectName" value="<?= htmlspecialchars($projet['NOM']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Description *</label>
                    <textarea id="description" name="description" required><?= htmlspecialchars($projet['DESCRIPTION']) ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="category">Catégorie *</label>
                    <select id="category" name="category" required>
                        <option value="">Sélectionnez une catégorie</option>
                        <option value="web" <?= $projet['CATEGORIE'] == 'web' ? 'selected' : '' ?>>Développement Web</option>
                        <option value="mobile" <?= $projet['CATEGORIE'] == 'mobile' ? 'selected' : '' ?>>Développement Mobile</option>
                        <option value="ia" <?= $projet['CATEGORIE'] == 'ia' ? 'selected' : '' ?>>Intelligence Artificielle</option>
                        <option value="iot" <?= $projet['CATEGORIE'] == 'iot' ? 'selected' : '' ?>>Internet des Objets</option>
                        <option value="optimisation" <?= $projet['CATEGORIE'] == 'optimisation' ? 'selected' : '' ?>>Optimisation Industrielle</option>
                        <option value="autre" <?= $projet['CATEGORIE'] == 'autre' ? 'selected' : '' ?>>Autre</option>
                    </select>
                </div>

                <!--
                
                <div class="form-group">
                    <label for="projectType">Type de projet *</label>
                    <select id="projectType" name="projectType" required>
                        <option value="">Sélectionnez un type</option>
                        <option value="module" <?= $projet['TYPE'] == 'module' ? 'selected' : '' ?>>Projet de module</option>
                        <option value="stage d'observation" <?= $projet['TYPE'] == 'stage d\'observation' ? 'selected' : '' ?>>Projet de stage d'observation</option>
                        <option value="stage pfa" <?= $projet['TYPE'] == 'stage pfa' ? 'selected' : '' ?>>Projet de stage pfa</option>
                        <option value="stage pfe" <?= $projet['TYPE'] == 'stage pfe' ? 'selected' : '' ?>>Projet de stage pfe</option>
                    </select>
                </div>-->



                <div class="form-group">
    <label for="projectType">Type de projet *</label>
    <select id="projectType" name="projectType" required>
        <option value="">Sélectionnez un type</option>
        <option value="module" <?= ($projet['TYPE'] ?? '') === 'module' ? 'selected' : '' ?>>Projet de module</option>
        <option value="stage d'observation" <?= ($projet['TYPE'] ?? '') === 'stage d\'observation' ? 'selected' : '' ?>>Projet de stage d'observation</option>
        <option value="stage pfa" <?= ($projet['TYPE'] ?? '') === 'stage pfa' ? 'selected' : '' ?>>Projet de stage pfa</option>
        <option value="stage pfe" <?= ($projet['TYPE'] ?? '') === 'stage pfe' ? 'selected' : '' ?>>Projet de stage pfe</option>
    </select>
</div>

                
                <div class="form-group">
                    <label for="supervisor">Encadrant/Professeur *</label>
                    <select id="supervisor" name="supervisor" required>
                        <option value="">Sélectionnez un encadrant</option>
                        <?php foreach ($professeurs as $prof): ?>
                            <option value="<?= $prof['ID_PROF'] ?>" <?= $prof['ID_PROF'] == $projet['ID_PROF'] ? 'selected' : '' ?>>
                                <?= $prof['PRENOM'] ?> <?= $prof['NOM'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>


<div class="form-group">
    <label for="semester">Semestre *</label>
    <select id="semester" name="semester" required>
        <option value="">Sélectionnez un semestre</option>
        <?php for ($i = 1; $i <= 10; $i++): ?>
            <option value="s<?= $i ?>" <?= ($projet['SEMESTRE'] ?? '') == "s$i" ? 'selected' : '' ?>>
                Semestre <?= $i ?>
            </option>
        <?php endfor; ?>
    </select>
</div>

<div class="form-group">
    <label for="technologies">Technologies utilisées</label>
    <input type="text" id="technologies" name="technologies" 
           value="<?= htmlspecialchars($projet['TECHNOLOGIE'] ?? '') ?>" 
           placeholder="Ex: PHP, JavaScript, Python, React...">
    <small class="form-text">Séparez les technologies par des virgules</small>
</div>

                
                <div class="form-group">
                    <label>Fichiers du projet</label>
                    <div class="file-list" id="existingFiles">
                        <?php if ($projet['FICHIER']): ?>
                            <div class="file-item">
                                <div class="file-info">
                                    <i class="fas fa-file-archive file-icon"></i>
                                    <span><?= $projet['FICHIER'] ?></span>
                                </div>
                            </div>
                        <?php endif; ?>
                        
        
        <?php 
        // Afficher tous les fichiers supplémentaires (FILE1, FILE2, FILE3)
        for ($i = 1; $i <= 3; $i++): 
            if (!empty($projet['FILE'.$i])): 
                $ext = pathinfo($projet['FILE'.$i], PATHINFO_EXTENSION);
                $iconClass = 'fa-file';
                
                if ($ext === 'pdf') $iconClass = 'fa-file-pdf';
                elseif (in_array($ext, ['ppt', 'pptx'])) $iconClass = 'fa-file-powerpoint';
                elseif (in_array($ext, ['doc', 'docx'])) $iconClass = 'fa-file-word';
                elseif (in_array($ext, ['py', 'java', 'cpp', 'c', 'js', 'html', 'css', 'php', 'txt'])) $iconClass = 'fa-file-code';
        ?>
                <div class="file-item">
                    <div class="file-info">
                        <i class="fas <?= $iconClass ?> file-icon"></i>
                        <span><?= $projet['FILE'.$i] ?></span>
                    </div>
                </div>
            <?php endif; ?>
        <?php endfor; ?>
                        
                    </div>
                </div>
                
                <div class="form-group" style="display: flex; gap: 15px; flex-wrap: wrap;">
                    <button type="submit" name="action" value="valider" class="submit-btn">
                        <i class="fas fa-check"></i> Valider le projet
                    </button>
                    <button type="submit" name="action" value="annuler" class="submit-btn" style="background: var(--error-red);">
                        <i class="fas fa-times"></i> Annuler
                    </button>
                    <button type="submit" name="action" value="retour" class="submit-btn" style="background: var(--dark-gray);">
                        <i class="fas fa-arrow-left"></i> Retour à l'édition
                    </button>
                </div>
            </form>
        </div>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="script.js"></script>
</body>
</html>