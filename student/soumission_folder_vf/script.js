    /*    document.addEventListener('DOMContentLoaded', function() {
            // Gestion de l'affichage des noms de fichiers multiples
            document.getElementById('projectFile').addEventListener('change', function(e) {
                const files = e.target.files;
                let fileNames = 'Aucun fichier sélectionné';
                
                if (files.length > 0) {
                    fileNames = '';
                    for (let i = 0; i < files.length; i++) {
                        if (i > 0) fileNames += ', ';
                        fileNames += files[i].name;
                    }
                }
                
                document.getElementById('projectFileNames').textContent = fileNames;
            });
            
            document.getElementById('projectImage').addEventListener('change', function(e) {
                const fileName = e.target.files[0] ? e.target.files[0].name : 'Aucune image sélectionnée';
                document.getElementById('projectImageName').textContent = fileName;
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
                const files = document.getElementById('projectFile').files;
                
                if (!projectName || !description || !category || !projectType || !supervisor || files.length === 0) {
                    alert('Veuillez remplir tous les champs obligatoires (*)');
                    return;
                }
                
                // Simulation de soumission
                const submitBtn = this.querySelector('.submit-btn');
                
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Envoi en cours...';
                submitBtn.disabled = true;
                
                setTimeout(() => {
                    alert('Projet soumis avec succès!');
                    this.reset();
                    document.getElementById('projectFileNames').textContent = 'Aucun fichier sélectionné';
                    document.getElementById('projectImageName').textContent = 'Aucune image sélectionnée';
                    submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Soumettre le projet';
                    submitBtn.disabled = false;
                }, 1500);
            });
        });
*/

/*
 // Gestion de l'affichage des noms de fichiers
        document.getElementById('projectFile').addEventListener('change', function(e) {
            const fileName = e.target.files[0] ? e.target.files[0].name : 'Aucun fichier sélectionné';
            document.getElementById('projectFileNames').textContent = fileName;
        });

        document.getElementById('additionalFiles').addEventListener('change', function(e) {
            const files = e.target.files;
            let fileNames = 'Aucun fichier sélectionné';
            
            if (files.length > 0) {
                fileNames = '';
                for (let i = 0; i < Math.min(files.length, 3); i++) {
                    fileNames += files[i].name + (i < Math.min(files.length, 3) - 1 ? ', ' : '');
                }
                if (files.length > 3) {
                    fileNames += ' (+' + (files.length - 3) + ' autres)';
                }
            }
            
            document.getElementById('additionalFilesNames').textContent = fileNames;
        });

        document.getElementById('projectImage').addEventListener('change', function(e) {
            const fileName = e.target.files[0] ? e.target.files[0].name : 'Aucune image sélectionnée';
            document.getElementById('projectImageName').textContent = fileName;
        });

        // Validation avant soumission
        document.getElementById('projectForm').addEventListener('submit', function(e) {
            const additionalFiles = document.getElementById('additionalFiles').files;
            if (additionalFiles.length > 3) {
                alert('Vous ne pouvez sélectionner que 3 fichiers supplémentaires maximum');
                e.preventDefault();
            }
        });*/


        // Gestion de l'affichage des fichiers sélectionnés
        document.getElementById('projectFile').addEventListener('change', function(e) {
            if (this.files.length > 0) {
                document.getElementById('projectFileName').textContent = this.files[0].name;
                document.getElementById('projectFilePreview').style.display = 'block';
            }
        });

        document.getElementById('additionalFiles').addEventListener('change', function(e) {
            const preview = document.getElementById('additionalFilesPreview');
            preview.innerHTML = '';
            
            if (this.files.length > 0) {
                // Limiter à 3 fichiers
                const filesToShow = Array.from(this.files).slice(0, 3);
                
                filesToShow.forEach((file, index) => {
                    const fileItem = document.createElement('div');
                    fileItem.className = 'file-item';
                    
                    // Déterminer l'icône en fonction de l'extension
                    const ext = file.name.split('.').pop().toLowerCase();
                    let iconClass = 'fa-file';
                    
                    if (ext === 'pdf') iconClass = 'fa-file-pdf';
                    else if (['ppt', 'pptx'].includes(ext)) iconClass = 'fa-file-powerpoint';
                    else if (['zip', 'rar'].includes(ext)) iconClass = 'fa-file-archive';
                    else if (['jpg', 'jpeg', 'png', 'gif'].includes(ext)) iconClass = 'fa-image';
                    else if (['py', 'java', 'cpp', 'c', 'js', 'html', 'css', 'php', 'txt'].includes(ext)) iconClass = 'fa-file-code';
                    
                    fileItem.innerHTML = `
                        <div class="file-info">
                            <i class="fas ${iconClass} file-icon"></i>
                            <span class="file-name">${file.name}</span>
                        </div>
                        <button type="button" class="delete-file" onclick="removeAdditionalFile(${index})">
                            <i class="fas fa-trash"></i>
                        </button>
                    `;
                    
                    preview.appendChild(fileItem);
                });
            }
        });

        document.getElementById('projectImage').addEventListener('change', function(e) {
            if (this.files.length > 0) {
                document.getElementById('projectImageName').textContent = this.files[0].name;
                document.getElementById('projectImagePreview').style.display = 'block';
            }
        });

        // Fonctions pour supprimer les fichiers
        function removeFile(inputId) {
            const input = document.getElementById(inputId);
            input.value = '';
            
            if (inputId === 'projectFile') {
                document.getElementById('projectFilePreview').style.display = 'none';
            } else if (inputId === 'projectImage') {
                document.getElementById('projectImagePreview').style.display = 'none';
            }
        }

        function removeAdditionalFile(index) {
            const input = document.getElementById('additionalFiles');
            const files = Array.from(input.files);
            files.splice(index, 1);
            
            // Créer une nouvelle DataTransfer et ajouter les fichiers restants
            const dataTransfer = new DataTransfer();
            files.forEach(file => dataTransfer.items.add(file));
            input.files = dataTransfer.files;
            
            // Rafraîchir l'affichage
            const event = new Event('change');
            input.dispatchEvent(event);
        }








    // Gestion de l'affichage des fichiers sélectionnés
    document.getElementById('projectFile').addEventListener('change', function(e) {
        if (this.files.length > 0) {
            document.getElementById('projectFileName').textContent = this.files[0].name;
            document.getElementById('projectFilePreview').style.display = 'block';
            
            // Masquer l'ancien fichier existant s'il y en a un
            const existingItem = document.querySelector('#projectFilePreview .existing-file');
            if (existingItem) {
                existingItem.style.display = 'none';
            }
        }
    });

    document.getElementById('projectImage').addEventListener('change', function(e) {
        if (this.files.length > 0) {
            document.getElementById('projectImageName').textContent = this.files[0].name;
            document.getElementById('projectImagePreview').style.display = 'block';
            
            // Masquer l'ancienne image existante s'il y en a une
            const existingItem = document.querySelector('#projectImagePreview .existing-file');
            if (existingItem) {
                existingItem.style.display = 'none';
            }
        }
    });

    // Fonctions pour supprimer les fichiers
    function removeFile(inputId, isExisting = false) {
        const input = document.getElementById(inputId);
        const preview = document.getElementById(inputId + 'Preview');
        
        if (isExisting) {
            // Pour les fichiers existants, on ajoute un champ caché pour indiquer la suppression
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'delete_' + inputId;
            hiddenInput.value = '1';
            document.getElementById('projectForm').appendChild(hiddenInput);
            
            // On cache le preview de l'existant
            const existingItem = preview.querySelector('.existing-file');
            if (existingItem) {
                existingItem.style.display = 'none';
            }
            
            // On rend le champ obligatoire
            input.required = true;
        } else {
            // Pour les nouveaux fichiers, on réinitialise simplement
            input.value = '';
            preview.style.display = 'none';
        }
    }
/*
    // Gestion des fichiers supplémentaires
    document.getElementById('additionalFiles').addEventListener('change', function(e) {
        const preview = document.getElementById('additionalFilesPreview');
        const existingFiles = preview.querySelectorAll('.existing-file');
        let existingCount = 0;
        
        // Compter les fichiers existants non supprimés
        existingFiles.forEach(file => {
            if (file.style.display !== 'none') {
                existingCount++;
            }
        });
        
        // Calculer le nombre de fichiers qu'on peut encore ajouter
        const remainingSlots = 3 - existingCount;
        
        if (this.files.length > 0) {
            // Limiter à remainingSlots fichiers
            const filesToShow = Array.from(this.files).slice(0, remainingSlots);
            
            // Supprimer les anciens nouveaux fichiers (ceux qui ne sont pas 'existing-file')
            const newFiles = preview.querySelectorAll('.file-item:not(.existing-file)');
            newFiles.forEach(file => file.remove());
            
            // Ajouter les nouveaux fichiers
            filesToShow.forEach((file, index) => {
                const fileItem = document.createElement('div');
                fileItem.className = 'file-item';
                
                // Déterminer l'icône en fonction de l'extension
                const ext = file.name.split('.').pop().toLowerCase();
                let iconClass = 'fa-file';
                
                if (ext === 'pdf') iconClass = 'fa-file-pdf';
                else if (['ppt', 'pptx'].includes(ext)) iconClass = 'fa-file-powerpoint';
                else if (['doc', 'docx'].includes(ext)) iconClass = 'fa-file-word';
                else if (['py', 'java', 'cpp', 'c', 'js', 'html', 'css', 'php', 'txt'].includes(ext)) iconClass = 'fa-file-code';
                
                fileItem.innerHTML = `
                    <div class="file-info">
                        <i class="fas ${iconClass} file-icon"></i>
                        <span class="file-name">${file.name}</span>
                    </div>
                    <button type="button" class="delete-file" onclick="this.closest('.file-item').remove()">
                        <i class="fas fa-trash"></i>
                    </button>
                `;
                
                preview.appendChild(fileItem);
            });
        }
    });*/

    document.getElementById('additionalFiles').addEventListener('change', function(e) {
    const preview = document.getElementById('additionalFilesPreview');
    const existingFiles = preview.querySelectorAll('.existing-file');
    let existingCount = 0;
    
    // Compter les fichiers existants non supprimés
    existingFiles.forEach(file => {
        if (file.style.display !== 'none') {
            existingCount++;
        }
    });
    
    // Calculer le nombre de fichiers qu'on peut encore ajouter
    const remainingSlots = 3 - existingCount;
    
    if (this.files.length > 0) {
        // Limiter à remainingSlots fichiers
        const filesToShow = Array.from(this.files).slice(0, remainingSlots);
        
        // Supprimer les anciens nouveaux fichiers (ceux qui ne sont pas 'existing-file')
        const newFiles = preview.querySelectorAll('.file-item:not(.existing-file)');
        newFiles.forEach(file => file.remove());
        
        // Ajouter les nouveaux fichiers
        filesToShow.forEach((file, index) => {
            const fileItem = document.createElement('div');
            fileItem.className = 'file-item';
            
            // Déterminer l'icône en fonction de l'extension
            const ext = file.name.split('.').pop().toLowerCase();
            let iconClass = 'fa-file';
            
            if (ext === 'pdf') iconClass = 'fa-file-pdf text-danger';
            else if (['ppt', 'pptx'].includes(ext)) iconClass = 'fa-file-powerpoint text-warning';
            else if (['doc', 'docx'].includes(ext)) iconClass = 'fa-file-word text-primary';
            else if (['py', 'java', 'cpp', 'c', 'js', 'html', 'css', 'php', 'txt'].includes(ext)) iconClass = 'fa-file-code text-success';
            
            fileItem.innerHTML = `
                <div class="file-info">
                    <i class="fas ${iconClass} file-icon"></i>
                    <span class="file-name">${file.name}</span>
                </div>
                <button type="button" class="delete-file" onclick="this.closest('.file-item').remove(); updateAdditionalFilesInput()">
                    <i class="fas fa-trash"></i>
                </button>
            `;
            
            preview.appendChild(fileItem);
        });
        
        // Mettre à jour l'input files
        updateAdditionalFilesInput();
    }
});

function updateAdditionalFilesInput() {
    const input = document.getElementById('additionalFiles');
    const dataTransfer = new DataTransfer();
    
    // Réinitialiser l'input files
    input.files = dataTransfer.files;
}

    function removeExistingFile(button) {
        const fileItem = button.closest('.existing-file');
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'delete_additionalFiles[]';
        hiddenInput.value = fileItem.querySelector('input').value;
        document.getElementById('projectForm').appendChild(hiddenInput);
        
        fileItem.style.display = 'none';
    }
