<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['ID_PROF'])) {
    header("Location: loginp.php");
    exit();
}

// Régénérer l'ID de session pour prévenir les attaques de fixation
session_regenerate_id(true);

// Connexion à la base de données avec gestion des erreurs
require_once '../base_donnees/pdo.php';

$prof_id = $_SESSION['ID_PROF'];

// Fonctions utilitaires
function cleanInput($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function getInitials($nom, $prenom) {
    $initials = '';
    if (!empty($nom)) $initials .= mb_substr($nom, 0, 1, 'UTF-8');
    if (!empty($prenom)) $initials .= mb_substr($prenom, 0, 1, 'UTF-8');
    return strtoupper($initials);
}

// Récupération des données du professeur avec gestion des erreurs
try {
    $stmt = $db->prepare("SELECT * FROM prof WHERE ID_PROF = ?");
    $stmt->execute([$prof_id]);
    $prof = $stmt->fetch();
    
    if (!$prof) {
        throw new Exception("Professeur non trouvé");
    }
    
    $prof_initials = getInitials($prof['NOM'], $prof['PRENOM']);
} catch (Exception $e) {
    error_log("Error fetching professor: " . $e->getMessage());
    $_SESSION['error'] = "Erreur lors de la récupération des données";
    header("Location: loginp.php");
    exit();
}

// Traitement de l'évaluation de projet
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['note'])) {
    // Validation des données
    $note = filter_input(INPUT_POST, 'note', FILTER_VALIDATE_FLOAT, ['options' => ['min_range' => 0, 'max_range' => 20]]);
    $rq = filter_input(INPUT_POST, 'rq', FILTER_SANITIZE_STRING);
    $projectId = filter_input(INPUT_POST, 'project_id', FILTER_VALIDATE_INT);

    if ($note === false || $rq === null || $projectId === false) {
        $_SESSION['error'] = "Veuillez remplir tous les champs correctement";
        header("Location: evaluer.php");
        exit();
    }

    try {
        // Vérifier que le projet appartient bien au professeur
        $checkStmt = $db->prepare("SELECT 1 FROM project WHERE ID_PROJECT = ? AND ID_PROF = ?");
        $checkStmt->execute([$projectId, $prof_id]);
        
        if (!$checkStmt->fetch()) {
            throw new Exception("Vous n'êtes pas autorisé à évaluer ce projet");
        }

        // Mise à jour de l'évaluation
        $sql = "UPDATE project SET NOTE = :note, COMENTS = :rq, STATUT='EVALUE' WHERE ID_PROJECT = :id";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':note' => $note,
            ':rq' => $rq,
            ':id' => $projectId
        ]);
        
        $_SESSION['success'] = "Évaluation enregistrée avec succès";
        header("Location: evaluer.php");
        exit();
    } catch (PDOException $e) {
        error_log("Evaluation error: " . $e->getMessage());
        $_SESSION['error'] = "Erreur lors de l'enregistrement de l'évaluation";
        header("Location: evaluer.php");
        exit();
    }
}

// Récupération des modules avec gestion des erreurs
try {
    $stmt = $db->prepare("SELECT id_module, nom FROM modules WHERE ID_PROF = ?");
    $stmt->execute([$prof_id]);
    $modules = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching modules: " . $e->getMessage());
    $modules = [];
}

// Traitement de la mise à jour du profil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    try {
        $db->beginTransaction();
        
        // Validation des données
        $nom = filter_input(INPUT_POST, 'nom', FILTER_SANITIZE_STRING);
        $prenom = filter_input(INPUT_POST, 'prenom', FILTER_SANITIZE_STRING);
        $poste = filter_input(INPUT_POST, 'poste', FILTER_SANITIZE_STRING);
        $departement = filter_input(INPUT_POST, 'departement', FILTER_SANITIZE_STRING);
        $imagePath = $prof['IMG'] ?? null;

        // Validation des champs obligatoires
        if (empty($nom) || empty($prenom) || empty($departement)) {
            throw new Exception("Les champs Nom, Prénom et Département sont obligatoires");
        }

        // Gestion du téléchargement de l'image
        if(isset($_FILES['profile']) && $_FILES['profile']['error'] === UPLOAD_ERR_OK) {
            // Validation du fichier
            $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif'];
            $fileType = mime_content_type($_FILES['profile']['tmp_name']);
            
            if(!array_key_exists($fileType, $allowedTypes)) {
                throw new Exception("Type de fichier non autorisé. Seuls JPEG, PNG et GIF sont acceptés");
            }
            
            // Création du répertoire si inexistant
            $uploadDir = 'uploads/';
            if(!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Génération d'un nom de fichier sécurisé
            $extension = $allowedTypes[$fileType];
            $filename = uniqid('prof_', true) . '.' . $extension;
            $destination = $uploadDir . $filename;
            
            if(move_uploaded_file($_FILES['profile']['tmp_name'], $destination)) {
                $imagePath = $destination;
                
                // Suppression de l'ancienne image si elle existe
                if(!empty($prof['IMG']) && file_exists($prof['IMG'])) {
                    unlink($prof['IMG']);
                }
            } else {
                throw new Exception("Erreur lors du téléchargement de l'image");
            }
        }
            
        // Mise à jour du profil
        $stmt = $db->prepare("UPDATE prof SET NOM = ?, PRENOM = ?, POSTE = ?, DEPARTEMENT = ?, IMG = ? WHERE ID_PROF = ?");
        $stmt->execute([$nom, $prenom, $poste, $departement, $imagePath, $prof_id]);
        
        // Gestion des modules
        $moduleNames = $_POST['modules'] ?? [];
        $moduleIds = $_POST['module_id'] ?? [];

        // Suppression des modules non soumis
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

        // Mise à jour/insertion des modules
        foreach ($moduleNames as $index => $moduleName) {
            $moduleName = cleanInput($moduleName);
            if (empty($moduleName)) continue;
            
            $moduleId = $moduleIds[$index] ?? 'new';
            
            if ($moduleId === 'new') {
                $stmt = $db->prepare("INSERT INTO modules(nom, ID_PROF) VALUES(?, ?)");
                $stmt->execute([$moduleName, $prof_id]);
            } else {
                $checkStmt = $db->prepare("SELECT 1 FROM modules WHERE id_module = ? AND ID_PROF = ?");
                $checkStmt->execute([$moduleId, $prof_id]);
                
                if ($checkStmt->fetch()) {
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
        
        $prof_initials = getInitials($prof['NOM'], $prof['PRENOM']);
        
        $_SESSION['success'] = "Profil mis à jour avec succès";
    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['error'] = "Erreur lors de la mise à jour: " . $e->getMessage();
        error_log("Profile update error: " . $e->getMessage());
    } finally {
        header("Location: evaluer.php");
        exit();
    }
}

// Requête pour les projets à évaluer avec filtres sécurisés
$sql = "SELECT s.NOM AS Etudiant_Nom, s.PRENOM AS Etudiant_Prenom, p.NOTE, p.COMENTS, 
       p.NOM AS Projet_Nom, p.DESCRIPTION, p.TYPE, p.TECHNOLOGIE, p.DATE_DEP, p.SEMESTRE, 
       p.ID_PROJECT, p.STATUT, pr.NOM AS Prof_Nom, pr.PRENOM AS Prof_Prenom, 
       p.CATEGORIE, p.FICHIER, p.FILE1, p.FILE2, p.FILE3
FROM PROJECT p
JOIN STUDENT s ON p.APOGEE = s.APOGEE
JOIN PROF pr ON p.ID_PROF = pr.ID_PROF
WHERE (p.STATUT IS NULL OR p.STATUT = '') AND p.etat='V' AND p.ID_PROF = :prof_id";

$params = [':prof_id' => $prof_id];
$conditions = [];

// Application des filtres sécurisés
if ($_SERVER["REQUEST_METHOD"] === 'GET') {
    // Filtre par année
   if (!empty($_GET['annee']) && preg_match('/^\d{4}$/', $_GET['annee'])) {
    $conditions[] = "YEAR(p.DATE_DEP) = :annee";
    $params[':annee'] = $_GET['annee'];
}
    
    // Filtre par semestre
    if (!empty($_GET['semestre']) && in_array($_GET['semestre'], ['S1', 'S2', 'S3', 'S4', 'S5', 'S6'])) {
        $conditions[] = "p.SEMESTRE = :semestre";
        $params[':semestre'] = $_GET['semestre'];
    }
    
    // Filtre par module
    if (!empty($_GET['module']) && in_array($_GET['module'], ['developpement_web', 'base_donnees', 'mobile_development', 'intelligence_artificielle'])) {
        $conditions[] = "p.CATEGORIE = :module";
        $params[':module'] = $_GET['module'];
    }
    
    // Filtre par étudiant
    if (!empty($_GET['etudiant'])) {
        $etudiant = cleanInput($_GET['etudiant']);
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
}

// Construction de la requête finale
if (!empty($conditions)) {
    $sql .= " AND " . implode(" AND ", $conditions);
}

try {
    $stmt = $db->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $aeval = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching projects: " . $e->getMessage());
    $aeval = [];
    $_SESSION['error'] = "Erreur lors de la récupération des projets";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Projets à évaluer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
    <style>
       /* Style global */
       body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
            background-color: #f5f7fa;
            transition: all 0.3s ease;
        }

        :root {
            --primary-color: #002c84;
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
            padding: 15px 20px;
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

        .projects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
        }

        .project-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease;
        }

        .project-card:hover {
            transform: translateY(-5px);
        }

        .project-card .card-header {
            background-color: #002c84;
            color: white;
            padding: 15px;
            font-weight: 600;
        }

        .card-body {
            padding: 20px;
        }

        .project-meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 0.9rem;
            color: #666;
        }

        .project-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
        }

        .badge-pending {
            background-color: #fff3cd;
            color: #856404;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
        }
        
        /* Style structuré pour la modal */
        .project-modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(8px);
            z-index: 2000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            overflow-y: auto;
        }

        .modal-dialog {
            width: 100%;
            max-width: 1000px;
            margin: auto;
        }

        .modal-content {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 30px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }

        .modal-header {
            padding: 1.5rem;
            background: #002c84;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-content {
            flex: 1;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .project-meta-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-top: 0.5rem;
        }

        .module-badge {
            background: rgba(255, 255, 255, 0.2);
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
        }

        .grade-badge {
            background: #4CAF50;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-weight: bold;
        }

        .close-modal {
            background: none;
            border: none;
            color: white;
            font-size: 2rem;
            cursor: pointer;
            padding: 0 0.5rem;
        }

        .modal-body {
            padding: 1.5rem;
        }

        .project-grid {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 2rem;
        }

        .project-info-column {
            border-right: 1px solid #eaeaea;
            padding-right: 2rem;
        }

        .info-section {
            margin-bottom: 2rem;
        }

        .info-section h4 {
            color: #002c84;
            font-size: 1rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .date-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .date-label {
            display: block;
            font-size: 0.8rem;
            color: #666;
        }

        .tech-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .tech-tag {
            background: #e9f0f7;
            color: #002c84;
            padding: 0.25rem 0.75rem;
            border-radius: 4px;
            font-size: 0.8rem;
        }

        .file-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .file-list li {
            display: flex;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid #eee;
            gap: 0.5rem;
        }

        .file-list li i {
            color: #002c84;
            width: 20px;
            text-align: center;
        }

        .file-list li span {
            flex: 1;
        }

        .download-file-btn {
            background: none;
            border: 1px solid #002c84;
            color: #002c84;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            cursor: pointer;
        }

        .download-file-btn:hover {
            background: #002c84;
            color: white;
        }

        .project-image-container {
            margin-bottom: 1.5rem;
        }

        .project-image-container img {
            width: 100%;
            border-radius: 8px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }

        .description-section, .evaluation-section {
            margin-bottom: 1.5rem;
        }

        .description-content {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            border-left: 4px solid #002c84;
        }

        .evaluation-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .eval-label {
            font-weight: 600;
            color: #555;
        }

        .eval-value {
            display: block;
        }

        .comments-box {
            background: #f0f7ff;
            padding: 1rem;
            margin-top:8px;
            border-radius: 8px;
            border-left: 4px solid #4CAF50;
        }

        .modal-footer {
            padding: 1rem 1.5rem;
            background: #f8f9fa;
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                position: relative;
                height: auto;
                margin-top: 0;
            }

            .main-content {
                margin-left: 0;
            }

            .projects-grid {
                grid-template-columns: 1fr;
            }
            
            .project-grid {
                grid-template-columns: 1fr;
            }
            
            .project-info-column {
                border-right: none;
                padding-right: 0;
                border-bottom: 1px solid #eaeaea;
                padding-bottom: 2rem;
                margin-bottom: 2rem;
            }
        }
         .btn-outline-primary {
            color: #002c84;
            border-color: #002c84;
        }

        .btn-outline-primary:hover {
            background-color: #002c84;
            color: white;
        }
        #evaluationModal form{
            padding :20px;
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
@media (max-width: 991px) {
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

<!-- Barre de navigation avec logo intégré -->
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
        <li class="active"><a href="evaluer.php"><i class="fas fa-chalkboard-teacher"></i> Projets à évaluer</a></li>
        <li><a href="tous_projets.php"><i class="fas fa-project-diagram"></i> Tous les projets</a></li>
        <li><a href="statu.php"><i class="fas fa-chart-bar"></i>Status des projets</a></li>
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
                   value="<?= !empty($_GET['etudiant']) ? htmlspecialchars($_GET['etudiant']) : '' ?>">
        </div>
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary flex-grow-1"><i class="fas fa-filter"></i> Appliquer</button>
            <a href="evaluer.php" class="btn btn-outline-secondary"><i class="fas fa-times"></i></a>
        </div>
    </form>
</div>

<!-- Contenu principal -->
<div class="main-content">
    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger"><?= $error_message ?></div>
    <?php endif; ?>
    
    <div class="projects-grid">
         <?php if(empty($aeval)): ?>
                <div class="alert alert-info">
                    Aucun projet trouvé.
                </div>
            <?php else: ?>
        <?php foreach($aeval as $ae): ?>
        <div class="card project-card" 
             data-id="<?= htmlspecialchars($ae['ID_PROJECT']) ?>"
             data-title="<?= htmlspecialchars($ae['Projet_Nom']) ?>"
             data-student="<?= htmlspecialchars($ae['Etudiant_Nom']) ?> <?= htmlspecialchars($ae['Etudiant_Prenom']) ?>"
             data-grade="<?= $ae['NOTE'] ?>"
             data-prof-name="<?= htmlspecialchars($ae['Prof_Nom']) ?> <?= htmlspecialchars($ae['Prof_Prenom']) ?>"
             data-description="<?= htmlspecialchars($ae['DESCRIPTION']) ?>"
             data-module="<?= htmlspecialchars($ae['CATEGORIE']) ?>"
             data-type="<?= htmlspecialchars($ae['TYPE']) ?>"
             data-tech="<?= htmlspecialchars($ae['TECHNOLOGIE']) ?>"
             data-annee="<?= htmlspecialchars($ae['DATE_DEP']) ?>"
             data-semestre="<?= htmlspecialchars($ae['SEMESTRE']) ?>"
             data-comments="<?= htmlspecialchars($ae['COMENTS']) ?>"
             data-fichier="<?= htmlspecialchars($ae['FICHIER']) ?>"
             data-file1="<?= htmlspecialchars($ae['FILE1']) ?>"
             data-file2="<?= htmlspecialchars($ae['FILE2']) ?>"
             data-file3="<?= htmlspecialchars($ae['FILE3']) ?>">
            <div class="card-header "><?= htmlspecialchars($ae['Projet_Nom']) ?></div>

            <div class="card-body">
                <div class="project-meta">
                    <span><i class="fas fa-user-graduate me-1"></i> <?= htmlspecialchars($ae['Etudiant_Nom']) . ' ' . htmlspecialchars($ae['Etudiant_Prenom']) ?></span>
                    <span><i class="fas fa-clock me-1 text-warning"></i> En attente</span>
                </div>
                <p><?= htmlspecialchars($ae['DESCRIPTION']) ?></p>
                <div class="project-meta">
                    <span><i class="fas fa-book me-1"></i><?= htmlspecialchars($ae['CATEGORIE']) ?></span>
                    <span class="badge-pending">En attente</span>
                </div>
                <div class="project-actions">
                    <button class="btn btn-sm btn-outline-primary view-project-btn"><i class="fas fa-eye me-1"></i> Voir</button>
                    <button class="btn btn-sm btn-outline-primary me-1 ae-btn" 
                            data-project-id="<?= $ae['ID_PROJECT'] ?>" 
                            data-bs-toggle="modal" 
                            data-bs-target="#evaluationModal">
                        <i class="fas fa-edit"></i> Évaluer
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
         <?php endif; ?>
    </div>
</div>

<!-- Modal de téléchargement -->
<div class="modal fade" id="downloadModal" tabindex="-1" aria-labelledby="downloadModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="downloadModalLabel">Télécharger les livrables</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="download-options">
                    <h6>Sélectionnez les fichiers à télécharger :</h6>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="downloadReport" checked>
                        <label class="form-check-label" for="downloadReport">
                            Rapport du projet (PDF)
                        </label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="downloadPresentation" checked>
                        <label class="form-check-label" for="downloadPresentation">
                            Présentation (PPTX)
                        </label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="downloadSource" checked>
                        <label class="form-check-label" for="downloadSource">
                            Code source (ZIP)
                        </label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="downloadEvaluation">
                        <label class="form-check-label" for="downloadEvaluation">
                            Fiche d'évaluation (PDF)
                        </label>
                    </div>
                </div>
                <div class="download-format mt-3">
                    <h6>Format d'archive :</h6>
                    <select class="form-select">
                        <option selected>ZIP (recommandé)</option>
                        <option>RAR</option>
                        <option>Fichiers séparés</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="confirmDownload">Télécharger</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal des détails du projet -->
<div class="project-modal" id="projectModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <div class="header-content">
                    <h3 id="modalProjectTitle">Titre du projet</h3>
                    <div class="project-meta-header">
                        <span class="badge module-badge" id="modalProjectModule">Module</span>
                        <span class="grade-badge" id="modalProjectGrade">18/20</span>
                    </div>
                </div>
                <button class="close-modal" id="closeModalBtn">&times;</button>
            </div>
            
            <div class="modal-body">
                <div class="project-grid">
                    <div class="project-info-column">
                        <div class="info-section">
                            <h4><i class="fas fa-user-tie"></i> Étudiant</h4>
                            <p id="modalStudentName">Prénom Nom</p>
                            <h4><i class="fas fa-chalkboard-teacher"></i> Encadrant</h4>
                            <p id="modalSupervisor">Nom Prénom</p>
                        </div>
                        
                        <div class="info-section">
                            <h4><i class="fas fa-calendar-alt"></i> Dates</h4>
                            <div class="date-grid">
                                <div>
                                    <span class="date-label">Année:</span>
                                    <span id="modalProjectYear">2023</span>
                                </div>
                                <div>
                                    <span class="date-label">Semestre:</span>
                                    <span id="modalProjectSemester">S1</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="info-section">
                            <h4><i class="fas fa-code"></i> Type de projet</h4>
                            <div class="tech-tags" id="modalType">
                                <span class="tech-tag">Stage</span>
                            </div>
                        </div>
                        
                        <div class="info-section">
                            <h4><i class="fas fa-file-alt"></i> Fichiers</h4>
                            <ul class="file-list" id="modalFileList">
                                <!-- Fichiers seront chargés ici -->
                            </ul>
                        </div>
                    </div>
                    
                    <div class="project-content-column">
                        <div class="description-section">
                            <h4><i class="fas fa-align-left"></i> Description</h4>
                            <div class="comments-box" id="modalProjectDesc">
                                <p>Description du projet...</p>
                            </div>
                        </div>

                        <div class="description-section">
                            <h4><i class="fas fa-code"></i> Technologies</h4>
                            <div class="comments-box" id="modalTech">
                                <p>technologie...</p>
                            </div>
                        </div>
                        
                        <div class="evaluation-section">
                            <h4><i class="fas fa-clipboard-check"></i> Évaluation</h4>
                            <div class="evaluation-grid">
                                <div>
                                    <span class="eval-label">Note:</span>
                                    <span class="eval-value" id="modalProjectGradeValue">18/20</span>
                                </div>
                            </div>
                            <span class="eval-label">Commentaire:</span>
                            <div class="comments-box" id="modalComments">
                                <p>Commentaires d'évaluation...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button class="btn btn-secondary" id="modalPrintBtn">
                    <i class="fas fa-print"></i> Imprimer
                </button>
                <button class="btn btn-primary" id="modalDownloadAllBtn">
                    <i class="fas fa-download"></i> Télécharger tout
                </button>
            </div>
        </div>
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

<!-- Modal d'évaluation -->
<div class="modal fade" id="evaluationModal" tabindex="-1" aria-labelledby="evaluationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="evaluationModalLabel">Évaluation du projet</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
           
                
                <!-- Formulaire simple d'évaluation -->
<form method="POST" action="">
    <input type="hidden" name="project_id" id="modalProjectId" value="">
    
    <div class="mb-4">
        <label class="form-label">Note (sur 20)</label>
        <input type="number" class="form-control" name="note" min="0" max="20" step="0.5" placeholder="00.00" required>
    </div>
    <div class="mb-4">
        <label class="form-label">Remarques</label>
        <textarea class="form-control" name="rq" rows="4" placeholder="Vos remarques sur le projet..." required></textarea>
    </div>
    <div>
        <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Enregistrer l'évaluation</button>
        </div>
    </div>
</form>

<script>
    // Gestion des boutons d'évaluation
document.querySelectorAll('.ae-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const projectId = this.getAttribute('data-project-id');
        document.getElementById('modalProjectId').value = projectId;
    });
});   
</script>


<script>
    function redirectToDashboard() {
        window.location.href = "evaluer.php";
    }
</script>

            </div>
            
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Fonctions utilitaires
function removeModule(btn) {
    const moduleItem = btn.closest('.module-item');
    if (moduleItem && confirm('Supprimer ce module ?')) {
        moduleItem.remove();
    }
}

    // Soumission de l'évaluation
    document.getElementById('evaluationForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const grade = document.getElementById('projectGrade').value;
        const comments = document.getElementById('projectComments').value;
        const status = document.querySelector('input[name="projectStatus"]:checked').value;
        
        // Afficher une notification
        alert('Évaluation enregistrée avec succès!\nNote: ' + grade + '/20\nStatut: ' + (status === 'approved' ? 'Validé' : 'Refusé'));
        
        // Fermer le modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('evaluationModal'));
        modal.hide();
    });

    // Remplissage dynamique du modal
    document.querySelectorAll('[data-bs-target="#evaluationModal"]').forEach(btn => {
        btn.addEventListener('click', function() {
            const data = JSON.parse(this.getAttribute('data-project'));
            document.getElementById('projectTitle').textContent = data.title;
            document.getElementById('projectStudent').innerHTML = `Projet soumis par <strong>${data.student}</strong>`;
            document.getElementById('projectYear').textContent = data.year;
            document.getElementById('projectSemester').textContent = data.semester;
            document.getElementById('projectModule').textContent = data.module;
            
            // Réinitialiser le formulaire
            document.getElementById('projectGrade').value = '';
            document.getElementById('projectComments').value = '';
            document.getElementById('statusApproved').checked = true;
        });
    });
</script>
<script>
    // Gestion du menu profil
    const profileBtn = document.getElementById('profileBtn');
    const profileMenu = document.getElementById('profileMenu');
    const body = document.body;
    
    // Toggle menu profil
    profileBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        profileMenu.classList.toggle('show');
    });
    
    // Fermer le menu quand on clique ailleurs
    document.addEventListener('click', function() {
        profileMenu.classList.remove('show');
    });
    
    // Empêcher la fermeture quand on clique dans le menu
    profileMenu.addEventListener('click', function(e) {
        e.stopPropagation();
        if (!e.target.closest('.keep-open')) {
            profileMenu.classList.remove('show');
        }
    });
    
    // Gestion de l'affichage des détails du projet
            document.querySelectorAll('.view-project-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const projectCard = this.closest('.project-card');
                    
                    // Récupérer toutes les données du projet
                    const projectData = {
                        id: projectCard.dataset.id,
                        title: projectCard.dataset.title,
                        student: projectCard.dataset.student,
                        grade: projectCard.dataset.grade,
                        description: projectCard.dataset.description,
                        module: projectCard.dataset.module,
                        type: projectCard.dataset.type,
                        annee: projectCard.dataset.annee,
                        semestre: projectCard.dataset.semestre,
                        comments: projectCard.dataset.comments,
                        fichiers: [
                            {name: projectCard.dataset.fichier, field: 'FICHIER'},
                            {name: projectCard.dataset.file1, field: 'FILE1'},
                            {name: projectCard.dataset.file2, field: 'FILE2'},
                            {name: projectCard.dataset.file3, field: 'FILE3'}
                        ].filter(file => file.name) // Ne garder que les fichiers non vides
                    };
                    
                    // Remplir la modal avec ces données
                    document.getElementById('modalProjectTitle').textContent = projectData.title;
                    document.getElementById('modalStudentName').textContent = projectData.student;
                    document.getElementById('modalProjectGrade').textContent = projectData.grade ? projectData.grade + '/20' : 'Non évalué';
                    document.getElementById('modalProjectGradeValue').textContent = projectData.grade ? projectData.grade + '/20' : 'Non évalué';
                    document.getElementById('modalProjectModule').textContent = projectData.module;
                    document.getElementById('modalSupervisor').textContent = projectCard.dataset.profName;
                    document.getElementById('modalTech').textContent = projectCard.dataset.tech;
                    document.getElementById('modalType').textContent = projectCard.dataset.type;
                    document.getElementById('modalProjectDesc').innerHTML = `<p>${projectData.description}</p>`;
                    document.getElementById('modalProjectYear').textContent = projectData.annee;
                    document.getElementById('modalProjectSemester').textContent = projectData.semestre;
                    
                    // Commentaires
                    const commentsBox = document.getElementById('modalComments');
                    commentsBox.innerHTML = projectData.comments ? `<p>${projectData.comments}</p>` : '<p>Aucun commentaire.</p>';
                    
                    // Fichiers
                    const fileList = document.getElementById('modalFileList');
                    fileList.innerHTML = '';
                    
                    projectData.fichiers.forEach(file => {
                        const ext = file.name.split('.').pop().toLowerCase();
                        const fileTypes = {
                            'pdf': 'file-pdf',
                            'doc': 'file-word', 'docx': 'file-word',
                            'ppt': 'file-powerpoint', 'pptx': 'file-powerpoint',
                            'xls': 'file-excel', 'xlsx': 'file-excel',
                            'zip': 'file-archive', 'rar': 'file-archive', '7z': 'file-archive',
                            'jpg': 'file-image', 'jpeg': 'file-image', 'png': 'file-image', 'gif': 'file-image'
                        };
                        const fileType = fileTypes[ext] || 'file';
                        
                        const li = document.createElement('li');
                        li.innerHTML = `
                            <i class="fas fa-${fileType}"></i>
                            <span>${file.name}</span>
                            <button class="download-file-btn" data-file="${file.name}" data-field="${file.field}" data-project="${projectData.id}">Télécharger</button>
                        `;
                        fileList.appendChild(li);
                    });
                    
                    // Afficher la modal
                    document.getElementById('projectModal').style.display = 'flex';
                    document.body.style.overflow = 'hidden';
                });
            });
            
            // Fermer la modal
            document.getElementById('closeModalBtn').addEventListener('click', function() {
                document.getElementById('projectModal').style.display = 'none';
                document.body.style.overflow = 'auto';
            });
            
            document.getElementById('projectModal').addEventListener('click', function(e) {
                if (e.target === this) {
                    document.getElementById('projectModal').style.display = 'none';
                    document.body.style.overflow = 'auto';
                }
            });

// Gestion des clics sur "Voir"
document.querySelectorAll('.btn-outline-primary').forEach(btn => {
  if (btn.textContent.includes('Voir')) {
    btn.addEventListener('click', function() {
      showProjectDetails(this.closest('.project-card'));
    });
  }
});


// Boutons de la modal
document.getElementById('modalDownloadAllBtn').addEventListener('click', function() {
  alert('Téléchargement de tous les fichiers lancé !');
});

document.getElementById('modalPrintBtn').addEventListener('click', function() {
  window.print();
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
            // Mettre à jour aussi la photo dans la navbar
            document.querySelector('.profile-pic').src = event.target.result;
        };
        reader.readAsDataURL(e.target.files[0]);
    }
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
    
    // Ajouter l'événement de suppression
    newModule.querySelector('button').addEventListener('click', function() {
        newModule.remove();
    });
});

// Sauvegarder les modifications
document.getElementById('saveProfileBtn').addEventListener('click', function() {
    // Ici, vous devriez normalement envoyer les données au serveur
    // Pour cet exemple, nous allons juste afficher une notification
    
    // Mettre à jour le nom dans la modal de visualisation
    const newName = document.getElementById('fullName').value;
    document.querySelector('#viewProfileModal h4').textContent = newName;
    
    // Fermer la modal d'édition
    const editProfileModal = bootstrap.Modal.getInstance(document.getElementById('editProfileModal'));
    editProfileModal.hide();
    
    // Réafficher la modal de visualisation avec les nouvelles données
    const viewProfileModal = new bootstrap.Modal(document.getElementById('viewProfileModal'));
    viewProfileModal.show();
    
    // Afficher une notification
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