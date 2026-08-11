<?php
/** Theme Mairie : barre utilitaire, bloc-marque, navigation pleine largeur. */
require_once dirname(dirname(__DIR__)) . '/includes/bootstrap.php';
?>
<!DOCTYPE html>
<html lang="<?php echo e(cms_page_lang()); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php echo cms_page_head(); ?>
</head>
<body class="theme-mairie">
    <a class="saut-contenu" href="#contenu">Aller au contenu</a>

    <div class="barre-utilitaire">
        <div class="dedans">
            <span><?php echo e(cms_config_get('meta_description', '')); ?></span>
            <span class="liens-service"><?php echo cms_admin_links(); ?></span>
        </div>
    </div>

    <header>
        <div class="dedans">
            <a class="bloc-marque" href="<?php echo e(cms_base_uri()); ?>">
                <span class="marque-nom"><?php echo e(cms_config_get('page_title', '')); ?></span>
                <span class="marque-sous">Site officiel</span>
            </a>
        </div>
    </header>

    <nav class="nav-principale" aria-label="Navigation principale">
        <ul class="dedans">
            <?php foreach (cms_nav_items() as $libelle => $lien): ?>
                <li><a href="<?php echo e(cms_url($lien)); ?>"><?php echo e($libelle); ?></a></li>
            <?php endforeach; ?>
        </ul>
    </nav>

    <main id="contenu" class="dedans">
