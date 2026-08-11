<?php
/** Bootstrap du CMS */

// ---------------------------------------------------------------------

define('CMS_VERSION', '1.0');

define('CMS_ROOT', dirname(__DIR__));

/** Repertoire des donnees privees */
define('CMS_DATA_DIR', CMS_ROOT . '/data');

define('CMS_USERS_FILE',    CMS_DATA_DIR . '/users.json');
define('CMS_ATTEMPTS_FILE', CMS_DATA_DIR . '/login_attempts.json');
define('CMS_CONFIG_FILE',   CMS_ROOT . '/config.json');
define('CMS_PAGES_DIR',     CMS_ROOT . '/pages');
define('CMS_PLUGINS_DIR',   CMS_ROOT . '/plugins');

// Politique de blocage des connexions
define('CMS_LOGIN_MAX_ATTEMPTS', 5);    // echecs avant blocage
define('CMS_LOGIN_LOCKOUT',      900);  // duree du blocage en secondes (15 min)
define('CMS_LOGIN_WINDOW',       3600); // duree de vie d'un compteur (1 h)

// ---------------------------------------------------------------------

if (session_status() === PHP_SESSION_NONE) {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $https,   // cookie non transmis en clair si le site est en HTTPS
        'httponly' => true,     // illisible par JavaScript (vol de session via XSS)
        'samesite' => 'Lax',    // non envoye depuis un site tiers (CSRF)
    ]);
    session_name('cmssession');
    session_start();
}

// ---------------------------------------------------------------------

if (!is_dir(CMS_DATA_DIR)) {
    @mkdir(CMS_DATA_DIR, 0750, true);
}

// Migration automatique : users.json etait a la racine web (donc
// telechargeable). On le deplace une seule fois vers data/.
$cms_legacy_users = CMS_ROOT . '/users.json';
if (is_file($cms_legacy_users) && !is_file(CMS_USERS_FILE)) {
    @rename($cms_legacy_users, CMS_USERS_FILE);
}
unset($cms_legacy_users);

// ---------------------------------------------------------------------

/** Lit un fichier JSON et renvoie toujours un tableau */
function cms_read_json($file)
{
    if (!is_file($file)) {
        return [];
    }
    $data = json_decode((string) file_get_contents($file), true);

    return is_array($data) ? $data : [];
}

/** Ecrit un fichier JSON avec verrou exclusif */
function cms_write_json($file, array $data)
{
    $dir = dirname($file);
    if (!is_dir($dir) && !@mkdir($dir, 0750, true)) {
        return false;
    }
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    return $json !== false && file_put_contents($file, $json, LOCK_EX) !== false;
}

/** Configuration du site, chargee une seule fois par requete */
function cms_config()
{
    static $config = null;
    if ($config === null) {
        $config = cms_read_json(CMS_CONFIG_FILE);
    }

    return $config;
}

/** Enregistre la configuration du site */
function cms_config_save(array $config)
{
    return cms_write_json(CMS_CONFIG_FILE, $config);
}

/** Valeur de configuration avec repli */
function cms_config_get($key, $default = null)
{
    $config = cms_config();

    return isset($config[$key]) ? $config[$key] : $default;
}

// ---------------------------------------------------------------------

/** Prefixe URL du CMS : '/' a la racine du domaine, '/cms/' si le site */
function cms_base_uri()
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }

    $script = isset($_SERVER['SCRIPT_NAME'])
        ? str_replace('\\', '/', $_SERVER['SCRIPT_NAME'])
        : '/index.php';

    $dir = rtrim(dirname($script), '/');

    // Les scripts de ces repertoires sont un cran plus bas dans l'arborescence
    foreach (['/admin', '/auth'] as $sousRepertoire) {
        if (substr($dir, -strlen($sousRepertoire)) === $sousRepertoire) {
            $dir = substr($dir, 0, -strlen($sousRepertoire));
            break;
        }
    }

    $base = $dir . '/';

    return $base;
}

/** 'page1' ou '/page1' -> URL valide quel que soit le repertoire d'installation. */
function cms_url($link)
{
    $link = (string) $link;

    if ($link === '' || preg_match('#^([a-z][a-z0-9+.-]*:|//|\#)#i', $link)) {
        return $link;
    }

    return cms_base_uri() . ltrim($link, '/');
}

// ---------------------------------------------------------------------

/** Echappe une valeur pour affichage HTML (texte ou attribut) */
function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** URL d'une ressource statique, avec sa date de modification en */
function cms_asset_url($chemin)
{
    $chemin  = ltrim((string) $chemin, '/');
    $absolu  = CMS_ROOT . '/' . $chemin;
    $version = is_file($absolu) ? filemtime($absolu) : CMS_VERSION;

    return cms_base_uri() . $chemin . '?v=' . $version;
}

// ---------------------------------------------------------------------

define('CMS_THEMES_DIR', CMS_ROOT . '/themes');

/** Identifiant du theme actif. 'standard' correspond au socle seul */
function cms_current_theme()
{
    $theme = (string) cms_config_get('theme', 'standard');

    return preg_match('/^[a-z0-9_-]+$/', $theme) ? $theme : 'standard';
}

/** Themes disponibles, decrits par themes/<slug>/theme.json */
function cms_themes_list()
{
    $themes = [
        'standard' => [
            'name'        => 'Standard',
            'description' => "Le socle nu : typographie suisse, noir et rouge, aucun parti pris sectoriel.",
            'colors'      => ['#ffffff', '#111111', '#e2001a'],
        ],
    ];

    if (!is_dir(CMS_THEMES_DIR)) {
        return $themes;
    }

    foreach (scandir(CMS_THEMES_DIR) as $entree) {
        if ($entree === '.' || $entree === '..'
            || !is_file(CMS_THEMES_DIR . '/' . $entree . '/theme.css')) {
            continue;
        }

        $infos = cms_read_json(CMS_THEMES_DIR . '/' . $entree . '/theme.json');

        $themes[$entree] = [
            'name'        => isset($infos['name']) ? $infos['name'] : $entree,
            'description' => isset($infos['description']) ? $infos['description'] : '',
            'colors'      => isset($infos['colors']) ? (array) $infos['colors'] : [],
        ];
    }

    return $themes;
}

/** Repertoire du theme actif, ou '' pour le socle seul */
function cms_theme_dir()
{
    $theme = cms_current_theme();

    return $theme === 'standard' ? '' : CMS_THEMES_DIR . '/' . $theme;
}

/** Metadonnees du theme actif (theme.json) */
function cms_theme_meta()
{
    static $meta = null;

    if ($meta === null) {
        $dir  = cms_theme_dir();
        $meta = $dir === '' ? [] : cms_read_json($dir . '/theme.json');
    }

    return $meta;
}

/** Resout un gabarit : la version du theme si elle existe, sinon celle */
function cms_template($nom)
{
    $nom = preg_replace('/[^a-z0-9_-]/', '', (string) $nom);
    $dir = cms_theme_dir();

    if ($dir !== '' && is_file($dir . '/' . $nom . '.php')) {
        return $dir . '/' . $nom . '.php';
    }

    $socle = CMS_ROOT . '/includes/' . $nom . '.php';

    return is_file($socle) ? $socle : '';
}

/** Feuilles de style de la page publique */
function cms_styles_links()
{
    $meta   = cms_theme_meta();
    $liens  = '';

    if (empty($meta['standalone'])) {
        $liens .= '<link rel="stylesheet" href="' . e(cms_asset_url('styles.css')) . '">';
    }

    $dir = cms_theme_dir();

    if ($dir !== '' && is_file($dir . '/theme.css')) {
        $liens .= "\n    " . '<link rel="stylesheet" href="'
            . e(cms_asset_url('themes/' . cms_current_theme() . '/theme.css')) . '">';
    }

    return $liens;
}

/** Contenu complet du <head> d'une page publique : titre, metadonnees, */
function cms_page_head()
{
    $page = isset($GLOBALS['cms_page']) ? $GLOBALS['cms_page'] : null;
    $meta = $page !== null ? $page['meta'] : cmsh_defaults();

    $titreSite = cms_config_get('page_title', 'CMS');
    $titrePage = trim((string) $meta['title']);
    $titre     = $titrePage !== '' ? $titrePage . ' — ' . $titreSite : $titreSite;

    $description = $meta['meta']['description'] !== ''
        ? $meta['meta']['description']
        : cms_config_get('meta_description', '');

    $robots    = $meta['meta']['robots'] !== '' ? $meta['meta']['robots'] : 'index, follow';
    $canonical = $meta['meta']['canonical'];

    $ogTitre = $meta['og']['title'] !== '' ? $meta['og']['title']
        : ($titrePage !== '' ? $titrePage : $titreSite);
    $ogDesc  = $meta['og']['description'] !== '' ? $meta['og']['description'] : $description;
    $ogImage = $meta['og']['image'];
    $ogType  = $meta['og']['type'] !== '' ? $meta['og']['type'] : 'website';

    $html  = '<title>' . e($titre) . '</title>' . "\n";
    if ($description !== '') {
        $html .= '    <meta name="description" content="' . e($description) . '">' . "\n";
    }
    if ($meta['meta']['keywords'] !== '') {
        $html .= '    <meta name="keywords" content="' . e($meta['meta']['keywords']) . '">' . "\n";
    }
    $html .= '    <meta name="robots" content="' . e($robots) . '">' . "\n";
    if ($canonical !== '') {
        $html .= '    <link rel="canonical" href="' . e($canonical) . '">' . "\n";
    }

    $html .= '    <meta property="og:title" content="' . e($ogTitre) . '">' . "\n";
    if ($ogDesc !== '') {
        $html .= '    <meta property="og:description" content="' . e($ogDesc) . '">' . "\n";
    }
    if ($ogImage !== '') {
        $html .= '    <meta property="og:image" content="' . e($ogImage) . '">' . "\n";
    }
    $html .= '    <meta property="og:type" content="' . e($ogType) . '">' . "\n";
    $html .= '    <meta name="twitter:card" content="'
        . ($ogImage !== '' ? 'summary_large_image' : 'summary') . '">' . "\n";

    $html .= '    ' . cms_styles_links() . "\n";

    // Un plugin peut completer le head (police locale, JSON-LD, etc.)
    ob_start();
    do_action('head', $meta);
    $html .= '    ' . trim((string) ob_get_clean());

    return $html;
}

/** Langue du document, definie par la page */
function cms_page_lang()
{
    $page = isset($GLOBALS['cms_page']) ? $GLOBALS['cms_page'] : null;
    $meta = $page !== null ? $page['meta'] : cmsh_defaults();

    return $meta['meta']['lang'] !== '' ? $meta['meta']['lang'] : 'fr';
}

/** Navigation principale, telle que definie dans les reglages */
function cms_nav_items()
{
    return (array) cms_config_get('navbar', ['Accueil' => 'index.php']);
}

/** Bloc « Connexion » ou « Administration », commun a tous les themes */
function cms_admin_links()
{
    if (cms_is_admin()) {
        return '<a href="' . e(cms_url('admin/index.php')) . '">Administration</a> '
            . '<a href="' . e(cms_url('logout.php')) . '?token=' . urlencode(csrf_token()) . '">Déconnexion</a>';
    }

    return '<a href="' . e(cms_url('login.php')) . '">Connexion</a>';
}

/** Conserve pour compatibilite : n'emet plus que la feuille du theme */
function cms_theme_link()
{
    $dir = cms_theme_dir();

    if ($dir === '' || !is_file($dir . '/theme.css')) {
        return '';
    }

    return '<link rel="stylesheet" href="'
        . e(cms_asset_url('themes/' . cms_current_theme() . '/theme.css')) . '">';
}

/** Barre d'outils de redaction, posee au-dessus d'une zone de saisie */
function cms_editor_toolbar($cible)
{
    $outils = [
        'Titre'      => "<h2>|</h2>",
        'Sous-titre' => "<h3>|</h3>",
        'Paragraphe' => "<p>|</p>",
        'Gras'       => "<strong>|</strong>",
        'Italique'   => "<em>|</em>",
        'Lien'       => "<a href=\"https://\">|</a>",
        'Liste'      => "<ul>\n    <li>|</li>\n</ul>",
        'Image'      => "<img src=\"\" alt=\"\">|",
        'Séparateur' => "<hr>|",
    ];

    $html = '<div class="barre-outils" data-editeur="' . e($cible) . '">';

    foreach ($outils as $libelle => $insertion) {
        $html .= '<button type="button" data-inserer="' . e($insertion) . '">'
            . e($libelle) . '</button>';
    }

    return $html . '</div>';
}

/** Nonce de la requete, pour autoriser nommement les rares scripts en */
function cms_nonce()
{
    static $nonce = null;
    if ($nonce === null) {
        $nonce = base64_encode(random_bytes(12));
    }

    return $nonce;
}

// ---------------------------------------------------------------------

/** Jeton de la session courante (cree au premier appel) */
function csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/** Champ cache a placer dans chaque formulaire POST */
function csrf_field()
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/** Compare un jeton recu au jeton de session, sans fuite de timing */
function csrf_valid($token)
{
    return is_string($token)
        && !empty($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

/** Refuse la requete si le jeton est absent ou invalide */
function csrf_check()
{
    if (!csrf_valid(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : null)) {
        http_response_code(403);
        exit('Requete refusee : jeton de securite invalide ou expire. Rechargez la page et reessayez.');
    }
}

// ---------------------------------------------------------------------

function cms_users()
{
    return cms_read_json(CMS_USERS_FILE);
}

/** Vrai tant qu'aucun compte n'existe : le site est fraichement installe */
function cms_needs_setup()
{
    return count(cms_users()) === 0;
}

// ---------------------------------------------------------------------
// Flux simplifie inspire d'OAuth2 (https://connect.rayor.fr/llms.txt) :
// redirection vers authorize.php, retour avec un code a usage unique,

define('CMS_RAYOR_AUTHORIZE', 'https://connect.rayor.fr/authorize.php');
define('CMS_RAYOR_API',       'https://connect.rayor.fr/api.php');

function cms_rayor_config()
{
    $conf = cms_config_get('rayor_connect', []);

    return is_array($conf) ? $conf : [];
}

/** Actif par defaut : aucune inscription prealable n'etant necessaire */
function cms_rayor_enabled()
{
    $conf = cms_rayor_config();

    return !isset($conf['enabled']) || $conf['enabled'] !== false;
}

/** Le client_id est le domaine, deduit de la requete : zero configuration. */
function cms_rayor_client_id()
{
    $conf = cms_rayor_config();
    if (!empty($conf['client_id'])) {
        return $conf['client_id'];
    }

    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';

    // On retire un eventuel port : le client_id est un domaine
    return preg_replace('/:\d+$/', '', $host);
}

function cms_rayor_scopes()
{
    $conf = cms_rayor_config();

    // Minimisation des donnees : on ne demande que ce dont le CMS se
    // sert reellement pour creer et reconnaitre un compte.
    return !empty($conf['scopes']) ? $conf['scopes'] : 'openid profile email';
}

/** URL de retour. Doit etre sur le domaine du client_id, c'est verifie */
function cms_rayor_redirect_uri()
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';

    return ($https ? 'https' : 'http') . '://' . $host . cms_base_uri() . 'auth/callback.php';
}

/** GET JSON serveur a serveur, avec verification stricte du certificat */
function cms_http_get_json($url, $timeout = 10)
{
    $raw = false;

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => $timeout,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT      => 'CMS/1.0 (+rayor-connect)',
        ]);
        $raw = curl_exec($ch);
        curl_close($ch);
    } elseif (ini_get('allow_url_fopen')) {
        $context = stream_context_create([
            'http' => ['timeout' => $timeout, 'ignore_errors' => true],
            'ssl'  => ['verify_peer' => true, 'verify_peer_name' => true],
        ]);
        $raw = @file_get_contents($url, false, $context);
    }

    if (!is_string($raw) || $raw === '') {
        return null;
    }

    $data = json_decode($raw, true);

    return is_array($data) ? $data : null;
}

/** Cherche par rayor_id : seul identifiant stable, jamais l'e-mail. */
function cms_find_user_by_rayor_id($rayorId)
{
    foreach (cms_users() as $user) {
        if (isset($user['rayor_id']) && hash_equals((string) $user['rayor_id'], (string) $rayorId)) {
            return $user;
        }
    }

    return null;
}

function cms_is_admin()
{
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

/** Enregistrement complet de l'utilisateur connecte, ou null */
function cms_current_user()
{
    if (empty($_SESSION['username'])) {
        return null;
    }

    foreach (cms_users() as $user) {
        if (isset($user['username']) && $user['username'] === $_SESSION['username']) {
            return $user;
        }
    }

    return null;
}

/** Bloque l'acces si l'utilisateur n'est pas connecte en admin */
function cms_require_admin($loginUrl = 'login.php')
{
    if (!cms_is_admin()) {
        header('Location: ' . $loginUrl);
        exit;
    }
}

/** Ouvre une session admin. session_regenerate_id empeche la fixation */
function cms_login_user($username)
{
    session_regenerate_id(true);
    $_SESSION['is_admin'] = true;
    $_SESSION['username'] = $username;
    unset($_SESSION['csrf_token']); // nouveau jeton pour la nouvelle session
}

// ---------------------------------------------------------------------

/** REMOTE_ADDR et pas X-Forwarded-For, falsifiable par le client. */
function cms_client_key()
{
    return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'inconnu';
}

/** Secondes de blocage restantes, 0 si la connexion est autorisee */
function cms_login_lock_remaining()
{
    $attempts = cms_read_json(CMS_ATTEMPTS_FILE);
    $key      = cms_client_key();

    if (!isset($attempts[$key]['locked_until'])) {
        return 0;
    }
    $remaining = (int) $attempts[$key]['locked_until'] - time();

    return $remaining > 0 ? $remaining : 0;
}

/** Enregistre un echec et declenche le blocage au seuil atteint */
function cms_login_record_failure()
{
    $attempts = cms_read_json(CMS_ATTEMPTS_FILE);
    $key      = cms_client_key();
    $now      = time();

    // Purge des entrees expirees pour que le fichier ne grossisse pas
    foreach ($attempts as $k => $entry) {
        $last = isset($entry['last']) ? (int) $entry['last'] : 0;
        $lock = isset($entry['locked_until']) ? (int) $entry['locked_until'] : 0;
        if ($last < $now - CMS_LOGIN_WINDOW && $lock < $now) {
            unset($attempts[$k]);
        }
    }

    $count = isset($attempts[$key]['count']) ? (int) $attempts[$key]['count'] : 0;
    $count++;

    $attempts[$key] = [
        'count'        => $count,
        'last'         => $now,
        'locked_until' => $count >= CMS_LOGIN_MAX_ATTEMPTS ? $now + CMS_LOGIN_LOCKOUT : 0,
    ];

    cms_write_json(CMS_ATTEMPTS_FILE, $attempts);
}

/** Remet le compteur a zero apres une connexion reussie */
function cms_login_reset()
{
    $attempts = cms_read_json(CMS_ATTEMPTS_FILE);
    unset($attempts[cms_client_key()]);
    cms_write_json(CMS_ATTEMPTS_FILE, $attempts);
}

// ---------------------------------------------------------------------

/** Page « introuvable » du CMS */
function cms_render_404($slug = '')
{
    http_response_code(404);

    // Un plugin peut vouloir journaliser, ou rediriger avant tout rendu
    do_action('404', $slug);

    // Remplacement complet : le plugin renvoie le document entier
    $complet = apply_filters('404_page', null, $slug);

    if (is_string($complet)) {
        echo $complet;
        exit;
    }

    // Quelques pages a proposer, plutot qu'une impasse
    $suggestions = [];
    if (function_exists('cmsh_list')) {
        foreach (cmsh_list() as $autreSlug => $autrePage) {
            $suggestions[$autreSlug] = $autrePage['meta']['title'] !== ''
                ? $autrePage['meta']['title']
                : $autreSlug;
            if (count($suggestions) >= 6) {
                break;
            }
        }
    }

    $contenu = '<p class="code-erreur">404</p>'
        . '<h1>Cette page n\'existe pas</h1>'
        . '<p>L\'adresse demandée ne correspond à aucune page de ce site. '
        . 'Elle a peut-être été renommée ou supprimée.</p>';

    if ($suggestions) {
        $contenu .= '<h2>Ces pages existent</h2><ul>';
        foreach ($suggestions as $autreSlug => $titre) {
            $contenu .= '<li><a href="' . e(cms_url($autreSlug)) . '">' . e($titre) . '</a></li>';
        }
        $contenu .= '</ul>';
    }

    $contenu = apply_filters('404_content', $contenu, $slug);

    // Metadonnees de la page d'erreur, pour l'en-tete du site
    if (function_exists('cmsh_defaults')) {
        $meta                   = cmsh_defaults();
        $meta['title']          = 'Page introuvable';
        $meta['meta']['robots'] = 'noindex, nofollow';

        $GLOBALS['cms_page'] = [
            'meta'   => $meta,
            'body'   => '',
            'slug'   => $slug,
            'altere' => false,
        ];
    }

    // Un theme peut fournir son propre gabarit 404 complet
    $gabarit404 = cms_template('404');

    if ($gabarit404 !== '' && strpos($gabarit404, CMS_THEMES_DIR) === 0) {
        include $gabarit404;
        exit;
    }

    $entete = cms_template('header');
    $pied   = cms_template('footer');

    if ($entete !== '' && $pied !== '') {
        include $entete;
        echo $contenu;
        include $pied;
    } elseif (is_file(CMS_ROOT . '/404.html')) {
        readfile(CMS_ROOT . '/404.html');
    } else {
        echo $contenu;
    }

    exit;
}

// ---------------------------------------------------------------------

require_once __DIR__ . '/functions.php';

cms_load_plugins();

// Le theme est charge apres les plugins : il a donc le dernier mot.
// plugin. Charge apres eux, il a donc le dernier mot.
$cms_theme_functions = cms_theme_dir();
if ($cms_theme_functions !== '' && is_file($cms_theme_functions . '/functions.php')) {
    require_once $cms_theme_functions . '/functions.php';
}
unset($cms_theme_functions);

// Premier point d'accroche : rien n'a encore ete affiche, un plugin
// peut donc poser des en-tetes HTTP ou interrompre la requete.
do_action('init');

// ---------------------------------------------------------------------

require_once __DIR__ . '/cmsh.php';

// Migration des anciennes pages .php vers .cmsh.
if (glob(CMS_PAGES_DIR . '/*.php')) {
    cmsh_migrate_legacy();
}

// ---------------------------------------------------------------------

/** Tant qu'aucun compte n'existe, le site entier renvoie vers la creation */
if (!defined('CMS_SKIP_SETUP') && cms_needs_setup()) {
    $cms_script = basename(isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '');

    if ($cms_script !== 'register.php') {
        header('Location: ' . cms_url('register.php'));
        exit;
    }
    unset($cms_script);
}
