<?php
require_once __DIR__ . '/includes/bootstrap.php';

// Slug demande. Sans parametre, on sert l'accueil.
$slug = isset($_GET['page']) ? trim((string) $_GET['page'], '/') : '';
if ($slug === '') {
    $slug = 'home';
}

// Un seul segment alphanumerique : neutralise les traversees de repertoire. repertoire.
// repertoire et les chemins absolus.
if (!cmsh_slug_ok($slug)) {
    cms_render_404($slug);
}

$page = cmsh_load($slug);

if ($page === null) {
    cms_render_404($slug);
}

// Metadonnees exposees a l'en-tete et aux plugins
$GLOBALS['cms_page'] = $page;

do_action('page_before', $page);

// Un theme peut fournir un gabarit complet et prendre la main sur tout le document.
$gabarit = cms_template('page');

if ($gabarit !== '' && strpos($gabarit, CMS_THEMES_DIR) === 0) {
    include $gabarit;
} else {
    include cms_template('header');

    // Le corps est de l'hypertexte, jamais du code : il est affiche tel
    // quel, sans etre execute. Les plugins peuvent le filtrer.
    echo apply_filters('page_content', $page['body'], $page['meta']);

    include cms_template('footer');
}

do_action('page_after', $page);
