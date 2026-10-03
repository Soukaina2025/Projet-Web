/*const documents = [
    {
        id: 1,
        title: "Rapport Final Projet X",
        description: "Rapport détaillé du projet X avec analyse des résultats et conclusions.",
        type: "pdf",
        date: "15/05/2024",
        size: "2.4 MB",
        url: "#"
    },
    {
        id: 2,
        title: "Présentation Soutenance",
        description: "Diaporama utilisé pour la soutenance du projet Y.",
        type: "ppt",
        date: "10/05/2024",
        size: "5.1 MB",
        url: "#"
    },
    {
        id: 3,
        title: "Code Source Application",
        description: "Dépôt Git contenant tout le code source de l'application Z.",
        type: "code",
        date: "01/05/2024",
        size: "15.2 MB",
        url: "#"
    },
    {
        id: 4,
        title: "Documentation Technique",
        description: "Documentation complète de l'API développée.",
        type: "pdf",
        date: "20/04/2024",
        size: "1.8 MB",
        url: "#"
    },
    {
        id: 5,
        title: "Présentation Intermédiaire",
        description: "Diaporama présenté lors de la revue intermédiaire.",
        type: "ppt",
        date: "05/04/2024",
        size: "3.7 MB",
        url: "#"
    },
    {
        id: 6,
        title: "Scripts d'Analyse",
        description: "Scripts Python utilisés pour l'analyse des données.",
        type: "code",
        date: "28/03/2024",
        size: "0.8 MB",
        url: "#"
    }
];

// Function to render documents
function renderDocuments(filter = 'all') {
    const container = document.getElementById('documents-container');
    container.innerHTML = '';
    
    const filteredDocs = filter === 'all' ? documents : documents.filter(doc => doc.type === filter);
    
    filteredDocs.forEach(doc => {
        let iconClass, badgeClass;
        
        switch(doc.type) {
            case 'pdf':
                iconClass = 'bi bi-file-earmark-pdf';
                badgeClass = 'badge-pdf';
                break;
            case 'ppt':
                iconClass = 'bi bi-file-earmark-ppt';
                badgeClass = 'badge-ppt';
                break;
            case 'code':
                iconClass = 'bi bi-file-earmark-code';
                badgeClass = 'badge-code';
                break;
        }
        
        const docElement = `
            <div class="col-md-4 col-sm-6">
                <div class="card document-card h-100">
                    <div class="card-body text-center p-4">
                        <i class="${iconClass} document-icon"></i>
                        <span class="badge ${badgeClass} mb-2">${doc.type.toUpperCase()}</span>
                        <h5 class="card-title">${doc.title}</h5>
                        <p class="card-text">${doc.description}</p>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <small class="text-muted">${doc.date}</small>
                            <small class="text-muted">${doc.size}</small>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent border-0 pb-3 pt-0 text-center">
                        <a href="${doc.url}" class="download-btn" download>
                            <i class="bi bi-download"></i> Télécharger
                        </a>
                    </div>
                </div>
            </div>
        `;
        
        container.innerHTML += docElement;
    });
}

// Initial render
document.addEventListener('DOMContentLoaded', () => {
    renderDocuments();
    
    // Filter buttons event listeners
    document.querySelectorAll('[data-filter]').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('[data-filter]').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            renderDocuments(this.dataset.filter);
        });
    });
    
    // Search functionality
    const searchInput = document.querySelector('.search-box input');
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const cards = document.querySelectorAll('.document-card');
        
        cards.forEach(card => {
            const title = card.querySelector('.card-title').textContent.toLowerCase();
            const description = card.querySelector('.card-text').textContent.toLowerCase();
            
            if (title.includes(searchTerm) || description.includes(searchTerm)) {
                card.parentElement.style.display = 'block';
            } else {
                card.parentElement.style.display = 'none';
            }
        });
    });
});*/

/*
document.addEventListener('DOMContentLoaded', function() {
    // Filtrage par type
    const filterButtons = document.querySelectorAll('.filter-button');
    const documentItems = document.querySelectorAll('.document-item');
    
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            const filter = this.getAttribute('data-filter');
            
            // Mettre à jour l'état actif des boutons
            filterButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            // Filtrer les éléments
            documentItems.forEach(item => {
                if (filter === 'all' || item.getAttribute('data-type') === filter) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    });
    
    // Recherche
    const searchInput = document.getElementById('search-input');
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        
        documentItems.forEach(item => {
            const title = item.querySelector('.card-title').textContent.toLowerCase();
            const category = item.querySelector('.card-text').textContent.toLowerCase();
            const filename = item.querySelector('small').textContent.toLowerCase();
            
            if (title.includes(searchTerm) || category.includes(searchTerm) || filename.includes(searchTerm)) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    });
});
*/