        // Script pour gérer les boutons "Voir" des projets
        document.querySelectorAll('.btn-outline-primary').forEach(button => {
            button.addEventListener('click', function() {
                // Ici vous pouvez ajouter la logique pour afficher les détails du projet
                alert('Affichage des détails du projet');
            });
        });