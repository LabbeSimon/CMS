<?php
// Gabarit Sportive : hero, raccourcis, actualites, equipes, infos pratiques

$meta       = $page['meta'];
$visuel     = cms_page_visual($meta, cms_asset_url('themes/association-sportive/hero.jpg'));
$raccourcis = (array) cms_config_get('shortcuts', []);
$contact    = (array) cms_config_get('contact', []);

$autres = array_diff_key(cmsh_list(), [$page['slug'] => true]);
uasort($autres, function ($a, $b) {
    return strcmp((string) $b['meta']['modified'], (string) $a['meta']['modified']);
});

include cms_template('header');
?>

<section class="hero" style="background-image: url('<?php echo e($visuel); ?>')">
    <div class="hero-texte">
        <h1><?php echo e($meta['title']); ?></h1>
        <?php if ($meta['meta']['description'] !== ''): ?>
            <p><?php echo e($meta['meta']['description']); ?></p>
        <?php endif; ?>

        <?php if ($raccourcis): ?>
            <p class="hero-actions">
                <?php foreach (array_slice($raccourcis, 0, 2) as $r): ?>
                    <a class="bouton" href="<?php echo e(cms_url($r['link'])); ?>"><?php echo e($r['label']); ?></a>
                <?php endforeach; ?>
            </p>
        <?php endif; ?>
    </div>
</section>

<article class="corps">
    <?php echo apply_filters('page_content', $page['body'], $meta); ?>
</article>

<?php if ($autres): ?>
    <section class="sections-club" aria-labelledby="sections-titre">
        <h2 id="sections-titre">Actualités du club</h2>

        <ul class="cartes">
            <?php foreach ($autres as $slug => $autre): ?>
                <?php $vignette = $autre['meta']['og']['image'] !== '' ? cms_url($autre['meta']['og']['image']) : ''; ?>
                <li>
                    <a href="<?php echo e(cms_url($slug)); ?>">
                        <?php if ($vignette !== ''): ?>
                            <img src="<?php echo e($vignette); ?>" alt="" loading="lazy">
                        <?php endif; ?>

                        <span class="carte-corps">
                            <span class="carte-titre"><?php echo e($autre['meta']['title'] !== '' ? $autre['meta']['title'] : $slug); ?></span>

                            <?php if ($autre['meta']['modified']): ?>
                                <time datetime="<?php echo e($autre['meta']['modified']); ?>">
                                    <?php echo e(date('d/m/Y', strtotime($autre['meta']['modified']))); ?>
                                </time>
                            <?php endif; ?>

                            <?php if ($autre['meta']['meta']['description'] !== ''): ?>
                                <span class="carte-texte"><?php echo e($autre['meta']['meta']['description']); ?></span>
                            <?php endif; ?>
                        </span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php endif; ?>

<section class="pratique">
    <div class="dedans pratique-colonnes">
        <div>
            <h2>Nous rejoindre</h2>
            <p><?php echo e(cms_config_get('footer_text', '')); ?></p>
            <?php if ($raccourcis): ?>
                <p><a class="bouton" href="<?php echo e(cms_url($raccourcis[0]['link'])); ?>"><?php echo e($raccourcis[0]['label']); ?></a></p>
            <?php endif; ?>
        </div>

        <div>
            <h2>Infos pratiques</h2>
            <?php if (!empty($contact['adresse'])): ?><p><?php echo e($contact['adresse']); ?></p><?php endif; ?>
            <?php if (!empty($contact['horaires'])): ?><p><?php echo e($contact['horaires']); ?></p><?php endif; ?>
            <?php if (!empty($contact['telephone'])): ?>
                <p><a href="tel:<?php echo e(preg_replace('/\s+/', '', $contact['telephone'])); ?>"><?php echo e($contact['telephone']); ?></a></p>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include cms_template('footer'); ?>
