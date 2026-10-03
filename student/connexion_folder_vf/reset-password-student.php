<?php
header('Content-Type: application/json');
require_once 'db_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Méthode non autorisée']);
    exit;
}

$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Email invalide']);
    exit;
}

// Vérification que l'email appartient au domaine UIT
if (!str_ends_with($email, '@uit.ac.ma')) {
    echo json_encode(['status' => 'not_found', 'message' => 'Email UIT non trouvé']);
    exit;
}

// Générer un mot de passe temporaire
function genererMotDePasse() {
    $caracteres = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    $motDePasse = '';
    for ($i = 0; $i < 8; $i++) {
        $motDePasse .= $caracteres[rand(0, strlen($caracteres) - 1)];
    }
    return $motDePasse;
}

$nouveauMotDePasse = genererMotDePasse();

// Hacher le mot de passe
$motDePasseHache = password_hash($nouveauMotDePasse, PASSWORD_DEFAULT);

try {
    // Vérifie si l'email existe
    $stmt = $conn->prepare("SELECT * FROM student WHERE EMAIL_INST = :email");
    $stmt->execute(['email' => $email]);

    if ($stmt->rowCount() > 0) {
        // Mise à jour du mot de passe haché
        $update = $conn->prepare("UPDATE student SET PASSWRD = :nouveau WHERE EMAIL_INST = :email");
        $update->execute(['nouveau' => $motDePasseHache, 'email' => $email]);
        
        echo json_encode([
            'status' => 'ok',
            'password' => $nouveauMotDePasse,
            'message' => 'Mot de passe réinitialisé avec succès'
        ]);
    } else {
        echo json_encode(['status' => 'not_found', 'message' => 'Email non trouvé dans la base de données']);
    }
} catch(PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Erreur de base de données']);
}
?>