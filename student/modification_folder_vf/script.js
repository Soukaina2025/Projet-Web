    /*    document.addEventListener('DOMContentLoaded', function() {
            // Gestion de l'affichage des noms de fichiers multiples
            document.getElementById('projectFile').addEventListener('change', function(e) {
                const files = e.target.files;
                let fileNames = '';
                
                if (files.length > 0) {
                    fileNames = 'Fichiers sélectionnés: ';
                    for (let i = 0; i < files.length; i++) {
                        if (i > 0) fileNames += ', ';
                        fileNames += files[i].name;
                        
                        // Ajouter le nouveau fichier à la liste
                        const fileItem = document.createElement('div');
                        fileItem.className = 'file-item';
                        fileItem.innerHTML = `
                            <div class="file-info">
                                <i class="fas fa-file file-icon"></i>
                                <span>${files[i].name}</span>
                            </div>
                            <div class="file-actions">
                                <button class="file-action-btn" title="Télécharger">
                                    <i class="fas fa-download"></i>
                                </button>
                                <button class="file-action-btn delete-file" title="Supprimer">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        `;
                        document.getElementById('existingFiles').appendChild(fileItem);
                    }
                }
                
                document.getElementById('projectFileNames').textContent = fileNames;
            });
            
            // Gestion de l'image du projet
            document.getElementById('projectImage').addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(event) {
                        document.getElementById('projectImagePreview').src = event.target.result;
                    };
                    reader.readAsDataURL(file);
                    document.getElementById('projectImageName').textContent = file.name;
                }
            });
            
            // Gestion de la suppression des fichiers et de l'image
            document.addEventListener('click', function(e) {
                // Suppression des fichiers
                if (e.target.closest('.delete-file') && e.target.closest('.file-item')) {
                    const fileItem = e.target.closest('.file-item');
                    if (confirm('Êtes-vous sûr de vouloir supprimer ce fichier?')) {
                        fileItem.remove();
                    }
                }
                
                // Suppression de l'image
                if (e.target.closest('.delete-file') && !e.target.closest('.file-item')) {
                    if (confirm('Êtes-vous sûr de vouloir supprimer l\'image du projet?')) {
                        document.getElementById('projectImagePreview').src = '';
                        document.getElementById('projectImage').value = '';
                        document.getElementById('projectImageName').textContent = '';
                    }
                }
            });
            
            // Gestion de la soumission du formulaire
            document.getElementById('projectForm').addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Validation
                const projectName = document.getElementById('projectName').value.trim();
                const description = document.getElementById('description').value.trim();
                const category = document.getElementById('category').value;
                const projectType = document.getElementById('projectType').value;
                const supervisor = document.getElementById('supervisor').value;
                
                if (!projectName || !description || !category || !projectType || !supervisor) {
                    alert('Veuillez remplir tous les champs obligatoires (*)');
                    return;
                }
                
                // Simulation de soumission
                const submitBtn = this.querySelector('.submit-btn');
                const originalText = submitBtn.innerHTML;
                
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enregistrement en cours...';
                submitBtn.disabled = true;
                
                setTimeout(() => {
                    alert('Projet modifié avec succès!');
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                    
                    // Redirection vers la page des projets après modification
                    window.location.href = 'projects.html';
                }, 1500);
            });
            
            // Gestion du bouton Annuler
            document.getElementById('cancelBtn').addEventListener('click', function() {
                if (confirm('Voulez-vous vraiment annuler les modifications? Les changements non enregistrés seront perdus.')) {
                    window.location.href = 'projects.html';
                }
            });
        });*/

/*
         document.getElementById('cancelBtn').addEventListener('click', function() {
            if (confirm('Voulez-vous vraiment annuler la soumission de ce projet ? Tous les fichiers seront supprimés.')) {
                window.location.href = '../soumission_folder_vf/soumission.php';
            }
        });*/
