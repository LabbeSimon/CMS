<?php
/**
 * Plugin: Compatibilite WordPress
 * Version: 1.0
 * Description: Fait tourner les plugins WordPress simples : hooks, options, echappement, shortcodes, assets.
 * Author: CMS
 * Load-Priority: 1
 * CSS-Priority: -1
 * JS-Priority: -1
 *
 * Ce que ca couvre : les plugins qui s'accrochent a un hook, lisent ou
 * ecrivent une option, echappent du texte, declarent un shortcode ou
 * ajoutent une feuille de style. C'est la majorite des petits plugins.
 *
 * Ce que ca ne couvrira jamais : tout ce qui suppose la base de donnees
 * de WordPress — $wpdb, WP_Query, get_post_meta, register_post_type,
 * l'API REST. Ces plugins-la sont des applications WordPress, pas des
 * extensions ; les convertir reviendrait a reecrire WordPress.
 *
 * Les options vivent dans data/wp-options.json, a part de config.json :
 * un plugin tiers n'a pas a ecrire dans la configuration du site.
 */

define('WPC_OPTIONS', CMS_DATA_DIR . '/wp-options.json');

// --- Hooks ------------------------------------------------------------
// add_action et add_filter existent deja et portent les memes noms. On
// complete juste ce que WordPress offre en plus.

if (!function_exists('remove_action')) {
    function remove_action($hook, $callback, $priority = 10) {
        if (isset($GLOBALS['cms_actions'][$hook][$priority])) {
            foreach ($GLOBALS['cms_actions'][$hook][$priority] as $i => $c) {
                if ($c === $callback) {
                    unset($GLOBALS['cms_actions'][$hook][$priority][$i]);
                }
            }
        }
    }
}

if (!function_exists('has_action')) {
    function has_action($hook, $callback = null) {
        return !empty($GLOBALS['cms_actions'][$hook]);
    }
}

if (!function_exists('has_filter')) {
    function has_filter($hook, $callback = null) {
        return !empty($GLOBALS['cms_filters'][$hook]);
    }
}

// Les plugins WordPress s'accrochent a wp_head, wp_footer, the_content.
// On rejoue ces noms sur les points d'accroche du CMS.
add_action('init', function () {
    do_action('plugins_loaded');
    do_action('wp_loaded');
    if (cms_is_admin()) {
        do_action('admin_init');
    }
}, 5);

add_action('head', function () {
    do_action('wp_enqueue_scripts');
    do_action('wp_head');
    echo wpc_assets('head');
});

add_action('footer', function () {
    do_action('wp_footer');
    echo wpc_assets('footer');
});

add_filter('page_content', function ($contenu) {
    $contenu = do_shortcode($contenu);
    return apply_filters('the_content', $contenu);
}, 20);

// --- Options ----------------------------------------------------------

function wpc_options($nouvelles = null) {
    static $options = null;

    if ($nouvelles !== null) {
        $options = $nouvelles;
        cms_write_json(WPC_OPTIONS, $options);
        return $options;
    }
    if ($options === null) {
        $options = cms_read_json(WPC_OPTIONS);
    }

    return $options;
}

if (!function_exists('get_option')) {
    function get_option($nom, $defaut = false) {
        $o = wpc_options();
        return array_key_exists($nom, $o) ? $o[$nom] : $defaut;
    }
}

if (!function_exists('update_option')) {
    function update_option($nom, $valeur) {
        $o = wpc_options();
        $o[$nom] = $valeur;
        wpc_options($o);
        return true;
    }
}

if (!function_exists('add_option')) {
    function add_option($nom, $valeur = '') {
        $o = wpc_options();
        if (array_key_exists($nom, $o)) {
            return false;
        }
        return update_option($nom, $valeur);
    }
}

if (!function_exists('delete_option')) {
    function delete_option($nom) {
        $o = wpc_options();
        unset($o[$nom]);
        wpc_options($o);
        return true;
    }
}

// --- Echappement et nettoyage ----------------------------------------

if (!function_exists('esc_html')) { function esc_html($t) { return e($t); } }
if (!function_exists('esc_attr')) { function esc_attr($t) { return e($t); } }
if (!function_exists('esc_textarea')) { function esc_textarea($t) { return e($t); } }
if (!function_exists('esc_js')) { function esc_js($t) { return addslashes((string) $t); } }

if (!function_exists('esc_url')) {
    function esc_url($url) {
        $url = trim((string) $url);
        // On refuse tout ce qui n'est pas http, https, mailto ou relatif
        if (preg_match('#^[a-z][a-z0-9+.-]*:#i', $url)
            && !preg_match('#^(https?|mailto|tel):#i', $url)) {
            return '';
        }
        return e($url);
    }
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($t) {
        return trim(preg_replace('/[\r\n\t]+/', ' ', strip_tags((string) $t)));
    }
}

if (!function_exists('sanitize_key')) {
    function sanitize_key($t) {
        return preg_replace('/[^a-z0-9_\-]/', '', strtolower((string) $t));
    }
}

if (!function_exists('wp_kses_post')) {
    function wp_kses_post($html) {
        return strip_tags((string) $html,
            '<p><br><strong><em><a><ul><ol><li><h2><h3><h4><blockquote><img><figure><figcaption><hr><table><thead><tbody><tr><th><td>');
    }
}

if (!function_exists('absint')) { function absint($n) { return abs((int) $n); } }

if (!function_exists('wp_json_encode')) {
    function wp_json_encode($d, $flags = 0) { return json_encode($d, $flags | JSON_UNESCAPED_UNICODE); }
}

if (!function_exists('wp_parse_args')) {
    function wp_parse_args($args, $defauts = []) {
        return array_merge($defauts, (array) $args);
    }
}

// --- Traductions : passe-plat ----------------------------------------

if (!function_exists('__')) { function __($t, $d = null) { return $t; } }
if (!function_exists('_e')) { function _e($t, $d = null) { echo $t; } }
if (!function_exists('_x')) { function _x($t, $c = '', $d = null) { return $t; } }
if (!function_exists('esc_html__')) { function esc_html__($t, $d = null) { return e($t); } }
if (!function_exists('esc_html_e')) { function esc_html_e($t, $d = null) { echo e($t); } }
if (!function_exists('esc_attr__')) { function esc_attr__($t, $d = null) { return e($t); } }
if (!function_exists('_n')) { function _n($s, $p, $n, $d = null) { return $n > 1 ? $p : $s; } }
if (!function_exists('load_plugin_textdomain')) { function load_plugin_textdomain() { return false; } }

// --- URL et chemins ---------------------------------------------------

if (!function_exists('home_url')) {
    function home_url($chemin = '') { return cms_url(ltrim((string) $chemin, '/')); }
}
if (!function_exists('site_url')) {
    function site_url($chemin = '') { return home_url($chemin); }
}
if (!function_exists('admin_url')) {
    function admin_url($chemin = '') { return cms_url('admin/' . ltrim((string) $chemin, '/')); }
}
if (!function_exists('plugins_url')) {
    function plugins_url($chemin = '', $base = '') {
        $dossier = $base !== '' ? basename(dirname($base)) : '';
        return cms_url('plugins/' . ($dossier ? $dossier . '/' : '') . ltrim((string) $chemin, '/'));
    }
}
if (!function_exists('plugin_dir_path')) {
    function plugin_dir_path($fichier) { return rtrim(dirname($fichier), '/') . '/'; }
}
if (!function_exists('plugin_dir_url')) {
    function plugin_dir_url($fichier) {
        return cms_url('plugins/' . basename(dirname($fichier)) . '/');
    }
}
if (!function_exists('plugin_basename')) {
    function plugin_basename($fichier) { return basename(dirname($fichier)) . '/' . basename($fichier); }
}

// --- Utilisateur et contexte -----------------------------------------

if (!function_exists('is_user_logged_in')) {
    function is_user_logged_in() { return cms_is_admin(); }
}
if (!function_exists('current_user_can')) {
    function current_user_can($cap) { return cms_is_admin(); }
}
if (!function_exists('is_admin')) {
    function is_admin() {
        return strpos((string) (isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : ''), '/admin/') !== false;
    }
}
if (!function_exists('get_bloginfo')) {
    function get_bloginfo($quoi = 'name') {
        switch ($quoi) {
            case 'description': return cms_config_get('meta_description', '');
            case 'url': case 'wpurl': return home_url();
            case 'charset': return 'UTF-8';
            case 'language': return 'fr-FR';
            case 'version': return CMS_VERSION;
            default: return cms_config_get('page_title', '');
        }
    }
}
if (!function_exists('wp_die')) {
    function wp_die($message = '') { http_response_code(500); exit(strip_tags((string) $message)); }
}

// --- Jetons : on reutilise le CSRF du CMS -----------------------------

if (!function_exists('wp_create_nonce')) {
    function wp_create_nonce($action = '') { return csrf_token(); }
}
if (!function_exists('wp_verify_nonce')) {
    function wp_verify_nonce($nonce, $action = '') { return csrf_valid($nonce) ? 1 : false; }
}
if (!function_exists('wp_nonce_field')) {
    function wp_nonce_field($action = '', $nom = '_wpnonce', $referer = true, $afficher = true) {
        $html = '<input type="hidden" name="' . e($nom) . '" value="' . e(csrf_token()) . '">'
              . '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
        if ($afficher) { echo $html; }
        return $html;
    }
}

// --- Requetes sortantes -----------------------------------------------

if (!function_exists('wp_remote_get')) {
    function wp_remote_get($url, $args = []) {
        $ctx = stream_context_create(['http' => [
            'timeout' => isset($args['timeout']) ? $args['timeout'] : 10,
            'ignore_errors' => true,
        ]]);
        $corps = @file_get_contents($url, false, $ctx);

        return $corps === false
            ? ['response' => ['code' => 0], 'body' => '']
            : ['response' => ['code' => 200], 'body' => $corps];
    }
}
if (!function_exists('wp_remote_retrieve_body')) {
    function wp_remote_retrieve_body($r) { return isset($r['body']) ? $r['body'] : ''; }
}
if (!function_exists('wp_remote_retrieve_response_code')) {
    function wp_remote_retrieve_response_code($r) { return isset($r['response']['code']) ? $r['response']['code'] : 0; }
}
if (!function_exists('is_wp_error')) {
    function is_wp_error($chose) { return false; }
}

// --- Shortcodes -------------------------------------------------------
// Beaucoup de petits plugins ne sont que ca. C'est aussi utile au CMS
// lui-meme : ecrire [contact] dans une page devient possible.

$GLOBALS['wpc_shortcodes'] = [];

if (!function_exists('add_shortcode')) {
    function add_shortcode($balise, $callback) {
        $GLOBALS['wpc_shortcodes'][$balise] = $callback;
    }
}
if (!function_exists('remove_shortcode')) {
    function remove_shortcode($balise) { unset($GLOBALS['wpc_shortcodes'][$balise]); }
}
if (!function_exists('shortcode_atts')) {
    function shortcode_atts($defauts, $atts, $balise = '') {
        return array_merge($defauts, is_array($atts) ? $atts : []);
    }
}

if (!function_exists('do_shortcode')) {
    function do_shortcode($contenu) {
        if (!$GLOBALS['wpc_shortcodes'] || strpos($contenu, '[') === false) {
            return $contenu;
        }

        $balises = implode('|', array_map('preg_quote', array_keys($GLOBALS['wpc_shortcodes'])));

        // [balise attr="valeur"] et [balise]contenu[/balise]
        return preg_replace_callback(
            '/\[(' . $balises . ')((?:\s+[a-z0-9_-]+=(?:"[^"]*"|\'[^\']*\'|[^\s\]]+))*)\s*\](?:(.*?)\[\/\1\])?/is',
            function ($m) {
                $atts = [];
                if (preg_match_all('/([a-z0-9_-]+)=("[^"]*"|\'[^\']*\'|[^\s\]]+)/i', $m[2], $paires, PREG_SET_ORDER)) {
                    foreach ($paires as $p) {
                        $atts[strtolower($p[1])] = trim($p[2], "\"'");
                    }
                }
                $rendu = call_user_func($GLOBALS['wpc_shortcodes'][$m[1]], $atts,
                    isset($m[3]) ? $m[3] : null, $m[1]);

                return (string) $rendu;
            },
            $contenu
        );
    }
}

// --- Assets -----------------------------------------------------------
// Les fichiers locaux d'un plugin passent par le moteur de fusion. Les
// URL externes sont emises telles quelles, mais signalees : elles vont
// a l'encontre du principe du projet.

$GLOBALS['wpc_assets'] = ['head' => [], 'footer' => []];

function wpc_enqueue($type, $handle, $src, $pied) {
    if ($src === '' || $src === false) {
        return;
    }
    $zone = $pied ? 'footer' : 'head';
    $GLOBALS['wpc_assets'][$zone][$handle] = ['type' => $type, 'src' => $src];
}

if (!function_exists('wp_enqueue_style')) {
    function wp_enqueue_style($handle, $src = '', $deps = [], $ver = false, $media = 'all') {
        wpc_enqueue('css', $handle, $src, false);
    }
}
if (!function_exists('wp_enqueue_script')) {
    function wp_enqueue_script($handle, $src = '', $deps = [], $ver = false, $pied = false) {
        wpc_enqueue('js', $handle, $src, $pied);
    }
}
if (!function_exists('wp_register_style')) {
    function wp_register_style($handle, $src = '', $deps = [], $ver = false) { wpc_enqueue('css', $handle, $src, false); }
}
if (!function_exists('wp_register_script')) {
    function wp_register_script($handle, $src = '', $deps = [], $ver = false, $pied = false) { wpc_enqueue('js', $handle, $src, $pied); }
}
if (!function_exists('wp_localize_script')) {
    function wp_localize_script($handle, $objet, $donnees) {
        $GLOBALS['wpc_assets']['head']['loc-' . $handle] = [
            'type' => 'inline-js',
            'src' => 'var ' . $objet . ' = ' . wp_json_encode($donnees) . ';',
        ];
    }
}
if (!function_exists('wp_add_inline_style')) {
    function wp_add_inline_style($handle, $css) {
        $GLOBALS['wpc_assets']['head']['inline-' . $handle] = ['type' => 'inline-css', 'src' => $css];
    }
}

function wpc_assets($zone) {
    $html = '';

    foreach ($GLOBALS['wpc_assets'][$zone] as $a) {
        if ($a['type'] === 'css') {
            $html .= '<link rel="stylesheet" href="' . esc_url($a['src']) . '">';
        } elseif ($a['type'] === 'js') {
            $html .= '<script src="' . esc_url($a['src']) . '" defer></script>';
        } elseif ($a['type'] === 'inline-css') {
            $html .= '<style>' . $a['src'] . '</style>';
        } elseif ($a['type'] === 'inline-js') {
            $html .= '<script nonce="' . e(cms_nonce()) . '">' . $a['src'] . '</script>';
        }
    }

    return $html;
}

// --- Cycle de vie : sans objet ici ------------------------------------

if (!function_exists('register_activation_hook')) { function register_activation_hook($f, $c) {} }
if (!function_exists('register_deactivation_hook')) { function register_deactivation_hook($f, $c) {} }
if (!function_exists('add_option_update_handler')) { function add_option_update_handler() {} }

// --- Mise en forme du texte -------------------------------------------

if (!function_exists('wptexturize')) {
    // Version courte : apostrophes et guillemets typographiques, tirets.
    function wptexturize($texte) {
        $texte = (string) $texte;
        $texte = str_replace("'", '’', $texte);
        $texte = preg_replace('/"([^"]*)"/u', '« $1 »', $texte);
        $texte = str_replace('--', '—', $texte);
        return $texte;
    }
}

if (!function_exists('wp_strip_all_tags')) {
    function wp_strip_all_tags($t, $sauts = false) {
        $t = strip_tags((string) $t);
        return $sauts ? trim(preg_replace('/[\r\n\t ]+/', ' ', $t)) : $t;
    }
}

if (!function_exists('sanitize_title')) {
    function sanitize_title($t) {
        $t = strtolower(trim((string) $t));
        $t = preg_replace('/[^a-z0-9]+/', '-', $t);
        return trim($t, '-');
    }
}

if (!function_exists('esc_url_raw')) { function esc_url_raw($u) { return (string) $u; } }
if (!function_exists('wpautop')) {
    function wpautop($t) {
        $t = trim((string) $t);
        return $t === '' ? '' : '<p>' . preg_replace('/\n{2,}/', "</p>\n<p>", $t) . '</p>';
    }
}
if (!function_exists('checked')) {
    function checked($valeur, $courant = true, $afficher = true) {
        $r = ((string) $valeur === (string) $courant) ? ' checked' : '';
        if ($afficher) { echo $r; }
        return $r;
    }
}
if (!function_exists('selected')) {
    function selected($valeur, $courant = true, $afficher = true) {
        $r = ((string) $valeur === (string) $courant) ? ' selected' : '';
        if ($afficher) { echo $r; }
        return $r;
    }
}
if (!function_exists('number_format_i18n')) {
    function number_format_i18n($n, $dec = 0) { return number_format((float) $n, $dec, ',', ' '); }
}
if (!function_exists('date_i18n')) {
    function date_i18n($format, $ts = null) { return date($format, $ts === null ? time() : $ts); }
}
if (!function_exists('current_time')) {
    function current_time($type = 'timestamp') { return $type === 'timestamp' ? time() : date('Y-m-d H:i:s'); }
}

// --- Langue et utilisateur courant ------------------------------------

if (!function_exists('get_locale')) { function get_locale() { return 'fr_FR'; } }
if (!function_exists('get_user_locale')) { function get_user_locale($u = 0) { return get_locale(); } }
if (!function_exists('is_rtl')) { function is_rtl() { return false; } }
if (!function_exists('get_current_user_id')) {
    function get_current_user_id() { return cms_is_admin() ? 1 : 0; }
}
if (!function_exists('wp_get_current_user')) {
    function wp_get_current_user() {
        $u = cms_current_user();
        return (object) [
            'ID' => cms_is_admin() ? 1 : 0,
            'user_login' => $u ? $u['username'] : '',
            'display_name' => $u ? $u['username'] : '',
            'user_email' => $u && isset($u['email']) ? $u['email'] : '',
        ];
    }
}
