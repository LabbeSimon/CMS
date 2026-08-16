<?php
// Depart du flux Rayor Connect

// L'installation passe par ici : ne pas se faire renvoyer vers register.php
define('CMS_SKIP_SETUP', true);
require_once __DIR__ . '/../includes/bootstrap.php';

// Le plugin peut avoir ete retire : ces deux pages n'ont alors plus de raison d'etre.
if (!function_exists('cms_rayor_enabled') || !cms_rayor_enabled()) {
    http_response_code(404);
    exit('Rayor Connect est desactive sur ce site.');
}

$clientId = cms_rayor_client_id();
if ($clientId === '' || $clientId === null) {
    http_response_code(500);
    exit('Impossible de determiner le domaine du site pour Rayor Connect. Renseignez rayor_connect.client_id dans config.json.');
}

// L'intention distingue la creation du compte proprietaire d'une simple
// connexion : au retour, on ne fait pas la meme chose.
$intent = (isset($_GET['intent']) && $_GET['intent'] === 'setup' && cms_needs_setup())
    ? 'setup'
    : 'login';

$state = bin2hex(random_bytes(16));

$_SESSION['rayor'] = [
    'state'   => $state,
    'intent'  => $intent,
    'expires' => time() + 600,
];

$url = RAYOR_AUTHORIZE . '?' . http_build_query([
    'client_id'     => $clientId,
    'redirect_uri'  => cms_rayor_redirect_uri(),
    'scope'         => cms_rayor_scopes(),
    'response_type' => 'code',
    'state'         => $state,
]);

header('Location: ' . $url);
exit;
