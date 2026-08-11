<?php
require_once __DIR__ . '/includes/bootstrap.php';

// La deconnexion modifie l'etat : elle exige le jeton de securite,
if (!csrf_valid(isset($_GET['token']) ? $_GET['token'] : null)) {
    http_response_code(403);
    exit('Requete refusee : jeton de securite invalide.');
}

$_SESSION = [];

// Suppression effective du cookie de session cote navigateur
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

session_destroy();

header('Location: login.php');
exit;
