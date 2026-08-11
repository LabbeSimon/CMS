<?php
require_once __DIR__ . '/bootstrap.php';

// Metadonnees de la page en cours, posees par index.php
$page = isset($GLOBALS['cms_page']) ? $GLOBALS['cms_page'] : null;
$meta = $page !== null ? $page['meta'] : cmsh_defaults();

$titreSite   = cms_config_get('page_title', 'CMS');
$titrePage   = trim((string) $meta['title']);
$titre       = $titrePage !== '' ? $titrePage . ' — ' . $titreSite : $titreSite;

$description = $meta['meta']['description'] !== ''
    ? $meta['meta']['description']
    : cms_config_get('meta_description', '');

$robots    = $meta['meta']['robots'] !== '' ? $meta['meta']['robots'] : 'index, follow';
$langue    = $meta['meta']['lang'] !== '' ? $meta['meta']['lang'] : 'fr';
$canonical = $meta['meta']['canonical'];

// Partage social : les valeurs Open Graph retombent sur celles de la page
$ogTitre  = $meta['og']['title'] !== ''       ? $meta['og']['title']       : ($titrePage !== '' ? $titrePage : $titreSite);
$ogDesc   = $meta['og']['description'] !== '' ? $meta['og']['description'] : $description;
$ogImage  = $meta['og']['image'];
$ogType   = $meta['og']['type'] !== '' ? $meta['og']['type'] : 'website';

$navbarItems = cms_config_get('navbar', ['Accueil' => 'index.php']);
?>
<!DOCTYPE html>
<html lang="<?php echo e($langue); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($titre); ?></title>

    <?php if ($description !== ''): ?>
        <meta name="description" content="<?php echo e($description); ?>">
    <?php endif; ?>
    <?php if ($meta['meta']['keywords'] !== ''): ?>
        <meta name="keywords" content="<?php echo e($meta['meta']['keywords']); ?>">
    <?php endif; ?>
    <meta name="robots" content="<?php echo e($robots); ?>">
    <?php if ($canonical !== ''): ?>
        <link rel="canonical" href="<?php echo e($canonical); ?>">
    <?php endif; ?>

    <meta property="og:title" content="<?php echo e($ogTitre); ?>">
    <?php if ($ogDesc !== ''): ?>
        <meta property="og:description" content="<?php echo e($ogDesc); ?>">
    <?php endif; ?>
    <?php if ($ogImage !== ''): ?>
        <meta property="og:image" content="<?php echo e($ogImage); ?>">
    <?php endif; ?>
    <meta property="og:type" content="<?php echo e($ogType); ?>">
    <meta name="twitter:card" content="<?php echo $ogImage !== '' ? 'summary_large_image' : 'summary'; ?>">

    <?php echo cms_styles_links(); ?>
    <?php do_action('head', $meta); ?>
</head>
<body>
    <a class="saut-contenu" href="#contenu">Aller au contenu</a>

    <header>
        <nav aria-label="Navigation principale">
            <ul>
                <?php foreach ((array) $navbarItems as $label => $link): ?>
                    <li><a href="<?php echo e(cms_url($link)); ?>"><?php echo e($label); ?></a></li>
                <?php endforeach; ?>
            </ul>
        </nav>

        <div class="outils">

            <?php if (cms_is_admin()): ?>
                <span class="admin-menu">
                    <a href="<?php echo e(cms_url('admin/index.php')); ?>">Administration</a>
                    <a href="<?php echo e(cms_url('logout.php')); ?>?token=<?php echo urlencode(csrf_token()); ?>">Deconnexion</a>
                </span>
            <?php else: ?>
                <a href="<?php echo e(cms_url('login.php')); ?>">Connexion</a>
            <?php endif; ?>
        </div>
    </header>

    <main id="contenu">
