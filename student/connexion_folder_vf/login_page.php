<?php
session_start();
require_once 'db_config.php';
require_once '../../vendor/autoload.php';

// Vérification de session APOGEE - une seule condition suffit
if (isset($_SESSION['APOGEE'])) {
    header('Location: ../projets_page_folder_vf_acceuil_esp_etudiant/home_student.php');
    exit();
}

// Traitement connexion Google
if (!empty($_POST['credential'])) {
    // Vérification CSRF
    if (empty($_COOKIE['g_csrf_token']) || empty($_POST['g_csrf_token']) || 
        $_COOKIE['g_csrf_token'] != $_POST['g_csrf_token']) {
        $_SESSION['error'] = "Erreur de sécurité CSRF";
        header("Location: login_page.php");
        exit();
    }

    try {
        $client = new Google_Client(['client_id' => '1009418419367-rnh5dt5q69fvre4c6d21o3rbg925eni5.apps.googleusercontent.com']);
        $payload = $client->verifyIdToken($_POST['credential']);

        if ($payload) {
            // Vérification domaine email
            $email = $payload['email'];
            if (!str_ends_with($email, '@uit.ac.ma')) {
                $_SESSION['error'] = "Seuls les emails @uit.ac.ma sont autorisés";
                header("Location: login_page.php");
                exit();
            }

            // Gestion utilisateur
            $stmt = $conn->prepare("SELECT * FROM student WHERE EMAIL_INST = ? OR GOOGLE_ID = ?");
            $stmt->execute([$email, $payload['sub']]);
            $user = $stmt->fetch();

            if (!$user) {
                $_SESSION['error'] = "Utilisateur n'existe pas. Veuillez vous inscrire d'abord.";
                header("Location: login_page.php");
                exit();
            }

            // Création de session
            $_SESSION['APOGEE'] = $user['APOGEE'];
            $_SESSION['EMAIL'] = $email;
            $_SESSION['NOM'] = $user['NOM'];
            $_SESSION['PRENOM'] = $user['PRENOM'];
            
            header("Location: ../projets_page_folder_vf_acceuil_esp_etudiant/home_student.php");
            exit();
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Erreur d'authentification Google: ".$e->getMessage();
        header("Location: login_page.php");
        exit();
    }
}

$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']); // Nettoyer l'erreur après l'avoir affichée
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion | Espace Etudiant</title>
    
    <!-- Liens CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap">
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.0/css/line.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css">
  <style>
    :root {
        --primary-blue: #002c84;
        --secondary-blue: #001a4f;
        --light-gray: #f8f9fa;
        --medium-gray: #e9ecef;
        --dark-gray: #343a40;
        --white: #ffffff;
        --transition: all 0.3s ease;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    body {
        background-color: var(--light-gray);
        color: var(--dark-gray);
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }

    .header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px 5%;
        background-color: var(--white);
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        position: sticky;
        top: 0;
        z-index: 1000;
    }

    .logo {
        height: 60px;
    }

    .nav {
        display: flex;
        gap: 30px;
    }

    .nav a {
        color: var(--primary-blue);
        text-decoration: none;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.9rem;
        letter-spacing: 0.5px;
        position: relative;
        padding: 5px 0;
        transition: var(--transition);
    }

    .nav a::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 0;
        height: 2px;
        background-color: var(--primary-blue);
        transition: var(--transition);
    }

    .nav a:hover {
        color: var(--secondary-blue);
    }

    .nav a:hover::after {
        width: 100%;
    }

    .login-container {
        display: flex;
        flex: 1;
        align-items: center;
        padding: 40px 20px;
    }

    .login-image {
        flex: 1;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 20px;
    }

    .login-image img {
        width: 100%;
        height: 400px;
        border-radius: 10px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    }

    .login-box {
        width: 420px;
        padding: 30px;
        background-color: #fff;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    }

    .form-label {
        font-weight: 600;
        color: #495057;
    }

    .btn-primary {
        background-color: var(--primary-blue) !important;
        border-color: var(--primary-blue) !important;
        color: white !important;
        font-weight: 500;
        letter-spacing: 0.5px;
        padding: 10px 20px;
        border-radius: 8px;
        transition: all 0.3s ease;
        display: block !important;
    }

    .btn-primary:hover {
        background-color: var(--secondary-blue) !important;
        box-shadow: 0 4px 8px rgba(0, 44, 132, 0.3);
        transform: translateY(-2px);
    }

    .google-btn {
        background-color: white;
        color: #3c4043;
        border: 1px solid #003366;
        font-weight: 500;
        border-radius: 8px;
    }

    .google-btn:hover {
        border: 1px solid #2a75e6;
    }

    .google-btn img {
        width: 30px;
        height: 30px;
    }

    .form-control::placeholder {
        color: #6c757d;
        opacity: 0.7;
    }

    .menu-toggle {
        display: none;
        background: none;
        border: none;
        font-size: 1.5rem;
        color: var(--primary-blue);
        cursor: pointer;
    }

    /* Footer Styles */
    .footer-ensa {
        background-color: var(--primary-blue);
        color: var(--white);
        padding: 60px 5% 30px;
        font-size: 15px;
    }

    .footer-container {
        max-width: 1200px;
        margin: 0 auto;
    }

    .footer-columns {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        gap: 30px;
        margin-bottom: 40px;
    }

    .footer-column {
        flex: 1;
        min-width: 250px;
    }

    .footer-logo img {
        max-width: 200px;
        margin-bottom: 20px;
    }

    .footer-column h4 {
        color: var(--white);
        margin-bottom: 20px;
        font-size: 18px;
        position: relative;
        padding-bottom: 10px;
    }

    .footer-column h4::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 50px;
        height: 2px;
        background-color: var(--white);
    }

    .footer-column p {
        margin-bottom: 15px;
        line-height: 1.6;
        color: var(--medium-gray);
    }

    .footer-links {
        list-style: none;
        padding: 0;
    }

    .footer-links li {
        margin-bottom: 10px;
    }

    .footer-links a {
        color: var(--medium-gray);
        text-decoration: none;
        transition: var(--transition);
    }

    .footer-links a:hover {
        color: var(--white);
        padding-left: 5px;
    }

    .social-links {
        display: flex;
        gap: 15px;
        margin-top: 20px;
    }

    .social-links a {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: rgba(255, 255, 255, 0.1);
        color: var(--white);
        transition: var(--transition);
        text-decoration: none;
    }

    .social-links a:hover {
        background-color: var(--white);
        color: var(--primary-blue);
        transform: translateY(-3px);
    }

    .footer-bottom {
        text-align: center;
        padding-top: 30px;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        color: var(--medium-gray);
        font-size: 14px;
    }

    @media (max-width: 992px) {
        .menu-toggle {
            display: block;
        }

        .nav {
            display: none;
            position: absolute;
            top: 90px;
            left: 0;
            right: 0;
            background: white;
            flex-direction: column;
            padding: 20px;
            box-shadow: 0 5px 10px rgba(0,0,0,0.1);
            z-index: 1000;
        }

        .nav.show {
            display: flex;
        }

        .login-container {
            flex-direction: column;
        }

        .login-image {
            order: -1;
            margin-bottom: 30px;
        }

        .login-box {
            width: 100%;
            max-width: 500px;
        }
    }

    @media (max-width: 576px) {
        .header {
            padding: 15px;
        }

        .nav {
            top: 80px;
        }

        .login-box {
            padding: 20px;
        }

        .footer-column {
            min-width: 100%;
        }
    }
    
/* ==================== ROOT VARIABLES ==================== */
:root {
  --ensa-blue: #0056b3;
  --ensa-light-blue: #e9f0f7;
  --ensa-dark: #343a40;
  --main-bg: #f0f4f8;
  --primary-color: #002C84;
  --secondary-color: #54595F;
  --text-color: #333;
  --card-bg: #fff;
  --primary-blue: #002c84;
  --secondary-blue: #001a4f;
  --light-gray: #f8f9fa;
  --medium-gray: #e9ecef;
  --dark-gray: #343a40;
  --white: #ffffff;
  --transition: all 0.3s ease;
  --box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}

/* ==================== BASE STYLES ==================== */
body {
  font-family: 'Roboto', Tahoma, Geneva, Verdana, sans-serif;
  margin: 0;
  /*background-color: var(--main-bg);*/
  color: var(--text-color);
}

/* ==================== HEADER STYLES ==================== */
.header-wrapper {
  background-color: white;
  box-shadow: var(--box-shadow);
}

.header-wrapper nav {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 0;
}

.logo img {
  width: 300px;
  height: auto;
}



.nav-link {
  text-decoration: none;
  color: var(--primary-color);
  font-weight: 500;
  font-size: 1.2rem;
  position: relative;
  transition: color 0.3s ease;
}

.nav-link:hover {
  color: var(--primary-color);
}

.nav-link::after {
  content: "";
  position: absolute;
  left: 0;
  bottom: -4px;
  width: 0;
  height: 2px;
  background: var(--primary-color);
  transition: width 0.3s ease-in-out;
}

.nav-link:hover::after {
  width: 100%;
}


.nav-links {
  display: flex;
  align-items: center;
  justify-content: space-between;
  row-gap: 3rem;
  list-style: none;
  margin: 0px;
  padding: 0px;
  gap: 3rem;

    column-gap: 5rem;
    
  
}



.navbar-nav .nav-link.active, .navbar-nav .nav-link.show {
  color: #002C84;
}

.navbar-collapse {
  flex-grow: 0; 
}

/* ==================== DROPDOWN & SOCIAL ICONS ==================== */
.links_container {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.social_icons {
  display: flex;
  gap: 1rem;
}

.social_icons a i,
.footer_icons a i {
  transition: transform 0.3s ease;
  cursor: pointer;
  color: var(--primary-color);
  font-size: 1.5rem;
}

.social_icons a i:hover,
.footer_icons a i:hover {
  transform: scale(1.2);
      transform: scale(1.2);
  
}

/* ==================== FOOTER STYLES ==================== */
.footer-wrapper {
  background-color:#002C84;
  color: white;
  padding: 2rem;
  text-align: center;
  box-shadow: var(--box-shadow);

}

.footer-section {
  display: flex;
  justify-content: space-between;
  flex-wrap: wrap;
}

.footer-info img {
  max-width: 250px;
}

.footer-info p a {
  text-decoration: none;
  padding-bottom: 4px;
  border-bottom: 2px solid white;
}

.footer-links h3, .footer-contact h3 {
  margin-inline: 20px;
  position: relative; 
  display: inline;
  font-size: 22px;
}

.footer-links h3::after, .footer-contact h3::after {
  content: "";
  position: absolute;
  left: 0;
  bottom: -4px;
  width: 0;
  height: 2px;
  background: white;
  width: 50%;
}

.footer-links, .footer-contact {
  flex: 1;
  margin: 1rem;
}

.footer-links ul {
  display: flex;
  flex-direction: column;
  gap: 28px;
  margin-top: 20px;


}

.footer-links ul li a {
  text-decoration: none;
  color: white;
  font-weight: 500;
  font-size: 1rem;
  margin-inline: 20px;
  position: relative; 

}

.footer_icons,.footer-links ul {
  margin-top: 27px;
  margin-bottom: 20px;
}

.footer_icons a {
  margin-right: 5px;
}

.footer_icons a i {

  font-size: 27px;
  color: gray;

}

.footer-bottom {
  margin-top: 1rem;
  border-top: 1px solid gray;
  padding-top: 1rem;
  color: white;
}



.footer-links ul li a::after ,.contact_link::after{
  content: "";
  position: absolute;
  left: 0;
  bottom: -4px;
  width: 0;
  height: 2px;
  background: white;
  transition: width 0.3s ease-in-out;
}

.footer-links ul li a:hover::after, .contact_link:hover::after {
  width: 100%;
}

.footer-formation ul, .footer-links ul {
  list-style: none;
  padding: 0;
}

.contact_link{
  margin-top: 20px;
  margin-bottom: 30px;
  text-decoration: none;
  color: white;
  font-weight: 500;
  font-size: 1rem;
  margin-inline: 20px;
  position: relative; 
}

p {
  margin-top: 1rem;
  margin-bottom: 1rem;
}

.footer-info {
  margin: 1rem;
}

/* ==================== CAROUSEL STYLES ==================== */
#introCarousel,
.carousel-inner,
.carousel-item,
.carousel-item.active {
  height: 100vh;
}

.carousel-item {
  background-size: cover;
  background-position: center center;
  background-repeat: no-repeat;
}








/* Ajoutez ces styles à votre fichier CSS */

/* Styles pour le carrousel */
.carousel-item:nth-child(1) {
    background-image: url('images/animation.jpg');
    background-size: cover;
    background-position: center;
}

.carousel-item:nth-child(2) {
    background-image: url('images/ensa-enseignants.webp');
    background-size: cover;
    background-position: center;
}

.carousel-item:nth-child(3) {
    background-image: url('images/ensa-administration.webp');
    background-size: cover;
    background-position: center;
}

.caroussel_title {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 1rem;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
}

.caroussel_subtitle {
    font-size: 1.5rem;
    margin-bottom: 2rem;
    text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.5);
    max-width: 600px;
}

/* Overlay sombre pour meilleure lisibilité */
.carousel-item::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.4);
}

.carousel-item > div {
    position: relative;
    z-index: 2;
}

/* Adaptation responsive */
@media (max-width: 768px) {
    .caroussel_title {
        font-size: 1.8rem;
    }
    
    .caroussel_subtitle {
        font-size: 1.2rem;
    }
    
    .learn_button {
        padding: 8px 20px;
        font-size: 0.9rem;
    }
}


.carousel-indicators button.active {
    background-color: var(--primary-blue);
}


.carousel-item {
    transition: transform 1s ease-in-out;
}


.carousel-control-prev, .carousel-control-next {
  width: fit-content;
  top: 50%;
  transform: translateY(-50%);
}

.carousel-control-prev {
  left: 10px;
}

.carousel-control-next {
  right: 10px;
}

.caroussel-item div div h1{
  margin-bottom: 35px;


}


/* ==================== BUTTON STYLES ==================== */
.learn_button {
  position: relative;
  overflow: hidden;
  border: 2px solid transparent;
  border-radius: 0.5rem;
  background: linear-gradient(135deg, rgb(52, 54, 134) 0%, rgb(10, 15, 75) 100%);
  transition: border-color 0.5s ease-in-out;
}

.learn_button::before {
  content: "";
  position: absolute;
  top: 0;
  left: 0;
  height: 100%;
  width: 0%;
  background: rgba(255, 255, 255, 0.15);
  backdrop-filter: blur(5px);
  transition: width 0.5s ease;
  z-index: 1;
}

.learn_button:hover::before {
  width: 100%;
}

.learn_button:hover {
  border:2px solid #0a0c5d;
}

.learn_button span {
  position: relative;
  z-index: 10;
}

.learn_button{
  transition: all 0.3s ease-in-out;
  display: inline-block; 
  background: linear-gradient(135deg, #343686 0%, #0a0f4b 100%); 
  border: none;
  width: fit-content;
  padding-left: 20px;
  padding-right: 20px;
}

/* ==================== TITLE AND CARD STYLES ==================== */
        /* Espaces Section */
        .espaces{
          padding: 80px 5%;
          text-align: center;
          background-color: var(--white);
      }

      .espace-titre {
          font-size: 2rem;
          margin-bottom: 50px;
          color: var(--primary-blue);
          position: relative;
          display: inline-block;
      }

      .espace-titre::after {
          content: '';
          position: absolute;
          bottom: -10px;
          left: 50%;
          transform: translateX(-50%);
          width: 80px;
          height: 3px;
          background-color: var(--primary-blue);
      }

      .espaces-grid {
          display: flex;
          align-items: center;
          justify-content: center;
          flex-wrap: wrap;
          gap: 30px;
          margin-top: 40px;
          min-height: 100%;

          
      }

      .espace-carte {
          background: var(--light-gray);
          border-radius: 8px;
          overflow: hidden;
          box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
          transition: var(--transition);
          text-align: center;
          padding: 40px 30px;
          min-height: 100%;
      }

      .espace-carte:hover {
          transform: translateY(-10px);
          box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
      }

      .espace-icon {
          width: 80px;
          height: 80px;
          margin: 0 auto 20px;
          display: flex;
          align-items: center;
          justify-content: center;
          border-radius: 50%;
          font-size: 2rem;
          color: var(--white);
          background-color: var(--primary-blue);
      }

      .espace-carte h3 {
          font-size: 1.5rem;
          margin-bottom: 15px;
          color: var(--primary-blue);
      }

      .espace-carte p {
          margin-bottom: 25px;
          color: var(--dark-gray);
          line-height: 1.6;
      }

      .bouton {
          display: inline-block;
          padding: 12px 30px;
          border-radius: 50px;
          text-decoration: none;
          font-weight: 600;
          transition: var(--transition);
          border: none;
          cursor: pointer;
          font-size: 1rem;
          background-color: var(--primary-blue);
          color: var(--white);
      }

      .bouton:hover {
          background-color: var(--secondary-blue);
          transform: translateY(-3px);
          box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
      }

/* ==================== RESPONSIVE STYLES ==================== */


@media (max-width: 850px){
  .footer-section {
    flex-direction: column;
    align-items: center;
    text-align: center;
}

.footer-info,
.footer-links,
.footer-contact {
    margin-bottom: 30px;
    margin-right: 0;
  margin-left: 0;
}

.footer-links h3::after,
.footer-contact h3::after {
    left: 50%;
    transform: translateX(-50%);
    width: 100%;
}



}


@media (max-width: 768px) {
  .header-wrapper {
      padding: 10px;
  }
  


  .navbar-toggler {
      border: none;
      background: transparent;
  }

  
    
    .swiper-button-next:after, 
    .swiper-button-prev:after {
      font-size: 18px;
    }
  
    }

@media (min-width: 992px) {
  .navbar-expand-lg {
      flex-wrap: nowrap;
  }

  .container, .container-lg, .container-md, .container-sm {
    max-width: 97%;
  }


    .logo img {
        width: 300px;
    }
    
    .nav-links {
        gap: 1.5rem;
    }

  
    
}

@media screen and (max-width:1020px) {
  .navbar-brand img {
    width:270px;
  }
}

@media (max-width: 1100px) {
  .nav-link {
    font-size: 1rem;
  }
}


@media (max-width: 1220px) {
  .nav-link {
      font-size: 1.2rem;
  }
}

@media (max-width: 992px) {
.logo img {
      width: 250px;
  }
  
  .nav-links {
      gap: 1.5rem;
  }


    .caroussel_title{
      font-size:19px;
    }

    .learn_button{
      font-size:19px;
      padding: 3px 15px;


    }
    .caroussel-item div div h1{
      margin-bottom:0;
    }


}

.footer-info,.footer-links,.footer-contact{
  flex: 1;
}
  </style>
</head>
<body>
  <header class="header-wrapper bg-white shadow-sm">
      <nav class="navbar navbar-expand-lg navbar-light container">
                    <a class="navbar-brand logo" href="../projets_espaces_folder_vf_apropos/index.html">
                <img src="images/ensa_logo.png" alt="Logo ENSA">
            </a>

            <div class="collapse navbar-collapse justify-content-between" id="navbarNav">
                <ul class="navbar-nav nav-links">
                    <li class="nav-item"><a class="nav-link" href="../../projets_espaces_folder_vf_apropos/index.html">À propos</a></li>
                    <li class="nav-item"><a class="nav-link" href="../../reglements_page_folder_vf/index.html">Règlement</a></li>
                    <li class="nav-item"><a class="nav-link" href="../../contact_folder_vf/index.html">Contact</a></li>
                </ul>      
            </div>
            
          <div class="d-flex align-items-center gap-3 links_container">
              <div class="social_icons">
                  <a href="https://www.instagram.com/ensak.official" class="text-dark" target="_blank"><i class="fab fa-instagram fa-lg"></i></a>
                  <a href="https://ma.linkedin.com/company/ensa-kenitra-official" class="text-dark" target="_blank"><i class="fab fa-linkedin fa-lg"></i></a>
              </div>
              
              <button class="navbar-toggler toggle" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                  <span class="navbar-toggler-icon"></span>
              </button>
          </div>
      </nav>
  </header>

  <div class="login-container container">
    <div class="login-box">
      <h3 class="text-center mb-4"><i class="bi bi-person-fill"></i> Connexion Etudiant</h3>
      <form class="form-login" id="studentLoginForm" method="POST" action="login_page_trt.php">
        <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <div class="mb-3">
          <label for="apogee" class="form-label">Numéro Apogée</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fas fa-user"></i></span>
            <input type="text" name="apogee" id="apogee"  class="form-control" placeholder="Entrez votre numéro Apogée" required>
          </div>
        </div>

        <div class="mb-3">
          <label for="password" class="form-label">Mot de passe</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
            <input type="password" class="form-control" name="password" id="password" placeholder="Entrez votre Mot de passe" required>
          </div>
        </div>

        <div class="d-grid mb-3">
          <button type="submit" class="btn btn-primary">Connexion</button>
        </div>

        <div class="text-center mb-3">
          <a href="password-student.php" class="text-decoration-none">Mot de passe perdu ?</a>
        </div>

        <div class="text-center mb-2">
          <small>Connectez-vous avec</small>
        </div>

        <div class="google-btn-container">
        <div id="g_id_onload"
             data-client_id="1009418419367-rnh5dt5q69fvre4c6d21o3rbg925eni5.apps.googleusercontent.com"
             data-context="signin"
             data-ux_mode="popup"
             data-login_uri="<?= htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']) ?>"
             data-auto_prompt="false">
        </div>

        <div class="g_id_signin"
             data-type="standard"
             data-shape="pill"
             data-theme="outline"
             data-text="signin_with"
             data-size="large"
             data-logo_alignment="left">
        </div>
      </div>
      </form>
    </div>

    <div class="login-image">
      <img src="images/man-is-standing-front-computer-with-many-icons-it_1108514-78398.jpg" alt="Image université">
    </div>
  </div>

 <footer class="footer-wrapper">
        <div class="footer-section">
          <div class="footer-info">
            <img src="images/LOGO-ENSA.png" alt="Logo ENSA">
            <p><a href="https://ensa.uit.ac.ma/" target="_blank" style="color: white; ">Visiter le site officiel</a></p>
          </div>
          <div class="footer-links">
            <h3>Liens utiles</h3>
            <ul>
                
              <li><a href="../../projets_espaces_folder_vf_apropos/index.html">À propos</a></li>
              <li><a href="../../reglements_page_folder_vf/index.html">Règlement</a></li>
              <li><a href="../../contact_folder_vf/index.html">Contact</a></li>
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

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

    <script src="script.js"></script>
    <script src="https://accounts.google.com/gsi/client" async defer></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  
</body>
</html>