<?php
// Connexion à la base de données
$host = '127.0.0.1';
$dbname = 'projet_web';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion: " . $e->getMessage());


}


// Initialisation des filtres depuis GET
$filiere = $_GET['filiere'] ?? null;
$type = $_GET['type'] ?? null;
$niveau = $_GET['niveau'] ?? null;
$categorie = $_GET['categorie'] ?? null;
$encadrant = $_GET['encadrant'] ?? null;


// Construction de la requête de base
$query = "SELECT p.*, pr.NOM AS PROF_NOM, pr.PRENOM AS PROF_PRENOM, 
                 s.NOM AS ETUD_NOM, s.PRENOM AS ETUD_PRENOM, s.FILIERE, s.NIV
          FROM project p
          JOIN prof pr ON p.ID_PROF = pr.ID_PROF
          JOIN student s ON p.APOGEE = s.APOGEE
          WHERE p.STATUT_STU = 'validé'";

// Ajout des conditions de filtrage
$params = [];
if ($filiere) {
    $query .= " AND s.FILIERE = :filiere";
    $params[':filiere'] = $filiere;
}
if ($type) {
    $query .= " AND p.TYPE = :type";
    $params[':type'] = $type;
}
if ($niveau) {
    $query .= " AND s.NIV = :niveau";
    $params[':niveau'] = $niveau;
}
if ($categorie) {
    $query .= " AND p.CATEGORIE = :categorie";
    $params[':categorie'] = $categorie;
}
if ($encadrant) {
    $query .= " AND (pr.NOM LIKE :encadrant OR pr.PRENOM LIKE :encadrant)";
    $params[':encadrant'] = "%$encadrant%";
}

$query .= " ORDER BY p.DATE_DEP DESC";

// Préparation et exécution de la requête
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupération des options pour les filtres
$filieres = $pdo->query("SELECT DISTINCT FILIERE FROM student")->fetchAll(PDO::FETCH_COLUMN);
$types = $pdo->query("SELECT DISTINCT TYPE FROM project")->fetchAll(PDO::FETCH_COLUMN);
$niveaux = $pdo->query("SELECT DISTINCT NIV FROM student")->fetchAll(PDO::FETCH_COLUMN);
$categories = $pdo->query("SELECT DISTINCT CATEGORIE FROM project")->fetchAll(PDO::FETCH_COLUMN);
$encadrants = $pdo->query("SELECT DISTINCT CONCAT(PRENOM, ' ', NOM) AS NOM_COMPLET FROM prof")->fetchAll(PDO::FETCH_COLUMN);
?>