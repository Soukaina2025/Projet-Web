


/*
document.addEventListener("DOMContentLoaded", function () {
    const toggleButton = document.getElementById("user-menu-toggle");
    const dropdownMenu = document.getElementById("user-action-menu");

    toggleButton.addEventListener("click", function (e) {
        e.preventDefault();
        dropdownMenu.style.display = dropdownMenu.style.display === "block" ? "none" : "block";
    });



    // Carousel navigation
    const carouselLinks = document.querySelectorAll('.carousel-navigation-link');
    carouselLinks.forEach(link => {
        link.addEventListener('click', function () {
            const targetId = this.getAttribute('data-carousel-target-id');
            const currentItem = document.querySelector('.carousel-item.active');
            if (currentItem) currentItem.classList.remove('active');

            const newItem = document.getElementById(targetId);
            if (newItem) newItem.classList.add('active');
        });
    });

    // Optional: close dropdown when clicking outside
    document.addEventListener("click", function (event) {
        if (!dropdownMenu.contains(event.target) && !toggleButton.contains(event.target)) {
            dropdownMenu.style.display = "none";
        }
    });
});


// Sélectionner le bouton, la barre de filtrage et les cartes
const toggleButton = document.getElementById('toggleSidebar');
const filterSidebar = document.getElementById('filterSidebar');
const cardList = document.querySelector('.card-list');

// Ajouter un événement au bouton pour afficher/masquer la barre de filtrage
toggleButton.addEventListener('click', () => {
    // Activer ou désactiver la classe active sur la barre de filtrage
    filterSidebar.classList.toggle('active');
    
    // Déplacer les cartes pour faire de la place à la barre de filtrage
    cardList.classList.toggle('shift');
});



// Masquer tous les sous-menus au chargement de la page
window.onload = function() {
  var submenus = document.querySelectorAll('.submenu');
  submenus.forEach(function(submenu) {
    submenu.style.display = 'none';
  });
};


document.addEventListener("DOMContentLoaded", function () {
    const toggleButton = document.getElementById("user-menu-toggle");
    const dropdownMenu = document.getElementById("user-action-menu");

    if (toggleButton && dropdownMenu) {
        toggleButton.addEventListener("click", function (e) {
            e.preventDefault();
            dropdownMenu.style.display = dropdownMenu.style.display === "block" ? "none" : "block";
        });

        // Fermer le menu en cliquant à l'extérieur
        document.addEventListener("click", function (event) {
            if (!dropdownMenu.contains(event.target) && !toggleButton.contains(event.target)) {
                dropdownMenu.style.display = "none";
            }
        });
    }
});


document.addEventListener('DOMContentLoaded', function() {
  const swiper = new Swiper('.swiper', {
      loop: true,
      spaceBetween: 30,
      autoplay: {
          delay: 2000,
          disableOnInteraction: false,
          pauseOnMouseEnter: true,
      },
      pagination: {
          el: '.swiper-pagination',
          clickable: true,
          dynamicBullets: true,
      },
      navigation: {
          nextEl: '.swiper-button-next',
          prevEl: '.swiper-button-prev',
      },
      breakpoints: {
          0: { slidesPerView: 1 },
          768: { slidesPerView: 2 },
          1024: { slidesPerView: 3 }
      }
  });

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
  }
});


// Fonction pour basculer l'affichage des sous-menus
function toggleMenu(menuId) {
  const submenu = document.getElementById(menuId);
  if (submenu) {
      submenu.style.display = submenu.style.display === 'block' ? 'none' : 'block';
  }
}


function incrementCounter(icon) {
  const countSpan = icon.nextElementSibling;
  if (countSpan) {
      let currentCount = parseInt(countSpan.textContent, 10) || 0;
      countSpan.textContent = currentCount + 1;
  }
}








// Fonction pour réinitialiser les filtres
function resetFilters() {
    const form = document.getElementById('filterForm');
    const checkboxes = form.querySelectorAll('input[type="checkbox"]');
    const searchInput = form.querySelector('input[name="search"]');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = false;
    });
    
    if (searchInput) {
        searchInput.value = '';
    }
    
    form.submit();
}

// Fonction pour fermer le sidebar
function closeSidebar() {
    const sidebar = document.getElementById('bsbSidebar1');
    if (sidebar) {
        const bsSidebar = bootstrap.Offcanvas.getInstance(sidebar);
        bsSidebar.hide();
    }
}







// Modifiez votre code Swiper pour le rendre réutilisable
let swiperInstance = null;

function initSwiper() {
    swiperInstance = new Swiper(".wrapper", {
        loop: true,
        spaceBetween: 30,
        autoplay: {
            delay: 5000,
            disableOnInteraction: false,
            pauseOnMouseEnter: true,
        },
        pagination: {
            el: ".swiper-pagination",
            clickable: true,
            dynamicBullets: true,
        },
        navigation: {
            nextEl: ".swiper-button-next",
            prevEl: ".swiper-button-prev",
        },
        breakpoints: {
            0: { slidesPerView: 1 },
            768: { slidesPerView: 2 },
            1024: { slidesPerView: 3 }
        }
    });
}

// Détruire et réinitialiser Swiper après filtrage
function resetSwiper() {
    if (swiperInstance) {
        swiperInstance.destroy(true, true);
    }
    initSwiper();
}

// Initialiser Swiper au chargement
document.addEventListener('DOMContentLoaded', function() {
    initSwiper();
    
    // Réinitialiser Swiper après soumission du formulaire
    const filterForm = document.getElementById('filterForm');
    if (filterForm) {
        filterForm.addEventListener('submit', function(e) {
            // Pour une solution AJAX, vous devriez prévenir le comportement par défaut
            // e.preventDefault();
            // ... faire la requête AJAX ici ...
            
            // Pour une solution simple avec rechargement de page:
            // Laisser le formulaire se soumettre normalement
            // Swiper sera réinitialisé au prochain DOMContentLoaded
        });
    }
});

// Réinitialiser Swiper aussi lorsque la page est entièrement chargée
window.addEventListener('load', resetSwiper);



document.getElementById('filterForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    fetch(this.action + '?' + new URLSearchParams(new FormData(this)), {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.text())
    .then(html => {
        document.querySelector('.swiper-wrapper').innerHTML = html;
        resetSwiper();
    });
});
*/


new Swiper(".wrapper", {
  loop: true,
  spaceBetween: 30,

  // Autoplay
  autoplay: {
    delay: 5000,
    disableOnInteraction: false,
    pauseOnMouseEnter: true,
  },

  // Pagination bullets
  pagination: {
    el: ".swiper-pagination",
    clickable: true,
    dynamicBullets: true,
   },

  // Navigation arrows
  navigation: {
    nextEl: ".swiper-button-next",
    prevEl: ".swiper-button-prev",
  },

  // Responsive breakpoints
  breakpoints: {
    0: {
      slidesPerView: 1,
    },
    768: {
      slidesPerView: 2,
    },
    1024: {
      slidesPerView: 3,
    },
  },
});




// Variable globale pour l'instance Swiper
let swiperInstance = null;

// Fonction d'initialisation de Swiper
function initSwiper() {
    swiperInstance = new Swiper(".swiper", {
        loop: true,
        spaceBetween: 30,
        slidesPerView: 'auto', // Modifié pour s'adapter à la largeur des cartes
        centeredSlides: true,


        autoplay: {
            delay: 5000,
            disableOnInteraction: false,
            pauseOnMouseEnter: true,
        },
        pagination: {
            el: ".swiper-pagination",
            clickable: true,
            dynamicBullets: true,
        },
        navigation: {
            nextEl: ".swiper-button-next",
            prevEl: ".swiper-button-prev",
        },
        breakpoints: {
            0: { 
                slidesPerView: 1,
                spaceBetween: 20
            },
            768: { 
                slidesPerView: 'auto',
                spaceBetween: 30
            },
            1024: { 
                slidesPerView: 'auto',
                spaceBetween: 30
            }
        },


        // Ajoutez cette configuration pour améliorer le loop
        loopAdditionalSlides: 1, // Ajoute des slides supplémentaires pour un meilleur effet de boucle
        loopFillGroupWithBlank: false // Empêche l'ajout de slides vides
    });
}

// Fonction pour réinitialiser Swiper
function resetSwiper() {
    if (swiperInstance) {
        swiperInstance.destroy(true, true);
    }
    initSwiper();
}

// Gestion du menu utilisateur
function setupUserMenu() {
    const toggleButton = document.getElementById("dropdownUser");
    const dropdownMenu = document.querySelector('.dropdown-menu');

    if (toggleButton && dropdownMenu) {
        toggleButton.addEventListener("click", function(e) {
            e.preventDefault();
            dropdownMenu.style.display = dropdownMenu.style.display === "block" ? "none" : "block";
        });

        document.addEventListener("click", function(event) {
            if (!dropdownMenu.contains(event.target)) {
                dropdownMenu.style.display = "none";
            }
        });
    }
}

// Gestion des sous-menus
function setupSubmenus() {
    // Masquer tous les sous-menus au chargement
    const submenus = document.querySelectorAll('.submenu');
    submenus.forEach(submenu => {
        submenu.style.display = 'none';
    });
}

// Gestion du sidebar de filtrage
function setupFilterSidebar() {
    const sidebar = document.getElementById('bsbSidebar1');
    if (sidebar) {
        const closeButton = sidebar.querySelector('.close-btn');
        if (closeButton) {
            closeButton.addEventListener('click', closeSidebar);
        }
    }
}

// Fonction pour fermer le sidebar
function closeSidebar() {
    const sidebar = document.getElementById('bsbSidebar1');
    if (sidebar) {
        const bsSidebar = bootstrap.Offcanvas.getInstance(sidebar);
        if (bsSidebar) {
            bsSidebar.hide();
        }
    }
}

// Fonction pour basculer l'affichage des sous-menus
function toggleMenu(menuId) {
    const submenu = document.getElementById(menuId);
    if (submenu) {
        submenu.style.display = submenu.style.display === 'block' ? 'none' : 'block';
    }
}






// Fonction pour incrémenter les compteurs
function incrementCounter(icon) {
    const countSpan = icon.nextElementSibling;
    if (countSpan) {
        let currentCount = parseInt(countSpan.textContent, 10) || 0;
        countSpan.textContent = currentCount + 1;
    }
}

// Fonction pour réinitialiser les filtres
function resetFilters() {
    const form = document.getElementById('filterForm');
    if (form) {
        const checkboxes = form.querySelectorAll('input[type="checkbox"]');
        const searchInput = form.querySelector('input[name="search"]');
        
        checkboxes.forEach(checkbox => {
            checkbox.checked = false;
        });
        
        if (searchInput) {
            searchInput.value = '';
        }
        
        form.submit();
    }
}

// Gestion du formulaire de filtrage
function setupFilterForm() {
    const filterForm = document.getElementById('filterForm');
    if (filterForm) {
        filterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Créer un objet FormData à partir du formulaire
            const formData = new FormData(filterForm);
            const params = new URLSearchParams();
            
            // Ajouter tous les paramètres du formulaire
            for (const [key, value] of formData.entries()) {
                params.append(key, value);
            }
            
            // Faire la requête AJAX
            fetch(window.location.pathname + '?' + params.toString(), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.text();
            })
            .then(html => {
                // Parser la réponse HTML
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                
                // Extraire les nouveaux projets
                const newProjects = doc.querySelector('.swiper-wrapper').innerHTML;
                
                // Mettre à jour le contenu
                document.querySelector('.swiper-wrapper').innerHTML = newProjects;
                
                // Réinitialiser Swiper
                resetSwiper();
            })
            .catch(error => {
                console.error('Error:', error);
                // En cas d'erreur, soumettre le formulaire normalement
                filterForm.submit();
            });
        });
    }
}

// Initialisation au chargement du DOM
document.addEventListener('DOMContentLoaded', function() {
    initSwiper();
    setupUserMenu();
    setupSubmenus();
    setupFilterSidebar();
    setupFilterForm();
});

// Réinitialisation au chargement complet de la page
window.addEventListener('load', function() {
    resetSwiper();
});



// Fonction pour gérer la recherche
function setupSearch() {
    const searchForm = document.getElementById('searchForm');
    const searchInput = searchForm ? searchForm.querySelector('input[name="search"]') : null;
    
    if (searchInput) {
        // Utilisation d'un debounce pour limiter les requêtes
        let debounceTimer;
        
        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                submitSearchForm();
            }, 500);
        });
        
        // Empêcher la soumission du formulaire par défaut
        if (searchForm) {
            searchForm.addEventListener('submit', function(e) {
                e.preventDefault();
                submitSearchForm();
            });
        }
    }
}

// Fonction pour soumettre le formulaire de recherche
function submitSearchForm() {
    const searchForm = document.getElementById('searchForm');
    const filterForm = document.getElementById('filterForm');
    
    if (searchForm) {
        // Créer un objet URLSearchParams pour la recherche
        const searchParams = new URLSearchParams(new FormData(searchForm));
        
        // Ajouter les filtres actuels s'ils existent
        if (filterForm) {
            const filterParams = new URLSearchParams(new FormData(filterForm));
            filterParams.forEach((value, key) => {
                // Ne pas écraser le terme de recherche
                if (key !== 'search' && !searchParams.has(key)) {
                    searchParams.append(key, value);
                }
            });
        }
        
        // Faire la requête AJAX
        fetch(window.location.pathname + '?' + searchParams.toString(), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.text();
        })
        .then(html => {
            // Parser la réponse HTML
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            
            // Extraire les nouveaux projets
            const newProjects = doc.querySelector('.swiper-wrapper').innerHTML;
            
            // Mettre à jour le contenu
            document.querySelector('.swiper-wrapper').innerHTML = newProjects;
            
            // Réinitialiser Swiper
            resetSwiper();
        })
        .catch(error => {
            console.error('Error:', error);
            // En cas d'erreur, soumettre le formulaire normalement
            window.location.search = searchParams.toString();
        });
    }
}

// Modifiez l'initialisation pour inclure la recherche
document.addEventListener('DOMContentLoaded', function() {
    initSwiper();
    setupUserMenu();
    setupSubmenus();
    setupFilterSidebar();
    setupSearch(); // Ajoutez cette ligne
    setupFilterForm();
});











































// Fonction pour charger une page via AJAX
function loadPage(page) {
    const params = new URLSearchParams(window.location.search);
    params.set('page', page);
    
    fetch(window.location.pathname + '?' + params.toString(), {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.text())
    .then(html => {
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        
        // Mettre à jour les projets
        document.getElementById('projects-grid').innerHTML = 
            doc.getElementById('projects-grid').innerHTML;
            
        // Mettre à jour la pagination
        const newPagination = doc.querySelector('.pagination');
        if (newPagination) {
            document.querySelector('.pagination').outerHTML = newPagination.outerHTML;
        }
        
        // Réattacher les événements
        attachPaginationEvents();
    });
}

// Fonction pour attacher les événements de pagination
function attachPaginationEvents() {
    document.querySelectorAll('.page-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            if (this.getAttribute('href')) {
                const page = new URL(this.href).searchParams.get('page');
                loadPage(page);
            }
        });
    });
}

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    attachPaginationEvents();
});





// Fonction pour incrémenter les compteurs
function incrementCounter(icon) {
    const countSpan = icon.nextElementSibling;
    if (countSpan) {
        let currentCount = parseInt(countSpan.textContent, 10) || 0;
        countSpan.textContent = currentCount + 1;
        
        // Récupérer l'ID du projet (vous devrez l'ajouter dans votre HTML)
        const card = icon.closest('.card');
        const projectId = card.dataset.projectId;
        
        // Déterminer le type d'action (aimer, clap, support)
        const actionType = icon.classList.contains('fa-heart') ? 'aimer' : 
                         icon.classList.contains('fa-hands-clapping') ? 'clap' : 
                         icon.classList.contains('fa-thumbs-up') ? 'support' : null;
        
        if (projectId && actionType) {
            // Envoyer la mise à jour au serveur
            updateActionCount(projectId, actionType, currentCount + 1);
        }
    }
}





// Fonction pour incrémenter les compteurs
function incrementCounter(icon) {
    const countSpan = icon.nextElementSibling;
    if (countSpan) {
        let currentCount = parseInt(countSpan.textContent, 10) || 0;
        countSpan.textContent = currentCount + 1;
        
        // Récupérer l'ID du projet (vous devrez l'ajouter dans votre HTML)
        const card = icon.closest('.card');
        const projectId = card.dataset.projectId;
        
        // Déterminer le type d'action (aimer, clap, support)
        const actionType = icon.classList.contains('fa-heart') ? 'aimer' : 
                         icon.classList.contains('fa-hands-clapping') ? 'clap' : 
                         icon.classList.contains('fa-thumbs-up') ? 'support' : null;
        
        if (projectId && actionType) {
            // Envoyer la mise à jour au serveur
            updateActionCount(projectId, actionType, currentCount + 1);
        }
    }
}