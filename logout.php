<?php
/**
 * Déconnexion sécurisée de la session utilisateur
 */

require_once __DIR__ . '/includes/auth.php';

// Vider le tableau de session
$_SESSION = [];

// Détruire le cookie de session si présent
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Détruire la session
session_destroy();

// Démarrer une nouvelle session temporaire pour le message flash
session_start();
set_flash_message('info', 'Vous avez été déconnecté avec succès. À bientôt sur NovaStore !');

header('Location: index.php');
exit;
