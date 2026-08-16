<?php
// Theme Sportive : banniere, bande diagonale, navigation en bandeau
require_once dirname(dirname(__DIR__)) . '/includes/bootstrap.php';
?>
<!DOCTYPE html>
<html lang="<?php echo e(cms_page_lang()); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php echo cms_page_head(); ?>
</head>
<body class="theme-sport">
    <a class="saut-contenu" href="#contenu">Aller au contenu</a>

    <header class="banniere">
        <div class="dedans">
            <a class="club" href="<?php echo e(cms_base_uri()); ?>">
                <?php echo e(cms_config_get('page_title', '')); ?>
            </a>
            <?php if (cms_config_get('meta_description', '') !== ''): ?>
                <p class="club-baseline"><?php echo e(cms_config_get('meta_description', '')); ?></p>
            <?php endif; ?>
        </div>
    </header>

    <div class="bande-sport" aria-hidden="true"></div>

    <nav class="nav-bandeau" aria-label="Navigation principale">
        <ul class="dedans">
            <?php foreach (cms_nav_items() as $libelle => $lien): ?>
                <li><a href="<?php echo e(cms_url($lien)); ?>"><?php echo e($libelle); ?></a></li>
            <?php endforeach; ?>
            <li class="nav-service"><?php echo cms_admin_links(); ?></li>
        </ul>
    </nav>

    <main id="contenu">
