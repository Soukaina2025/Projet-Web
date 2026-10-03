<?php
session_start();
require_once 'db_config.php';

// Vérifiez si l'utilisateur est déjà connecté
if (isset($_SESSION['APOGEE'])) {
    // Rediriger vers la page d'accueil si déjà connecté
    header("Location: ../projets_page_folder_vf_acceuil_esp_etudiant/home_student.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $apogee = trim($_POST['apogee']);
    $password = $_POST['password'];

    // Validation des champs
    if (empty($apogee) || empty($password)) {
        header("Location: login_page.php?error=" . urlencode("Veuillez remplir tous les champs"));
        exit();
    }

    if (!is_numeric($apogee)) {
        header("Location: login_page.php?error=" . urlencode("Le numéro Apogée doit être numérique"));
        exit();
    }

    try {
        // Requête préparée pour éviter les injections SQL
        $stmt = $conn->prepare("SELECT APOGEE, NOM, PRENOM, PASSWRD, FILIERE, NIV, EMAIL_INST,AVATAR, IMG FROM STUDENT WHERE APOGEE = :apogee");
        $stmt->bindParam(':apogee', $apogee, PDO::PARAM_INT);
        $stmt->execute();
        $etudiant = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($etudiant) {
            // Vérification du mot de passe hashé
            if (password_verify($password, $etudiant['PASSWRD'])) {
                // Création de la session
                $_SESSION['APOGEE'] = $etudiant['APOGEE'];
                $_SESSION['NOM'] = $etudiant['NOM'];
                $_SESSION['PRENOM'] = $etudiant['PRENOM'];
                $_SESSION['FILIERE'] = $etudiant['FILIERE'];
                $_SESSION['NIVEAU'] = $etudiant['NIV'];
                $_SESSION['EMAIL'] = $etudiant['EMAIL_INST'];
                $_SESSION['AVATAR']=$etudiant['AVATAR'];
                $_SESSION['ROLE'] = 'etudiant';

                // Redirection vers l'espace étudiant
                header("Location: ../projets_page_folder_vf_acceuil_esp_etudiant/home_student.php");
                exit();
            } else {
                header("Location: login_page.php?error=" . urlencode("Mot de passe incorrect"));
                exit();
            }
        } else {
            header("Location: login_page.php?error=" . urlencode("Numéro Apogée introuvable"));
            exit();
        }
    } catch (PDOException $e) {
        error_log("Erreur de base de données : " . $e->getMessage());
        header("Location: login_page.php?error=" . urlencode("Erreur système. Veuillez réessayer plus tard."));
        exit();
    }
} else {
    header("HTTP/1.1 405 Method Not Allowed");
    header("Location: login_page.php?error=" . urlencode("Méthode non autorisée"));
    exit();
}
?>