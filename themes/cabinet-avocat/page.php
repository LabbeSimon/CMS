<?php
// Gabarit Cabinet : accroche, domaines, publications, honoraires et rendez-vous

$meta    = $page['meta'];
$visuel  = cms_page_visual($meta, cms_asset_url('themes/cabinet-avocat/hero.jpg'));
$contact = (array) cms_config_get('contact', []);

$autres = array_diff_key(cmsh_list(), [$page['slug'] => true]);
uasort($autres, function ($a, $b) {
    return strcmp((string) $b['meta']['modified'], (string) $a['meta']['modified']);
});

include cms_template('header');
?>

<section class="bandeau" style="background-image: url('<?php echo e($visuel); ?>')">
    <div class="bandeau-texte">
        <h1><?php echo e($meta['title']); ?></h1>
        <?php if ($meta['meta']['description'] !== ''): ?>
            <p><?php echo e($meta['meta']['description']); ?></p>
        <?php endif; ?>
    </div>
</section>

<article class="corps">
    <?php echo apply_filters('page_content', $page['body'], $meta); ?>
</article>

<?php if ($autres): ?>
    <section class="domaines" aria-labelledby="domaines-titre">
        <h2 id="domaines-titre">Domaines d'intervention</h2>

        <div class="colonnes">
            <?php foreach ($autres as $slug => $autre): ?>
                <article>
                    <h3><a href="<?php echo e(cms_url($slug)); ?>"><?php echo e($autre['meta']['title'] !== '' ? $autre['meta']['title'] : $slug); ?></a></h3>
                    <?php if ($autre['meta']['meta']['description'] !== ''): ?>
                        <p><?php echo e($autre['meta']['meta']['description']); ?></p>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="publications" aria-labelledby="publications-titre">
        <h2 id="publications-titre">Publications</h2>

        <ul class="liste-datee">
            <?php foreach (array_slice($autres, 0, 5, true) as $slug => $autre): ?>
                <li>
                    <?php if ($autre['meta']['modified']): ?>
                        <time datetime="<?php echo e($autre['meta']['modified']); ?>">
                            <?php echo e(date('d.m.Y', strtotime($autre['meta']['modified']))); ?>
                        </time>
                    <?php endif; ?>
                    <a href="<?php echo e(cms_url($slug)); ?>"><?php echo e($autre['meta']['title'] !== '' ? $autre['meta']['title'] : $slug); ?></a>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php endif; ?>

<section class="appel">
    <h2>Prendre rendez-vous</h2>

    <?php if (!empty($contact['adresse'])): ?>
        <p><?php echo e($contact['adresse']); ?></p>
    <?php endif; ?>

    <?php if (!empty($contact['telephone'])): ?>
        <p class="appel-tel">
            <a href="tel:<?php echo e(preg_replace('/\s+/', '', $contact['telephone'])); ?>"><?php echo e($contact['telephone']); ?></a>
        </p>
    <?php endif; ?>

    <?php if (!empty($contact['horaires'])): ?>
        <p class="appel-horaires"><?php echo e($contact['horaires']); ?></p>
    <?php endif; ?>

    <?php if (!empty($contact['email'])): ?>
        <p><a class="bouton" href="mailto:<?php echo e($contact['email']); ?>">Écrire au cabinet</a></p>
    <?php endif; ?>
</section>

<?php include cms_template('footer'); ?>
