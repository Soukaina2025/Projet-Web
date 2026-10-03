<?php
session_start();

// Vérifiez que l'utilisateur est connecté  
if (!isset($_SESSION['APOGEE'])) {     
    header("Location: ../connexion_folder_vf/login_page.php");     
    exit(); 
}

// Connexion à la base de données
require_once('../connexion_folder_vf/db_config.php');

// Récupérer les infos de l'étudiant pour l'image de profil
try {
    $query = "SELECT IMG, PRENOM, AVATAR FROM student WHERE APOGEE = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$_SESSION['APOGEE']]);
    $student = $stmt->fetch();
    
    // Déterminer quoi afficher
    $profile_img = !empty($student['IMG']) ? $student['IMG'] : null;
    $avatar_char = $student['AVATAR'] ?? substr($student['PRENOM'], 0, 1);
    $avatar_color = $_SESSION['AVATAR_COLOR'] ?? '#1B2631';
    
    // Stocker en session pour éviter de requêter la base à chaque page
    $_SESSION['PROFILE_IMG'] = $profile_img;
    $_SESSION['AVATAR_CHAR'] = $avatar_char;
} catch (PDOException $e) {
    // En cas d'erreur, utiliser les valeurs par défaut
    $profile_img = $_SESSION['PROFILE_IMG'] ?? null;
    $avatar_char = $_SESSION['AVATAR_CHAR'] ?? '?';
    $avatar_color = $_SESSION['AVATAR_COLOR'] ?? '#1B2631';
}

// Récupérer les dates de soumission depuis la base de données
$soumissions = [];
try {
    $query = "SELECT ID_PROJECT, NOM, TYPE, CATEGORIE, DATE_DEP 
              FROM project 
              WHERE APOGEE = ? AND DATE_DEP IS NOT NULL
              ORDER BY DATE_DEP";
    $stmt = $conn->prepare($query);
    $stmt->execute([$_SESSION['APOGEE']]);
    $soumissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des soumissions: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendrier des Projets | ENSA Kénitra</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        :root {
            --primary-blue: #002C84;
            --secondary-blue: #1A4B8C;
            --light-blue: #E6F0FF;
            --dark-blue: #001A4B;
        }
        
    /*    .calendar-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 44, 132, 0.1);
            padding: 20px;
            margin-bottom: 30px;
        }
    */    
        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .calendar-title {
            color: var(--primary-blue);
            font-weight: 700;
            margin: 0;
        }
        
        .calendar-nav button {
            background-color: var(--primary-blue);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            margin: 0 5px;
        }
        
        .days-header {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            text-align: center;
            font-weight: bold;
            margin-bottom: 10px;
            color: var(--primary-blue);
        }
    /*    
        .days-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 5px;
        }
    */    
        .day {
            aspect-ratio: 1;
            border: 1px solid #ddd;
            padding: 5px;
            position: relative;
            background: white;
            transition: all 0.2s ease;
        }
        
        .day-number {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .day.soumission {
            background-color: rgba(0, 44, 132, 0.1);
            border-color: var(--primary-blue);
            background-color: lightcyan;
        }
        
        .day.soumission .day-number {
            color: var(--primary-blue);
        }
        
        .event-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 3px;
        }
        
        .soumission-dot {
            background-color: var(--primary-blue);
        }
        
        .event-tooltip {
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 44, 132, 0.2);
            z-index: 100;
            width: auto;
            min-width: 180px;
            display: none;
            border: 2px solid var(--primary-blue);
            animation: fadeIn 0.3s ease;
        }
        
        .day:hover .event-tooltip {
            display: block;
        }
        
        .event-tooltip h5 {
            margin-top: 0;
            color: var(--primary-blue);
            font-size: 14px;
            font-weight: bold;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
            margin-bottom: 8px;
        }
        
        .event-tooltip .count-badge {
            display: inline-block;
            background-color: var(--primary-blue);
            color: white;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            text-align: center;
            line-height: 25px;
            font-size: 12px;
            font-weight: bold;
            margin-right: 8px;
        }
        
        .other-month {
            color: #aaa;
            background-color: #f9f9f9;
        }
        
        .today {
            background-color: var(--light-blue);
            border-color: var(--primary-blue);
            border-width: 2px;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateX(-50%) translateY(-10px); }
            to { opacity: 1; transform: translateX(-50%) translateY(0); }
        }

    /*    @media (max-width:620px){
            .event-tooltip {
    position: absolute;
    top: 100%;
    left: -20px;}
    /*
            .event-tooltip{
                padding: 8px 5px;
            }
            .event-tooltip h5 {
    
    font-size: 12px;
    
}
        
        .event-tooltip p {
    
    font-size: 8px;
        }*/
    

/*
        @media (max-width:530px){
            
    
            .event-tooltip{
                padding: 8px 5px;
                width: fit-content;
                /*transform: translateX(50%);
            
            .event-tooltip h5 {
    
    font-size: 15px;
    
}
        
        .event-tooltip p {
    
    font-size: 10px;
        }

            .event-tooltip {
            
            min-width: 100px;
        }
        .event-tooltip .count-badge{
            font-size: 8px;
            width: 20px;
            height: 20px;
            line-height: 20px;
            margin-right:0;
        }
    
}

        @media (max-width:510px){
            .event-tooltip{
                display: none;
            }
        }*/
/*
        @media (max-width:630px){
            .event-tooltip{
                
                    display: none;
                
            }
        }*/


        
        /* Media Query pour les petits écrans */
        @media (max-width: 630px) {
            .event-tooltip {
                display: none !important;
            }
            
            .calendar-header {
                flex-direction: column;
                gap: 10px;
            }
            
            .calendar-nav {
                display: flex;
                gap: 5px;
            }
            
            .calendar-nav button {
                padding: 6px 12px;
                font-size: 14px;
            }
            
            .days-header div {
                font-size: 12px;
            }
            
            .day-number {
                font-size: 14px;
            }
        }



        
    </style>
</head>
<body>
    <header class="header-wrapper bg-white shadow-sm">
        <nav class="navbar navbar-expand-lg navbar-light container">
            <a class="navbar-brand logo" href="../projets_page_folder_vf_acceuil_esp_etudiant/home_student.php">
                <img src="images/ensa_logo.png" alt="Logo ENSA">
            </a>
  
            <div class="collapse navbar-collapse justify-content-between" id="navbarNav">
                <ul class="navbar-nav nav-links">
                    <li class="nav-item"><a class="nav-link" href="../projets_page_folder_vf_acceuil_esp_etudiant/home_student.php">Accueil</a></li>
                    <li class="nav-item"><a class="nav-link" href="../soumission_folder_vf/soumission.php">Soumission</a></li>
                    <li class="nav-item"><a class="nav-link" href="../projets_historique_folder_vf_projets/projets.php">Projets</a></li>
                    <li class="nav-item"><a class="nav-link active" href="../resultats_folder_vf/resultats.php">Résultats</a></li>
                    <li class="nav-item"><a class="nav-link" href="../calendrier_folder_vf/calendrier.php">Calendrier</a></li>
                </ul>       
            </div>
  
            <div class="d-flex align-items-center gap-3 links_container">
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center text-black text-decoration-none dropdown-toggle" id="dropdownUser" data-bs-toggle="dropdown" aria-expanded="false">
                       
                            <div class="avatar-circle" style="background-color: <?php echo htmlspecialchars($avatar_color); ?>; width:40px; height: 40px;">
                                <?php echo htmlspecialchars($avatar_char); ?>
                            </div>
                                    
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="dropdownUser">
                        <li><a class="dropdown-item d-flex align-items-center" href="../profil_vf/profil.php">
                            <i class="bi bi-person-circle me-2"></i>
                            <span>Profil</span>
                        </a></li>
                        <li><a class="dropdown-item d-flex align-items-center" href="../rapports_archive_user_folder_vf/rapports.php">
                            <i class="bi bi-file-earmark-bar-graph me-2"></i>
                            <span>Rapports</span>
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item d-flex align-items-center text-danger" href="../logout.php">
                            <i class="bi bi-box-arrow-right me-2"></i>
                            <span>Déconnexion</span>
                        </a></li>
                    </ul>
                </div>
                
                <button class="navbar-toggler toggle" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
            </div>
        </nav>
    </header>

    <main class="main-content">
        <div class="container">
            <div class="page-header">
                <h1>Calendrier des Projets</h1>
                <p>Suivez les dates importantes pour vos projets académiques</p>
            </div>
            
            <div class="calendar-container">
                <div class="calendar-header">
                    <h2 class="calendar-title" id="month-year">Mois 2023</h2>
                    <div class="calendar-nav">
                        <button id="prev-month"><i class="fas fa-chevron-left"></i> Précédent</button>
                        <button id="next-month">Suivant <i class="fas fa-chevron-right"></i></button>
                    </div>
                </div>

                <div class="days-header">
                    <div>Dim</div><div>Lun</div><div>Mar</div><div>Mer</div>
                    <div>Jeu</div><div>Ven</div><div>Sam</div>
                </div>

                <div class="days-grid" id="calendar-days">
                    <!-- Jours générés par JavaScript -->
                </div>
            </div>
        </div>
    </main>

    <footer class="footer-wrapper">
        <div class="footer-section">
            <div class="footer-info">
                <img src="images/LOGO-ENSA.png" alt="Logo ENSA">
                <p><a href="https://ensa.uit.ac.ma/" target="_blank" style="color: white;">Visiter le site officiel</a></p>
            </div>
            <div class="footer-links">
                <h3>Liens utiles</h3>
                <ul>
                    <li><a href="../projets_espaces_folder_vf_apropos/index.html">À propos</a></li>
                    <li><a href="../reglements_page_folder_vf/index.html">Règlement</a></li>
                    <li><a href="../contact_folder_vf/index.html">Contact</a></li>
                </ul>
            </div>
            <div class="footer-contact">
                <h3>Réseaux Sociaux</h3>
                <div class="footer_icons">
                    <a target="_blank" href="https://www.instagram.com/ensak.official" class="text-dark"><i class="fab fa-instagram fa-lg"></i></a>
                    <a target="_blank" href="https://ma.linkedin.com/company/ensa-kenitra-official" class="text-dark"><i class="fab fa-linkedin fa-lg"></i></a>
                </div>
                <a class="contact_link" href="../contact_folder_vf/index.html">Contactez-nous</a>
                <p>(+212) 5 37 37 67 65</p>
                <p>Campus universitaire, BP 241, Kénitra – Maroc</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>École Nationale des Sciences Appliquées © 2025 Université Ibn Tofail. All Rights Reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Données PHP converties en JSON pour JavaScript
        const soumissions = <?php echo json_encode($soumissions); ?>;
        
        document.addEventListener('DOMContentLoaded', function() {
            let currentDate = new Date();
            let currentMonth = currentDate.getMonth();
            let currentYear = currentDate.getFullYear();
            let today = new Date();
            today.setHours(0, 0, 0, 0);
            
            // Fonction pour formater la date au format YYYY-MM-DD pour comparaison
            function formatDate(date) {
                return date.getFullYear() + '-' + 
                       String(date.getMonth() + 1).padStart(2, '0') + '-' + 
                       String(date.getDate()).padStart(2, '0');
            }
            
            // Fonction pour vérifier si une date a des soumissions
            function hasSoumission(date) {
                const dateStr = formatDate(date);
                return soumissions.some(s => {
                    const soumissionDate = new Date(s.DATE_DEP);
                    return formatDate(soumissionDate) === dateStr;
                });
            }
            
            // Fonction pour obtenir les projets soumis à une date donnée
            function getSoumissionsForDate(date) {
                const dateStr = formatDate(date);
                return soumissions.filter(s => {
                    const soumissionDate = new Date(s.DATE_DEP);
                    return formatDate(soumissionDate) === dateStr;
                });
            }
            
            // Fonction pour générer le calendrier
            function generateCalendar(month, year) {
                const calendarDays = document.getElementById('calendar-days');
                calendarDays.innerHTML = '';
                
                // Mettre à jour le titre du mois
                const monthNames = ["Janvier", "Février", "Mars", "Avril", "Mai", "Juin",
                                    "Juillet", "Août", "Septembre", "Octobre", "Novembre", "Décembre"];
                document.getElementById('month-year').textContent = `${monthNames[month]} ${year}`;
                
                // Premier jour du mois
                const firstDay = new Date(year, month, 1);
                // Dernier jour du mois
                const lastDay = new Date(year, month + 1, 0);
                // Jour de la semaine du premier jour (0 = dimanche, 6 = samedi)
                const firstDayOfWeek = firstDay.getDay();
                // Nombre total de jours dans le mois
                const totalDays = lastDay.getDate();
                
                // Jours du mois précédent à afficher
                const prevMonthLastDay = new Date(year, month, 0).getDate();
                let dayCount = 1;
                let nextMonthDayCount = 1;
                
                // Créer les cases du calendrier (6 semaines * 7 jours = 42 cases)
                for (let i = 0; i < 42; i++) {
                    const dayElement = document.createElement('div');
                    dayElement.className = 'day';
                    
                    // Jours du mois précédent
                    if (i < firstDayOfWeek) {
                        const prevDay = prevMonthLastDay - (firstDayOfWeek - i - 1);
                        dayElement.innerHTML = `<div class="day-number">${prevDay}</div>`;
                        dayElement.classList.add('other-month');
                    } 
                    // Jours du mois courant
                    else if (dayCount <= totalDays) {
                        const currentDay = new Date(year, month, dayCount);
                        dayElement.innerHTML = `<div class="day-number">${dayCount}</div>`;
                        
                        // Vérifier si c'est aujourd'hui
                        if (currentDay.getTime() === today.getTime()) {
                            dayElement.classList.add('today');
                        }
                        
                        // Vérifier s'il y a des soumissions ce jour-là
                        if (hasSoumission(currentDay)) {
                            dayElement.classList.add('soumission');
                            
                            // Ajouter le tooltip avec le nombre de projets soumis
                            const projets = getSoumissionsForDate(currentDay);
                            const tooltip = document.createElement('div');
                            tooltip.className = 'event-tooltip';
                            
                            tooltip.innerHTML = `
                                <h5>
                                    <span class="count-badge">${projets.length}</span>
                                    Projet${projets.length > 1 ? 's' : ''} soumis
                                </h5>
                                <p>Cliquez pour plus de détails</p>
                            `;
                            
                            dayElement.appendChild(tooltip);
                            
                            // Ajouter un événement de clic pour afficher les détails complets
                            dayElement.addEventListener('click', function() {
                                alert(
                                      projets.map(p => `• ${p.NOM} (${p.TYPE})`).join('\n'));
                            });
                        }
                        
                        dayCount++;
                    } 
                    // Jours du mois suivant
                    else {
                        dayElement.innerHTML = `<div class="day-number">${nextMonthDayCount}</div>`;
                        dayElement.classList.add('other-month');
                        nextMonthDayCount++;
                    }
                    
                    calendarDays.appendChild(dayElement);
                }
            }
            
            // Initialiser le calendrier
            generateCalendar(currentMonth, currentYear);
            
            // Gestion des boutons de navigation
            document.getElementById('prev-month').addEventListener('click', function() {
                currentMonth--;
                if (currentMonth < 0) {
                    currentMonth = 11;
                    currentYear--;
                }
                generateCalendar(currentMonth, currentYear);
            });
            
            document.getElementById('next-month').addEventListener('click', function() {
                currentMonth++;
                if (currentMonth > 11) {
                    currentMonth = 0;
                    currentYear++;
                }
                generateCalendar(currentMonth, currentYear);
            });
            
            // Vérifier périodiquement les mises à jour (toutes les 5 minutes)
            setInterval(function() {
                fetch(window.location.href)
                    .then(response => response.text())
                    .then(html => {
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        const newSoumissions = JSON.parse(doc.querySelector('script').textContent.split('=')[1].trim().replace(';', ''));
                        
                        // Comparer avec les données actuelles
                        if (JSON.stringify(soumissions) !== JSON.stringify(newSoumissions)) {
                            window.location.reload(); // Recharger si changement détecté
                        }
                    })
                    .catch(error => console.error('Erreur de vérification des mises à jour:', error));
            }, 300000); // 5 minutes = 300000 ms
        });
    </script>
</body>
</html>