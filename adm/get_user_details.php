<?php
require_once('../base_donnees/pdo.php');
header('Content-Type: application/json');

// Vérifier les paramètres
if (!isset($_POST['id']) || !isset($_POST['role'])) {
    echo json_encode(['error' => 'Paramètres manquants']);
    exit;
}

$id = $_POST['id'];
$role = $_POST['role'];

try {
    if ($role === 'prof') {
        $stmt = $db->prepare("SELECT * FROM prof WHERE ID_PROF = ?");
    } elseif ($role === 'student') {
        $stmt = $db->prepare("SELECT * FROM student WHERE APOGEE = ?");
    } else {
        exit;
    }

    $stmt->execute([$id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(['error' => 'Utilisateur non trouvé']);
        exit;
    }

    // Préparer les données de réponse
    $response = [
        'id' => $role === 'prof' ? $user['ID_PROF'] : $user['APOGEE'],
        'nom' => $user['NOM'],
        'prenom' => $user['PRENOM'],
        'email' => $role === 'prof' ? $user['EMAIL'] : $user['EMAIL_INST'],
        'role' => $role === 'prof' ? 'Professeur' : 'Étudiant'
    ];

    if ($role === 'prof') {
        $response['departement'] = $user['DEPARTEMENT'];
    } else {
        $response['filiere'] = $user['FILIERE'];
        $response['niveau'] = $user['NIV'];
    }

    echo json_encode($response);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Erreur de base de données: ' . $e->getMessage()]);
}
?>