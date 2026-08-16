<?php
/**
 * Plugin: Rayor Connect
 * Version: 1.0
 * Description: Connexion avec un compte Rayor, sans mot de passe stocke sur le site.
 * Author: CMS
 * CSS-Priority: -1
 * JS-Priority: -1
 *
 * Flux simplifie inspire d'OAuth2 : on envoie l'utilisateur sur
 * authorize.php, il revient avec un code, on echange le code contre son
 * profil. Pas de client_secret, le client_id est le domaine du site —
 * donc rien a configurer dans le cas courant.
 *
 * Les deux points d'entree sont auth/rayor.php et auth/callback.php.
 * Retirez ce fichier et le bouton disparait partout, proprement.
 */

define('RAYOR_AUTHORIZE', 'https://connect.rayor.fr/authorize.php');
define('RAYOR_API', 'https://connect.rayor.fr/api.php');

function cms_rayor_config() {
    $conf = cms_config_get('rayor_connect', []);
    return is_array($conf) ? $conf : [];
}

// Actif par defaut : aucune inscription prealable n'est necessaire cote Rayor.
function cms_rayor_enabled() {
    $conf = cms_rayor_config();
    return !isset($conf['enabled']) || $conf['enabled'] !== false;
}

// Le client_id est le domaine servi ; config.json peut le forcer.
function cms_rayor_client_id() {
    $conf = cms_rayor_config();
    if (!empty($conf['client_id'])) {
        return $conf['client_id'];
    }
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
    return preg_replace('/:\d+$/', '', $host);
}

// On ne demande que ce qui sert a creer et reconnaitre un compte.
function cms_rayor_scopes() {
    $conf = cms_rayor_config();
    return !empty($conf['scopes']) ? $conf['scopes'] : 'openid profile email';
}

function cms_rayor_redirect_uri() {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';

    return ($https ? 'https' : 'http') . '://' . $host . cms_base_uri() . 'auth/callback.php';
}

// Echange serveur a serveur. Delais courts, certificat verifie.
function cms_rayor_get_json($url) {
    $brut = false;

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT => 'CMS/' . CMS_VERSION,
        ]);
        $brut = curl_exec($ch);
        curl_close($ch);
    } elseif (ini_get('allow_url_fopen')) {
        $brut = @file_get_contents($url, false, stream_context_create([
            'http' => ['timeout' => 10, 'ignore_errors' => true],
            'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
        ]));
    }

    if (!is_string($brut) || $brut === '') {
        return null;
    }

    $data = json_decode($brut, true);
    return is_array($data) ? $data : null;
}

// C'est le rayor_id qui fait foi : l'e-mail et le pseudo peuvent changer.
function cms_find_user_by_rayor_id($rayorId) {
    foreach (cms_users() as $user) {
        if (isset($user['rayor_id']) && hash_equals($user['rayor_id'], (string) $rayorId)) {
            return $user;
        }
    }
    return null;
}

// Bouton propose sur la page de connexion et a l'installation.
function cms_rayor_bouton($intention = 'login') {
    if (!cms_rayor_enabled()) {
        return '';
    }

    $libelle = $intention === 'setup' ? 'Continuer avec Rayor' : 'Se connecter avec Rayor';

    return '<a class="bouton" href="' . e(cms_url('auth/rayor.php'))
        . '?intent=' . e($intention) . '">' . $libelle . '</a>';
}
