/**
 * Initialisation après le chargement du DOM
 */
document.addEventListener('DOMContentLoaded', function() {
    
    /* Animation des éléments de détail */
    const detailItems = document.querySelectorAll('.detail-item');
    detailItems.forEach((item, index) => {
        item.style.opacity = '0';
        item.style.transform = 'translateY(20px)';
        item.style.transition = `all 0.5s ease ${index * 0.1}s`;
        
        setTimeout(() => {
            item.style.opacity = '1';
            item.style.transform = 'translateY(0)';
        }, 100);
    });
    
    /* Gestion des téléchargements */
    const downloadButtons = document.querySelectorAll('[download]');
    downloadButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            console.log(`Téléchargement: ${this.previousElementSibling.textContent.trim()}`);
        });
    });

    /* Animation des profils étudiants */
    const studentProfiles = document.querySelectorAll('.student-profile');
    studentProfiles.forEach(profile => {
        profile.style.opacity = '0';
        profile.style.transform = 'translateX(20px)';
        profile.style.transition = 'all 0.5s ease';
        
        setTimeout(() => {
            profile.style.opacity = '1';
            profile.style.transform = 'translateX(0)';
        }, 200);
    });
});