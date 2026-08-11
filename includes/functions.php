<?php
/** Moteur de plugins : actions, filtres, chargement. */

$GLOBALS['cms_actions'] = [];
$GLOBALS['cms_filters'] = [];
$GLOBALS['cms_plugins'] = [];

// ---------------------------------------------------------------------

function add_action($hook, $callback, $priority = 10)
{
    $GLOBALS['cms_actions'][$hook][(int) $priority][] = $callback;
}

function do_action($hook)
{
    if (empty($GLOBALS['cms_actions'][$hook])) {
        return;
    }

    $args = array_slice(func_get_args(), 1);
    $parPriorite = $GLOBALS['cms_actions'][$hook];
    ksort($parPriorite);

    foreach ($parPriorite as $callbacks) {
        foreach ($callbacks as $callback) {
            call_user_func_array($callback, $args);
        }
    }
}

// ---------------------------------------------------------------------

function add_filter($hook, $callback, $priority = 10)
{
    $GLOBALS['cms_filters'][$hook][(int) $priority][] = $callback;
}

function apply_filters($hook, $value)
{
    if (empty($GLOBALS['cms_filters'][$hook])) {
        return $value;
    }

    $extra       = array_slice(func_get_args(), 2);
    $parPriorite = $GLOBALS['cms_filters'][$hook];
    ksort($parPriorite);

    foreach ($parPriorite as $callbacks) {
        foreach ($callbacks as $callback) {
            $value = call_user_func_array($callback, array_merge([$value], $extra));
        }
    }

    return $value;
}

// ---------------------------------------------------------------------

/** Lit l'en-tete d'un fichier de plugin sans l'executer */
function cms_plugin_headers($fichier)
{
    $entete = (string) file_get_contents($fichier, false, null, 0, 8192);

    $champs = [
        'plugin'       => 'Plugin',
        'version'      => 'Version',
        'description'  => 'Description',
        'author'       => 'Author',
        'css_priority' => 'CSS-Priority',
        'js_priority'  => 'JS-Priority',
    ];

    $trouve = [];
    foreach ($champs as $cle => $libelle) {
        if (preg_match('/^[ \t\/*#@]*' . preg_quote($libelle, '/') . '\s*:\s*(.+)$/mi', $entete, $m)) {
            $trouve[$cle] = trim($m[1]);
        } else {
            $trouve[$cle] = '';
        }
    }

    $trouve['file'] = basename($fichier);

    return $trouve;
}

/** Inventaire de plugins/, en-tetes compris. Ne charge rien */
function cms_plugins_info()
{
    $liste = [];

    foreach (glob(CMS_PLUGINS_DIR . '/*.php') as $fichier) {
        $entete            = cms_plugin_headers($fichier);
        $entete['charge']  = $entete['plugin'] !== '';
        $liste[basename($fichier)] = $entete;
    }

    ksort($liste);

    return $liste;
}

/** Charge les plugins declares, avant tout affichage. */
function cms_load_plugins()
{
    foreach (cms_plugins_info() as $nom => $entete) {
        if (!$entete['charge']) {
            continue;
        }

        $chemin = CMS_PLUGINS_DIR . '/' . $nom;
        $GLOBALS['cms_plugins'][$nom] = $entete;

        require_once $chemin;
    }
}
