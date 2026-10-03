<?php
session_start();

if (!isset($_SESSION['APOGEE'])) {
    header("Location: ../connexion_folder_vf/login_page.php");
    exit();
}

require_once '../connexion_folder_vf/db_config.php';

// Récupérer les données du formulaire si elles existent
$form_data = $_SESSION['form_data'] ?? null;
if ($form_data) {
    // Préparer les variables pour le formulaire
    $projectName = htmlspecialchars($form_data['projectName']);
    $description = htmlspecialchars($form_data['description']);
    $category = htmlspecialchars($form_data['category']);
    $projectType = htmlspecialchars($form_data['projectType']);
    $supervisor = htmlspecialchars($form_data['supervisor']);
    $semester = htmlspecialchars($form_data['semester'] ?? '');
    $technologies = htmlspecialchars($form_data['technologies'] ?? '');
    $id_project = htmlspecialchars($form_data['id_project']);
    
    // Récupérer les infos des fichiers existants
    $existing_files = $form_data['existing_files'] ?? [];
    
    // Supprimer les données de la session après utilisation
    unset($_SESSION['form_data']);
} else {
    // Valeurs par défaut si pas de données en session
    $projectName = $description = $category = $projectType = $supervisor = '';
    $id_project = null;
    $existing_files = [];
}

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
    $stmt = $conn->prepare("SELECT ID_PROF, NOM, PRENOM FROM PROF");
    $stmt->execute();
    $professeurs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $professeurs = [];
    error_log("Erreur de récupération des professeurs: " . $e->getMessage());
}

$color = $_SESSION['AVATAR_COLOR'] ?? '#1B2631';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Soumission | ENSA Kénitra</title>
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
            <h1 class="form-title">SOUMISSION DE PROJET</h1>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>
            
            <form id="projectForm" action="traitement_soumission.php" method="POST" enctype="multipart/form-data">
                <?php if ($id_project): ?>
                    <input type="hidden" name="projectId" value="<?= $id_project ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="projectName">Nom du projet *</label>
                    <input type="text" id="projectName" name="projectName" 
                           value="<?= $projectName ?>" 
                           placeholder="Entrez le nom de votre projet" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Description *</label>
                    <textarea id="description" name="description" required><?= $description ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="category">Catégorie *</label>
                    <select id="category" name="category" required>
                        <option value="">Sélectionnez une catégorie</option>
                        <option value="web" <?= $category === 'web' ? 'selected' : '' ?>>Développement Web</option>
                        <option value="mobile" <?= $category === 'mobile' ? 'selected' : '' ?>>Développement Mobile</option>
                        <option value="ia" <?= $category === 'ia' ? 'selected' : '' ?>>Intelligence Artificielle</option>
                        <option value="iot" <?= $category === 'iot' ? 'selected' : '' ?>>Internet des Objets</option>
                        <option value="optimisation" <?= $category === 'optimisation' ? 'selected' : '' ?>>Optimisation Industrielle</option>
                        <option value="autre" <?= $category === 'autre' ? 'selected' : '' ?>>Autre</option>
                    </select>
                </div>
                <!--
                
                <div class="form-group">
                    <label for="projectType">Type de projet *</label>
                    <select id="projectType" name="projectType" required>
                        <option value="">Sélectionnez un type</option>
                        <option value="module" <?= $projectType === 'module' ? 'selected' : '' ?>>Projet de module</option>
                        <option value="stage d'observation" <?= $projectType === 'stage d\'observation' ? 'selected' : '' ?>>Projet de stage d'observation</option>
                        <option value="stage pfa" <?= $projectType === 'stage pfa' ? 'selected' : '' ?>>Projet de stage pfa</option>
                        <option value="stage pfe" <?= $projectType === 'stage pfe' ? 'selected' : '' ?>>Projet de stage pfe</option>
                    </select>
                </div>-->


                <div class="form-group">
    <label for="projectType">Type de projet *</label>
    <select id="projectType" name="projectType" required>
        <option value="">Sélectionnez un type</option>
        <option value="module" <?= ($projectType ?? '') === 'module' ? 'selected' : '' ?>>Projet de module</option>
        <option value="stage d'observation" <?= ($projectType ?? '') === 'stage d\'observation' ? 'selected' : '' ?>>Projet de stage d'observation</option>
        <option value="stage pfa" <?= ($projectType ?? '') === 'stage pfa' ? 'selected' : '' ?>>Projet de stage pfa</option>
        <option value="stage pfe" <?= ($projectType ?? '') === 'stage pfe' ? 'selected' : '' ?>>Projet de stage pfe</option>
    </select>
</div>

                
                <div class="form-group">
                    <label for="supervisor">Encadrant/Professeur *</label>
                    <select id="supervisor" name="supervisor" required>
                        <option value="">Sélectionnez un encadrant</option>
                        <?php foreach ($professeurs as $prof): ?>
                            <option value="<?= $prof['ID_PROF'] ?>" <?= $supervisor == $prof['ID_PROF'] ? 'selected' : '' ?>>
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
            <option value="s<?= $i ?>" <?= ($semester ?? '') === "s$i" ? 'selected' : '' ?>>
                Semestre <?= $i ?>
            </option>
        <?php endfor; ?>
    </select>
</div>

<div class="form-group">
    <label for="technologies">Technologies utilisées</label>
    <input type="text" id="technologies" name="technologies" 
           value="<?= htmlspecialchars($technologies ?? '') ?>" 
           placeholder="Ex: PHP, JavaScript, Python, React...">
    <small class="form-text">Séparez les technologies par des virgules</small>
</div>
            

    <!-- Fichier principal (ZIP) -->
<div class="form-group">
    <label>Fichier principal (ZIP contenant tout le projet) *</label>
    <label class="file-upload-label">
        <input type="file" id="projectFile" name="projectFile" style="display: none;" accept=".zip,.rar" <?= empty($existing_files['FICHIER']) ? 'required' : '' ?>>
        <i class="fas fa-file-archive"></i> Choisir un fichier ZIP...
    </label>
    <div id="projectFilePreview" class="file-preview">
        <?php if (!empty($existing_files['FICHIER'])): ?>
            <div class="file-item existing-file">
                <div class="file-info">
                    <i class="fas fa-file-archive file-icon"></i>
                    <span class="file-name"><?= $existing_files['FICHIER'] ?></span>
                    <input type="hidden" name="existing_projectFile" value="<?= $existing_files['FICHIER'] ?>">
                </div>
                <button type="button" class="delete-file" onclick="removeFile('projectFile', true)">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        <?php endif; ?>
        <div class="file-item new-file" style="display: none;">
            <div class="file-info">
                <i class="fas fa-file-archive file-icon"></i>
                <span class="file-name" id="projectFileName"></span>
            </div>
            <button type="button" class="delete-file" onclick="removeFile('projectFile')">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    </div>
</div>

                
                <div class="form-group">
                    <label>Fichiers supplémentaires (max 3 - PDF, PPT, code source)</label>
                    <label class="file-upload-label">
                        <input type="file" id="additionalFiles" name="additionalFiles[]" style="display: none;" 
                               accept=".pdf,.ppt,.pptx,.doc,.docx,.txt,.py,.java,.cpp,.c,.js,.html,.css,.php" multiple>
                        <i class="fas fa-file-upload"></i> Choisir des fichiers...
                    </label>
                    <div id="additionalFilesPreview" class="file-preview">
                        <?php 
                        for ($i = 1; $i <= 3; $i++) {
                            $fileKey = 'FILE'.$i;
                            if (!empty($existing_files[$fileKey])) {
                                $fileExt = pathinfo($existing_files[$fileKey], PATHINFO_EXTENSION);
                                $iconClass = '';
                                
                                if (in_array($fileExt, ['pdf'])) $iconClass = 'fa-file-pdf text-danger';
                                elseif (in_array($fileExt, ['ppt', 'pptx'])) $iconClass = 'fa-file-powerpoint text-warning';
                                elseif (in_array($fileExt, ['doc', 'docx'])) $iconClass = 'fa-file-word text-primary';
                                else $iconClass = 'fa-file-code text-success';
                                
                                echo '<div class="file-item existing-file">
                                    <div class="file-info">
                                        <i class="fas '.$iconClass.' file-icon"></i>
                                        <span>'.$existing_files[$fileKey].'</span>
                                        <input type="hidden" name="existing_additionalFiles[]" value="'.$existing_files[$fileKey].'">
                                    </div>
                                    <button type="button" class="delete-file" onclick="removeExistingFile(this, true)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>';
                            }
                        }
                        ?>
                    </div>
                </div>
<div class="form-group">
    <label>Image du projet *</label>
    <label class="file-upload-label">
        <input type="file" id="projectImage" name="projectImage" style="display: none;" accept="image/*" <?= empty($existing_files['IMG']) ? 'required' : '' ?>>
        <i class="fas fa-image"></i> Choisir une image...
    </label>
    <div id="projectImagePreview" class="file-preview">
        <?php if (!empty($existing_files['IMG'])): ?>
            <div class="file-item existing-file">
                <div class="file-info">
                    <i class="fas fa-image file-icon"></i>
                    <span class="file-name"><?= $existing_files['IMG'] ?></span>
                    <input type="hidden" name="existing_projectImage" value="<?= $existing_files['IMG'] ?>">
                </div>
                <button type="button" class="delete-file" onclick="removeFile('projectImage', true)">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        <?php endif; ?>
        <div class="file-item new-file" style="display: none;">
            <div class="file-info">
                <i class="fas fa-image file-icon"></i>
                <span class="file-name" id="projectImageName"></span>
            </div>
            <button type="button" class="delete-file" onclick="removeFile('projectImage')">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    </div>
</div>
                
                <button type="submit" class="submit-btn">
                    <i class="fas fa-paper-plane"></i> Soumettre le projet
                </button>
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

    

    <script>
        // Gestion de l'affichage des fichiers sélectionnés
        document.getElementById('projectFile').addEventListener('change', function(e) {
            if (this.files.length > 0) {
                document.getElementById('projectFileName').textContent = this.files[0].name;
                document.getElementById('projectFilePreview').style.display = 'block';
            }
        });

        document.getElementById('additionalFiles').addEventListener('change', function(e) {
            const preview = document.getElementById('additionalFilesPreview');
            preview.innerHTML = '';
            
            if (this.files.length > 0) {
                // Limiter à 3 fichiers
                const filesToShow = Array.from(this.files).slice(0, 3);
                
                filesToShow.forEach((file, index) => {
                    const fileItem = document.createElement('div');
                    fileItem.className = 'file-item';
                    
                    // Déterminer l'icône en fonction de l'extension
                    const ext = file.name.split('.').pop().toLowerCase();
                    let iconClass = 'fa-file';
                    
                    if (ext === 'pdf') iconClass = 'fa-file-pdf';
                    else if (['ppt', 'pptx'].includes(ext)) iconClass = 'fa-file-powerpoint';
                    else if (['zip', 'rar'].includes(ext)) iconClass = 'fa-file-archive';
                    else if (['jpg', 'jpeg', 'png', 'gif'].includes(ext)) iconClass = 'fa-image';
                    else if (['py', 'java', 'cpp', 'c', 'js', 'html', 'css', 'php', 'txt'].includes(ext)) iconClass = 'fa-file-code';
                    
                    fileItem.innerHTML = `
                        <div class="file-info">
                            <i class="fas ${iconClass} file-icon"></i>
                            <span class="file-name">${file.name}</span>
                        </div>
                        <button type="button" class="delete-file" onclick="removeAdditionalFile(${index})">
                            <i class="fas fa-trash"></i>
                        </button>
                    `;
                    
                    preview.appendChild(fileItem);
                });
            }
        });
        

        document.getElementById('projectImage').addEventListener('change', function(e) {
            if (this.files.length > 0) {
                document.getElementById('projectImageName').textContent = this.files[0].name;
                document.getElementById('projectImagePreview').style.display = 'block';
            }
        });

        // Fonctions pour supprimer les fichiers
        function removeFile(inputId) {
            const input = document.getElementById(inputId);
            input.value = '';
            
            if (inputId === 'projectFile') {
                document.getElementById('projectFilePreview').style.display = 'none';
            } else if (inputId === 'projectImage') {
                document.getElementById('projectImagePreview').style.display = 'none';
            }
        }

        function removeAdditionalFile(index) {
            const input = document.getElementById('additionalFiles');
            const files = Array.from(input.files);
            files.splice(index, 1);
            
            // Créer une nouvelle DataTransfer et ajouter les fichiers restants
            const dataTransfer = new DataTransfer();
            files.forEach(file => dataTransfer.items.add(file));
            input.files = dataTransfer.files;
            
            // Rafraîchir l'affichage
            const event = new Event('change');
            input.dispatchEvent(event);
        }


// Gestion de la suppression des fichiers existants
document.querySelectorAll('.delete-existing-file').forEach(button => {
    button.addEventListener('click', function() {
        const fileType = this.getAttribute('data-file-type');
        const fileItem = this.closest('.file-item');
        
        // Supprimer l'affichage du fichier
        fileItem.remove();
        
        // Ajouter un champ caché pour indiquer la suppression
        const deleteInput = document.createElement('input');
        deleteInput.type = 'hidden';
        deleteInput.name = 'delete_' + fileType;
        deleteInput.value = '1';
        document.getElementById('projectForm').appendChild(deleteInput);
    });
});
    </script>







<script>
        // Gestion de l'affichage des fichiers sélectionnés
        document.getElementById('projectFile').addEventListener('change', function(e) {
            if (this.files.length > 0) {
                document.getElementById('projectFileName').textContent = this.files[0].name;
                document.getElementById('projectFilePreview').style.display = 'block';
            }
        });

        document.getElementById('additionalFiles').addEventListener('change', function(e) {
            const preview = document.getElementById('additionalFilesPreview');
            const existingFiles = preview.querySelectorAll('.existing-file').length;
            const remainingSlots = 3 - existingFiles;
            
            if (this.files.length > 0) {
                // Limiter à 3 fichiers (en comptant les existants)
                const filesToShow = Array.from(this.files).slice(0, remainingSlots);
                
                filesToShow.forEach((file, index) => {
                    const fileItem = document.createElement('div');
                    fileItem.className = 'file-item';
                    
                    // Déterminer l'icône en fonction de l'extension
                    const ext = file.name.split('.').pop().toLowerCase();
                    let iconClass = 'fa-file';
                    
                    if (ext === 'pdf') iconClass = 'fa-file-pdf';
                    else if (['ppt', 'pptx'].includes(ext)) iconClass = 'fa-file-powerpoint';
                    else if (['doc', 'docx'].includes(ext)) iconClass = 'fa-file-word';
                    else if (['py', 'java', 'cpp', 'c', 'js', 'html', 'css', 'php', 'txt'].includes(ext)) iconClass = 'fa-file-code';
                    
                    fileItem.innerHTML = `
                        <div class="file-info">
                            <i class="fas ${iconClass} file-icon"></i>
                            <span class="file-name">${file.name}</span>
                        </div>
                        <button type="button" class="delete-file" onclick="removeAdditionalFile(${index})">
                            <i class="fas fa-trash"></i>
                        </button>
                    `;
                    
                    preview.appendChild(fileItem);
                });
            }
        });

        document.getElementById('projectImage').addEventListener('change', function(e) {
            if (this.files.length > 0) {
                document.getElementById('projectImageName').textContent = this.files[0].name;
                document.getElementById('projectImagePreview').style.display = 'block';
            }
        });

        // Fonctions pour supprimer les fichiers
        function removeFile(inputId, isExisting = false) {
            const input = document.getElementById(inputId);
            const preview = document.getElementById(inputId + 'Preview');
            
            if (isExisting) {
                // Pour les fichiers existants, on ajoute un champ caché pour indiquer la suppression
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'delete_' + inputId;
                hiddenInput.value = '1';
                document.getElementById('projectForm').appendChild(hiddenInput);
                
                // On cache le preview
                preview.style.display = 'none';
                
                // On rend le champ obligatoire si c'était un fichier existant
                if (inputId === 'projectFile' || inputId === 'projectImage') {
                    input.required = true;
                }
            } else {
                // Pour les nouveaux fichiers, on réinitialise simplement
                input.value = '';
                preview.style.display = 'none';
            }
        }

        function removeAdditionalFile(index) {
            const input = document.getElementById('additionalFiles');
            const files = Array.from(input.files);
            files.splice(index, 1);
            
            // Créer une nouvelle DataTransfer et ajouter les fichiers restants
            const dataTransfer = new DataTransfer();
            files.forEach(file => dataTransfer.items.add(file));
            input.files = dataTransfer.files;
            
            // Rafraîchir l'affichage
            const event = new Event('change');
            input.dispatchEvent(event);
        }

        function removeExistingFile(button, isExisting = false) {
            const fileItem = button.closest('.file-item');
            if (isExisting) {
                // Ajouter un champ caché pour indiquer la suppression
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'delete_additionalFiles[]';
                hiddenInput.value = fileItem.querySelector('input').value;
                document.getElementById('projectForm').appendChild(hiddenInput);
            }
            fileItem.remove();
        }



        
    // Gestion de l'affichage des fichiers sélectionnés
    document.getElementById('projectFile').addEventListener('change', function(e) {
        if (this.files.length > 0) {
            document.getElementById('projectFileName').textContent = this.files[0].name;
            document.getElementById('projectFilePreview').style.display = 'block';
            
            // Masquer l'ancien fichier existant s'il y en a un
            const existingItem = document.querySelector('#projectFilePreview .existing-file');
            if (existingItem) {
                existingItem.style.display = 'none';
            }
        }
    });

    document.getElementById('projectImage').addEventListener('change', function(e) {
        if (this.files.length > 0) {
            document.getElementById('projectImageName').textContent = this.files[0].name;
            document.getElementById('projectImagePreview').style.display = 'block';
            
            // Masquer l'ancienne image existante s'il y en a une
            const existingItem = document.querySelector('#projectImagePreview .existing-file');
            if (existingItem) {
                existingItem.style.display = 'none';
            }
        }
    });

    // Fonctions pour supprimer les fichiers
    function removeFile(inputId, isExisting = false) {
        const input = document.getElementById(inputId);
        const preview = document.getElementById(inputId + 'Preview');
        
        if (isExisting) {
            // Pour les fichiers existants, on ajoute un champ caché pour indiquer la suppression
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'delete_' + inputId;
            hiddenInput.value = '1';
            document.getElementById('projectForm').appendChild(hiddenInput);
            
            // On cache le preview de l'existant
            const existingItem = preview.querySelector('.existing-file');
            if (existingItem) {
                existingItem.style.display = 'none';
            }
            
            // On rend le champ obligatoire
            input.required = true;
        } else {
            // Pour les nouveaux fichiers, on réinitialise simplement
            input.value = '';
            preview.style.display = 'none';
        }
    }

    // Gestion des fichiers supplémentaires
    document.getElementById('additionalFiles').addEventListener('change', function(e) {
        const preview = document.getElementById('additionalFilesPreview');
        const existingFiles = preview.querySelectorAll('.existing-file');
        let existingCount = 0;
        
        // Compter les fichiers existants non supprimés
        existingFiles.forEach(file => {
            if (file.style.display !== 'none') {
                existingCount++;
            }
        });
        
        // Calculer le nombre de fichiers qu'on peut encore ajouter
        const remainingSlots = 3 - existingCount;
        
        if (this.files.length > 0) {
            // Limiter à remainingSlots fichiers
            const filesToShow = Array.from(this.files).slice(0, remainingSlots);
            
            // Supprimer les anciens nouveaux fichiers (ceux qui ne sont pas 'existing-file')
            const newFiles = preview.querySelectorAll('.file-item:not(.existing-file)');
            newFiles.forEach(file => file.remove());
            
            // Ajouter les nouveaux fichiers
            filesToShow.forEach((file, index) => {
                const fileItem = document.createElement('div');
                fileItem.className = 'file-item';
                
                // Déterminer l'icône en fonction de l'extension
                const ext = file.name.split('.').pop().toLowerCase();
                let iconClass = 'fa-file';
                
                if (ext === 'pdf') iconClass = 'fa-file-pdf';
                else if (['ppt', 'pptx'].includes(ext)) iconClass = 'fa-file-powerpoint';
                else if (['doc', 'docx'].includes(ext)) iconClass = 'fa-file-word';
                else if (['py', 'java', 'cpp', 'c', 'js', 'html', 'css', 'php', 'txt'].includes(ext)) iconClass = 'fa-file-code';
                
                fileItem.innerHTML = `
                    <div class="file-info">
                        <i class="fas ${iconClass} file-icon"></i>
                        <span class="file-name">${file.name}</span>
                    </div>
                    <button type="button" class="delete-file" onclick="this.closest('.file-item').remove()">
                        <i class="fas fa-trash"></i>
                    </button>
                `;
                
                preview.appendChild(fileItem);
            });
        }
    });

    function removeExistingFile(button) {
        const fileItem = button.closest('.existing-file');
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'delete_additionalFiles[]';
        hiddenInput.value = fileItem.querySelector('input').value;
        document.getElementById('projectForm').appendChild(hiddenInput);
        
        fileItem.style.display = 'none';
    }







    // Gestion des fichiers principaux (ZIP et image)
function handleFileInput(inputId, previewId, fileNameId) {
    const input = document.getElementById(inputId);
    const preview = document.getElementById(previewId);
    
    input.addEventListener('change', function(e) {
        if (this.files.length > 0) {
            // Afficher le nouveau fichier
            const newFileItem = preview.querySelector('.new-file');
            document.getElementById(fileNameId).textContent = this.files[0].name;
            newFileItem.style.display = 'flex';
            
            // Masquer l'ancien fichier existant s'il y en a un
            const existingItem = preview.querySelector('.existing-file');
            if (existingItem) {
                existingItem.style.display = 'none';
            }
        }
    });
}

// Initialisation des gestionnaires d'événements
handleFileInput('projectFile', 'projectFilePreview', 'projectFileName');
handleFileInput('projectImage', 'projectImagePreview', 'projectImageName');

// Fonction pour supprimer les fichiers
function removeFile(inputId, isExisting = false) {
    const input = document.getElementById(inputId);
    const preview = document.getElementById(inputId + 'Preview');
    
    if (isExisting) {
        // Pour les fichiers existants
        const existingItem = preview.querySelector('.existing-file');
        if (existingItem) {
            existingItem.style.display = 'none';
            
            // Ajouter un champ caché pour indiquer la suppression
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'delete_' + inputId;
            hiddenInput.value = '1';
            document.getElementById('projectForm').appendChild(hiddenInput);
        }
        
        // Rendre le champ obligatoire
        input.required = true;
    } else {
        // Pour les nouveaux fichiers
        const newFileItem = preview.querySelector('.new-file');
        if (newFileItem) {
            newFileItem.style.display = 'none';
        }
        input.value = '';
    }
}
/*
// Gestion des fichiers supplémentaires
document.getElementById('additionalFiles').addEventListener('change', function(e) {
    const preview = document.getElementById('additionalFilesPreview');
    const existingFiles = preview.querySelectorAll('.existing-file');
    let existingCount = 0;
    
    // Compter les fichiers existants non supprimés
    existingFiles.forEach(file => {
        if (file.style.display !== 'none') {
            existingCount++;
        }
    });
    
    // Calculer le nombre de fichiers qu'on peut encore ajouter
    const remainingSlots = 3 - existingCount;
    
    if (this.files.length > 0) {
        // Limiter à remainingSlots fichiers
        const filesToShow = Array.from(this.files).slice(0, remainingSlots);
        
        // Supprimer les anciens nouveaux fichiers (ceux qui ne sont pas 'existing-file')
        const newFiles = preview.querySelectorAll('.file-item:not(.existing-file)');
        newFiles.forEach(file => file.remove());
        
        // Ajouter les nouveaux fichiers
        filesToShow.forEach((file, index) => {
            const fileItem = document.createElement('div');
            fileItem.className = 'file-item';
            
            // Déterminer l'icône en fonction de l'extension
            const ext = file.name.split('.').pop().toLowerCase();
            let iconClass = 'fa-file';
            
            if (ext === 'pdf') iconClass = 'fa-file-pdf text-danger';
            else if (['ppt', 'pptx'].includes(ext)) iconClass = 'fa-file-powerpoint text-warning';
            else if (['doc', 'docx'].includes(ext)) iconClass = 'fa-file-word text-primary';
            else if (['py', 'java', 'cpp', 'c', 'js', 'html', 'css', 'php', 'txt'].includes(ext)) iconClass = 'fa-file-code text-success';
            
            fileItem.innerHTML = `
                <div class="file-info">
                    <i class="fas ${iconClass} file-icon"></i>
                    <span class="file-name">${file.name}</span>
                </div>
                <button type="button" class="delete-file" onclick="this.closest('.file-item').remove(); updateAdditionalFilesInput()">
                    <i class="fas fa-trash"></i>
                </button>
            `;
            
            preview.appendChild(fileItem);
        });
        
        // Mettre à jour l'input files
        updateAdditionalFilesInput();
    }
});*/

function updateAdditionalFilesInput() {
    const input = document.getElementById('additionalFiles');
    const dataTransfer = new DataTransfer();
    
    // Réinitialiser l'input files
    input.files = dataTransfer.files;
}

/*
function removeExistingFile(button) {
    const fileItem = button.closest('.existing-file');
    const hiddenInput = document.createElement('input');
    hiddenInput.type = 'hidden';
    hiddenInput.name = 'delete_additionalFiles[]';
    hiddenInput.value = fileItem.querySelector('input').value;
    document.getElementById('projectForm').appendChild(hiddenInput);
    
    fileItem.style.display = 'none';
}*/

    </script>
</body>
</html>