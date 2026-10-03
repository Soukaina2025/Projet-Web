<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Récupération de mot de passe - UIT</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet" />
  <style>
    :root {
      --primary-blue: #002c84;
      --secondary-blue: #001a4f;
      --light-gray: #f8f9fa;
      --medium-gray: #e9ecef;
      --dark-gray: #343a40;
      --white: #ffffff;
      --transition: all 0.3s ease;
      --error-red: #dc3545;
      --success-green: #28a745;
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

    .password-container {
      flex: 1;
      display: flex;
      align-items: center;
      padding: 40px 20px;
    }

    .password-image {
      flex: 1;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 20px;
    }

    .password-image img {
      max-width: 100%;
      height: auto;
      border-radius: 15px;
      box-shadow: 0 5px 25px rgba(0, 0, 0, 0.15);
    }

    .password-box {
      width: 550px;
      padding: 40px;
      background-color: #fff;
      border-radius: 15px;
      box-shadow: 0 5px 25px rgba(0, 0, 0, 0.1);
      margin: 0 auto;
    }

    .form-label {
      font-weight: 600;
      color: #495057;
      font-size: 1.05rem;
    }

    .form-control {
      padding: 12px 15px;
      font-size: 1rem;
      border-radius: 8px;
      transition: var(--transition);
    }

    .form-control:focus {
      border-color: var(--primary-blue);
      box-shadow: 0 0 0 0.25rem rgba(0, 44, 132, 0.25);
    }

    .input-group-text {
      padding: 0 15px;
      background-color: #f8f9fa;
      transition: var(--transition);
    }

    .btn-primary {
      background-color: var(--primary-blue) !important;
      border-color: var(--primary-blue) !important;
      color: white !important;
      font-weight: 500;
      letter-spacing: 0.5px;
      padding: 10px 20px;
      border-radius: 8px;
      transition: var(--transition);
      display: block !important;
    }

    .btn-primary:hover {
      background-color: var(--secondary-blue) !important;
      box-shadow: 0 4px 8px rgba(0, 44, 132, 0.3);
      transform: translateY(-2px);
    }

    .btn-primary:disabled {
      background-color: #6c757d !important;
      border-color: #6c757d !important;
      transform: none !important;
      box-shadow: none !important;
    }

    .password-title {
      font-size: 1.8rem;
      margin-bottom: 30px;
      color: var(--primary-blue);
      text-align: center;
    }

    .password-instructions {
      color: #6c757d;
      margin-bottom: 30px;
      text-align: center;
      line-height: 1.6;
    }

    .alert-message {
      padding: 15px;
      border-radius: 8px;
      margin-bottom: 20px;
      text-align: center;
      display: none;
    }

    .alert-success {
      background-color: #d4edda;
      color: var(--success-green);
      border: 1px solid #c3e6cb;
    }

    .alert-error {
      background-color: #f8d7da;
      color: var(--error-red);
      border: 1px solid #f5c6cb;
    }

    .loading-spinner {
      display: none;
      width: 20px;
      height: 20px;
      border: 3px solid rgba(255, 255, 255, 0.3);
      border-radius: 50%;
      border-top-color: #fff;
      animation: spin 1s ease-in-out infinite;
      margin-right: 10px;
    }

    @keyframes spin {
      to { transform: rotate(360deg); }
    }

    .footer-ensa {
      background-color: var(--primary-blue);
      color: var(--white);
      padding: 60px 5% 30px;
      font-size: 15px;
      margin-top: auto;
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

    @media (max-width: 1200px) {
      .password-container {
        flex-direction: column;
      }
      
      .password-image {
        order: -1;
        margin-bottom: 40px;
        max-width: 600px;
      }
      
      .password-box {
        width: 100%;
        max-width: 600px;
      }
    }

    @media (max-width: 768px) {
      .header {
        flex-direction: column;
        padding: 15px;
      }
      
      .nav {
        margin-top: 20px;
        flex-wrap: wrap;
        justify-content: center;
        gap: 15px;
      }
      
      .password-box {
        padding: 30px 20px;
      }
      
      .password-title {
        font-size: 1.6rem;
      }
    }

    @media (max-width: 576px) {
      .password-box {
        padding: 25px 15px;
      }
      
      .nav a {
        font-size: 0.85rem;
      }
    }
  </style>
</head>
<body>

  <div class="header">
    <a href="about.html">
      <img src="images/ensa_logo.png" alt="ENSA Kenitra" class="logo">
    </a>
    
    <nav class="nav">
      <a href="about.html">À propos</a>
      <a href="reglement.html">Règlement</a>
      <a href="contact.html">Contact</a>
    </nav>
  </div>

  <div class="password-container container">
    <div class="password-box">
      <h3 class="password-title"><i class="bi bi-shield-lock"></i> Réinitialiser votre mot de passe</h3>
      
      <div id="alertMessage" class="alert-message">
        <i class="bi" id="alertIcon"></i> <span id="alertText"></span>
      </div>
      
      <p class="password-instructions">
        Entrez l'adresse email associée à votre compte UIT (@uit.ac.ma). Nous vous enverrons un nouveau mot de passe.
      </p>
      
      <form id="passwordResetForm">
        <div class="mb-4">
          <label for="email" class="form-label">Adresse email UIT</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-envelope-fill"></i></span>
            <input type="email" class="form-control" id="email" name="email" placeholder="prenom.nom@uit.ac.ma" required>
          </div>
          <div id="emailError" class="invalid-feedback d-none">Veuillez fournir une adresse email UIT valide (@uit.ac.ma)</div>
        </div>

        <div class="d-grid mb-3">
          <button type="submit" class="btn btn-primary" id="submitBtn">
            <span class="loading-spinner" id="loadingSpinner"></span>
            <span id="buttonText"><i class="bi bi-send-fill"></i> Envoyer le nouveau mot de passe</span>
          </button>
        </div>

        <div class="text-center">
          <a href="login_page.php" class="text-decoration-none"><i class="bi bi-arrow-left"></i> Retour à la page de connexion</a>
        </div>
      </form>
    </div>
    
    <div class="password-image">
      <img src="images/password-recovery-methods.jpg" alt="Sécurité informatique">
    </div>
  </div>

  <footer class="footer-ensa">
    <div class="footer-container">
      <div class="footer-columns">
        <div class="footer-column">
          <div class="footer-logo">
            <img src="https://ensa.uit.ac.ma/wp-content/uploads/2025/03/LOGO-ENSA.png" alt="ENSA Kenitra">
          </div>
        </div>
        
        <div class="footer-column">
          <h4>Formation</h4>
          <ul class="footer-links">
            <li><a href="#">Formation initiale</a></li>
            <li><a href="#">Formation continue</a></li>
            <li><a href="#">Inscription</a></li>
          </ul>
        </div> 
        
        <div class="footer-column">
          <h4>Liens utiles</h4>
          <ul class="footer-links">
            <li><a href="#">Événements</a></li>
            <li><a href="#">Cérémonies</a></li>
            <li><a href="#">Appels d'offres</a></li>
            <li><a href="#">Clubs et Associations</a></li>
          </ul>
        </div>
        
        <div class="footer-column">
          <h4>Réseaux sociaux</h4>
          <div class="social-links">
            <a href="https://www.instagram.com/ensak.official" target="_blank" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
            <a href="https://ma.linkedin.com/company/ensa-kenitra-official" target="_blank" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
            <a href="https://www.facebook.com/ensakenitra" target="_blank" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
          </div>
          
          <h4 style="margin-top: 30px;">Contactez-nous</h4>
          <p>(+212) 5 37 37 67 65</p>
          <p>Université Ibn Tofail, BP 241, Kenitra – Maroc</p>
        </div>
      </div>
      
      <div class="footer-bottom">
        <p>École Nationale des Sciences Appliquées © 2025, Université Ibn Tofail. Tous droits réservés</p>
      </div>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/@emailjs/browser@4/dist/email.min.js"></script>
  <script>
    // Initialisation EmailJS (à remplacer par votre propre clé)
    emailjs.init({
      publicKey: "Mb21DjjO93ySp6IMz",
    });

    document.addEventListener('DOMContentLoaded', function() {
      const form = document.getElementById('passwordResetForm');
      const emailInput = document.getElementById('email');
      const emailError = document.getElementById('emailError');
      const alertMessage = document.getElementById('alertMessage');
      const alertText = document.getElementById('alertText');
      const alertIcon = document.getElementById('alertIcon');
      const submitBtn = document.getElementById('submitBtn');
      const loadingSpinner = document.getElementById('loadingSpinner');
      const buttonText = document.getElementById('buttonText');

      // Validation de l'email
      emailInput.addEventListener('input', function() {
        if (this.value.includes('@uit.ac.ma')) {
          this.classList.remove('is-invalid');
          emailError.classList.add('d-none');
        } else {
          this.classList.add('is-invalid');
          emailError.classList.remove('d-none');
        }
      });

      form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Validation avant envoi
        if (!emailInput.value.includes('@uit.ac.ma')) {
          showAlert('error', 'Veuillez utiliser votre email institutionnel (@uit.ac.ma)');
          emailInput.classList.add('is-invalid');
          emailError.classList.remove('d-none');
          return;
        }

        // Désactiver le bouton et afficher le spinner
        submitBtn.disabled = true;
        loadingSpinner.style.display = 'inline-block';
        buttonText.textContent = 'Envoi en cours...';

        try {
          // Envoyer la demande (simulation)
          await sendPasswordRequest(emailInput.value);
          
          // Afficher le message de succès
          showAlert('success', 'Un email de réinitialisation a été envoyé à votre adresse. Veuillez vérifier votre boîte de réception.');
          
          // Réinitialiser le formulaire
          form.reset();
        } catch (error) {
          console.error('Erreur:', error);
          showAlert('error', 'Une erreur est survenue. Vérifier votre email.');
        } finally {
          // Réactiver le bouton
          submitBtn.disabled = false;
          loadingSpinner.style.display = 'none';
          buttonText.innerHTML = '<i class="bi bi-send-fill"></i> Envoyer le nouveau mot de passe';
        }
      });

      function showAlert(type, message) {
        alertMessage.className = `alert-message alert-${type}`;
        alertText.textContent = message;
        
        if (type === 'success') {
          alertIcon.className = 'bi bi-check-circle-fill';
        } else {
          alertIcon.className = 'bi bi-exclamation-triangle-fill';
        }
        
        alertMessage.style.display = 'block';
        
        // Masquer l'alerte après 5 secondes
        setTimeout(() => {
          alertMessage.style.display = 'none';
        }, 5000);
      }
      function genererMotDePasse() {
    const caracteres = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
    let motDePasse = "";
    
    for (let i = 0; i < 8; i++) {
        const indexAleatoire = Math.floor(Math.random() * caracteres.length);
        motDePasse += caracteres[indexAleatoire];
    }
    
    return motDePasse;
}

     function sendPasswordRequest(email) {
  return fetch("reset-password-student.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded"
    },
    body: `email=${encodeURIComponent(email)}`
  })
  .then(response => {
    if (!response.ok) {
      throw new Error('Erreur réseau');
    }
    return response.json();
  })
  .then(data => {
    if (data.status === 'ok') {
      return emailjs.send("service_sjsyrk4", "template_9yl3k7q", {
        email: email,
        Password: data.password,
        role:"étudiant(e)"
      });
    } else if (data.status === 'not_found') {
      throw new Error("Aucun compte trouvé avec cette adresse email UIT.");
    } else {
      throw new Error(data.message || "Erreur lors de la réinitialisation");
    }
  })
  .catch(error => {
    console.error('Erreur:', error);
    throw new Error(error.message || "Échec de l'envoi de la demande");
  });
}

    });
  </script>
</body>
</html>