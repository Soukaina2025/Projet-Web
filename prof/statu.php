<?php
session_start();
if (!isset($_SESSION['EMAIL'])) {
    header("Location: loginp.php");
    exit();
}

// Connexion à la base de données
require_once '../base_donnees/pdo.php';

// Traitement de la validation/refus des projets
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['project_id'])) {
    try {
        $project_id = filter_input(INPUT_POST, 'project_id', FILTER_VALIDATE_INT);
        $action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);
        
        if ($project_id && in_array($action, ['V', 'R'])) {
            $stmt = $db->prepare("UPDATE PROJECT SET etat = ? WHERE ID_PROJECT = ?");
            $stmt->execute([$action, $project_id]);
            
            $_SESSION['success'] = "Le projet a bien été " . ($action === 'V' ? 'validé' : 'refusé');
            header("Location: ".$_SERVER['PHP_SELF']);
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur lors de la mise à jour du projet: " . $e->getMessage();
    }
}

$prof_id = $_SESSION['ID_PROF'];

// Fonction pour nettoyer les entrées
function cleanInput($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Fonction pour générer les initiales
function getInitials($nom, $prenom) {
    $initials = '';
    if (!empty($nom)) $initials .= mb_substr($nom, 0, 1, 'UTF-8');
    if (!empty($prenom)) $initials .= mb_substr($prenom, 0, 1, 'UTF-8');
    return strtoupper($initials);
}

// Récupération des données du professeur
try {
    $stmt = $db->prepare("SELECT * FROM prof WHERE ID_PROF = ?");
    $stmt->execute([$prof_id]);
    $prof = $stmt->fetch();
    
    if (!$prof) {
        throw new Exception("Professeur non trouvé");
    }
    
    // Générer les initiales pour l'affichage
    $prof_initials = getInitials($prof['NOM'], $prof['PRENOM']);
} catch (Exception $e) {
    error_log("Error fetching professor: " . $e->getMessage());
    $_SESSION['error'] = "Erreur lors de la récupération des données";
    header("Location: loginp.php");
    exit();
}

// Récupération des modules avec protection
try {
    $stmt = $db->prepare("SELECT id_module, nom FROM modules WHERE ID_PROF = ?");
    $stmt->execute([$prof_id]);
    $modules = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching modules: " . $e->getMessage());
    $modules = [];
}

// Traitement du formulaire de mise à jour avec validation renforcée
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    try {
        $db->beginTransaction();
        
        // Validation des entrées
        $nom = filter_input(INPUT_POST, 'nom', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        $prenom = filter_input(INPUT_POST, 'prenom', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        $poste = filter_input(INPUT_POST, 'poste', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        $departement = filter_input(INPUT_POST, 'departement', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        
        if (empty($nom) || empty($prenom)) {
            throw new Exception("Le nom et le prénom sont obligatoires");
        }
        
        $imagePath = $prof['IMG'] ?? null;

        // Gestion du téléchargement de l'image avec vérifications renforcées
        if (isset($_FILES['profile']) && $_FILES['profile']['error'] === UPLOAD_ERR_OK) {
            $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($fileInfo, $_FILES['profile']['tmp_name']);
            finfo_close($fileInfo);
            
            $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif'];
            if (!array_key_exists($mimeType, $allowedTypes)) {
                throw new Exception("Type de fichier non autorisé");
            }
            
            $maxFileSize = 2 * 1024 * 1024; // 2MB
            if ($_FILES['profile']['size'] > $maxFileSize) {
                throw new Exception("La taille du fichier dépasse la limite autorisée (2MB)");
            }
            
            $extension = $allowedTypes[$mimeType];
            $name = uniqid('img_', true) . '.' . $extension;
            $pathperm = 'uploads/';
            
            if (!file_exists($pathperm)) {
                mkdir($pathperm, 0755, true);
            }
            
            $cheminabs = $pathperm . $name;
            if (!move_uploaded_file($_FILES['profile']['tmp_name'], $cheminabs)) {
                throw new Exception("Erreur lors de l'enregistrement du fichier");
            }
            
            $imagePath = $cheminabs;
            
            // Supprimer l'ancienne image si elle existe
            if (!empty($prof['IMG']) && file_exists($prof['IMG']) && $prof['IMG'] !== $cheminabs) {
                unlink($prof['IMG']);
            }
        }
            
        // Mise à jour du profil avec requête préparée
        $stmt = $db->prepare("UPDATE prof SET NOM = ?, PRENOM = ?, POSTE = ?, DEPARTEMENT = ?, IMG = ? WHERE ID_PROF = ?");
        $stmt->execute([$nom, $prenom, $poste, $departement, $imagePath, $prof_id]);
        
        // Gestion des modules avec validation
        $moduleNames = $_POST['modules'] ?? [];
        $moduleIds = $_POST['module_id'] ?? [];
        
        if (!is_array($moduleNames) || !is_array($moduleIds)) {
            throw new Exception("Format de données invalide pour les modules");
        }

        // D'abord gérer les suppressions
        if (!empty($modules)) {
            $currentModuleIds = array_column($modules, 'id_module');
            $submittedModuleIds = array_filter($moduleIds, function($id) { 
                return $id !== 'new' && is_numeric($id); 
            });
            
            $modulesToDelete = array_diff($currentModuleIds, $submittedModuleIds);
            
            if (!empty($modulesToDelete)) {
                $placeholders = implode(',', array_fill(0, count($modulesToDelete), '?'));
                $stmt = $db->prepare("DELETE FROM modules WHERE id_module IN ($placeholders) AND ID_PROF = ?");
                $stmt->execute(array_merge($modulesToDelete, [$prof_id]));
            }
        }

        // Ensuite gérer les mises à jour et insertions
        foreach ($moduleNames as $index => $moduleName) {
            $moduleName = trim(htmlspecialchars($moduleName, ENT_QUOTES, 'UTF-8'));
            if (empty($moduleName)) continue;
            
            $moduleId = $moduleIds[$index] ?? 'new';
            
            if ($moduleId === 'new') {
                // Insertion d'un nouveau module
                $stmt = $db->prepare("INSERT INTO modules(nom, ID_PROF) VALUES(?, ?)");
                $stmt->execute([$moduleName, $prof_id]);
            } else {
                // Vérification de l'appartenance du module
                $checkStmt = $db->prepare("SELECT 1 FROM modules WHERE id_module = ? AND ID_PROF = ?");
                $checkStmt->execute([$moduleId, $prof_id]);
                
                if ($checkStmt->fetch()) {
                    // Mise à jour du module existant
                    $stmt = $db->prepare("UPDATE modules SET nom = ? WHERE id_module = ? AND ID_PROF = ?");
                    $stmt->execute([$moduleName, $moduleId, $prof_id]);
                }
            }
        }
        
        $db->commit();
        
        // Rafraîchir les données
        $stmt = $db->prepare("SELECT * FROM prof WHERE ID_PROF = ?");
        $stmt->execute([$prof_id]);
        $prof = $stmt->fetch();
        
        $stmt = $db->prepare("SELECT id_module, nom FROM modules WHERE ID_PROF = ?");
        $stmt->execute([$prof_id]);
        $modules = $stmt->fetchAll();
        
        $_SESSION['success'] = "Profil mis à jour avec succès!";
    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['error'] = "Erreur lors de la mise à jour du profil: " . $e->getMessage();
        error_log("Profile update error: " . $e->getMessage());
    }
}


// Récupération des projets avec filtres sécurisés
// Récupération des projets avec filtres sécurisés
try {
    $sql = "SELECT s.NOM AS Etudiant_Nom, s.PRENOM AS Etudiant_Prenom, p.NOTE, p.CATEGORIE,
           p.COMENTS, p.NOM AS Projet_Nom, p.DESCRIPTION, p.TYPE, p.DATE_DEP, p.SEMESTRE, 
           p.ID_PROJECT, p.STATUT, p.etat
           FROM PROJECT p
           JOIN STUDENT s ON p.APOGEE = s.APOGEE
           WHERE p.ID_PROF = :prof_id";


$params = [':prof_id' => $prof_id];
$conditions = [];

    if ($_SERVER["REQUEST_METHOD"] === 'GET') {
        // Filtre par statut avec validation
        if (!empty($_GET['statut'])) {
            $statut = $_GET['statut'];
            if (in_array($statut, ['V', 'R', 'P'])) {
                if ($statut === 'P') {
                    $conditions[] = "(p.etat IS NULL OR p.etat = '')";
                } else {
                    $conditions[] = "p.etat = :statut";
                    $params[':statut'] = $statut;
                }
            }
        }
        
        // Filtre par année avec validation
        // Filtre par année avec validation
if (!empty($_GET['annee'])) {
    $annee = filter_var($_GET['annee'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 2000, 'max_range' => 2100]]);
    if ($annee !== false) {
        $conditions[] = "YEAR(p.DATE_DEP) = :annee";
        $params[':annee'] = $annee;
    }
}
        
        // Filtre par semestre avec validation
        if (!empty($_GET['semestre'])) {
            $semestre = $_GET['semestre'];
            if (preg_match('/^S[1-6]$/', $semestre)) {
                $conditions[] = "p.SEMESTRE = :semestre";
                $params[':semestre'] = $semestre;
            }
        }
        
        // Filtre par module avec validation
        if (!empty($_GET['module'])) {
            $module = filter_var($_GET['module'], FILTER_SANITIZE_STRING);
            $conditions[] = "p.CATEGORIE = :module";
            $params[':module'] = $module;
        }
        
        // Filtre par étudiant avec validation
        if (!empty($_GET['etudiant'])) {
            $etudiant = filter_var($_GET['etudiant'], FILTER_SANITIZE_STRING);
            $etudiant_parts = explode(" ", $etudiant, 2);
            
            if (count($etudiant_parts) >= 1) {
                $conditions[] = "s.NOM LIKE :nom";
                $params[':nom'] = $etudiant_parts[0] . '%';
            }
            if (count($etudiant_parts) >= 2) {
                $conditions[] = "s.PRENOM LIKE :prenom";
                $params[':prenom'] = $etudiant_parts[1] . '%';
            }
        }
        
        if (!empty($conditions)) {
            $sql .= " AND " . implode(" AND ", $conditions);
        }
    }

    $stmt = $db->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    error_log("Project query error: " . $e->getMessage());
    $projects = [];
    $_SESSION['error'] = "Erreur lors de la récupération des projets";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <title>Statut des Projets</title>
    <style>
        /* Style global */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
            background-color: #f5f7fa;
            transition: all 0.3s ease;
        }
           .profile-initials-lg {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    background-color: #002c84;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 3rem;
    border: 3px solid #002c84;
    margin: 0 auto;
}
.profile-initials {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: #002c84;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 1.2rem;
    border: 2px solid #002c84;
    cursor: pointer;
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
            margin-right: 15px;
            cursor: pointer;
            border: 2px solid #002c84;
        }

        /* Nouveau style pour le menu profil */
        .profile-section {
            position: relative;
        }

        .profile-btn {
            display: flex;
            align-items: center;
            background: none;
            border: none;
            cursor: pointer;
            padding: 5px;
            border-radius: 50%;
            transition: all 0.3s ease;
        }
                       .profile-initials-lg {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    background-color: #002c84;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 3rem;
    border: 3px solid #002c84;
    margin: 0 auto;
}
.profile-initials {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: #002c84;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 1.2rem;
    border: 2px solid #002c84;
    cursor: pointer;
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
            margin-right: 15px;
            cursor: pointer;
            border: 2px solid #002c84;
        }


        .profile-menu {
            position: absolute;
            right: 0;
            top: 100%;
            background: white;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            padding: 10px 0;
            min-width: 200px;
            z-index: 1000;
            display: none;
        }

        .profile-menu.show {
            display: block;
        }

        .profile-menu-item {
            display: flex;
            align-items: center;
            padding: 8px 15px;
            color: #333;
            text-decoration: none;
            transition: all 0.2s;
        }

        .profile-menu-item:hover {
            background: rgba(0, 44, 132, 0.1);
            color: #002c84;
        }

        .profile-menu-item i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        .divider {
            height: 1px;
            background: #eee;
            margin: 5px 0;
        }

        .nav-container {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1030;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 40px;
            background: rgba(255, 255, 255, 0.9);
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(5px);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .nav-logo {
            margin-right: auto;
        }
        
        .nav-logo img {
            height: 45px;
            transition: transform 0.3s ease;
        }
        
        .nav-logo img:hover {
            transform: scale(1.05);
        }
        
        .nav-menu {
            display: flex;
            list-style: none;
            margin: 0;
            padding: 0;
            align-items: center;
        }
        
        .nav-menu li {
            margin-left: 30px;
            position: relative;
        }
        
        .nav-menu a {
            color: #333;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            letter-spacing: 0.5px;
            padding: 8px 0;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
        }
        
        .nav-menu a i {
            margin-right: 8px;
            font-size: 0.9rem;
        }
        
        .nav-menu a:hover {
            color: #002c84;
        }
        
        .nav-menu a::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            background: #002c84;
            bottom: 0;
            left: 0;
            transition: width 0.3s ease;
        }
        
        .nav-menu a:hover::after {
            width: 100%;
        }
        
        .nav-menu .active a {
            color: #002c84;
            font-weight: 600;
        }
        
        .nav-menu .active a::after {
            width: 100%;
        }

        .profile-section {
            display: flex;
            align-items: center;
            margin-left: 30px;
        }

        .profile-pic {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 15px;
            cursor: pointer;
            border: 2px solid #002c84;
        }

        /* Sidebar */
        .sidebar {
            background-color: #f8f9fa;
            padding: 30px 20px;
            height: 100vh;
            position: fixed;
            top: 60px;
            width: 250px;
            border-right: 1px solid #eaeaea;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.02);
            transition: all 0.3s ease;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 30px;
            margin-top: 80px; /* Compensation pour la navbar fixe */
        }

        /* Projects section */
        .projects-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .projects-header h2 {
            color: #002c84;
            font-weight: 600;
        }

        .projects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .project-card {
            border-radius: 10px;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .project-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .project-card .card-header {
            background-color: #002c84;
            color: white;
            padding: 15px;
            font-weight: 600;
        }
        .card-header {
            background-color: var(--primary-color);
            color: rgb(52, 55, 59);
            font-weight: bold;
            padding: 15px;
        }
        .project-card .card-body {
            padding: 20px;
        }
         :root {
            --primary-color: #002c84;
        }

        .project-meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            font-size: 0.9rem;
            color: #666;
        }

        .project-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
        }

        .status-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .badge-validated {
            background-color: #28a745;
            color: white;
        }

        .badge-rejected {
            background-color: #dc3545;
            color: white;
        }

        .badge-pending {
            background-color: #ffc107;
            color: #212529;
        }

        .btn-validate {
            background-color: #28a745;
            color: white;
        }

        .btn-reject {
            background-color: #dc3545;
            color: white;
        }

        .btn-outline-primary {
            color: #002c84;
            border-color: #002c84;
        }

        .btn-outline-primary:hover {
            background-color: #002c84;
            color: white;
        }
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            
            .main-content {
                margin-left: 0;
            }

            .projects-grid {
                grid-template-columns: 1fr;
            }

            .nav-container {
                padding: 15px 20px;
            }

            .nav-menu li {
                margin-left: 15px;
            }
        }

        /* Styles pour les modals de profil */
        .profile-modal-img {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border: 3px solid #002c84;
        }

        .module-item {
            transition: all 0.3s ease;
        }

        .module-item:hover {
            background-color: rgba(0, 44, 132, 0.05);
        }

        .badge-module {
            font-size: 0.8rem;
            font-weight: normal;
        }

        #profilePreview {
            cursor: pointer;
            transition: all 0.3s ease;
        }

        #profilePreview:hover {
            opacity: 0.8;
        }

        .list-group-item {
            transition: all 0.3s ease;
        }

        .list-group-item:hover {
            background-color: #f8f9fa;
        }

        /* Style pour le statut */
        .status-indicator {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
         .modal-dialog {
  width: 100%;
  max-width: 1000px;
  margin: auto;
}
/* Responsive */
@media (max-width: 900px) {
  .project-grid {
    grid-template-columns: 1fr;
  }
  
  .project-info-column {
    border-right: none;
    border-bottom: 1px solid #eaeaea;
    padding-right: 0;
    padding-bottom: 2rem;
    margin-bottom: 2rem;
  }
}

@media (max-width: 600px) {
  .modal-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }
  
  .close-modal {
    position: absolute;
    top: 1rem;
    right: 1rem;
  }
  
  .evaluation-grid {
    grid-template-columns: 1fr;
  }
  
  .date-grid {
    grid-template-columns: 1fr;
  }
}
/* Styles pour les modals de profil */
.profile-modal-img {
    width: 150px;
    height: 150px;
    object-fit: cover;
    border: 3px solid #002c84;
}

.module-item {
    transition: all 0.3s ease;
}

.module-item:hover {
    background-color: rgba(0, 44, 132, 0.05);
}

.badge-module {
    font-size: 0.8rem;
    font-weight: normal;
}

#profilePreview {
    cursor: pointer;
    transition: all 0.3s ease;
}

#profilePreview:hover {
    opacity: 0.8;
}

.list-group-item {
    transition: all 0.3s ease;
}

.list-group-item:hover {
    background-color: #f8f9fa;
}
/* Styles pour le bouton burger */
.menu-toggle {
    display: none; /* Caché par défaut sur desktop */
    background: none;
    border: none;
    font-size: 1.5rem;
    color: #002c84;
    cursor: pointer;
    padding: 5px 10px;
    order: 1; /* Position après le logo */
}

/* Navigation en mode mobile */
@media (max-width: 1024px) {
    .menu-toggle {
        display: block; /* Visible sur tablettes et mobiles */
    }
    
    .nav-menu {
        display: none;
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        flex-direction: column;
        padding: 0;
        margin: 0;
        box-shadow: 0 5px 10px rgba(0,0,0,0.1);
        z-index: 1000;
    }
    
    .nav-menu.show {
        display: flex;
    }
    
    .nav-menu li {
        margin: 0;
        padding: 0;
        border-bottom: 1px solid #eee;
    }
    
    .nav-menu a {
        padding: 12px 20px;
        display: block;
    }
    
    .profile-section {
        margin-left: auto;

    }
}

/* Réorganisation des éléments dans la navbar */
.nav-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
}

.nav-logo {
    order: 0;
}

/* Pour les très petits écrans */
@media (max-width: 576px) {
    .menu-toggle {
        font-size: 1.3rem;
    }
    
    .nav-menu a {
        padding: 10px 15px;
        font-size: 0.9rem;
    }
    
    .nav-menu a i {
        margin-right: 5px;
        font-size: 0.8rem;
    }
}
    </style>
</head>
<body>
    <!-- Barre de navigation -->
   <div class="nav-container">
    <div class="nav-logo">
        <img src="../images/zmr.png" alt="Logo ZMR">
    </div>
    
    <!-- Bouton Burger -->
    <button class="menu-toggle" id="menuToggle">
        <i class="fas fa-bars"></i>
    </button>
    
    <ul class="nav-menu" id="navMenu">
        <!-- Vos liens de navigation existants -->
        <li ><a href="acceuil.php"><i class="fas fa-chalkboard-teacher"></i> Projets évalués</a></li>
        <li><a href="evaluer.php"><i class="fas fa-chalkboard-teacher"></i> Projets à évaluer</a></li>
        <li><a href="tous_projets.php"><i class="fas fa-project-diagram"></i> Tous les projets</a></li>
        <li class="active"><a href="statu.php"><i class="fas fa-chart-bar"></i>Status des projets</a></li>
    </ul>

      
      <div class="profile-section">
    <button class="profile-btn" id="profileBtn">
        <?php if(!empty($prof['IMG']) && file_exists($prof['IMG'])): ?>
            <img src="<?= htmlspecialchars($prof['IMG']) ?>" alt="Profile" class="profile-pic">
        <?php else: ?>
            <div class="initials-avatar"><?= $prof_initials ?></div>
        <?php endif; ?>
    </button>
            
            <div class="profile-menu" id="profileMenu">
                <a href="#" class="profile-menu-item" data-bs-toggle="modal" data-bs-target="#viewProfileModal">
                    <i class="fas fa-user"></i> Voir mon profil
                </a>
                <a href="#" class="profile-menu-item" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                    <i class="fas fa-edit"></i> Modifier profil
                </a>
                <div class="divider"></div>
                <a href="logout.php" class="profile-menu-item">
                    <i class="fas fa-sign-out-alt"></i> Déconnexion
                </a>
            </div>
        </div>
    </div>

   <!-- Sidebar -->
    <div class="sidebar">
        <h5 class="mb-4" style="color: var(--primary-color);"><i class="fas fa-filter"></i> Filtres</h5>
        <form id="filterForm" method="GET" action="">
             <div class="mb-3">
                <label class="form-label">Année académique</label>
                <select class="form-select" name="annee" id="annee">
                    <option value="">Toutes les années</option>
                    <option value="2025" <?= (!empty($_GET['annee']) && $_GET['annee'] == '2025') ? 'selected' : '' ?>>2025-2026</option>
                    <option value="2024" <?= (!empty($_GET['annee']) && $_GET['annee'] == '2024') ? 'selected' : '' ?>>2024-2025</option>
                    <option value="2023" <?= (!empty($_GET['annee']) && $_GET['annee'] == '2023') ? 'selected' : '' ?>>2023-2024</option>
                    <option value="2022" <?= (!empty($_GET['annee']) && $_GET['annee'] == '2022' )? 'selected' : '' ?>>2022-2023</option>
                    <option value="2021" <?= (!empty($_GET['annee']) && $_GET['annee'] == '2021' )? 'selected' : '' ?>>2021-2022</option>
                    <option value="2020" <?= (!empty($_GET['annee']) && $_GET['annee'] == '2020' )? 'selected' : '' ?>>2020-2021</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Semestre</label>
                <select class="form-select" name="semestre" id="semestre">
                    <option value="">Tous les semestres</option>
                    <option value="S1" <?= (!empty($_GET['semestre']) && $_GET['semestre'] == 'S1' )? 'selected' : '' ?>>S1</option>
                    <option value="S2" <?= (!empty($_GET['semestre']) && $_GET['semestre'] == 'S2' )? 'selected' : '' ?>>S2</option>
                    <option value="S3" <?= (!empty($_GET['semestre']) && $_GET['semestre'] == 'S3') ? 'selected' : '' ?>>S3</option>
                    <option value="S4" <?= (!empty($_GET['semestre']) && $_GET['semestre'] == 'S4') ? 'selected' : '' ?>>S4</option>
                    <option value="S5" <?= (!empty($_GET['semestre']) && $_GET['semestre'] == 'S5' )? 'selected' : '' ?>>S5</option>
                    <option value="S6" <?= (!empty($_GET['semestre']) && $_GET['semestre'] == 'S6') ? 'selected' : '' ?>>S6</option>
                    <option value="S7" <?= (!empty($_GET['semestre']) && $_GET['semestre'] == 'S6') ? 'selected' : '' ?>>S7</option>
                    <option value="S8" <?= (!empty($_GET['semestre']) && $_GET['semestre'] == 'S6') ? 'selected' : '' ?>>S8</option>
                    <option value="S9" <?= (!empty($_GET['semestre']) && $_GET['semestre'] == 'S6') ? 'selected' : '' ?>>S9</option>
                    <option value="S10" <?= (!empty($_GET['semestre']) && $_GET['semestre'] == 'S10') ? 'selected' : '' ?>>S10</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Statut</label>
                <select class="form-select" name="statut" id="statut">
                    <option value="">Tous les statuts</option>
                    <option value="V" <?php echo (!empty($_GET['statut']) && $_GET['statut'] == 'V') ? 'selected' : ''; ?>>Validés</option>
                    <option value="R" <?php echo (!empty($_GET['statut']) && $_GET['statut'] == 'R') ? 'selected' : ''; ?>>Refusés</option>
                    <option value="P" <?php echo (!empty($_GET['statut']) && $_GET['statut'] == 'P') ? 'selected' : ''; ?>>En attente</option>
                </select>
            </div>
           <div class="mb-3">
                <label class="form-label">Module</label>
                <select class="form-select" name="module" id="module">
                    <option value="">Tous les modules</option>
                    <option value="web" <?= (!empty($_GET['module']) && $_GET['module'] == 'web') ? 'selected' : '' ?>>Développement Web</option>
                    <option value="iot" <?= (!empty($_GET['module']) && $_GET['module'] == 'iot' )? 'selected' : '' ?>>Internet des objets</option>
                    <option value="mobile" <?= (!empty($_GET['module']) && $_GET['module'] == 'mobile') ? 'selected' : '' ?>>Mobile Developpement</option>
                    <option value="ia" <?= (!empty($_GET['module']) && $_GET['module'] == 'ia') ? 'selected' : '' ?>>Intelligence Artificielle</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Étudiant</label>
                <input type="text" class="form-control" name="etudiant" id="etudiant" placeholder="Nom de l'étudiant" 
                       value="<?php echo !empty($_GET['etudiant']) ? htmlspecialchars($_GET['etudiant']) : ''; ?>">
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1"><i class="fas fa-filter"></i> Appliquer</button>
                <a href="statu.php" class="btn btn-outline-secondary"><i class="fas fa-times"></i></a>
            </div>
        </form>
    </div>

 <!-- Contenu principal avec projets -->
    <div class="main-content">
        <div class="projects-header">
            <div class="d-flex gap-2">
                <span class="badge bg-success">Validés: <?php echo count(array_filter($projects, function($p) { return $p['etat'] === 'V'; })); ?></span>
                <span class="badge bg-danger">Refusés: <?php echo count(array_filter($projects, function($p) { return $p['etat'] === 'R'; })); ?></span>
                <span class="badge bg-warning text-dark">En attente: <?php echo count(array_filter($projects, function($p) { return empty($p['etat']) || $p['etat'] === null; })); ?></span>
            </div>
        </div>
        
   <div class="projects-grid">
    <?php 
    if (count($projects) > 0) {
        foreach($projects as $row) {
            $statusClass = '';
            $statusText = 'En attente';
            
            if ($row['etat'] === 'V') {
                $statusClass = 'badge-validated';
                $statusText = 'Validé';
            } elseif ($row['etat'] === 'R') {
                $statusClass = 'badge-rejected';
                $statusText = 'Refusé';
            } else {
                $statusClass = 'badge-pending';
            }
    ?>
    <div class="card project-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <?php echo htmlspecialchars($row['Projet_Nom']); ?>
            <span class="badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
        </div>
        <div class="card-body">
            <div class="project-meta">
                <span><i class="fas fa-user-graduate mr-2"></i><?php echo htmlspecialchars($row['Etudiant_Nom'] . ' ' . $row['Etudiant_Prenom']); ?></span>
            </div>
            <p><?php echo htmlspecialchars($row['DESCRIPTION']); ?></p>
            <div class="project-meta">
                <span><i class="fas fa-calendar mr-2"></i><?php echo htmlspecialchars($row['DATE_DEP']); ?></span>
                <span><i class="fas fa-book mr-2"></i><?php echo htmlspecialchars($row['CATEGORIE']); ?></span>
            </div>
            <?php if (empty($row['etat']) || $row['etat'] === null) { ?>
                <form method="POST" class="status-actions">
                    <input type="hidden" name="project_id" value="<?php echo $row['ID_PROJECT']; ?>">
                    <button type="submit" name="action" value="V" class="btn btn-sm btn-validate"><i class="fas fa-check mr-1"></i> Valider</button>
                    <button type="submit" name="action" value="R" class="btn btn-sm btn-reject"><i class="fas fa-times mr-1"></i> Refuser</button>
                </form>
            <?php } ?>
        </div>
    </div>
    <?php
        }
    } else { ?>
              <div class="alert alert-info">
                    Aucun projet trouvé.
                </div>
   <?php }
    ?>
</div>
</div>
 <!-- Modal Voir Profil -->
<div class="modal fade" id="viewProfileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Mon Profil</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                
                <div class="row">
                    <div class="col-md-4 text-center">
                         <?php if(!empty($prof['IMG']) && file_exists($prof['IMG'])): ?>
            <img src="<?= htmlspecialchars($prof['IMG']) ?>" alt="Profile" class="profile-pic">
        <?php else: ?>
            <div id="profileInitials" class="profile-initials-lg mb-3"><?= $prof_initials ?></div>
        <?php endif; ?>
                       
                        <h4><?= htmlspecialchars($prof['NOM'] . ' ' . $prof['PRENOM']) ?></h4>
                        <p class="text-muted"><?= htmlspecialchars($prof['POSTE']) ?></p>
                        
                    </div>
                    <div class="col-md-8">
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">Informations Personnelles</h6>
                            </div>
                            <div class="card-body">
                                <div class="row mb-2">
                                    <div class="col-sm-4 fw-bold">Email:</div>
                                    <div class="col-sm-8"><?= htmlspecialchars($prof['EMAIL']) ?></div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-sm-4 fw-bold">Département:</div>
                                    <div class="col-sm-8"><?= htmlspecialchars($prof['DEPARTEMENT']) ?></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">Modules Enseignés</h6>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($modules)): ?>
                                    <ul class="list-group list-group-flush">
                                        <?php foreach($modules as $module): ?>
                                            <li class="list-group-item">
                                                <?= htmlspecialchars($module['nom']) ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <p class="text-muted">Aucun module enseigné</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-primary" id="editProfileBtn">Modifier Profil</button>
            </div>
        </div>
    </div>
</div>

<!-- HTML pour le modal "Modifier Profil" -->
<div class="modal fade" id="editProfileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Modifier Mon Profil</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="profileForm" method="POST" action="" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="row mb-4">
                        <div class="col-md-4 text-center">
                           <?php if(!empty($prof['IMG']) && file_exists($prof['IMG'])): ?>
            <img src="<?= htmlspecialchars($prof['IMG']) ?>" alt="Profile" class="profile-pic">
        <?php else: ?>
<div id="profileInitials" class="profile-initials-lg mb-3"><?= $prof_initials ?></div>
        <?php endif; ?>
                            <input type="file" id="profileImageUpload" class="d-none" name="profile" accept="image/jpeg, image/png, image/gif">

                            <div><button type="button" class="btn btn-sm btn-outline-primary" onclick="document.getElementById('profileImageUpload').click()">
                                <i class="fas fa-camera me-1"></i> Changer photo
                            </button></div>
                        </div>
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="nom" class="form-label">Nom</label>
                                <input type="text" class="form-control" id="nom" name="nom" 
                                       value="<?= htmlspecialchars($prof['NOM']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="prenom" class="form-label">Prénom</label>
                                <input type="text" class="form-control" id="prenom" name="prenom" 
                                       value="<?= htmlspecialchars($prof['PRENOM']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="poste" class="form-label">Poste</label>
                                <input type="text" class="form-control" id="poste" name="poste" 
                                       value="<?= htmlspecialchars($prof['POSTE']) ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Informations Professionnelles</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" 
                                       value="<?= htmlspecialchars($prof['EMAIL']) ?>" disabled>
                            </div>
                            <div class="mb-3">
                                <label for="departement" class="form-label">Département</label>
                                <select class="form-select" id="departement" name="departement" required>
                                    <option value="Informatique" <?= $prof['DEPARTEMENT'] === 'Informatique' ? 'selected' : '' ?>>Informatique</option>
                                    <option value="Mathématiques" <?= $prof['DEPARTEMENT'] === 'Mathématiques' ? 'selected' : '' ?>>Mathématiques</option>
                                    <option value="Physique" <?= $prof['DEPARTEMENT'] === 'Physique' ? 'selected' : '' ?>>Physique</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Modules Enseignés</h6>
                        </div>
                        <div class="card-body">
                            <div id="modulesContainer">
                                <?php foreach ($modules as $module): ?>
                                    <div class="module-item mb-3">
                                        <div class="input-group">
                                            <input type="hidden" name="module_id[]" 
                                                   value="<?= htmlspecialchars($module['id_module']) ?>">
                                            <input type="text" class="form-control" name="modules[]" 
                                                   value="<?= htmlspecialchars($module['nom']) ?>" required>
                                            <button class="btn btn-outline-danger" type="button" onclick="removeModule(this)">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                
                                <div class="module-item mb-3">
                                    <div class="input-group">
                                        <input type="hidden" name="module_id[]" value="new">
                                        <input type="text" class="form-control" name="modules[]" placeholder="Ajouter un nouveau module">
                                        <button class="btn btn-outline-danger" type="button" onclick="removeModule(this)">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="addModuleBtn">
                                <i class="fas fa-plus me-1"></i> Ajouter un module
                            </button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" name="update_profile">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Gestion du menu profil
        const profileBtn = document.getElementById('profileBtn');
        const profileMenu = document.getElementById('profileMenu');
        
        profileBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            profileMenu.classList.toggle('show');
        });
        
        document.addEventListener('click', function() {
            profileMenu.classList.remove('show');
        });
        
        profileMenu.addEventListener('click', function(e) {
            e.stopPropagation();
        });



        // Bouton "Modifier Profil" dans la modal de visualisation
        document.getElementById('editProfileBtn').addEventListener('click', function() {
            const viewProfileModal = bootstrap.Modal.getInstance(document.getElementById('viewProfileModal'));
            viewProfileModal.hide();
            
            const editProfileModal = new bootstrap.Modal(document.getElementById('editProfileModal'));
            editProfileModal.show();
        });

        // Gestion du changement de photo de profil
        document.getElementById('profileImageUpload').addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    document.getElementById('profilePreview').src = event.target.result;
                    document.querySelector('.profile-pic').src = event.target.result;
                };
                reader.readAsDataURL(e.target.files[0]);
            }
        });
         // Gestion des filtres
            document.getElementById('filterForm').addEventListener('submit', function(e) {
                // Le formulaire se soumet normalement via GET
            });

        // Ajouter un nouveau module
        document.getElementById('addModuleBtn').addEventListener('click', function() {
            const newModule = document.createElement('div');
            newModule.className = 'module-item mb-2';
            newModule.innerHTML = `
                <div class="input-group">
                    <input type="text" class="form-control" placeholder="Nom du module">
                    <select class="form-select" style="max-width: 120px;">
                        <option>Master</option>
                        <option>Licence</option>
                        <option>Doctorat</option>
                    </select>
                    <button class="btn btn-outline-danger" type="button">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            document.getElementById('modulesContainer').appendChild(newModule);
            
            newModule.querySelector('button').addEventListener('click', function() {
                newModule.remove();
            });
        });

        // Sauvegarder les modifications
        document.getElementById('saveProfileBtn').addEventListener('click', function() {
            const newName = document.getElementById('fullName').value;
            document.querySelector('#viewProfileModal h4').textContent = newName;
            
            const editProfileModal = bootstrap.Modal.getInstance(document.getElementById('editProfileModal'));
            editProfileModal.hide();
            
            const viewProfileModal = new bootstrap.Modal(document.getElementById('viewProfileModal'));
            viewProfileModal.show();
            
            alert('Profil mis à jour avec succès !');
        });

        // Gestion de la suppression des modules existants
        document.querySelectorAll('.module-item button').forEach(btn => {
            btn.addEventListener('click', function() {
                this.closest('.module-item').remove();
            });
        });

    </script>
<script>
            //responsive design
document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.getElementById('menuToggle');
    const navMenu = document.getElementById('navMenu');
    
    if (menuToggle && navMenu) {
        menuToggle.addEventListener('click', function() {
            navMenu.classList.toggle('show');
            
            // Changer l'icône burger/croix
            const icon = this.querySelector('i');
            if (navMenu.classList.contains('show')) {
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-times');
            } else {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
        });
        
        // Fermer le menu quand on clique à l'extérieur
        document.addEventListener('click', function(e) {
            if (!menuToggle.contains(e.target)) {
                navMenu.classList.remove('show');
                const icon = menuToggle.querySelector('i');
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
        });
    }
});
    </script>
</body>
</html>