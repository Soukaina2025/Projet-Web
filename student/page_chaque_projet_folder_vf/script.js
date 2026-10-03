
// Vous pouvez ajouter ici des fonctionnalités interactives si nécessaire
document.addEventListener('DOMContentLoaded', function() {
    // Exemple: Animation des éléments d'évaluation
    const evaluationItems = document.querySelectorAll('.evaluation-item');
    evaluationItems.forEach((item, index) => {
        item.style.opacity = '0';
        item.style.transform = 'translateY(20px)';
        item.style.transition = `all 0.5s ease ${index * 0.1}s`;
        
        setTimeout(() => {
            item.style.opacity = '1';
            item.style.transform = 'translateY(0)';
        }, 100);
    });
    
    // Gestion du téléchargement des livrables
    const downloadButtons = document.querySelectorAll('[download]');
    downloadButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            // Ici vous pourriez ajouter un suivi des téléchargements
            console.log(`Téléchargement du fichier: ${this.previousElementSibling.textContent.trim()}`);
        });
    });
});
