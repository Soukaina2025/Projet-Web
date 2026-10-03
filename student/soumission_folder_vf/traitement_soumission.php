<?php
session_start();

if (!isset($_SESSION['APOGEE'])) {
    header("Location: ../connexion_folder_vf/login_page.php");
    exit();
}

require_once '../connexion_folder_vf/db_config.php';

// Vérification des champs obligatoires
$required_fields = ['projectName', 'description', 'category', 'projectType', 'supervisor', 'semester'];foreach ($required_fields as $field) {
    if (empty($_POST[$field])) {
        $_SESSION['error'] = "Le champ " . ucfirst($field) . " est obligatoire.";
        header("Location: soumission.php");
        exit();
    }
}

// Vérification des fichiers obligatoires
$hasProjectFile = !empty($_FILES['projectFile']['name']) || (!empty($_POST['existing_projectFile']) && !isset($_POST['delete_projectFile']));
$hasProjectImage = !empty($_FILES['projectImage']['name']) || (!empty($_POST['existing_projectImage']) && !isset($_POST['delete_projectImage']));

if (!$hasProjectFile) {
    $_SESSION['error'] = "Le fichier principal (ZIP) est obligatoire.";
    header("Location: soumission.php");
    exit();
}

if (!$hasProjectImage) {
    $_SESSION['error'] = "L'image du projet est obligatoire.";
    header("Location: soumission.php");
    exit();
}

try {
    // Créer les dossiers pour les uploads s'ils n'existent pas
    $upload_dirs = [
        '../../uploads/projets',
        '../../uploads/images',
        '../page_chaque_projet_folder_vf'
    ];
    
    foreach ($upload_dirs as $dir) {
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
    }

    // Récupérer l'ID du projet s'il existe (pour modification)
    $id_project = $_POST['projectId'] ?? null;

    // Traitement du fichier principal (ZIP)
    $projectFileName = null;
    if (!empty($_FILES['projectFile']['name'])) {
        $projectFile = $_FILES['projectFile'];
        $projectFileName = uniqid() . '_' . basename($projectFile['name']);
        $projectFileTarget = "../../uploads/projets/" . $projectFileName;
        
        if (!move_uploaded_file($projectFile['tmp_name'], $projectFileTarget)) {
            throw new Exception("Erreur lors du téléchargement du fichier principal.");
        }
    } elseif (!empty($_POST['existing_projectFile']) && !isset($_POST['delete_projectFile'])) {
        $projectFileName = $_POST['existing_projectFile'];
    }

    
/*
    // Traitement des fichiers supplémentaires (max 3)
    $pdfFile = null;
    $pptFile = null;
    $codeFile = null;
    $fileCount = 0;

    // Gestion des fichiers existants
    if (!empty($_POST['existing_additionalFiles'])) {
        foreach ($_POST['existing_additionalFiles'] as $key => $existingFile) {
            if ($fileCount >= 3) break;
            
            $fileKey = $key + 1;
            if (!isset($_POST['delete_additionalFile_' . $fileKey])) {
                $fileExt = strtolower(pathinfo($existingFile, PATHINFO_EXTENSION));
                
                if (in_array($fileExt, ['pdf']) && $pdfFile === null) {
                    $pdfFile = $existingFile;
                    $fileCount++;
                } elseif (in_array($fileExt, ['ppt', 'pptx']) && $pptFile === null) {
                    $pptFile = $existingFile;
                    $fileCount++;
                } elseif (in_array($fileExt, ['py', 'java', 'cpp', 'c', 'js', 'html', 'css', 'php', 'txt']) && $codeFile === null) {
                    $codeFile = $existingFile;
                    $fileCount++;
                }
            }
        }
    }

    // Gestion des nouveaux fichiers
    if (!empty($_FILES['additionalFiles']['name'][0])) {
        foreach ($_FILES['additionalFiles']['tmp_name'] as $key => $tmp_name) {
            if ($fileCount >= 3) break;
            
            if ($_FILES['additionalFiles']['error'][$key] === UPLOAD_ERR_OK) {
                $file_name = $_FILES['additionalFiles']['name'][$key];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                
                if (in_array($file_ext, ['pdf']) && $pdfFile === null) {
                    $pdfFile = uniqid() . '_' . $file_name;
                    move_uploaded_file($tmp_name, "../uploads/projets/" . $pdfFile);
                    $fileCount++;
                } elseif (in_array($file_ext, ['ppt', 'pptx']) && $pptFile === null) {
                    $pptFile = uniqid() . '_' . $file_name;
                    move_uploaded_file($tmp_name, "../uploads/projets/" . $pptFile);
                    $fileCount++;
                } elseif (in_array($file_ext, ['py', 'java', 'cpp', 'c', 'js', 'html', 'css', 'php', 'txt']) && $codeFile === null) {
                    $codeFile = uniqid() . '_' . $file_name;
                    move_uploaded_file($tmp_name, "../uploads/projets/" . $codeFile);
                    $fileCount++;
                }
            }
        }
    }

*/

// Remplacer toute la partie de gestion des fichiers supplémentaires par :

// Tableau pour stocker les noms des fichiers supplémentaires
$additionalFiles = [];

// 1. Gestion des fichiers existants non supprimés
if (!empty($_POST['existing_additionalFiles'])) {
    foreach ($_POST['existing_additionalFiles'] as $existingFile) {
        if (count($additionalFiles) >= 3) break;
        $additionalFiles[] = $existingFile;
    }
}

// 2. Gestion des nouveaux fichiers uploadés
if (!empty($_FILES['additionalFiles']['name'][0])) {
    foreach ($_FILES['additionalFiles']['tmp_name'] as $key => $tmp_name) {
        if (count($additionalFiles) >= 3) break;
        
        if ($_FILES['additionalFiles']['error'][$key] === UPLOAD_ERR_OK) {
            $file_name = $_FILES['additionalFiles']['name'][$key];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            // Vérifier que c'est un type de fichier autorisé
            $allowed_extensions = ['pdf', 'ppt', 'pptx', 'doc', 'docx', 'txt', 'py', 'java', 'cpp', 'c', 'js', 'html', 'css', 'php'];
            if (in_array($file_ext, $allowed_extensions)) {
                $new_filename = uniqid() . '_' . $file_name;
                $target_path = "../../uploads/projets/" . $new_filename;
                
                if (move_uploaded_file($tmp_name, $target_path)) {
                    $additionalFiles[] = $new_filename;
                }
            }
        }
    }
}

// Maintenant on assigne les fichiers aux colonnes FILE1, FILE2, FILE3
$file1 = $additionalFiles[0] ?? null;
$file2 = $additionalFiles[1] ?? null;
$file3 = $additionalFiles[2] ?? null;

    // Traitement de l'image du projet
    $projectImageName = null;
    if (!empty($_FILES['projectImage']['name'])) {
        $projectImage = $_FILES['projectImage'];
        $projectImageName = uniqid() . '_' . basename($projectImage['name']);
        $projectImageTarget = "../../uploads/images/" . $projectImageName;
        
        if (!move_uploaded_file($projectImage['tmp_name'], $projectImageTarget)) {
            throw new Exception("Erreur lors du téléchargement de l'image du projet.");
        }
    } elseif (!empty($_POST['existing_projectImage']) && !isset($_POST['delete_projectImage'])) {
        $projectImageName = $_POST['existing_projectImage'];
    }

    if ($id_project) {
        // Mise à jour du projet existant
        /*
        $stmt = $conn->prepare("UPDATE project SET 
            NOM = ?, DESCRIPTION = ?, TYPE = ?, CATEGORIE = ?, 
            FICHIER = ?, FILE1 = ?, FILE2 = ?, FILE3 = ?, 
            IMG = ?, ID_PROF = ?, STATUT_STU = 'en_attente'
            WHERE ID_PROJECT = ?");
            */
        /*
        $stmt->execute([
            $_POST['projectName'],
            $_POST['description'],
            $_POST['projectType'],
            $_POST['category'],
            $projectFileName,
            $pdfFile,
            $pptFile,
            $codeFile,
            $projectImageName,
            $_POST['supervisor'],
            $id_project
        ]);*/
/*
        $stmt->execute([
    $_POST['projectName'],
    $_POST['description'],
    $_POST['projectType'],
    $_POST['category'],
    $projectFileName,
    $file1,  // Au lieu de $pdfFile
    $file2,  // Au lieu de $pptFile
    $file3,  // Au lieu de $codeFile
    $projectImageName,
    $_POST['supervisor'],
    $id_project
]);*/


$stmt = $conn->prepare("UPDATE project SET 
    NOM = ?, DESCRIPTION = ?, TYPE = ?, CATEGORIE = ?, 
    FICHIER = ?, FILE1 = ?, FILE2 = ?, FILE3 = ?, 
    IMG = ?, ID_PROF = ?, STATUT_STU = 'en_attente',
    SEMESTRE = ?, TECHNOLOGIE = ?
    WHERE ID_PROJECT = ?");

$stmt->execute([
    $_POST['projectName'],
    $_POST['description'],
    $_POST['projectType'],
    $_POST['category'],
    $projectFileName,
    $file1,
    $file2,
    $file3,
    $projectImageName,
    $_POST['supervisor'],
    $_POST['semester'],
    $_POST['technologies'], // Sauvegarde directe comme texte
    $id_project
]);
        
        // Supprimer les fichiers marqués pour suppression
        if (isset($_POST['delete_projectFile']) && !empty($_POST['existing_projectFile'])) {
            unlink("../../uploads/projets/" . $_POST['existing_projectFile']);
        }
        
        if (isset($_POST['delete_projectImage']) && !empty($_POST['existing_projectImage'])) {
            unlink("../../uploads/images/" . $_POST['existing_projectImage']);
        }
        
        for ($i = 1; $i <= 3; $i++) {
            if (isset($_POST['delete_additionalFile_' . $i]) && !empty($_POST['existing_additionalFiles'][$i-1])) {
                unlink("../../uploads/projets/" . $_POST['existing_additionalFiles'][$i-1]);
            }
        }
    } else {
        /*
        // Nouveau projet
        $stmt = $conn->prepare("INSERT INTO project 
            (NOM, DESCRIPTION, TYPE, CATEGORIE, FICHIER, FILE1, FILE2, FILE3, IMG, ID_PROF, APOGEE, STATUT_STU) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'en_attente')");
            */
        /*
        $stmt->execute([
            $_POST['projectName'],
            $_POST['description'],
            $_POST['projectType'],
            $_POST['category'],
            $projectFileName,
            $pdfFile,
            $pptFile,
            $codeFile,
            $projectImageName,
            $_POST['supervisor'],
            $_SESSION['APOGEE']
        ]);*/
/*
        $stmt->execute([
    $_POST['projectName'],
    $_POST['description'],
    $_POST['projectType'],
    $_POST['category'],
    $projectFileName,
    $file1,  // Au lieu de $pdfFile
    $file2,  // Au lieu de $pptFile
    $file3,  // Au lieu de $codeFile
    $projectImageName,
    $_POST['supervisor'],
    $id_project
]);*/



$stmt = $conn->prepare("INSERT INTO project 
    (NOM, DESCRIPTION, TYPE, CATEGORIE, FICHIER, FILE1, FILE2, FILE3, IMG, ID_PROF, APOGEE, STATUT_STU, SEMESTRE, TECHNOLOGIE) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'en_attente', ?, ?)");

$stmt->execute([
    $_POST['projectName'],
    $_POST['description'],
    $_POST['projectType'],
    $_POST['category'],
    $projectFileName,
    $file1,
    $file2,
    $file3,
    $projectImageName,
    $_POST['supervisor'],
    $_SESSION['APOGEE'],
    $_POST['semester'],
    $_POST['technologies'] // Sauvegarde directe comme texte
]);
        
        $id_project = $conn->lastInsertId();
    }
    
    // Redirection vers la page de modification
    header("Location: ../modification_folder_vf/modification.php?id=" . $id_project);
    exit();

} catch (Exception $e) {
    $_SESSION['error'] = "Erreur lors de la soumission: " . $e->getMessage();
    header("Location: soumission.php");
    exit();
}
?>