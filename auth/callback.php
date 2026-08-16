<?php
// Retour de Rayor Connect

define('CMS_SKIP_SETUP', true);
require_once __DIR__ . '/../includes/bootstrap.php';

// Affiche une erreur lisible et s'arrete. On ne renvoie jamais le detail
function rayor_echec($message, $code = 400) {
    unset($_SESSION['rayor']);

    http_response_code($code);
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Connexion impossible</title>
        <link rel="stylesheet" href="<?php echo e(cms_asset_url('styles.css')); ?>">
    </head>
    <body>
        <h1>Connexion impossible</h1>
        <p class="erreur"><?php echo e($message); ?></p>
        <p><a href="<?php echo e(cms_url(cms_needs_setup() ? 'register.php' : 'login.php')); ?>">Retour</a></p>
    </body>
    </html>
    <?php
    exit;
}

// Le plugin peut avoir ete retire : ces deux pages n'ont alors plus de raison d'etre.
if (!function_exists('cms_rayor_enabled') || !cms_rayor_enabled()) {
    rayor_echec('Rayor Connect est desactive sur ce site.', 404);
}

// --- 1. Verification du state -----------------------------------------

$session = isset($_SESSION['rayor']) && is_array($_SESSION['rayor']) ? $_SESSION['rayor'] : null;
$state   = isset($_GET['state']) ? (string) $_GET['state'] : '';

if ($session === null || empty($session['state'])) {
    rayor_echec('Aucune demande de connexion en cours. Recommencez depuis la page de connexion.');
}

if (time() > (int) $session['expires']) {
    rayor_echec('La demande de connexion a expire. Recommencez.');
}

if (!hash_equals((string) $session['state'], $state)) {
    rayor_echec('Verification de securite echouee (state invalide).', 403);
}

$intent = isset($session['intent']) ? $session['intent'] : 'login';
unset($_SESSION['rayor']); // le state ne sert qu'une fois

// --- 2. Echange du code ------------------------------------------------

$code = isset($_GET['code']) ? (string) $_GET['code'] : '';

if ($code === '') {
    // L'utilisateur a refuse le consentement, ou Rayor a renvoye une erreur
    rayor_echec('Autorisation refusee ou code absent.');
}

$profil = cms_rayor_get_json(RAYOR_API . '?' . http_build_query(['code' => $code]));

if ($profil === null) {
    rayor_echec('Le fournisseur d\'identite n\'a pas repondu. Reessayez dans un instant.', 502);
}

if (!empty($profil['error'])) {
    rayor_echec('Le code d\'autorisation a ete refuse (il est a usage unique et expire en 5 minutes).');
}

if (empty($profil['rayor_id'])) {
    rayor_echec('Le profil renvoye ne contient pas d\'identifiant Rayor.');
}

$rayorId = (string) $profil['rayor_id'];

// Nom d'affichage : le pseudo si on l'a, sinon le prenom, sinon l'identifiant
$nom = $rayorId;
foreach (['nickname', 'given_name'] as $champ) {
    if (!empty($profil[$champ])) {
        $nom = (string) $profil[$champ];
        break;
    }
}

// --- 3. Installation : creation du compte proprietaire ------------------

if ($intent === 'setup' && cms_needs_setup()) {
    $users = cms_users();

    // Re-verification juste avant l'ecriture (course entre deux requetes)
    if (count($users) > 0) {
        rayor_echec('Un compte a ete cree entre-temps.', 409);
    }

    $users[] = [
        'username' => $nom,
        'password' => null,             // aucun mot de passe local
        'provider' => 'rayor',
        'rayor_id' => $rayorId,
        'email'    => isset($profil['email']) ? $profil['email'] : null,
        'picture'  => isset($profil['picture']) ? $profil['picture'] : null,
        'created'  => date('c'),
    ];

    if (!cms_write_json(CMS_USERS_FILE, $users)) {
        rayor_echec('Impossible d\'enregistrer le compte. Verifiez les droits du repertoire data/.', 500);
    }

    cms_login_user($nom);
    header('Location: ' . cms_url('admin/index.php'));
    exit;
}

// --- 4. Connexion d'un compte existant ---------------------------------

$utilisateur = cms_find_user_by_rayor_id($rayorId);

if ($utilisateur === null) {
    // Aucune creation implicite : un compte Rayor inconnu n'ouvre rien.
    rayor_echec('Ce compte Rayor n\'est pas autorise sur ce site.', 403);
}

cms_login_reset();
cms_login_user($utilisateur['username']);

header('Location: ' . cms_url('admin/index.php'));
exit;
