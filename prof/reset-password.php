<?php
require_once '../base_donnees/pdo.php';

function genererMotDePasse($longueur = 8) {
    $caracteres = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    return substr(str_shuffle(str_repeat($caracteres, $longueur)), 0, $longueur);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $nouveau = genererMotDePasse();

    // Hacher le mot de passe
    $motDePasseHache = password_hash($nouveau, PASSWORD_DEFAULT);

    // Vérifie si l’email existe
    $stmt = $db->prepare("SELECT * FROM prof WHERE EMAIL = :email");
    $stmt->execute(['email' => $email]);

    if ($stmt->rowCount() > 0) {
        // Mise à jour du mot de passe haché
        $update = $db->prepare("UPDATE prof SET PASSWRD = :nouveau WHERE EMAIL = :email");
        $update->execute(['nouveau' => $motDePasseHache, 'email' => $email]);

        // Renvoyer le mot de passe en clair pour envoi par email
        echo json_encode(['status' => 'ok', 'password' => $nouveau]);
    } else {
        echo json_encode(['status' => 'not_found']);
    }
}
?>
