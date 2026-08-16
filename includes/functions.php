<?php
// Moteur de plugins : actions, filtres, chargement

$GLOBALS['cms_actions'] = [];
$GLOBALS['cms_filters'] = [];
$GLOBALS['cms_plugins'] = [];
$GLOBALS['cms_plugin_errors'] = [];

// ---------------------------------------------------------------------

function add_action($hook, $callback, $priority = 10) {
    $GLOBALS['cms_actions'][$hook][(int) $priority][] = $callback;
}

function do_action($hook) {
    if (empty($GLOBALS['cms_actions'][$hook])) {
        return;
    }

    $args = array_slice(func_get_args(), 1);
    $parPriorite = $GLOBALS['cms_actions'][$hook];
    ksort($parPriorite);

    foreach ($parPriorite as $callbacks) {
        foreach ($callbacks as $callback) {
            // Un plugin qui plante ne doit pas emporter la page. On note
            // l'erreur, elle est affichee dans le panneau, et on continue.
            try {
                call_user_func_array($callback, $args);
            } catch (Throwable $e) {
                cms_plugin_erreur($hook, $e);
            }
        }
    }
}

function cms_plugin_erreur($hook, $e) {
    $GLOBALS['cms_plugin_errors'][] = [
        'hook' => $hook,
        'message' => $e->getMessage(),
        'fichier' => basename($e->getFile()),
        'ligne' => $e->getLine(),
    ];
}

// ---------------------------------------------------------------------

function add_filter($hook, $callback, $priority = 10) {
    $GLOBALS['cms_filters'][$hook][(int) $priority][] = $callback;
}

function apply_filters($hook, $value) {
    if (empty($GLOBALS['cms_filters'][$hook])) {
        return $value;
    }

    $extra       = array_slice(func_get_args(), 2);
    $parPriorite = $GLOBALS['cms_filters'][$hook];
    ksort($parPriorite);

    foreach ($parPriorite as $callbacks) {
        foreach ($callbacks as $callback) {
            try {
                $value = call_user_func_array($callback, array_merge([$value], $extra));
            } catch (Throwable $e) {
                cms_plugin_erreur($hook, $e);
            }
        }
    }

    return $value;
}

// ---------------------------------------------------------------------

// Lit l'en-tete d'un fichier de plugin sans l'executer
function cms_plugin_headers($fichier) {
    $entete = (string) file_get_contents($fichier, false, null, 0, 8192);

    $champs = [
        'plugin'        => 'Plugin',
        'plugin_wp'     => 'Plugin Name',
        'version'       => 'Version',
        'description'   => 'Description',
        'author'        => 'Author',
        'css_priority'  => 'CSS-Priority',
        'js_priority'   => 'JS-Priority',
        'load_priority' => 'Load-Priority',
    ];

    $trouve = [];
    foreach ($champs as $cle => $libelle) {
        if (preg_match('/^[ \t\/*#@]*' . preg_quote($libelle, '/') . '\s*:\s*(.+)$/mi', $entete, $m)) {
            $trouve[$cle] = trim($m[1]);
        } else {
            $trouve[$cle] = '';
        }
    }

    // Un plugin WordPress se declare avec « Plugin Name: »
    if ($trouve['plugin'] === '' && $trouve['plugin_wp'] !== '') {
        $trouve['plugin'] = $trouve['plugin_wp'];
        $trouve['wordpress'] = true;
    } else {
        $trouve['wordpress'] = false;
    }

    $trouve['file'] = basename($fichier);

    return $trouve;
}

// Inventaire de plugins/, en-tetes compris. Ne charge rien
function cms_plugins_info() {
    $liste = [];

    $fichiers = glob(CMS_PLUGINS_DIR . '/*.php');

    // Un plugin WordPress arrive en dossier : plugins/mon-plugin/mon-plugin.php
    foreach (glob(CMS_PLUGINS_DIR . '/*', GLOB_ONLYDIR) as $dossier) {
        $nom = basename($dossier);
        if (is_file($dossier . '/' . $nom . '.php')) {
            $fichiers[] = $dossier . '/' . $nom . '.php';
            continue;
        }
        // Sinon on cherche le premier .php qui porte un en-tete de plugin
        foreach (glob($dossier . '/*.php') as $candidat) {
            $h = cms_plugin_headers($candidat);
            if ($h['plugin'] !== '') {
                $fichiers[] = $candidat;
                break;
            }
        }
    }

    foreach ($fichiers as $fichier) {
        $entete           = cms_plugin_headers($fichier);
        $entete['charge'] = $entete['plugin'] !== '';
        $entete['chemin'] = $fichier;

        $cle = dirname($fichier) === CMS_PLUGINS_DIR
            ? basename($fichier)
            : basename(dirname($fichier)) . '/' . basename($fichier);

        $liste[$cle] = $entete;
    }

    ksort($liste);

    return $liste;
}

// Charge les plugins declares, avant tout affichage
function cms_load_plugins() {
    $charges = [];

    foreach (cms_plugins_info() as $nom => $entete) {
        if ($entete['charge']) {
            $charges[$nom] = $entete;
        }
    }

    // Load-Priority : la couche de compatibilite doit exister avant les
    // plugins qui s'en servent. Par defaut 10, comme les hooks.
    uasort($charges, function ($a, $b) {
        $pa = $a['load_priority'] !== '' ? (int) $a['load_priority'] : 10;
        $pb = $b['load_priority'] !== '' ? (int) $b['load_priority'] : 10;

        return $pa === $pb ? strcmp($a['file'], $b['file']) : $pa - $pb;
    });

    foreach ($charges as $nom => $entete) {
        $GLOBALS['cms_plugins'][$nom] = $entete;

        try {
            require_once $entete['chemin'];
        } catch (Throwable $e) {
            cms_plugin_erreur('chargement de ' . $nom, $e);
        }
    }
}

// ---------------------------------------------------------------------
// Fusion des assets de plugins
// ---------------------------------------------------------------------
//
// Chaque plugin peut poser un .css et un .js a cote de son .php. Le
// moteur les concatene dans un fichier unique, ordonne par la priorite
// declaree dans l'en-tete : 1 tout en haut, 64000 tout en bas, -1 pour
// rester dans un fichier separe. A priorite egale, l'ordre suit le nom
// du plugin, pour que le rendu soit reproductible.
//
// Le CSS de chaque plugin est enveloppe dans une couche @layer nommee :
// l'ordre de la cascade est alors declare explicitement en tete de
// fichier, et ne depend plus de l'ordre de concatenation.

define('CMS_CACHE_DIR', CMS_ROOT . '/assets/cache');
define('CMS_PRIORITE_DEFAUT', 32000);

function cms_asset_priority($entete, $type) {
    $cle = $type === 'css' ? 'css_priority' : 'js_priority';

    return isset($entete[$cle]) && $entete[$cle] !== ''
        ? (int) $entete[$cle]
        : CMS_PRIORITE_DEFAUT;
}

// Assets d'un type, tries par priorite puis par nom
function cms_collect_assets($type) {
    $fusion = [];
    $separes = [];

    foreach (cms_plugins_info() as $nom => $entete) {
        if (!$entete['charge']) {
            continue;
        }

        // L'asset porte le nom du fichier principal, a cote de lui
        $fichier = substr($entete['chemin'], 0, -4) . '.' . $type;
        if (!is_file($fichier)) {
            continue;
        }

        $slug = basename($entete['chemin'], '.php');
        $p    = cms_asset_priority($entete, $type);

        if ($p < 0) {
            $separes[$slug] = $fichier;
        } else {
            $fusion[] = ['priorite' => $p, 'slug' => $slug, 'fichier' => $fichier];
        }
    }

    usort($fusion, function ($a, $b) {
        return $a['priorite'] === $b['priorite']
            ? strcmp($a['slug'], $b['slug'])
            : $a['priorite'] - $b['priorite'];
    });

    ksort($separes);

    return [$fusion, $separes];
}

/**
 * Construit le fichier fusionne si besoin et renvoie son chemin relatif,
 * ou '' si aucun plugin n'apporte d'asset de ce type.
 */
function cms_build_bundle($type) {
    list($fusion, $separes) = cms_collect_assets($type);

    if (!$fusion) {
        return ['', $separes];
    }

    // L'empreinte couvre la liste, l'ordre et le contenu : si rien ne
    // change, on ne reecrit rien.
    $signature = '';
    foreach ($fusion as $a) {
        $signature .= $a['slug'] . ':' . $a['priorite'] . ':' . filemtime($a['fichier']) . ';';
    }
    $hash = substr(hash('sha256', $signature), 0, 12);

    $relatif = 'assets/cache/bundle-' . $hash . '.' . $type;
    $absolu  = CMS_ROOT . '/' . $relatif;

    if (is_file($absolu)) {
        return [$relatif, $separes];
    }

    if (!is_dir(CMS_CACHE_DIR) && !@mkdir(CMS_CACHE_DIR, 0755, true)) {
        return ['', $separes];
    }

    $sortie = '';

    if ($type === 'css') {
        $couches = [];
        foreach ($fusion as $a) {
            $couches[] = 'p_' . preg_replace('/[^a-z0-9_]/i', '_', $a['slug']);
        }
        $sortie .= '@layer ' . implode(', ', $couches) . ";\n";

        foreach ($fusion as $i => $a) {
            $sortie .= "\n/* " . $a['slug'] . ' — priorite ' . $a['priorite'] . " */\n";
            $sortie .= '@layer ' . $couches[$i] . " {\n" . file_get_contents($a['fichier']) . "\n}\n";
        }
    } else {
        foreach ($fusion as $a) {
            // Chaque plugin dans sa propre fonction : une erreur
            // d'execution chez l'un n'emporte pas les autres.
            $sortie .= "\n/* " . $a['slug'] . ' — priorite ' . $a['priorite'] . " */\n";
            $sortie .= "(function(){\ntry{\n" . file_get_contents($a['fichier'])
                . "\n}catch(e){if(window.console)console.error('Plugin " . $a['slug'] . " :',e);}\n})();\n";
        }
    }

    // Ecriture atomique, puis menage des anciennes versions
    $tmp = $absolu . '.tmp';
    if (file_put_contents($tmp, $sortie, LOCK_EX) === false || !@rename($tmp, $absolu)) {
        @unlink($tmp);

        return ['', $separes];
    }

    foreach (glob(CMS_CACHE_DIR . '/bundle-*.' . $type) as $vieux) {
        if ($vieux !== $absolu) {
            @unlink($vieux);
        }
    }

    return [$relatif, $separes];
}

// Balises <link> des styles apportes par les plugins
function cms_plugin_styles() {
    list($bundle, $separes) = cms_build_bundle('css');

    $html = '';

    if ($bundle !== '') {
        $html .= "\n    " . '<link rel="stylesheet" href="' . e(cms_asset_url($bundle)) . '">';
    }

    foreach ($separes as $slug => $fichier) {
        $rel = 'plugins/' . ltrim(str_replace(CMS_PLUGINS_DIR, '', $fichier), '/');
        $html .= "\n    " . '<link rel="stylesheet" href="' . e(cms_asset_url($rel)) . '">';
    }

    return $html;
}

// Balises <script> des scripts apportes par les plugins
function cms_plugin_scripts() {
    list($bundle, $separes) = cms_build_bundle('js');

    $html = '';

    // defer, jamais async : async casserait l'ordre des priorites.
    if ($bundle !== '') {
        $html .= '<script src="' . e(cms_asset_url($bundle)) . '" defer></script>';
    }

    foreach ($separes as $slug => $fichier) {
        $rel = 'plugins/' . ltrim(str_replace(CMS_PLUGINS_DIR, '', $fichier), '/');
        $html .= '<script src="' . e(cms_asset_url($rel)) . '" defer></script>';
    }

    return $html;
}
