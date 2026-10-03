
        document.addEventListener('DOMContentLoaded', function() {
            // Données de démonstration
            const events = [
                { date: '2023-06-05', type: 'soumission', title: 'Projet Web - Soumission finale' },
                { date: '2023-06-12', type: 'modification', title: 'Rapport de Stage - Corrections' },
                { date: '2023-06-15', type: 'evaluation', title: 'Projet IA - Présentation orale' },
                { date: '2023-06-20', type: 'soumission', title: 'Projet Mobile - Version initiale' },
                { date: '2023-06-25', type: 'evaluation', title: 'Projet Final - Soutenance' },
                { date: '2023-07-03', type: 'soumission', title: 'Rapport PFE - Version finale' },
                { date: '2023-07-10', type: 'evaluation', title: 'Examen de rattrapage' }
            ];

            let currentDate = new Date();
            let currentMonth = currentDate.getMonth();
            let currentYear = currentDate.getFullYear();

            const monthNames = [
                "Janvier", "Février", "Mars", "Avril", "Mai", "Juin",
                "Juillet", "Août", "Septembre", "Octobre", "Novembre", "Décembre"
            ];

            function renderCalendar() {
                const firstDay = new Date(currentYear, currentMonth, 1).getDay();
                const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();
                const today = new Date();
                
                document.getElementById('month-year').textContent = 
                    `${monthNames[currentMonth]} ${currentYear}`;

                let calendarHTML = '';
                
                // Jours vides en début de mois
                for (let i = 0; i < firstDay; i++) {
                    calendarHTML += `<div class="day"></div>`;
                }

                // Jours du mois
                for (let i = 1; i <= daysInMonth; i++) {
                    const dateStr = `${currentYear}-${String(currentMonth + 1).padStart(2, '0')}-${String(i).padStart(2, '0')}`;
                    const dayEvents = events.filter(e => e.date === dateStr);
                    const isToday = currentYear === today.getFullYear() && 
                                   currentMonth === today.getMonth() && 
                                   i === today.getDate();

                    let dayHTML = `<div class="day ${isToday ? 'current-day' : ''}">`;
                    dayHTML += `<div class="day-number">${i}</div>`;
                    
                    // Ajouter les points d'événements
                    dayEvents.forEach(event => {
                        dayHTML += `<div class="event-dot ${event.type}" title="${event.title}"></div>`;
                    });

                    dayHTML += `</div>`;
                    calendarHTML += dayHTML;
                }

                document.getElementById('calendar-days').innerHTML = calendarHTML;
            }

            // Navigation
            document.getElementById('prev-month').addEventListener('click', () => {
                currentMonth--;
                if (currentMonth < 0) {
                    currentMonth = 11;
                    currentYear--;
                }
                renderCalendar();
            });

            document.getElementById('next-month').addEventListener('click', () => {
                currentMonth++;
                if (currentMonth > 11) {
                    currentMonth = 0;
                    currentYear++;
                }
                renderCalendar();
            });

            // Initialisation
            renderCalendar();
        });
