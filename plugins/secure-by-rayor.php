<?php
/**
 * Plugin: Secure By Rayor
 * Version: 1.0
 * Description: Filtre les visiteurs signales par la base de reputation d'IP Secure By Rayor.
 * Author: CMS
 * CSS-Priority: -1
 * JS-Priority: -1
 *
 * Interroge secure.rayor.fr pour l'adresse du visiteur et refuse
 * l'acces au-dela d'un score d'abus configurable. Aucune clef livree :
 * l'appel anonyme est le mode nominal, une clef ne fait que relever les
 * quotas. Chaque verdict est mis en cache, sinon chaque visite paierait
 * un aller-retour reseau.
 */

define('SBR_BASE',      'https://secure.rayor.fr');
define('SBR_DIR',       CMS_DATA_DIR . '/secure-rayor');
define('SBR_CACHE',     SBR_DIR . '/cache.json');
define('SBR_CACHE_MAX', 5000);

function sbr_config() {
    static $conf = null;

    if ($conf === null) {
        $conf = array_merge([
            'enabled'           => true,
            'api_key'           => '',
            'threshold'         => 75,
            'action'            => 'block',
            'cache_ttl'         => 43200,
            'report_bruteforce' => false,
        ], cms_read_json(CMS_DATA_DIR . '/secure-rayor.json'));
    }

    return $conf;
}

function sbr_config_save(array $conf) {
    return cms_write_json(CMS_DATA_DIR . '/secure-rayor.json', $conf);
}

// Le plugin tourne des qu'il est active : la clef est facultative
function sbr_ready() {
    return !empty(sbr_config()['enabled']);
}

// Dernier resultat connu de l'API, pour que le panneau puisse alerter
function sbr_state() {
    return cms_read_json(SBR_DIR . '/state.json');
}

function sbr_state_set($code, $detail = '') {
    if (!is_dir(SBR_DIR)) {
        @mkdir(SBR_DIR, 0750, true);
    }

    cms_write_json(SBR_DIR . '/state.json', [
        'code'    => $code,
        'detail'  => $detail,
        'horodate' => date('c'),
    ]);
}

// Adresse du visiteur. X-Forwarded-For est ignore : il est falsifiable
function sbr_ip() {
    return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
}

// Appel a l'API. Renvoie le tableau "data", ou null en cas d'echec
function sbr_api_check($ip) {
    $conf = sbr_config();

    if (!function_exists('curl_init')) {
        return null;
    }

    $entetes = [];

    // La clef est facultative : elle ne fait que relever les quotas.
    if (!empty($conf['api_key'])) {
        $entetes[] = 'Key: ' . $conf['api_key'];
    }

    $ch = curl_init(SBR_BASE . '/api/v2/check?' . http_build_query(['ipAddress' => $ip]));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => $entetes,
        CURLOPT_CONNECTTIMEOUT => 2,
        CURLOPT_TIMEOUT        => 3,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_USERAGENT      => 'CMS/' . CMS_VERSION . ' (secure-by-rayor)',
    ]);

    $brut = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code === 401 || $code === 403) {
        sbr_state_set('clef-requise', 'L\'API a refuse l\'appel (HTTP ' . $code . ').');

        return null;
    }

    if ($code === 429) {
        sbr_state_set('quota', 'Quota atteint (HTTP 429).');

        return null;
    }

    if (!is_string($brut) || $brut === '' || $code !== 200) {
        sbr_state_set('injoignable', 'Reponse HTTP ' . $code . '.');

        return null;
    }

    $json = json_decode($brut, true);

    if (!isset($json['data']) || !is_array($json['data'])) {
        sbr_state_set('illisible', 'Reponse JSON inattendue.');

        return null;
    }

    sbr_state_set('ok', empty($conf['api_key']) ? 'Appel anonyme accepte.' : 'Appel authentifie.');

    return $json['data'];
}

// Verdict pour une adresse : cache d'abord, API ensuite
function sbr_verdict($ip) {
    $conf  = sbr_config();
    $cache = cms_read_json(SBR_CACHE);
    $now   = time();

    if (isset($cache[$ip]['expires']) && $cache[$ip]['expires'] > $now) {
        return $cache[$ip];
    }

    $data = sbr_api_check($ip);

    if ($data === null) {
        return null; // service muet ou clef refusee : on ne conclut pas
    }

    $verdict = [
        'score'     => isset($data['abuseConfidenceScore']) ? (int) $data['abuseConfidenceScore'] : 0,
        'malicious' => !empty($data['malicious']),
        'country'   => isset($data['countryCode']) ? $data['countryCode'] : '',
        'expires'   => $now + (int) $conf['cache_ttl'],
    ];

    // Purge des entrees perimees, puis plafond de taille
    foreach ($cache as $cle => $entree) {
        if (!isset($entree['expires']) || $entree['expires'] <= $now) {
            unset($cache[$cle]);
        }
    }
    if (count($cache) >= SBR_CACHE_MAX) {
        $cache = array_slice($cache, -SBR_CACHE_MAX / 2, null, true);
    }

    $cache[$ip] = $verdict;

    if (!is_dir(SBR_DIR)) {
        @mkdir(SBR_DIR, 0750, true);
    }
    cms_write_json(SBR_CACHE, $cache);

    return $verdict;
}

// Signalement : ecrit dans une base communautaire, donc desactive par defaut.
function sbr_report($ip, $categories, $commentaire) {
    $conf = sbr_config();

    if (empty($conf['api_key']) || empty($conf['report_bruteforce']) || !function_exists('curl_init')) {
        return false;
    }

    $ch = curl_init(SBR_BASE . '/api/v2/report');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query([
            'ipAddress'  => $ip,
            'categories' => $categories,
            'comment'    => $commentaire,
        ]),
        CURLOPT_HTTPHEADER     => ['Key: ' . $conf['api_key']],
        CURLOPT_CONNECTTIMEOUT => 2,
        CURLOPT_TIMEOUT        => 4,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);
    curl_exec($ch);
    curl_close($ch);

    return true;
}

// Page de refus. Explicite, avec le recours : un faux positif doit
function sbr_bloquer($ip, $score) {
    http_response_code(403);
    header('Content-Type: text/html; charset=UTF-8');
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="robots" content="noindex">
        <title>Acces refuse</title>
        <link rel="stylesheet" href="<?php echo e(cms_asset_url('styles.css')); ?>">
    </head>
    <body>
        <h1>Acces refuse</h1>
        <p>
            Votre adresse IP <code><?php echo e($ip); ?></code> presente un score d'abus de
            <strong><?php echo (int) $score; ?>/100</strong> dans la base de reputation
            <strong>Secure By Rayor</strong>.
        </p>
        <p>
            S'il s'agit d'une erreur, vous pouvez demander son retrait :
            <a href="https://secure.rayor.fr/retrait">secure.rayor.fr/retrait</a>.
        </p>
    </body>
    </html>
    <?php
    exit;
}

// ---------------------------------------------------------------------

add_action('init', function () {
    if (!sbr_ready()) {
        return; // plugin desactive dans les reglages
    }

    // Un administrateur connecte n'est jamais filtre : sans cette regle,
    // une adresse signalee a tort fermerait le site a son proprietaire.
    if (cms_is_admin()) {
        return;
    }

    $ip = sbr_ip();

    // Adresses privees, locales et reservees : rien a demander a l'API
    if ($ip === '' || !filter_var(
        $ip,
        FILTER_VALIDATE_IP,
        FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
    )) {
        return;
    }

    $verdict = sbr_verdict($ip);

    // Service injoignable ou reponse illisible : on laisse passer.
    // Un tiers indisponible ne doit jamais fermer le site.
    if ($verdict === null) {
        return;
    }

    $conf = sbr_config();

    if (!$verdict['malicious'] && $verdict['score'] < (int) $conf['threshold']) {
        return;
    }

    if ($conf['action'] === 'log') {
        if (!is_dir(SBR_DIR)) {
            @mkdir(SBR_DIR, 0750, true);
        }
        @file_put_contents(
            SBR_DIR . '/hits.log',
            date('c') . "\t" . $ip . "\t" . $verdict['score'] . "\t"
                . (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '') . "\n",
            FILE_APPEND | LOCK_EX
        );

        return;
    }

    sbr_bloquer($ip, $verdict['score']);
}, 1);
