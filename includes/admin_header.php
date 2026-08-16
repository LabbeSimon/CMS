<?php
// Coquille du panneau d'administration : barre horizontale (marque,
require_once __DIR__ . '/bootstrap.php';

$adminTitle   = isset($adminTitle) ? $adminTitle : 'Administration';
$adminSection = isset($adminSection) ? $adminSection : '';

$utilisateur = cms_current_user();
$base        = cms_base_uri();

$menu = [
    'tableau'  => ['Tableau de bord', 'index.php'],
    'pages'    => ['Pages',           'pages.php'],
    'medias'   => ['Médias',          'media.php'],
    'plugins'  => ['Plugins',         'plugins.php'],
    'reglages' => ['Réglages',        'settings.php'],
    'securite' => ['Sécurité',        'security.php'],
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title><?php echo e($adminTitle); ?> — Administration</title>
    <link rel="stylesheet" href="<?php echo e(cms_asset_url('styles.css')); ?>">
    <?php echo cms_theme_link(); ?>
    <?php do_action('admin_head'); ?>
</head>
<body class="admin">
    <a class="saut-contenu" href="#contenu">Aller au contenu</a>

    <header class="barre">
        <a class="marque" href="<?php echo e($base); ?>admin/index.php">CMS</a>

        <nav class="raccourcis" aria-label="Raccourcis">
            <a href="<?php echo e($base); ?>admin/add_page.php">Nouvelle page</a>
            <a href="<?php echo e($base); ?>admin/add_plugin.php">Nouveau plugin</a>
            <a href="<?php echo e($base); ?>" target="_blank" rel="noopener">Voir le site</a>
        </nav>

        <div class="compte">

            <?php if (!empty($utilisateur['picture'])): ?>
                <img class="avatar" src="<?php echo e($utilisateur['picture']); ?>" alt="" width="28" height="28">
            <?php endif; ?>

            <span class="identite">
                <?php echo e(isset($_SESSION['username']) ? $_SESSION['username'] : ''); ?>
                <?php if (isset($utilisateur['provider']) && $utilisateur['provider'] === 'rayor'): ?>
                    <span class="etiquette">Rayor</span>
                <?php endif; ?>
            </span>

            <a href="<?php echo e($base); ?>logout.php?token=<?php echo urlencode(csrf_token()); ?>">Deconnexion</a>
        </div>
    </header>

    <div class="bande" aria-hidden="true"></div>

    <div class="cadre">
        <nav class="lateral" aria-label="Gestion">
            <p class="groupe">Contenu</p>
            <ul>
                <?php foreach ($menu as $cle => $entree): ?>
                    <li>
                        <a href="<?php echo e($base . 'admin/' . $entree[1]); ?>"
                           <?php echo $cle === $adminSection ? 'class="actif" aria-current="page"' : ''; ?>>
                            <?php echo e($entree[0]); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>

        <main class="contenu" id="contenu">
            <?php do_action('admin_notices'); ?>
            <h1><?php echo e($adminTitle); ?></h1>
