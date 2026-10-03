
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



  