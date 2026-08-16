<?php
// Theme Cabinet : masthead centre, navigation sous filet
require_once dirname(dirname(__DIR__)) . '/includes/bootstrap.php';
?>
<!DOCTYPE html>
<html lang="<?php echo e(cms_page_lang()); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php echo cms_page_head(); ?>
</head>
<body class="theme-avocat">
    <a class="saut-contenu" href="#contenu">Aller au contenu</a>

    <header>
        <a class="masthead" href="<?php echo e(cms_base_uri()); ?>">
            <span class="masthead-nom"><?php echo e(cms_config_get('page_title', '')); ?></span>
        </a>

        <?php if (cms_config_get('meta_description', '') !== ''): ?>
            <p class="masthead-baseline"><?php echo e(cms_config_get('meta_description', '')); ?></p>
        <?php endif; ?>

        <nav aria-label="Navigation principale">
            <ul>
                <?php foreach (cms_nav_items() as $libelle => $lien): ?>
                    <li><a href="<?php echo e(cms_url($lien)); ?>"><?php echo e($libelle); ?></a></li>
                <?php endforeach; ?>
                <li class="nav-service"><?php echo cms_admin_links(); ?></li>
            </ul>
        </nav>
    </header>

    <main id="contenu">
