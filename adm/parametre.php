<?php
session_start();
require_once '../base_donnees/db_config.php';

// Vérifier si l'utilisateur est connecté et est un administrateur
if (!isset($_SESSION['ID_ADM'])) {
    header("Location: login.php");
    exit();
}

// Traitement du formulaire de mise à jour des informations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $nom = $_POST['nom'];
        $prenom = $_POST['prenom'];
        $email = $_POST['email'];
        $poste = $_POST['poste'];
        $id = $_SESSION['ID_ADM'];

        try {
            $stmt = $conn->prepare("UPDATE adm SET NOM = ?, PRENOM = ?, EMAIL = ?, poste = ? WHERE ID_ADM = ?");
            $stmt->execute([$nom, $prenom, $email, $poste, $id]);
            
            // Mettre à jour les données de session
            $_SESSION['NOM'] = $nom;
            $_SESSION['PRENOM'] = $prenom;
            $_SESSION['EMAIL'] = $email;
            $_SESSION['poste'] = $poste;
            
            $success_message = "Profil mis à jour avec succès!";
        } catch (PDOException $e) {
            $error_message = "Erreur lors de la mise à jour du profil: " . $e->getMessage();
        }
    } elseif (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        $id = $_SESSION['ID_ADM'];

        // Vérifier que le nouveau mot de passe correspond à la confirmation
        if ($new_password !== $confirm_password) {
            $error_message = "Les nouveaux mots de passe ne correspondent pas.";
        } else {
            // Récupérer le mot de passe actuel depuis la base de données
            $stmt = $conn->prepare("SELECT PASSWRD FROM adm WHERE ID_ADM = ?");
            $stmt->execute([$id]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($current_password, $admin['PASSWRD'])) {
                // Hasher le nouveau mot de passe
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                // Mettre à jour le mot de passe
                $stmt = $conn->prepare("UPDATE adm SET PASSWRD = ? WHERE ID_ADM = ?");
                $stmt->execute([$hashed_password, $id]);
                
                $success_message = "Mot de passe changé avec succès!";
            } else {
                $error_message = "Mot de passe actuel incorrect.";
            }
        }
    } elseif (isset($_POST['update_system_settings'])) {
        $maintenance_mode = isset($_POST['maintenance_mode']) ? 1 : 0;
        
        
    }
}

// Récupérer les informations actuelles de l'administrateur
$stmt = $conn->prepare("SELECT * FROM adm WHERE ID_ADM = ?");
$stmt->execute([$_SESSION['ID_ADM']]);
$admin = $stmt->fetch();

// Récupérer les paramètres système
$maintenance_mode = 0;
$session_timeout = 30;
$max_file_size = 20;

try {
    $stmt = $conn->query("SELECT * FROM system_settings WHERE id = 1");
    $settings = $stmt->fetch();
    
    if ($settings) {
        $maintenance_mode = $settings['maintenance_mode'];
        $session_timeout = $settings['session_timeout'];
    }
} catch (PDOException $e) {
    // La table n'existe pas encore, on utilise les valeurs par défaut
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ENSA Projects - Paramètres Administrateur</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --ensa-primary: #2C3E50;
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
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            border: none;
        }
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
        .avatar-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 18px;
            background-color: #3f51b5;
            color: white;
        }
        .settings-section {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .form-label {
            font-weight: 500;
        }
        .menu_button {
            display: none;
        }

        @media (max-width: 1205px) {
            .sidebar {
                position: fixed;
                left: -250px;
                top: 0;
                bottom: 0;
                z-index: 1000;
                transition: left 0.3s ease;
                display: block !important;
            }
            .sidebar.show {
                left: 0;
            }
            .main-content {
                margin-left: 0;
            }
            .menu_button {
                display: block;
            }
        }

        @media (min-width: 1200px) {
            .menu_button {
                display: none !important;
            }
        }

        @media (max-width: 363px) {
            .btn-ensa, .btn-group {
                font-size: 13px;
            }
        }



        @media (max-width:400px){

            .settings-section{
                padding-left: 13px;
                padding-right: 13px;
            }
            .settings-section h5{
                font-size: 1rem;
            }

            .main-content h4{
                text-align: center;
            }

            .avatar-circle{
                width: 40px;
                height: 40px;
                font-size: 25px;
            }
        }

        .avatar_container{
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 17px;
        }
    </style>



<style>
        :root {
            --ensa-primary: #2C3E50;
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
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            border: none;
        }
        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0,0,0,0.1);
            font-weight: 600;
        }
        .stat-card {
            border-left: 4px solid var(--ensa-secondary);
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
        .chart-container {
            position: relative;
            height: 259px;
            width: 100%;

        }
        @media (max-width:768px) {
            .donut_chart {
                    justify-content: center;
                    display: flex;
                    
            }
            
            
        }
        .chart-tab {
            display: none;
        }
        .chart-tab.active {
            display: block;
        }
        .btn-group .btn.active {
            background-color: #bfdbfe;
            color: #1e3a8a;
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
}


        .bg_proj{
            background-color: #bfdbfe;
            color: #1e3a8a;
        }

        .fa-chevron-down:before {
    


    content: none;
    
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
            transition: all 0.2s;
        }

        .bg_proj:hover{
            background-color:rgb(180, 206, 237);
            transform: translateY(-2px);
            
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

    .bar_chart{
        display: flex;
        justify-content: center;
    }

    .text_container{

    display: flex;
    flex-direction: column;
    align-items: center;
}
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

.settings-section .avatar-circle{
    width: 80px; height: 80px; font-size: 32px;

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
    .settings-section  {
        display: block;
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
    <div class="d-flex first_container">
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
                    <a class="nav-link" href="gestion-user.php">
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
                    <a class="nav-link active" href="parametre.php">
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
            

            <h4 class="mb-4">Paramètres Administrateur</h4>

            <!-- Messages d'alerte -->
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-6">
                    <!-- Section Profil -->
                    <div class="settings-section">
                        <h5 class="mb-4"><i class="bi bi-person-circle me-2"></i>Informations du Profil</h5>
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="nom" class="form-label">Nom</label>
                                <input type="text" class="form-control" id="nom" name="nom" value="<?php echo htmlspecialchars($admin['NOM']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="prenom" class="form-label">Prénom</label>
                                <input type="text" class="form-control" id="prenom" name="prenom" value="<?php echo htmlspecialchars($admin['PRENOM']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($admin['EMAIL']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="poste" class="form-label">Poste</label>
                                <input type="text" class="form-control" id="poste" name="poste" value="<?php echo htmlspecialchars($admin['poste']); ?>">
                            </div>
                            <button type="submit" name="update_profile" class="btn bg_proj">Mettre à jour le profil</button>
                        </form>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <!-- Section Mot de passe -->
                    <div class="settings-section">
                        <h5 class="mb-4"><i class="bi bi-shield-lock me-2 "></i>Changer le mot de passe</h5>
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Mot de passe actuel</label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                            </div>
                            <div class="mb-3">
                                <label for="new_password" class="form-label">Nouveau mot de passe</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirmer le nouveau mot de passe</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            <button type="submit" name="change_password" class="btn bg_proj">Changer le mot de passe</button>
                        </form>
                    </div>
                    
                    <!-- Section Avatar -->
                    <div class="settings-section mt-4">
                        <h5 class="mb-4"><i class="bi bi-image me-2"></i>Photo de profil</h5>
                        <div class="d-flex align-items-center mb-3 avatar_container">
                            <div class="avatar-circle me-3 photo_avatar">            
                                <?php echo strtoupper(substr($admin['PRENOM'], 0, 1)); ?>
                            </div>
                            <div>
                                <p class="mb-1">Votre initiale est utilisée comme avatar</p>
                                <small class="text-muted">Pour changer l'avatar, modifiez votre prénom</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Section Paramètres système (pour admin) -->
            <div class="settings-section mt-4">
                <h5 class="mb-4"><i class="bi bi-sliders me-2"></i>Paramètres Système</h5>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    Ces paramètres affectent l'ensemble de la plateforme. Utilisez avec prudence.
                </div>
                
                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Durée maximale des sessions (minutes)</label>
                                <input type="number" class="form-control" name="session_timeout" value="<?php echo $session_timeout; ?>" min="5" max="1440">
                            </div>
                            
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3 form-check form-switch">
                                <input type="checkbox" class="form-check-input" id="maintenance_mode" name="maintenance_mode" <?php echo $maintenance_mode ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="maintenance_mode">Mode maintenance</label>
                            </div>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle-fill me-2"></i>
                                En mode maintenance, les espaces étudiant et enseignant afficheront une page 404.
                            </div>
                        </div>
                    </div>
                    <button type="submit" name="update_system_settings" class="btn bg_proj">Enregistrer les paramètres système</button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
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

        // Validation du formulaire de changement de mot de passe
        const passwordForm = document.querySelector('form[name="change_password"]');
        if (passwordForm) {
            passwordForm.addEventListener('submit', function(e) {
                const newPassword = document.getElementById('new_password').value;
                const confirmPassword = document.getElementById('confirm_password').value;
                
                if (newPassword.length < 8) {
                    alert('Le mot de passe doit contenir au moins 8 caractères');
                    e.preventDefault();
                } else if (newPassword !== confirmPassword) {
                    alert('Les mots de passe ne correspondent pas');
                    e.preventDefault();
                }
            });
        }
    </script>
</body>
</html>