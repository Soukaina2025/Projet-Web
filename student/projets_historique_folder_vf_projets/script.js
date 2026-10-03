// Masquer tous les sous-menus au chargement
  const submenus = document.querySelectorAll('.submenu');
  submenus.forEach(submenu => {
      submenu.style.display = 'none';
  });

  // Gestion du menu utilisateur
  const toggleButton = document.getElementById('dropdownUser');
  if (toggleButton) {
      toggleButton.addEventListener('click', function(e) {
          e.preventDefault();
          const dropdownMenu = document.querySelector('.dropdown-menu');
          if (dropdownMenu) {
              dropdownMenu.style.display = dropdownMenu.style.display === 'block' ? 'none' : 'block';
          }
      });

      // Fermer le menu en cliquant à l'extérieur
      document.addEventListener('click', function(event) {
          const dropdownMenu = document.querySelector('.dropdown-menu');
          if (dropdownMenu && !toggleButton.contains(event.target) && !dropdownMenu.contains(event.target)) {
              dropdownMenu.style.display = 'none';
          }
      });
  };

// Fonction pour basculer l'affichage des sous-menus
function toggleMenu(menuId) {
  const submenu = document.getElementById(menuId);
  if (submenu) {
      submenu.style.display = submenu.style.display === 'block' ? 'none' : 'block';
  }
}










// Fonction pour basculer l'affichage des sous-menus
function toggleMenu(menuId) {
  const submenu = document.getElementById(menuId);
  if (submenu) {
      submenu.style.display = submenu.style.display === 'block' ? 'none' : 'block';
  }
}
















document.addEventListener('DOMContentLoaded', function() {
    // Configuration de la pagination
    const projectsContainer = document.getElementById('projects-container');
    const paginationNumbers = document.getElementById('pagination-numbers');
    
    const projectsPerPage = 3; // 3 cartes par page
    let currentPage = 1;
    
    // Fonction pour afficher les projets de la page spécifiée
    function displayProjects(page) {
        const projects = projectsContainer.getElementsByClassName('project-card');
        const startIndex = (page - 1) * projectsPerPage;
        const endIndex = startIndex + projectsPerPage;
        
        // Masquer tous les projets
        Array.from(projects).forEach(project => {
            project.style.display = 'none';
        });
        
        // Afficher seulement les projets de la page courante
        for (let i = startIndex; i < endIndex && i < projects.length; i++) {
            if (projects[i]) {
                projects[i].style.display = 'block';
            }
        }
    }
    
    // Fonction pour générer les numéros de page
    function setupPagination() {
        const projects = projectsContainer.getElementsByClassName('project-card');
        const pageCount = Math.ceil(projects.length / projectsPerPage);
        
        paginationNumbers.innerHTML = '';
        
        // Bouton Précédent
        const prevLi = document.createElement('li');
        prevLi.className = 'page-item';
        const prevLink = document.createElement('a');
        prevLink.className = 'page-link';
        prevLink.href = '#';
        prevLink.innerHTML = '&laquo;';
        prevLi.appendChild(prevLink);
        paginationNumbers.appendChild(prevLi);
        
        prevLink.addEventListener('click', function(e) {
            e.preventDefault();
            if (currentPage > 1) {
                currentPage--;
                displayProjects(currentPage);
                updatePagination();
            }
        });
        
        // Numéros de page
        for (let i = 1; i <= pageCount; i++) {
            const pageNumber = document.createElement('li');
            pageNumber.className = 'page-item';
            
            const pageLink = document.createElement('a');
            pageLink.className = 'page-link';
            pageLink.href = '#';
            pageLink.textContent = i;
            
            if (i === currentPage) {
                pageNumber.classList.add('active');
            }
            
            pageNumber.appendChild(pageLink);
            paginationNumbers.appendChild(pageNumber);
            
            pageLink.addEventListener('click', function(e) {
                e.preventDefault();
                currentPage = i;
                displayProjects(currentPage);
                updatePagination();
            });
        }
        
        // Bouton Suivant
        const nextLi = document.createElement('li');
        nextLi.className = 'page-item';
        const nextLink = document.createElement('a');
        nextLink.className = 'page-link';
        nextLink.href = '#';
        nextLink.innerHTML = '&raquo;';
        nextLi.appendChild(nextLink);
        paginationNumbers.appendChild(nextLi);
        
        nextLink.addEventListener('click', function(e) {
            e.preventDefault();
            if (currentPage < pageCount) {
                currentPage++;
                displayProjects(currentPage);
                updatePagination();
            }
        });
    }
    
    // Fonction pour mettre à jour l'état des boutons
    function updatePagination() {
        const projects = projectsContainer.getElementsByClassName('project-card');
        const pageCount = Math.ceil(projects.length / projectsPerPage);
        const pageItems = paginationNumbers.querySelectorAll('.page-item');
        
        // Mettre à jour l'état actif des numéros de page
        pageItems.forEach((item, index) => {
            // Ignorer les boutons précédent/suivant (premier et dernier éléments)
            if (index > 0 && index < pageItems.length - 1) {
                const pageNum = index; // Car index 1 = page 1 (index 0 est le bouton précédent)
                item.classList.toggle('active', pageNum === currentPage);
            }
        });
        
        // Désactiver les boutons précédent/suivant si nécessaire
        pageItems[0].classList.toggle('disabled', currentPage === 1); // Précédent
        pageItems[pageItems.length - 1].classList.toggle('disabled', currentPage === pageCount); // Suivant
    }
    
    // Initialisation
    displayProjects(currentPage);
    setupPagination();
    updatePagination();
    
    // Gestion du menu utilisateur
    const toggleButton = document.getElementById('dropdownUser');
    if (toggleButton) {
        toggleButton.addEventListener('click', function(e) {
            e.preventDefault();
            const dropdownMenu = document.querySelector('.dropdown-menu');
            if (dropdownMenu) {
                dropdownMenu.style.display = dropdownMenu.style.display === 'block' ? 'none' : 'block';
            }
        });
        
        document.addEventListener('click', function(event) {
            const dropdownMenu = document.querySelector('.dropdown-menu');
            if (dropdownMenu && !toggleButton.contains(event.target) && !dropdownMenu.contains(event.target)) {
                dropdownMenu.style.display = 'none';
            }
        });
    }
    
    // Fonction pour basculer l'affichage des sous-menus
    window.toggleMenu = function(menuId) {
        const submenu = document.getElementById(menuId);
        if (submenu) {
            submenu.style.display = submenu.style.display === 'block' ? 'none' : 'block';
        }
    }
});