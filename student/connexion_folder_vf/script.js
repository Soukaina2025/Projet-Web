document.addEventListener('DOMContentLoaded', function() {
    // Gestion de la connexion
    const loginForm = document.getElementById('studentLoginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const apogee = document.getElementById('apogee');
            const password = document.getElementById('password');
            
            // Validation basique côté client
            if (!apogee.value.trim()) {
                alert('Veuillez entrer votre numéro Apogée');
                apogee.focus();
                return;
            }
            
            if (!password.value.trim()) {
                alert('Veuillez entrer votre mot de passe');
                password.focus();
                return;
            }
            
            // Soumission du formulaire
            this.submit();
        });
    }

    // Gestion du mot de passe oublié
    const forgotPasswordLink = document.getElementById('forgotPasswordLink');
    const passwordResetModal = document.getElementById('passwordResetModal');
    const closeModal = document.querySelector('.close-modal');
    
    if (forgotPasswordLink && passwordResetModal && closeModal) {
        forgotPasswordLink.addEventListener('click', function(e) {
            e.preventDefault();
            passwordResetModal.style.display = 'block';
        });
        
        closeModal.addEventListener('click', function() {
            passwordResetModal.style.display = 'none';
        });
        
        window.addEventListener('click', function(e) {
            if (e.target === passwordResetModal) {
                passwordResetModal.style.display = 'none';
            }
        });
    }
    
    // Gestion du formulaire de réinitialisation
    const resetForm = document.getElementById('passwordResetForm');
    if (resetForm) {
        resetForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const email = document.getElementById('resetEmail');
            if (!email.value || !email.value.includes('@')) {
                alert('Veuillez entrer une adresse email valide');
                return;
            }
            
            // Simulation d'envoi
            alert('Un lien de réinitialisation a été envoyé à ' + email.value);
            passwordResetModal.style.display = 'none';
        });
    }
    
    // Gestion du bouton Google
    const googleBtn = document.querySelector('.google-btn');
    if (googleBtn) {
        googleBtn.addEventListener('click', function() {
            alert("La connexion avec Google n'est pas encore disponible");
        });
    }
});










document.addEventListener('DOMContentLoaded', function() {
    const sections = document.querySelectorAll('.reglement-section');
    const indicators = document.querySelectorAll('.indicator');
    const prevBtn = document.getElementById('prevSection');
    const nextBtn = document.getElementById('nextSection');
    let currentSection = 0;
  
    // Vérifier la taille de l'écran
    const isMobile = window.matchMedia("(max-width: 992px)").matches;
  
    // Initialisation
    function initSlider() {
      if (isMobile) {
        // Mode mobile - tout afficher en vertical
        sections.forEach(section => {
          section.style.display = 'block';
          section.classList.remove('active');
        });
        // Masquer les contrôles inutiles
        if (prevBtn) prevBtn.style.display = 'none';
        if (nextBtn) nextBtn.style.display = 'none';
      } else {
        // Mode desktop - slider horizontal
        sections[0].classList.add('active');
        indicators[0].classList.add('active');
        updateButtons();
      }
    }
  
    // Fonction pour afficher une section (desktop seulement)
    function showSection(index) {
      if (isMobile) return;
      
      sections.forEach(section => section.classList.remove('active'));
      indicators.forEach(indicator => indicator.classList.remove('active'));
      
      sections[index].classList.add('active');
      indicators[index].classList.add('active');
      currentSection = index;
      updateButtons();
    }
  
    // Mettre à jour les boutons (desktop seulement)
    function updateButtons() {
      if (isMobile) return;
      prevBtn.disabled = currentSection === 0;
      nextBtn.disabled = currentSection === sections.length - 1;
    }
  
    // Écouteurs d'événements (uniquement actifs sur desktop)
    if (!isMobile) {
      if (prevBtn) prevBtn.addEventListener('click', () => currentSection > 0 && showSection(currentSection - 1));
      if (nextBtn) nextBtn.addEventListener('click', () => currentSection < sections.length - 1 && showSection(currentSection + 1));
      
      indicators.forEach(indicator => {
        indicator.addEventListener('click', function() {
          const sectionIndex = parseInt(this.getAttribute('data-section')) - 1;
          showSection(sectionIndex);
        });
      });
  
      document.addEventListener('keydown', function(e) {
        if (e.key === 'ArrowLeft' && currentSection > 0) {
          showSection(currentSection - 1);
        } else if (e.key === 'ArrowRight' && currentSection < sections.length - 1) {
          showSection(currentSection + 1);
        }
      });
    }
  
    // Réinitialiser lors du redimensionnement
    window.addEventListener('resize', initSlider);
  
    // Initialisation
    initSlider();

});



  