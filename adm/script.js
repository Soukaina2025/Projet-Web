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