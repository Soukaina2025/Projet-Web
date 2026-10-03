<?php
session_start();

if (!isset($_SESSION['APOGEE'])) {
    header("Location: ../connexion_folder_vf/login_page.php");
    exit();
}

if (empty($_GET['id'])) {
    header("Location: ../projets_page_folder_vf_acceuil_esp_etudiant/home_student.php");
    exit();
}

require_once '../connexion_folder_vf/connexion_db.php';

try {
    // Récupérer les données du projet
    $stmt = $conn->prepare("SELECT p.*, pr.NOM as PROF_NOM, pr.PRENOM as PROF_PRENOM 
                           FROM project p 
                           JOIN prof pr ON p.ID_PROF = pr.ID_PROF 
                           WHERE p.ID_PROJECT = ? AND (p.APOGEE = ? OR p.STATUT_STU = 'validé')");
    $stmt->execute([$_GET['id'], $_SESSION['APOGEE']]);
    $projet = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$projet) {
        header("Location: ../projets_page_folder_vf_acceuil_esp_etudiant/home_student.php");
        exit();
    }
    
    // Récupérer les infos de l'étudiant
    $stmt = $conn->prepare("SELECT NOM, PRENOM FROM student WHERE APOGEE = ?");
    $stmt->execute([$projet['APOGEE']]);
    $etudiant = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Erreur de base de données: " . $e->getMessage());
}

$color = $_SESSION['AVATAR_COLOR'] ?? '#1B2631';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Projet <?= $projet['NOM'] ?> | ENSA Kénitra</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .project-header {
            background-color: #f8f9fa;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        .project-image {
            max-height: 400px;
            object-fit: cover;
            border-radius: 10px;
        }
        .file-download-btn {
            display: inline-block;
            margin-right: 10px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <!-- Même header que dans soumission.php -->

    <main class="container py-5">
        <div class="project-header">
            <div class="row">
                <div class="col-md-8">
                    <h1><?= htmlspecialchars($projet['NOM']) ?></h1>
                    <p class="text-muted">ID: PRJ-<?= $projet['ID_PROJECT'] ?> | Soumis le: <?= date('d/m/Y', strtotime($projet['DATE_DEP'])) ?></p>
                </div>
                <div class="col-md-4 text-end">
                    <span class="badge bg-<?= $projet['STATUT_STU'] == 'validé' ? 'success' : 'warning' ?>">
                        <?= $projet['STATUT_STU'] == 'validé' ? 'Validé' : 'En attente' ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-body">
                        <h3 class="card-title">Description</h3>
                        <p class="card-text"><?= nl2br(htmlspecialchars($projet['DESCRIPTION'])) ?></p>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <h3 class="card-title">Fichiers du projet</h3>
                        
                        <a href="../uploads/livrables/<?= $projet['FICHIER'] ?>" class="btn btn-primary file-download-btn" download>
                            <i class="fas fa-file-archive"></i> Fichier principal (ZIP)
                        </a>
                        
                        <?php for ($i = 1; $i <= 3; $i++): ?>
                            <?php if (!empty($projet['FILE'.$i])): ?>
                                <a href="../uploads/livrables/<?= $projet['FILE'.$i] ?>" class="btn btn-secondary file-download-btn" download>
                                    <i class="fas fa-file-alt"></i> Fichier supplémentaire <?= $i ?>
                                </a>
                            <?php endif; ?>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card mb-4">
                    <img src="../uploads/images/<?= $projet['IMG'] ?>" class="card-img-top project-image" alt="Image du projet">
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <h3 class="card-title">Détails</h3>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item">
                                <strong>Type:</strong> 
                                <?= $projet['TYPE'] == 'stage' ? 'Projet de stage' : 
                                   ($projet['TYPE'] == 'module' ? 'Projet de module' : 'Projet de fin d\'études') ?>
                            </li>
                            <li class="list-group-item">
                                <strong>Catégorie:</strong> 
                                <?= ucfirst($projet['CATEGORIE']) ?>
                            </li>
                            <li class="list-group-item">
                                <strong>Étudiant:</strong> 
                                <?= $etudiant['PRENOM'] ?> <?= $etudiant['NOM'] ?>
                            </li>
                            <li class="list-group-item">
                                <strong>Encadrant:</strong> 
                                <?= $projet['PROF_PRENOM'] ?> <?= $projet['PROF_NOM'] ?>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Même footer que dans soumission.php -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>