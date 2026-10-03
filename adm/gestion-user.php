<?php
require_once('../base_donnees/pdo.php');
session_start();

// Activer l'affichage des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Initialisation
$errors = [];

// Traitement du formulaire d'ajout
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submitUser'])) {
    // Récupération des données
    $prenom = trim($_POST['firstname'] ?? '');
    $nom = trim($_POST['lastname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $cpass = $_POST['Cpassword'] ?? '';
    $role = $_POST['role'] ?? '';

    // Validation de base
    if (empty($prenom)) $errors[] = "Le prénom est requis";
    if (empty($nom)) $errors[] = "Le nom est requis";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email invalide";
    if (empty($password) || strlen($password) < 8) $errors[] = "Mot de passe doit contenir au moins 8 caractères";
    if ($password !== $cpass) $errors[] = "Les mots de passe ne correspondent pas";
    if (empty($role)) $errors[] = "Le rôle est requis";

    // Validation spécifique au rôle
    if ($role === 'prof') {
        $id_prof = trim($_POST['ID_PROF'] ?? '');
        $departement = trim($_POST['DEPARTEMENT'] ?? '');
        $poste = trim($_POST['poste_prof'] ?? '');
        
        if (empty($id_prof)) $errors[] = "ID Professeur requis";
        if (empty($departement)) $errors[] = "Département requis";
        if (empty($poste)) $errors[] = "Poste requis";
    } 
    elseif ($role === 'student') {
        $apogee = trim($_POST['apogee'] ?? '');
        $filiere = trim($_POST['filiere'] ?? '');
        $niveau = trim($_POST['niveau'] ?? '');
        
        if (empty($apogee)) $errors[] = "Numéro Apogée requis";
        if (empty($filiere)) $errors[] = "Filière requise";
        if (empty($niveau)) $errors[] = "Niveau requis";
    }

    // Si pas d'erreurs, procéder à l'insertion
    if (empty($errors)) {
        $avatar_char = strtoupper(substr($prenom, 0, 1));
        $pass_hashed = password_hash($password, PASSWORD_DEFAULT);
        
        try {
            if ($role === 'prof') {
                // Vérifier si le prof existe déjà
                $stmt = $db->prepare("SELECT ID_PROF FROM prof WHERE ID_PROF = ? OR EMAIL = ?");
                $stmt->execute([$id_prof, $email]);
                if ($stmt->fetch()) {
                    $errors[] = "Un professeur avec cet ID ou email existe déjà";
                } else {
                    $sql = "INSERT INTO prof (ID_PROF, NOM, PRENOM, EMAIL, PASSWRD, DEPARTEMENT, POSTE, AVATAR) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $db->prepare($sql);
                    $stmt->execute([$id_prof, $nom, $prenom, $email, $pass_hashed, $departement, $poste, $avatar_char]);
                    
                    $_SESSION['success'] = "Professeur ajouté avec succès";
                }
            } 
            elseif ($role === 'student') {
                // Vérifier si l'étudiant existe déjà
                $stmt = $db->prepare("SELECT APOGEE FROM student WHERE APOGEE = ? OR EMAIL_INST = ?");
                $stmt->execute([$apogee, $email]);
                if ($stmt->fetch()) {
                    $errors[] = "Un étudiant avec cet Apogée ou email existe déjà";
                } else {
                    $sql = "INSERT INTO student (APOGEE, NOM, PRENOM, EMAIL_INST, PASSWRD, FILIERE, NIV, AVATAR) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $db->prepare($sql);
                    $stmt->execute([$apogee, $nom, $prenom, $email, $pass_hashed, $filiere, $niveau, $avatar_char]);
                    
                    $_SESSION['success'] = "Étudiant ajouté avec succès";
                }
            }
            
            // Redirection si succès
            if (empty($errors)) {
                header("Location: gestion-user.php");
                exit();
            }
        } 
        catch (PDOException $e) {
            $errors[] = "Erreur base de données: " . $e->getMessage();
            error_log("Erreur DB: " . $e->getMessage());
        }
    }
    
    // Stocker les erreurs dans la session si nécessaire
    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        $_SESSION['form_data'] = $_POST;
    }
}

// Traitement de la modification
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['updateUser'])) {
    $id = $_POST['id'] ?? '';
    $role = $_POST['role'] ?? '';
    $errors = [];

    if (empty($id) || empty($role)) {
        $errors[] = "Données manquantes pour la modification";
    }

    if ($role === 'prof') {
        $departement = trim($_POST['departement'] ?? '');
        $poste = trim($_POST['poste'] ?? '');
        
        if (empty($departement)) $errors[] = "Département requis";
        if (empty($poste)) $errors[] = "Poste requis";
        
        if (empty($errors)) {
            try {
                $sql = "UPDATE prof SET DEPARTEMENT = ?, POSTE = ? WHERE ID_PROF = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([$departement, $poste, $id]);
                $_SESSION['success'] = "Professeur modifié avec succès";
            } catch (PDOException $e) {
                $errors[] = "Erreur lors de la modification: " . $e->getMessage();
            }
        }
    } 
    elseif ($role === 'student') {
        $filiere = trim($_POST['filiere'] ?? '');
        $niveau = trim($_POST['niveau'] ?? '');
        
        if (empty($filiere)) $errors[] = "Filière requise";
        if (empty($niveau)) $errors[] = "Niveau requis";
        
        if (empty($errors)) {
            try {
                $sql = "UPDATE student SET FILIERE = ?, NIV = ? WHERE APOGEE = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([$filiere, $niveau, $id]);
                $_SESSION['success'] = "Étudiant modifié avec succès";
            } catch (PDOException $e) {
                $errors[] = "Erreur lors de la modification: " . $e->getMessage();
            }
        }
    }

    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
    }
    
    header("Location: gestion-user.php");
    exit();
}

// Récupération des paramètres de filtre
$roleFilter = $_GET['role'] ?? '';
$filiereFilter = $_GET['filiere'] ?? '';

// Configuration de la pagination
$records_per_page = 5;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $records_per_page;

$sql = "
    SELECT APOGEE, NOM, PRENOM, 'Étudiant' AS ROLE, FILIERE AS FIL_DEP, EMAIL_INST AS EMAIL, AVATAR 
    FROM student
    WHERE (:role = '' OR :role = 'student')
    AND (:filiere = '' OR FILIERE LIKE CONCAT('%', :filiere, '%'))
    
    UNION ALL
    
    SELECT ID_PROF AS APOGEE, NOM, PRENOM, 'Professeur' AS ROLE, DEPARTEMENT AS FIL_DEP, EMAIL, AVATAR 
    FROM prof
    WHERE (:role = '' OR :role = 'prof')
    AND (:filiere = '' OR DEPARTEMENT LIKE CONCAT('%', :filiere, '%'))
    
    ORDER BY NOM, PRENOM
    LIMIT :offset, :limit
";

$stmt = $db->prepare($sql);
$stmt->bindValue(':role', $roleFilter, PDO::PARAM_STR);
$stmt->bindValue(':filiere', $filiereFilter, PDO::PARAM_STR);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':limit', $records_per_page, PDO::PARAM_INT);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$count_sql = "
    SELECT 
        (SELECT COUNT(*) FROM student 
         WHERE (:role = '' OR :role = 'student')
         AND (:filiere = '' OR FILIERE LIKE CONCAT('%', :filiere, '%'))) +
        (SELECT COUNT(*) FROM prof 
         WHERE (:role = '' OR :role = 'prof')
         AND (:filiere = '' OR DEPARTEMENT LIKE CONCAT('%', :filiere, '%'))) AS total
";

$count_stmt = $db->prepare($count_sql);
$count_stmt->bindValue(':role', $roleFilter, PDO::PARAM_STR);
$count_stmt->bindValue(':filiere', $filiereFilter, PDO::PARAM_STR);
$count_stmt->execute();
$total_users = $count_stmt->fetchColumn();
$total_pages = ceil($total_users / $records_per_page);

// Récupération des informations de l'utilisateur connecté
try {
    $query = "SELECT IMG, PRENOM, AVATAR FROM adm WHERE ID_ADM = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$_SESSION['ID_ADM']]);
    $admin = $stmt->fetch();
    
    $profile_img = $admin['IMG'] ?? null;
    $avatar_char = $admin['AVATAR'] ?? substr($admin['PRENOM'], 0, 1);
    $avatar_color = '#3f51b5'; // Couleur fixe pour les admins
    
    $_SESSION['PROFILE_IMG'] = $profile_img;
    $_SESSION['AVATAR_CHAR'] = $avatar_char;
} catch (PDOException $e) {
    $profile_img = $_SESSION['PROFILE_IMG'] ?? null;
    $avatar_char = $_SESSION['AVATAR_CHAR'] ?? '?';
    $avatar_color = $_SESSION['AVATAR_COLOR'] ?? '#3f51b5';
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ENSA Projects - Gestion Utilisateurs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* [Conserver tout le CSS existant] */
    </style>
        <style>
        :root {
            --ensa-primary: #2C3E50;
            --ensa-secondary: #E74C3C;
            --ensa-light: #ECF0F1;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        
        .sidebar {
            background-color: var(--ensa-primary);
            color: white;
            height: 100vh;
            position: fixed;
            width: 250px;
            padding: 20px;
            transition: all 0.3s;
        }
        
        .main-content {
            margin-left: 110px;
            padding: 20px;
            transition: all 0.3s;
        }
        
        .card {
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
            border: none;
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            font-weight: 600;
            padding: 15px 20px;
        }
        
        .nav-link {
            color: rgba(255,255,255,0.8);
            border-radius: 5px;
            margin-bottom: 5px;
        }
        
        .nav-link:hover, .nav-link.active {
            color: white;
            background-color: rgba(255,255,255,0.1);
        }
        
        .btn-ensa {
            background-color: var(--ensa-secondary);
            color: white;
            border: none;
        }
        
        .btn-ensa:hover {
            background-color: #c0392b;
            color: white;
        }
        
        .table {
            --bs-table-striped-bg: rgba(0,0,0,0.01);
            --bs-table-hover-bg: rgba(0,0,0,0.03);
        }
        
        .table th {
            background-color: #f8f9fa;
            font-weight: 500;
            color: #495057;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            border-bottom-width: 1px;
        }
        
        .table td {
            vertical-align: middle;
            padding: 12px 16px;
            border-top: 1px solid #f1f1f1;
        }
        
        .role-badge {
            font-size: 0.7rem;
            padding: 0.3em 0.6em;
            font-weight: 400;
            background-color: #f1f1f1;
            color: #555;
        }
        
        .badge-admin {
            background-color: #f8f0ff;
            color: #8a2be2;
        }
        
        .badge-prof {
            background-color: #fff4e6;
            color: #ff8c00;
        }
        
        .badge-student {
            background-color: #e6f7ff;
            color: #1890ff;
        }
        
        .filter-section {
            background-color: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .pagination .page-link {
            color: var(--ensa-primary);
            border: none;
            margin: 0 3px;
            border-radius: 4px;
        }
        
        .pagination .page-item.active .page-link {
            background-color: var(--ensa-primary);
            border-color: var(--ensa-primary);
        }
        
        .action-btn {
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            border: 1px solid #e0e0e0;
            background: white;
            color: #555;
            transition: all 0.2s;
        }
        
        .action-btn:hover {
            background: #f5f5f5;
            color: #333;
        }
        
        .highlight {
            background-color: #fff3cd;
            color: #000;
            padding: 0 2px;
            border-radius: 3px;
        }
        
        .position-relative {
            position: relative;
        }
        
        .position-absolute {
            position: absolute;
        }
        
        .end-0 {
            right: 0;
        }
        
        .top-50 {
            top: 50%;
        }
        
        .translate-middle-y {
            transform: translateY(-50%);
        }
        
        .me-2 {
            margin-right: 0.5rem;
        }
        
        .avatar-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 18px;
            color: white;
        }
        
        .dropdown-toggle {
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .dropdown-toggle:hover {
            opacity: 0.8;
        }
        
        .dropdown-menu {
            min-width: 200px;
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .dropdown-item {
            padding: 8px 15px;
            transition: all 0.2s;
        }
        
        .dropdown-item:hover {
            background-color: #f8f9fa;
            color: #3f51b5;
        }
        
        .page-title {
            margin-left: 0;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        /* Styles pour les modales */
        .modal-lg-custom {
            max-width: 800px;
        }
        
        .user-details-table th {
            width: 30%;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                display: none;
            }
            
            .sidebar .nav-link span {
                display: none;
            }
            
            .sidebar .nav-link i {
                margin-right: 0;
                font-size: 1.2rem;
            }
                .main-content {
        margin-left: 0;
    }
    /*
            .main-content {
                margin-left: 80px;
            }*/
            
            .page-title {
                margin-left: 0;
            }
        }


        
@media (max-width: 1205px) {
    .sidebar {
        position: fixed;
        left: -250px;
        top: 0;
        bottom: 0;
        z-index: 1000;
        transition: left 0.3s ease;
        display: block !important; /* S'assurer qu'il est toujours en DOM */
    }
    .sidebar.show {
        left: 0;
    }
    .main-content {
        margin-left: 0;
    }
    .menu_button{
        display:block;
    }
    /*
    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }*/
    .table th, .table td {
        white-space: nowrap;
    }
}



        .fa-chevron-down:before {
    


    content: none;
    
}


@media (min-width: 1200px) {
    .menu_button {
        display: none !important; /* ou display: initial; selon le besoin */
    }
}

.bg_proj{
    background-color: #bfdbfe;
            color: #1e3a8a;
}




/* Style pour la carte de détails */
.user-details-card {
    border-radius: 10px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.user-details-table th {
    background-color: #f8f9fa;
    width: 30%;
}

.user-details-table td {
    vertical-align: middle;
}

.avatar-circle-lg {
    width: 100px;
    height: 100px;
    font-size: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 15px;
    color: white;
    font-weight: bold;
}

/* Style pour les champs non modifiables */
.form-control[readonly] {
    background-color: #f8f9fa;
    border: 1px solid #e9ecef;
    cursor: not-allowed;
}

/* Style pour les cards dans la modal */
.modal .card {
    margin-bottom: 1.5rem;
    border: 1px solid rgba(0,0,0,.125);
}

.modal .card-header {
    background-color: #f8f9fa;
    font-weight: 500;
    border-bottom: 1px solid rgba(0,0,0,.125);
}
    </style>






    <style>
        :root {
            --ensa-primary: #0b2341;
            --ensa-secondary: #E74C3C;
            --ensa-light: #ECF0F1;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
        }
        .sidebar {
            background-color: var(--ensa-primary);
            color: white;
            height: 100vh;
            position: fixed;
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
            margin-right: 5px;
            cursor: pointer;
            border: 2px solid #002c84;
        }

        .avatar-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 18px;
}

/* Style pour le dropdown */
.dropdown-toggle {
    cursor: pointer;
    transition: all 0.3s ease;
}

.dropdown-toggle:hover {
    opacity: 0.8;
}

.dropdown-menu {
    min-width: 200px;
    border: none;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.dropdown-item {
    padding: 8px 15px;
    transition: all 0.2s;
}

.dropdown-item:hover {
    background-color: #f8f9fa;
    color: #3f51b5;
}


.sidebar{
    padding-right: 2rem !important;
    height: 100%;
    background-color: #2C3E50;
}

        .sidebar {
            color: white;
            height: 100vh;
            position: fixed;
            width: 250px;
            padding: 20px;
            transition: all 0.3s;
        }
/*
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            border: none;
        }*/
        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0,0,0,0.1);
            font-weight: 600;
        }
        .stat-card {
            border-left: 4px solid rgb(248, 175, 108);
        }
        .nav-link {
            color: rgba(255,255,255,0.8);
            border-radius: 5px;
            margin-bottom: 5px;        }
        .nav-link:hover, .nav-link.active {
            color: white;
            background-color: rgba(255,255,255,0.1);
        }
        .btn-ensa {
            background-color: var(--ensa-secondary);
            color: white;
        }
        .btn-ensa:hover {
            background-color: #c0392b;
            color: white;
        }
        .table th {
            background-color: var(--ensa-light);
        }


        .bg_attente{
            background-color: #fff3cd;
            color: #856404;

        }

        .bg_valide{
            background-color: #d4edda;
            color: #155724;

        }


        .bg_refuse{
            background-color: #f8d7da;
            color: #721c24;

        }


            .bg_proj{
            background-color: #bfdbfe;
            color: #1e3a8a;
        }
/*
        .bg_proj{
            background-color: #e0f2fe;
            color: #0284c7;
        }*/

.fa-chevron-down:before {
    


    content: none;
    
}























/* Styles pour les cartes avec animation au hover */
.stat-card {
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1);
}


/* Styles responsives pour toute la page */
@media (max-width: 1199.98px) {
    /*
    .sidebar {
        width: 220px;
    }*/
    
    .main-content {
        margin-left: 220px;
    }
}

@media (max-width: 991.98px) {
    /*
    .sidebar {
        width: 70px;
        overflow: hidden;
        padding-right: 0.5rem !important;
    }
    .sidebar h4, .sidebar hr, .sidebar .nav-link span {
        display: none;
    }
    .sidebar .nav-link {
        text-align: center;
        padding: 0.75rem 0.25rem;
    }
    .sidebar .nav-link i {
        margin-right: 0;
        font-size: 1.25rem;
    }*/

        .sidebar .nav-link span {
        display: none;
    }
    .main-content {
        margin-left: 70px;
    }
    .avatar-circle {
        width: 30px;
        height: 30px;
        font-size: 14px;
    }
}

@media (max-width: 767.98px) {
    .sidebar {
        display: none;
    }
    .main-content {
        margin-left: 0;
    }
    .row {
        flex-direction: column;
    }
    .col-md-8, .col-md-4 {
        width: 100%;
    }
    .card {
        margin-bottom: 15px;
    }
    .stat-card .card-body h3 {
        font-size: 1.5rem;
    }
    .table-responsive {
        overflow-x: auto;
    }
    table {
        font-size: 0.9rem;
    }
    .dropdown-menu {
        position: absolute !important;
    }
}

@media (max-width: 575.98px) {
    /*
    .sidebar {
        display: none;
    }*/
    /*
    .main-content {
        margin-left: 0;
        padding: 15px;
    }*/
    .d-flex.justify-content-between.align-items-center.mb-4 {
        flex-direction: column;
        align-items: flex-start;
    }
    .d-flex.justify-content-between.align-items-center.mb-4 > div {
        margin-top: 10px;
    }
    .stat-card .card-body {
        padding: 1rem;
    }
    .stat-card .card-body h6 {
        font-size: 0.9rem;
    }
    .stat-card .card-body h3 {
        font-size: 1.3rem;
    }
    .card-header {
        font-size: 1rem;
    }
    .dropdown-toggle > div:last-child {
        display: none;
    }
}



@media (max-width: 1200px) {
    .first_container {
        display: block !important; /* ou display: initial; selon le besoin */
    }
}


@media (min-width: 1200px) {
    .menu_button {
        display: none !important; /* ou display: initial; selon le besoin */
    }
}

@media (max-width: 363px) {
    .btn-ensa,.btn-group {
        font-size: 13px;
    }
}








/* Améliorations globales pour les cartes */

.card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    /*border: none;*/
    border-radius: 10px;
    overflow: hidden;
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1);
}

.card-header {
    background-color: white;
    border-bottom: 1px solid rgba(0,0,0,0.1);
    font-weight: 600;
    padding: 1rem 1.25rem;
}

.card-body {
    padding: 1.25rem;
}*/

/* Amélioration de la lisibilité des tableaux */
.table th {
    white-space: nowrap;
    background-color: #f8f9fa;
}

.table td {
    vertical-align: middle;
}

/* Style pour les badges */
.badge {
    font-weight: 500;
    padding: 0.35em 0.65em;
    font-size: 0.85em;
}



@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Amélioration du formulaire de filtre */
.form-select {
    border-radius: 20px;
    border: 1px solid #dee2e6;
    padding: 0.375rem 1.75rem 0.375rem 0.75rem;
    cursor: pointer;
}

/* Style pour le bouton d'export */
.btn-success {

    transition: all 0.3s ease;
}

.btn-success:hover {
    background-color: #218838;
    border-color: #1e7e34;
    transform: translateY(-2px);
}

















        .sidebar {
            color: white;
            height: 100vh;
            position: fixed;
        }
        .main-content {
        margin-left: 110px;
            padding: 20px;
        }
        /*
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            border: none;
        }*/
        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0,0,0,0.1);
            font-weight: 600;
        }
        .nav-link {
            color: rgba(255,255,255,0.8);
        }
        .nav-link:hover, .nav-link.active {
            color: white;
            background-color: rgba(255,255,255,0.1);
        }
        .btn-ensa {
            background-color: var(--ensa-secondary);
            color: white;
        }
        .btn-ensa:hover {
            background-color: #c0392b;
            color: white;
        }
        .table th {
            background-color: var(--ensa-light);
        }
        .project-type-badge {
            font-size: 0.75rem;
            padding: 0.35em 0.65em;
        }
        .filter-section {
            background-color: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .status-badge {
            font-size: 0.75rem;
            padding: 0.5em 0.75em;
            border-radius: 12px;
        }
        .progress-thin {
            height: 6px;
        }
        #exportExcel:hover, #exportPdf:hover {
            transform: translateY(-2px);
            transition: transform 0.2s ease;
        }
        .file-download-btn {
            display: inline-block;
            margin: 5px;
            padding: 8px 15px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            color: #495057;
            text-decoration: none;
            transition: all 0.2s;
        }
        .file-download-btn:hover {
            background-color: #e9ecef;
            color: #212529;
        }
        .file-download-btn i {
            margin-right: 5px;
        }

        .pagination .page-item.active .page-link {
            background-color: #002c84;
            border-color: #002c84;
            color: white;
        }
        .pagination .page-link {
            color: #002c84;
        }
        .pagination .page-link:hover {
            color: #002c84;
            background-color: #e9ecef;
        }






        /* Style pour l'avatar circulaire */
.avatar-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 18px;
}

/* Style pour le dropdown */
.dropdown-toggle {
    cursor: pointer;
    transition: all 0.3s ease;
}

.dropdown-toggle:hover {
    opacity: 0.8;
}

.dropdown-menu {
    min-width: 200px;
    border: none;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.dropdown-item {
    padding: 8px 15px;
    transition: all 0.2s;
}

.dropdown-item:hover {
    background-color: #f8f9fa;
    color: #3f51b5;
}


.sidebar{
    padding-right: 2rem !important;
    height: 100%;
}







/* Responsive Design */
@media (max-width: 1200px) {
    /*.sidebar {
        width: 220px;
    }*/
    /*
    .main-content {
        margin-left: 220px;
    }*/
}

@media (max-width: 992px) {
    /*
    .sidebar {
        /*width: 80px;*/
        /*overflow: hidden;*/
        /*padding-right: 0.5rem !important;
    }
    .sidebar .nav-link {
        /*padding: 0.5rem;
        text-align: center;
    }*/
    .sidebar .nav-link span {
        display: none;
    }
    /*
    .sidebar .nav-link i {
        /*margin-right: 0;
        font-size: 1.2rem;
    }*/
    .sidebar h4 {
        font-size: 1rem;
        text-align: center;
    }
    /*
    .main-content {
        margin-left: 80px;
    }*/
    .filter-section .row {
        flex-direction: column;
    }
    .filter-section .col-md-3 {
        width: 100%;
        margin-bottom: 10px;
    }
}

@media (max-width: 768px) {
    .sidebar {
        display: none;
    }

    .stat-card {
    height: 170px;}
    /*
    .main-content {
        margin-left: 0;
    }*/
    /*
    .table-responsive {
        overflow-x: auto;
    }*/
    .table th, .table td {
        white-space: nowrap;
    }
    .card-header {
        flex-direction: column;
        align-items: flex-start;
    }
    .card-header form {
        width: 100%;
        margin-top: 10px;
    }
    .d-flex.justify-content-between.align-items-center.mb-4 {
        flex-direction: column;
        align-items: flex-start;
    }
    .d-flex.justify-content-between.align-items-center.mb-4 > div {
        margin-top: 10px;
    }
}

@media (max-width: 576px) {
    .pagination {
        flex-wrap: wrap;
    }
    .page-item {
        margin-bottom: 5px;
    }
    .modal-dialog {
        margin: 0.5rem;
    }
    .avatar-circle {
        width: 30px;
        height: 30px;
        font-size: 14px;
    }
    .dropdown-menu {
        min-width: 180px;
    }
}



.table-responsive {
    overflow-x: auto;

  scrollbar-width: auto; /* Firefox */
}

.table-responsive::-webkit-scrollbar {
  display: block;  /* Chrome, Safari */
  height: 8px;
}

.table-responsive::-webkit-scrollbar-thumb {
  background-color: #888;
  border-radius: 4px;
}

.table-responsive::-webkit-scrollbar-track {
  background-color: #f1f1f1;
}





@media (max-width: 1205px) {
    .sidebar {
        position: fixed;
        left: -250px;
        top: 0;
        bottom: 0;
        z-index: 1000;
        transition: left 0.3s ease;
        display: block !important; /* S'assurer qu'il est toujours en DOM */
    }
    .sidebar.show {
        left: 0;
    }
    .main-content {
        margin-left: 0;
    }
    .menu_button{
        display:block;
    }
    /*
    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }*/
    .table th, .table td {
        white-space: nowrap;
    }
}


@media (max-width: 1200px) {
    .first_container {
        display: block !important; /* ou display: initial; selon le besoin */
    }
}


@media (min-width: 1200px) {
    .menu_button {
        display: none !important; /* ou display: initial; selon le besoin */
    }
}

@media (max-width: 363px) {
    .btn-ensa,.btn-group {
        font-size: 13px;
    }
}






    </style>
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <div class="sidebar w-250 px-3 py-4">
            <h4 class="text-center mb-4 " style="display: flex; flex-direction:column; align-items:center;">
                <img src="../images/LOGO-ENSA.png" alt="ENSA LOGO" width="140px" style="margin-bottom:19px; ">
                 ENSA Projects
            </h4>
            <hr>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link " href="adm.php">
                        <i class="bi bi-speedometer2 me-2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="gestion-user.php">
                        <i class="bi bi-people me-2"></i> Gestion Utilisateurs
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="proj-adm.php">
                        <i class="bi bi-folder me-2"></i> Tous les Projets
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="statis.php">
                        <i class="bi bi-bar-chart me-2"></i> Statistiques
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="parametre.php">
                        <i class="bi bi-gear me-2"></i> Paramètres
                    </a>
                </li>
            </ul>
                <div class="position-absolute bottom-0 mb-4 ">
    <div class="dropdown">
        <div class="d-flex align-items-center dropdown-toggle" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
            <div class="avatar-circle bg-primary text-white me-2" style="background-color: #3f51b5;">
                <?php 
                $initial = strtoupper(substr($_SESSION['PRENOM'], 0, 1));
                echo $initial;
                ?>
            </div>
            <div>
                <div class="fw-bold"><?php echo $_SESSION['NOM'] . ' ' . $_SESSION['PRENOM']; ?></div>
                <small>Administrateur</small>
            </div>
            <i class="fas fa-chevron-down ms-2"></i>
        </div>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton">
            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Déconnexion</a></li>
        </ul>
            </div>
        </div>
        </div>


            <div class="main-content flex-grow-1">
            <button class="btn btn-ensa  mb-3 menu_button" id="sidebarToggle">
        <i class="bi bi-list"></i> Menu
    </button>
        <!-- Main Content -->
        <div class="main-content flex-grow-1">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="mb-0">Gestion des Utilisateurs</h4>
                <div>
                    <button class="btn btn-ensa me-2" data-bs-toggle="modal" data-bs-target="#addUserModal" style=" background-color: #bfdbfe;
            color: #1e3a8a;">
                        <i class="bi bi-plus-circle me-1"></i> Ajouter Utilisateur
                    </button>
                    <button class="btn btn-outline-secondary" id="exportExcelBtn">
                        <i class="bi bi-download me-1"></i> Export Excel
                    </button>
                </div>
            </div>

            <?php if (!empty($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= $_SESSION['success'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (!empty($_SESSION['errors'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0">
                        <?php foreach ($_SESSION['errors'] as $error): ?>
                            <li><?= $error ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['errors']); ?>
            <?php endif; ?>

            <!-- Filter Section -->
        <div class="filter-section mb-4">
    <form method="get" action="">
        <div class="row g-3">
            <div class="col-md-3">
                <label for="roleFilter" class="form-label">Rôle</label>
                <select id="roleFilter" name="role" class="form-select">
                    <option value="">Tous les rôles</option>
                    <option value="prof" <?= $roleFilter === 'prof' ? 'selected' : '' ?>>Professeur</option>
                    <option value="student" <?= $roleFilter === 'student' ? 'selected' : '' ?>>Étudiant</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="filiereFilter" class="form-label" id="filiereLabel">
                    <?= $roleFilter === 'prof' ? 'Département' : 'Filière' ?>
                </label>
                <select id="filiereFilter" name="filiere" class="form-select">
                    <option value="">Tous</option>
                    <?php if ($roleFilter === 'prof'): ?>
                        <!-- Options pour les départements (professeurs) -->
                        <option value="Département d'informatique, de logistique et de mathématiques" <?= $filiereFilter === "Département d'informatique, de logistique et de mathématiques" ? 'selected' : '' ?>>
                            Dépt. Informatique & Logistique
                        </option>
                        <option value="Département de génie électrique, des réseaux et des systèmes des télécommunications" <?= $filiereFilter === "Département de génie électrique, des réseaux et des systèmes des télécommunications" ? 'selected' : '' ?>>
                            Dépt. Génie Électrique
                        </option>
                    <?php else: ?>
                        <!-- Options pour les filières (étudiants) -->
                        <option value="Génie Informatique" <?= $filiereFilter === 'Génie Informatique' ? 'selected' : '' ?>>Génie Informatique</option>
                        <option value="Génie Industriel" <?= $filiereFilter === 'Génie Industriel' ? 'selected' : '' ?>>Génie Industriel</option>
                        <option value="Génie BIEE" <?= $filiereFilter === 'Génie BIEE' ? 'selected' : '' ?>>Génie BIEE</option>
                        <option value="Génie Mécatronique" <?= $filiereFilter === 'Génie Mécatronique' ? 'selected' : '' ?>>Génie Mécatronique</option>
                        <option value="Génie Electrique" <?= $filiereFilter === 'Génie Electrique' ? 'selected' : '' ?>>Génie Electrique</option>
                        <option value="Génie RST" <?= $filiereFilter === 'Génie RST' ? 'selected' : '' ?>>Génie Réseaux</option>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end ">
                <button type="submit" class="btn w-100" style="background-color: #bfdbfe;
            color: #1e3a8a;">
                    <i class="bi bi-funnel me-1 "></i> Filtrer
                </button>
            </div>
        </div>
    </form>
</div>

            <!-- Users Table -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Liste des Utilisateurs</span>
                    <div class="input-group w-auto">
                        <input type="text" id="globalSearch" class="form-control form-control-sm" 
                               placeholder="Rechercher...">
                        <button class="btn btn-outline-secondary btn-sm" type="button">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="usersTable">
                            <thead>
    <tr>
        <th style="width: 10%;">ID</th>
        <th style="width: 20%;">Utilisateur</th>
        <th style="width: 12%;">Rôle</th>
        <th style="width: 15%;">Filière/Département</th>
        <th style="width: 20%;">Email</th>
        <th style="width: 13%;">Actions</th>
    </tr>
</thead>
<tbody>
    <?php foreach ($users as $user): ?>
        <tr>
            <td>
                <?= htmlspecialchars($user['APOGEE']) ?>
            </td>
            <td>
                <div class="d-flex align-items-center">
                    <div>
                        <div class="fw-bold">
                            <?= htmlspecialchars($user['NOM']) . ' ' . htmlspecialchars($user['PRENOM']) ?>
                        </div>
                    </div>
                </div>
            </td>
            <td>
                <span class="badge role-badge <?= $user['ROLE'] === 'Étudiant' ? 'badge-student' : ($user['ROLE'] === 'Professeur' ? 'badge-prof' : 'badge-admin') ?>">
                    <?= $user['ROLE'] ?>
                </span>
            </td>
            <td><?= htmlspecialchars($user['FIL_DEP']) ?></td>
            <td><?= htmlspecialchars($user['EMAIL']) ?></td>
            <td>
                <button class="action-btn me-1 view-btn" 
                    title="Voir détails" 
                    data-id="<?= htmlspecialchars($user['APOGEE']) ?>" 
                    data-role="<?= strtolower(str_replace('Étudiant', 'student', str_replace('Professeur', 'prof', htmlspecialchars($user['ROLE'])))) ?>">
                    <i class="bi bi-eye"></i>
                </button>
                
                
                
                <button class="action-btn text-danger delete-btn" 
                    title="Supprimer" 
                    data-id="<?= htmlspecialchars($user['APOGEE']) ?>" 
                    data-role="<?= strtolower(str_replace('Étudiant', 'student', str_replace('Professeur', 'prof', htmlspecialchars($user['ROLE'])))) ?>">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>
    <?php endforeach; ?>
</tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="p-3 border-top">
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center mb-0">
                                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?page=<?= $page - 1 ?>&role=<?= $roleFilter ?>&filiere=<?= $filiereFilter ?>">
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </li>
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?>&role=<?= $roleFilter ?>&filiere=<?= $filiereFilter ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?page=<?= $page + 1 ?>&role=<?= $roleFilter ?>&filiere=<?= $filiereFilter ?>">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addUserModalLabel">Ajouter un Nouvel Utilisateur</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="" id="addUserForm">
                    <div class="modal-body">
                        <div id="errorContainer"></div>
                        <input type="hidden" name="submitUser" value="1">

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="firstName" class="form-label">Prénom *</label>
                                <input type="text" class="form-control" id="firstName" name="firstname" required
                                       value="<?= isset($_SESSION['form_data']['firstname']) ? htmlspecialchars($_SESSION['form_data']['firstname']) : '' ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="lastName" class="form-label">Nom *</label>
                                <input type="text" class="form-control" id="lastName" name="lastname" required
                                       value="<?= isset($_SESSION['form_data']['lastname']) ? htmlspecialchars($_SESSION['form_data']['lastname']) : '' ?>">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="email" name="email" required
                                       value="<?= isset($_SESSION['form_data']['email']) ? htmlspecialchars($_SESSION['form_data']['email']) : '' ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="role" class="form-label">Rôle *</label>
                                <select class="form-select" id="role" name="role" required>
                                    <option value="" selected>Sélectionner un rôle</option>
                                    <option value="prof" <?= isset($_SESSION['form_data']['role']) && $_SESSION['form_data']['role'] === 'prof' ? 'selected' : '' ?>>Professeur</option>
                                    <option value="student" <?= isset($_SESSION['form_data']['role']) && $_SESSION['form_data']['role'] === 'student' ? 'selected' : '' ?>>Étudiant</option>
                                </select>
                            </div>
                        </div>

                        <!-- Student Fields -->
                        <div class="row mb-3 d-none" id="studentFields">
                            <div class="col-md-6">
                                <label for="apogee" class="form-label">Numéro Apogée *</label>
                                <input type="text" class="form-control" id="apogee" name="apogee"
                                       value="<?= isset($_SESSION['form_data']['apogee']) ? htmlspecialchars($_SESSION['form_data']['apogee']) : '' ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="filiere" class="form-label">Filière *</label>
                                <select class="form-select" id="filiere" name="filiere">
                                    <option value="">Sélectionner une filière</option>
                                    <option value="Génie Informatique" <?= isset($_SESSION['form_data']['filiere']) && $_SESSION['form_data']['filiere'] === 'Génie Informatique' ? 'selected' : '' ?>>Génie Informatique</option>
                                    <option value="Génie Industriel" <?= isset($_SESSION['form_data']['filiere']) && $_SESSION['form_data']['filiere'] === 'Génie Industriel' ? 'selected' : '' ?>>Génie Industriel</option>
                                    <option value="Génie BIEE" <?= isset($_SESSION['form_data']['filiere']) && $_SESSION['form_data']['filiere'] === 'Génie BIEE' ? 'selected' : '' ?>>Génie BIEE</option>
                                    <option value="Génie Mécatronique" <?= isset($_SESSION['form_data']['filiere']) && $_SESSION['form_data']['filiere'] === 'Génie Mécatronique' ? 'selected' : '' ?>>Génie Mécatronique</option>
                                    <option value="Génie Electrique" <?= isset($_SESSION['form_data']['filiere']) && $_SESSION['form_data']['filiere'] === 'Génie Electrique' ? 'selected' : '' ?>>Génie Electrique</option>
                                    <option value="Génie RST" <?= isset($_SESSION['form_data']['filiere']) && $_SESSION['form_data']['filiere'] === 'Génie RST' ? 'selected' : '' ?>>Génie Réseaux</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="niveau" class="form-label">Niveau *</label>
                                <select class="form-select" id="niveau" name="niveau">
                                    <option value="">Sélectionner un niveau</option>
                                    <option value="CP1" <?= isset($_SESSION['form_data']['niveau']) && $_SESSION['form_data']['niveau'] === 'CP1' ? 'selected' : '' ?>>CP1</option>
                                    <option value="CP2" <?= isset($_SESSION['form_data']['niveau']) && $_SESSION['form_data']['niveau'] === 'CP2' ? 'selected' : '' ?>>CP2</option>
                                    <option value="CI1" <?= isset($_SESSION['form_data']['niveau']) && $_SESSION['form_data']['niveau'] === 'CI1' ? 'selected' : '' ?>>CI1</option>
                                    <option value="CI2" <?= isset($_SESSION['form_data']['niveau']) && $_SESSION['form_data']['niveau'] === 'CI2' ? 'selected' : '' ?>>CI2</option>
                                    <option value="CI3" <?= isset($_SESSION['form_data']['niveau']) && $_SESSION['form_data']['niveau'] === 'CI3' ? 'selected' : '' ?>>CI3</option>
                                </select>
                            </div>
                        </div>

                        <!-- Professor Fields -->
                        <div class="row mb-3 d-none" id="profFields">
                            <div class="col-md-6">
                                <label for="ID_PROF" class="form-label">ID Professeur *</label>
                                <input type="text" class="form-control" id="ID_PROF" name="ID_PROF"
                                       value="<?= isset($_SESSION['form_data']['ID_PROF']) ? htmlspecialchars($_SESSION['form_data']['ID_PROF']) : '' ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="DEPARTEMENT" class="form-label">Département *</label>
                                <select class="form-select" id="DEPARTEMENT" name="DEPARTEMENT">
                                    <option value="">Sélectionner un département</option>
                                    <option value="Département d'informatique, de logistique et de mathématiques" <?= isset($_SESSION['form_data']['DEPARTEMENT']) && $_SESSION['form_data']['DEPARTEMENT'] === "Département d'informatique, de logistique et de mathématiques" ? 'selected' : '' ?>>Dépt. Informatique & Logistique</option>
                                    <option value="Département de génie électrique, des réseaux et des systèmes des télécommunications" <?= isset($_SESSION['form_data']['DEPARTEMENT']) && $_SESSION['form_data']['DEPARTEMENT'] === "Département de génie électrique, des réseaux et des systèmes des télécommunications" ? 'selected' : '' ?>>Dépt. Génie Électrique</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="poste_prof" class="form-label">Poste *</label>
                                <input type="text" class="form-control" id="poste_prof" name="poste_prof"
                                       value="<?= isset($_SESSION['form_data']['poste_prof']) ? htmlspecialchars($_SESSION['form_data']['poste_prof']) : '' ?>">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="password" class="form-label">Mot de passe *</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="col-md-6">
                                <label for="confirmPassword" class="form-label">Confirmer le mot de passe *</label>
                                <input type="password" class="form-control" id="confirmPassword" name="Cpassword" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-12">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-circle me-3" id="avatarPreview" style="width: 50px; height: 50px; font-size: 24px;"></div>
                                    <small>L'avatar sera généré automatiquement à partir de la première lettre du prénom</small>
                                </div>
                            </div>
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="sendWelcomeEmail" checked>
                            <label class="form-check-label" for="sendWelcomeEmail">
                                Envoyer un email de bienvenue avec les informations de connexion
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-ensa">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View User Modal -->
    <div class="modal fade" id="viewUserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Détails de l'utilisateur</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3 text-center">
                                    <div class="avatar-circle mx-auto mb-3" id="viewAvatar" 
                                         style="width: 100px; height: 100px; font-size: 36px;"></div>
                                    <h5 id="viewName" class="mb-1"></h5>
                                    <span class="badge" id="viewRoleBadge"></span>
                                </div>
                                <div class="col-md-9">
                                    <table class="table table-bordered">
                                        <tbody>
                                            <tr>
                                                <th width="30%">ID</th>
                                                <td id="viewId"></td>
                                            </tr>
                                            <tr>
                                                <th>Nom</th>
                                                <td id="viewNom"></td>
                                            </tr>
                                            <tr>
                                                <th>Prénom</th>
                                                <td id="viewPrenom"></td>
                                            </tr>
                                            <tr>
                                                <th>Email</th>
                                                <td id="viewEmail"></td>
                                            </tr>
                                            <tr id="viewFiliereRow">
                                                <th>Filière</th>
                                                <td id="viewFiliere"></td>
                                            </tr>
                                            <tr id="viewDepartementRow" style="display:none;">
                                                <th>Département</th>
                                                <td id="viewDepartement"></td>
                                            </tr>
                                            <tr id="viewNiveauRow" style="display:none;">
                                                <th>Niveau</th>
                                                <td id="viewNiveau"></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Modifier l'utilisateur</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editUserForm" method="POST" action="">
                        <input type="hidden" name="updateUser" value="1">
                        <input type="hidden" id="editId" name="id">
                        <input type="hidden" id="editRole" name="role">
                        
                        

                        <!-- Champs modifiables pour professeur -->
                        <div class="card mb-4" id="profEditFields">
                            <div class="card-header">
                                Informations professionnelles
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="editDepartement" class="form-label">Département *</label>
                                        <select class="form-select" id="editDepartement" name="departement" required>
                                            <option value="Département d'informatique, de logistique et de mathématiques">Dépt. Informatique & Logistique</option>
                                            <option value="Département de génie électrique, des réseaux et des systèmes des télécommunications">Dépt. Génie Électrique</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="editPoste" class="form-label">Poste *</label>
                                        <input type="text" class="form-control" id="editPoste" name="poste" required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Champs modifiables pour étudiant -->
                        <div class="card mb-4" id="studentEditFields">
                            <div class="card-header">
                                Informations académiques
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="editFiliere" class="form-label">Filière *</label>
                                        <select class="form-select" id="editFiliere" name="filiere" required>
                                            <option value="Génie Informatique">Génie Informatique</option>
                                            <option value="Génie Industriel">Génie Industriel</option>
                                            <option value="Génie BIEE">Génie BIEE</option>
                                            <option value="Génie Mécatronique">Génie Mécatronique</option>
                                            <option value="Génie Electrique">Génie Electrique</option>
                                            <option value="Génie RST">Génie Réseaux</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="editNiveau" class="form-label">Niveau *</label>
                                        <select class="form-select" id="editNiveau" name="niveau" required>
                                            <option value="CP1">CP1</option>
                                            <option value="CP2">CP2</option>
                                            <option value="CI1">CI1</option>
                                            <option value="CI2">CI2</option>
                                            <option value="CI3">CI3</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-ensa">Enregistrer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteUserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Confirmer la suppression</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Êtes-vous sûr de vouloir supprimer cet utilisateur ? Cette action est irréversible.</p>
                    <input type="hidden" id="userToDeleteId" name="id">
                    <input type="hidden" id="userToDeleteRole" name="role">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Supprimer</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script>
        // Gestion dynamique des champs dans le formulaire d'ajout
        document.getElementById('role').addEventListener('change', function() {
            const role = this.value;
            document.getElementById('studentFields').classList.toggle('d-none', role !== 'student');
            document.getElementById('profFields').classList.toggle('d-none', role !== 'prof');
            updateAvatarPreview();
        });

        // Mise à jour de l'avatar dans le formulaire d'ajout
        function updateAvatarPreview() {
            const firstName = document.getElementById('firstName').value;
            const avatar = document.getElementById('avatarPreview');
            const role = document.getElementById('role').value;
            
            if (firstName) {
                const initial = firstName.charAt(0).toUpperCase();
                avatar.textContent = initial;
                avatar.style.backgroundColor = role === 'prof' ? '#ff8c00' : 
                                             role === 'student' ? '#1890ff' : '#6c757d';
            } else {
                avatar.textContent = '?';
                avatar.style.backgroundColor = '#6c757d';
            }
        }

        // Écouteur pour le prénom dans le formulaire d'ajout
        document.getElementById('firstName').addEventListener('input', updateAvatarPreview);

        // Initialisation de l'avatar au chargement
        document.addEventListener('DOMContentLoaded', function() {
            updateAvatarPreview();
            
            // Si des données de formulaire sont stockées en session, afficher les champs correspondants
            const role = "<?= isset($_SESSION['form_data']['role']) ? $_SESSION['form_data']['role'] : '' ?>";
            if (role) {
                document.getElementById('role').value = role;
                document.getElementById('role').dispatchEvent(new Event('change'));
            }
            
            // Nettoyer les données de formulaire stockées en session
            <?php unset($_SESSION['form_data']); ?>
        });

        // Gestion de la recherche globale
        document.getElementById('globalSearch').addEventListener('input', function() {
            const searchTerm = this.value.trim().toLowerCase();
            const rows = document.querySelectorAll('#usersTable tbody tr');
            
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                let rowMatch = false;
                
                // Vérifier chaque cellule sauf la dernière (Actions)
                for (let i = 0; i < cells.length - 1; i++) {
                    const cellText = cells[i].textContent.trim().toLowerCase();
                    if (cellText.includes(searchTerm)) {
                        rowMatch = true;
                        break;
                    }
                }
                
                row.style.display = rowMatch ? '' : 'none';
            });
        });

        // Gestion des boutons d'action
        document.querySelectorAll('.view-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const role = this.getAttribute('data-role');
                
                // Afficher un indicateur de chargement
                const modal = new bootstrap.Modal(document.getElementById('viewUserModal'));
                modal.show();
                document.getElementById('viewName').textContent = "Chargement...";
                
                // Requête AJAX pour récupérer les détails complets
                fetch('get_user_details.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `id=${encodeURIComponent(id)}&role=${encodeURIComponent(role)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        alert('Erreur: ' + data.error);
                        return;
                    }
                    
                    // Remplir les informations de la modal
                    document.getElementById('viewId').textContent = data.id;
                    document.getElementById('viewNom').textContent = data.nom;
                    document.getElementById('viewPrenom').textContent = data.prenom;
                    document.getElementById('viewEmail').textContent = data.email;
                    document.getElementById('viewName').textContent = data.nom + ' ' + data.prenom;
                    
                    // Avatar
                    const avatar = document.getElementById('viewAvatar');
                    avatar.textContent = data.prenom ? data.prenom.charAt(0).toUpperCase() : '?';
                    avatar.style.backgroundColor = role === 'prof' ? '#ff8c00' : 
                                                (role === 'student' ? '#1890ff' : '#8a2be2');
                    
                    // Badge de rôle
                    const roleBadge = document.getElementById('viewRoleBadge');
                    roleBadge.textContent = data.role;
                    roleBadge.className = 'badge ' + (role === 'prof' ? 'badge-prof' : 
                                         (role === 'student' ? 'badge-student' : 'badge-admin'));
                    
                    // Afficher les champs spécifiques au rôle
                    if (role === 'prof') {
                        document.getElementById('viewFiliereRow').style.display = 'none';
                        document.getElementById('viewDepartementRow').style.display = '';
                        document.getElementById('viewNiveauRow').style.display = 'none';
                        document.getElementById('viewDepartement').textContent = data.departement || 'Non spécifié';
                    } else if (role === 'student') {
                        document.getElementById('viewFiliereRow').style.display = '';
                        document.getElementById('viewDepartementRow').style.display = 'none';
                        document.getElementById('viewNiveauRow').style.display = '';
                        document.getElementById('viewFiliere').textContent = data.filiere || 'Non spécifié';
                        document.getElementById('viewNiveau').textContent = data.niveau || 'Non spécifié';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            });
        });

        // Gestion de l'édition
        document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const role = this.getAttribute('data-role');
                
                // Afficher un indicateur de chargement
                const modal = new bootstrap.Modal(document.getElementById('editUserModal'));
                modal.show();
                document.getElementById('editNom').value = "Chargement...";
                
                // Requête AJAX pour récupérer les détails complets
                fetch('get_user_details.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `id=${encodeURIComponent(id)}&role=${encodeURIComponent(role)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        alert('Erreur: ' + data.error);
                        return;
                    }
                    
                    // Remplir les informations de la modal
                    document.getElementById('editId').value = data.id;
                    document.getElementById('editIdDisplay').value = data.id;
                    document.getElementById('editNom').value = data.nom;
                    document.getElementById('editPrenom').value = data.prenom;
                    document.getElementById('editEmail').value = data.email;
                    document.getElementById('editRole').value = role;
                    
                    // Afficher les champs spécifiques au rôle
                    if (role === 'prof') {
                        document.getElementById('profEditFields').style.display = 'block';
                        document.getElementById('studentEditFields').style.display = 'none';
                        
                        // Remplir les champs professeur
                        if (data.departement) {
                            document.getElementById('editDepartement').value = data.departement;
                        }
                        if (data.poste) {
                            document.getElementById('editPoste').value = data.poste;
                        }
                    } else if (role === 'student') {
                        document.getElementById('profEditFields').style.display = 'none';
                        document.getElementById('studentEditFields').style.display = 'block';
                        
                        // Remplir les champs étudiant
                        if (data.filiere) {
                            document.getElementById('editFiliere').value = data.filiere;
                        }
                        if (data.niveau) {
                            document.getElementById('editNiveau').value = data.niveau;
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            });
        });

        // Gestion de la suppression
        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const role = this.getAttribute('data-role');
                
                document.getElementById('userToDeleteId').value = id;
                document.getElementById('userToDeleteRole').value = role;
                new bootstrap.Modal(document.getElementById('deleteUserModal')).show();
            });
        });

        // Confirmation de suppression
        document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
            const id = document.getElementById('userToDeleteId').value;
            const role = document.getElementById('userToDeleteRole').value;
            
            if (!id || !role) {
                alert('Identifiant ou rôle manquant');
                return;
            }
            
            fetch('delete_user.php', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id=${encodeURIComponent(id)}&role=${encodeURIComponent(role)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } 
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Erreur réseau lors de la suppression');
            });
        });

        // Export Excel
        document.getElementById('exportExcelBtn').addEventListener('click', function() {
            // Créer un tableau de données à exporter
            const rows = [];
            const headers = ['ID', 'Nom', 'Prénom', 'Rôle', 'Filière/Département', 'Email'];
            
            rows.push(headers);
            
            document.querySelectorAll('#usersTable tbody tr').forEach(row => {
                const cells = row.querySelectorAll('td');
                const rowData = [
                    cells[0].textContent.trim(), // ID
                    cells[1].textContent.trim().split(' ')[0], // Nom
                    cells[1].textContent.trim().split(' ').slice(1).join(' '), // Prénom
                    cells[2].textContent.trim(), // Rôle
                    cells[3].textContent.trim(), // Filière/Département
                    cells[4].textContent.trim()  // Email
                ];
                rows.push(rowData);
            });
            
            // Créer un workbook Excel
            const wb = XLSX.utils.book_new();
            const ws = XLSX.utils.aoa_to_sheet(rows);
            XLSX.utils.book_append_sheet(wb, ws, 'Utilisateurs');
            
            // Exporter le fichier
            const date = new Date().toISOString().slice(0, 10);
            XLSX.writeFile(wb, `utilisateurs_${date}.xlsx`);
        });

        // Toggle sidebar on mobile
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('show');
        });

        // Fermer le sidebar quand on clique à l'extérieur
        document.addEventListener('click', function(event) {
            const sidebar = document.querySelector('.sidebar');
            const sidebarToggle = document.getElementById('sidebarToggle');
            
            if (!sidebar.contains(event.target) && event.target !== sidebarToggle && !sidebarToggle.contains(event.target)) {
                sidebar.classList.remove('show');
            }
        });




        // Gestion dynamique du filtre
document.getElementById('roleFilter').addEventListener('change', function() {
    const role = this.value;
    const filiereLabel = document.getElementById('filiereLabel');
    const filiereFilter = document.getElementById('filiereFilter');
    
    // Mettre à jour le label
    filiereLabel.textContent = role === 'prof' ? 'Département' : 'Filière';
    
    // Mettre à jour les options
    filiereFilter.innerHTML = '<option value="">Tous</option>';
    
    if (role === 'prof') {
        // Options pour les départements
        filiereFilter.innerHTML += `
            <option value="Département d'informatique, de logistique et de mathématiques">
                Dépt. Informatique & Logistique
            </option>
            <option value="Département de génie électrique, des réseaux et des systèmes des télécommunications">
                Dépt. Génie Électrique
            </option>
        `;
    } else if (role === 'student') {
        // Options pour les filières
        filiereFilter.innerHTML += `
            <option value="Génie Informatique">Génie Informatique</option>
            <option value="Génie Industriel">Génie Industriel</option>
            <option value="Génie BIEE">Génie BIEE</option>
            <option value="Génie Mécatronique">Génie Mécatronique</option>
            <option value="Génie Electrique">Génie Electrique</option>
            <option value="Génie RST">Génie Réseaux</option>
        `;
    }
});

// Appliquer le filtre au chargement si un rôle est déjà sélectionné
document.addEventListener('DOMContentLoaded', function() {
    const roleFilter = document.getElementById('roleFilter');
    if (roleFilter.value) {
        roleFilter.dispatchEvent(new Event('change'));
    }
});
    </script>
</body>
</html>