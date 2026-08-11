<?php
/**
 * Format .cmsh — CMS Hypertext.
 *
 * CMSH/1 · en-tete JSON · ligne '--' · corps hypertexte.
 * Le corps est affiche, jamais execute.
 */
require_once __DIR__ . '/bootstrap.php';

define('CMSH_VERSION',    1);
define('CMSH_EXT',        '.cmsh');
define('CMSH_SEPARATEUR', '--');

/** Metadonnees par defaut ; toute cle absente d'un fichier reprend ces valeurs. */
function cmsh_defaults()
{
    return [
        'cmsh'        => CMSH_VERSION,
        'title'       => '',
        'slug'        => '',
        'author'      => '',
        'created'     => null,
        'modified'    => null,
        'modified_by' => '',
        'domain'      => '',
        'checksum'    => '',

        // Pre-requis techniques
        'requires'    => [
            'cms'     => CMS_VERSION,
            'php'     => '7.3',
            'plugins' => [],
        ],

        // Referencement
        'meta'        => [
            'description' => '',
            'keywords'    => '',
            'robots'      => 'index, follow',
            'canonical'   => '',
            'lang'        => 'fr',
        ],

        // Partage social
        'og'          => [
            'title'       => '',
            'description' => '',
            'image'       => '',
            'type'        => 'website',
        ],

        // Place dans l'arborescence du site
        'hierarchy'   => [
            'parent' => null,
            'order'  => 0,
            'menu'   => false,
        ],

        // Manifeste des ressources employees par la page
        'assets'      => [
            'media' => [],
            'css'   => [],
            'js'    => [],
        ],

        // Commentaires attaches a la page
        'comments'    => [
            'enabled' => false,
            'count'   => 0,
        ],
    ];
}

/** Fusion par-dessus les valeurs par defaut : le format peut evoluer sans casse. */
function cmsh_merge(array $defaut, array $lu)
{
    foreach ($lu as $cle => $valeur) {
        if (isset($defaut[$cle]) && is_array($defaut[$cle]) && is_array($valeur)
            && array_keys($defaut[$cle]) !== range(0, count($defaut[$cle]) - 1)) {
            $defaut[$cle] = cmsh_merge($defaut[$cle], $valeur);
        } else {
            $defaut[$cle] = $valeur;
        }
    }

    return $defaut;
}

/** Empreinte du corps : detecte une modification faite hors du CMS. */
function cmsh_checksum($body)
{
    return 'sha256:' . hash('sha256', (string) $body);
}

/** Decoupe un fichier .cmsh, ou null s'il n'est pas au format. */
function cmsh_parse($raw)
{
    $raw = (string) $raw;

    // 1. Ligne magique
    $sautLigne = strpos($raw, "\n");
    if ($sautLigne === false) {
        return null;
    }

    $magique = trim(substr($raw, 0, $sautLigne));
    if (strpos($magique, 'CMSH/') !== 0) {
        return null;
    }

    $reste = substr($raw, $sautLigne + 1);

    // 2. En-tete JSON, jusqu'a la ligne separatrice
    $lignes = explode("\n", $reste);
    $json   = [];
    $corps  = [];
    $dansEntete = true;

    foreach ($lignes as $ligne) {
        if ($dansEntete && rtrim($ligne, "\r") === CMSH_SEPARATEUR) {
            $dansEntete = false;
            continue;
        }
        if ($dansEntete) {
            $json[] = $ligne;
        } else {
            $corps[] = $ligne;
        }
    }

    $meta = json_decode(implode("\n", $json), true);
    if (!is_array($meta)) {
        return null;
    }

    return [
        'meta' => cmsh_merge(cmsh_defaults(), $meta),
        'body' => implode("\n", $corps),
    ];
}

/** Serialise une page au format .cmsh */
function cmsh_build(array $meta, $body)
{
    $meta = cmsh_merge(cmsh_defaults(), $meta);
    $meta['cmsh']     = CMSH_VERSION;
    $meta['checksum'] = cmsh_checksum($body);

    $json = json_encode(
        $meta,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    return 'CMSH/' . CMSH_VERSION . "\n" . $json . "\n" . CMSH_SEPARATEUR . "\n" . $body;
}

/** Chemin du fichier d'une page. Le slug est suppose deja valide */
function cmsh_path($slug)
{
    return CMS_PAGES_DIR . '/' . $slug . CMSH_EXT;
}

function cmsh_slug_ok($slug)
{
    return (bool) preg_match('/^[A-Za-z0-9_-]{1,64}$/', (string) $slug);
}

/** Charge une page, ou null si slug invalide, fichier absent ou format illisible. */
function cmsh_load($slug)
{
    if (!cmsh_slug_ok($slug)) {
        return null;
    }

    $chemin  = realpath(cmsh_path($slug));
    $dirReal = realpath(CMS_PAGES_DIR);

    if ($chemin === false || $dirReal === false
        || strpos($chemin, $dirReal . DIRECTORY_SEPARATOR) !== 0
        || !is_file($chemin)) {
        return null;
    }

    $page = cmsh_parse(file_get_contents($chemin));
    if ($page === null) {
        return null;
    }

    $page['file']  = $chemin;
    $page['slug']  = $slug;

    // Le contenu a-t-il ete modifie hors du CMS ?
    $page['altere'] = $page['meta']['checksum'] !== ''
        && $page['meta']['checksum'] !== cmsh_checksum($page['body']);

    return $page;
}

/** Enregistre une page et met a jour auteur, date, domaine et empreinte. */
function cmsh_save($slug, array $meta, $body)
{
    if (!cmsh_slug_ok($slug)) {
        return 'Slug invalide.';
    }

    $maintenant = date('c');
    $auteur     = isset($_SESSION['username']) ? $_SESSION['username'] : 'inconnu';

    $meta['slug']        = $slug;
    $meta['modified']    = $maintenant;
    $meta['modified_by'] = $auteur;

    if (empty($meta['created'])) {
        $meta['created'] = $maintenant;
    }
    if (empty($meta['author'])) {
        $meta['author'] = $auteur;
    }
    if (!empty($_SERVER['HTTP_HOST'])) {
        $meta['domain'] = $_SERVER['HTTP_HOST'];
    }

    // Manifeste des medias : releve automatique de ce que la page
    // reference reellement, plutot qu'une liste tenue a la main.
    $meta['assets']['media'] = cmsh_scan_media($body);

    if (file_put_contents(cmsh_path($slug), cmsh_build($meta, $body), LOCK_EX) === false) {
        return 'Ecriture impossible : verifiez les droits sur pages/.';
    }

    return '';
}

/** Releve les medias reellement references par le corps. */
function cmsh_scan_media($body)
{
    $trouve = [];

    if (preg_match_all('/\b(?:src|href|poster|data-src)\s*=\s*["\']([^"\']+)["\']/i', (string) $body, $m)) {
        foreach ($m[1] as $url) {
            $url = trim($url);

            // On ignore les ancres et les liens de page internes
            if ($url === '' || $url[0] === '#' || strpos($url, 'mailto:') === 0) {
                continue;
            }
            if (!preg_match('/\.(jpe?g|png|gif|webp|avif|svg|mp4|webm|mp3|ogg|wav|pdf|zip|css|js|woff2?)(\?|$)/i', $url)) {
                continue;
            }
            $trouve[$url] = true;
        }
    }

    return array_values(array_keys($trouve));
}

/** Liste des pages, metadonnees comprises, triee par slug */
function cmsh_list()
{
    $pages = [];

    foreach (scandir(CMS_PAGES_DIR) as $entree) {
        if (substr($entree, -strlen(CMSH_EXT)) !== CMSH_EXT) {
            continue;
        }
        $slug = substr($entree, 0, -strlen(CMSH_EXT));
        $page = cmsh_load($slug);
        if ($page !== null) {
            $pages[$slug] = $page;
        }
    }

    ksort($pages);

    return $pages;
}

/** Plugins requis par une page et absents de l'installation */
function cmsh_missing_plugins(array $meta)
{
    $manquants = [];

    foreach ((array) $meta['requires']['plugins'] as $plugin) {
        $nom = basename((string) $plugin);
        if ($nom !== '' && !is_file(CMS_PLUGINS_DIR . '/' . $nom)) {
            $manquants[] = $nom;
        }
    }

    return $manquants;
}

/** Migration unique des anciennes pages .php vers .cmsh */
function cmsh_migrate_legacy()
{
    $convertis = [];

    foreach (scandir(CMS_PAGES_DIR) as $entree) {
        if (substr($entree, -4) !== '.php') {
            continue;
        }

        $slug = basename($entree, '.php');
        if (!cmsh_slug_ok($slug) || is_file(cmsh_path($slug))) {
            continue;
        }

        $source = CMS_PAGES_DIR . '/' . $entree;
        $body   = (string) file_get_contents($source);

        // Retrait des blocs PHP : le corps ne doit plus contenir de code
        $body = preg_replace('/<\?php.*?\?>/s', '', $body);
        $body = preg_replace('/<\?(php|=).*/s', '', $body);
        $body = trim((string) $body);

        // Titre : le premier <h1> s'il existe
        $titre = $slug;
        if (preg_match('/<h1[^>]*>(.*?)<\/h1>/is', $body, $m)) {
            $titre = trim(strip_tags($m[1]));
        }

        $meta               = cmsh_defaults();
        $meta['title']      = $titre;
        $meta['slug']       = $slug;
        $meta['author']     = 'migration';
        $meta['created']    = date('c', (int) filemtime($source));
        $meta['meta']['robots'] = 'index, follow';

        if (file_put_contents(cmsh_path($slug), cmsh_build($meta, $body), LOCK_EX) !== false) {
            @rename($source, $source . '.bak');
            $convertis[] = $slug;
        }
    }

    return $convertis;
}
