<?php
// Gabarit Mairie : banniere photo, raccourcis de services, actualites

$meta       = $page['meta'];
$visuel     = cms_page_visual($meta, cms_asset_url('themes/mairie/hero.jpg'));
$raccourcis = (array) cms_config_get('shortcuts', []);

// Actualites : les autres pages, la plus recemment modifiee en tete
$actus = array_diff_key(cmsh_list(), [$page['slug'] => true]);
uasort($actus, function ($a, $b) {
    return strcmp((string) $b['meta']['modified'], (string) $a['meta']['modified']);
});

include cms_template('header');
?>

<section class="banniere" style="background-image: url('<?php echo e($visuel); ?>')">
    <div class="banniere-texte">
        <h1><?php echo e($meta['title']); ?></h1>
        <?php if ($meta['meta']['description'] !== ''): ?>
            <p><?php echo e($meta['meta']['description']); ?></p>
        <?php endif; ?>
    </div>
</section>

<?php if ($raccourcis): ?>
    <nav class="raccourcis-services" aria-label="Services en ligne">
        <ul class="dedans">
            <?php foreach ($raccourcis as $r): ?>
                <li>
                    <a href="<?php echo e(cms_url($r['link'])); ?>">
                        <svg viewBox="0 0 24 24" width="28" height="28" aria-hidden="true" focusable="false">
                            <rect x="3" y="3" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"/>
                            <path d="M7 9h10M7 13h10M7 17h6" fill="none" stroke="currentColor" stroke-width="2"/>
                        </svg>
                        <span><?php echo e($r['label']); ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>
<?php endif; ?>

<article class="corps">
    <?php echo apply_filters('page_content', $page['body'], $meta); ?>
</article>

<?php if ($actus): ?>
    <section class="actualites" aria-labelledby="actus-titre">
        <div class="dedans">
            <h2 id="actus-titre">Actualités</h2>

            <ul class="cartes-actu">
                <?php foreach (array_slice($actus, 0, 6, true) as $slug => $actu): ?>
                    <?php
                    $am     = $actu['meta'];
                    $vignette = $am['og']['image'] !== '' ? cms_url($am['og']['image']) : '';
                    $rubrique = !empty($am['hierarchy']['parent']) ? $am['hierarchy']['parent'] : '';
                    ?>
                    <li>
                        <a href="<?php echo e(cms_url($slug)); ?>">
                            <?php if ($vignette !== ''): ?>
                                <img src="<?php echo e($vignette); ?>" alt="" loading="lazy">
                            <?php else: ?>
                                <span class="vignette-vide" aria-hidden="true"></span>
                            <?php endif; ?>

                            <span class="actu-corps">
                                <?php if ($rubrique !== ''): ?>
                                    <span class="rubrique"><?php echo e($rubrique); ?></span>
                                <?php endif; ?>

                                <span class="actu-titre"><?php echo e($am['title'] !== '' ? $am['title'] : $slug); ?></span>

                                <?php if ($am['modified']): ?>
                                    <time datetime="<?php echo e($am['modified']); ?>">
                                        <?php echo e(date('d/m/Y', strtotime($am['modified']))); ?>
                                    </time>
                                <?php endif; ?>

                                <?php if ($am['meta']['description'] !== ''): ?>
                                    <span class="actu-texte"><?php echo e($am['meta']['description']); ?></span>
                                <?php endif; ?>
                            </span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </section>
<?php endif; ?>

<?php include cms_template('footer'); ?>
