<?php
// Gabarit Protection : appel au don, chiffres, actions, voies d'engagement

$meta       = $page['meta'];
$visuel     = cms_page_visual($meta, cms_asset_url('themes/association-protection/hero.jpg'));
$raccourcis = (array) cms_config_get('shortcuts', []);
$chiffres   = (array) cms_config_get('figures', []);
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
                <?php foreach (array_slice($raccourcis, 0, 2) as $i => $r): ?>
                    <a class="bouton<?php echo $i ? ' bouton-clair' : ''; ?>" href="<?php echo e(cms_url($r['link'])); ?>"><?php echo e($r['label']); ?></a>
                <?php endforeach; ?>
            </p>
        <?php endif; ?>
    </div>
</section>

<?php if ($chiffres): ?>
    <section class="chiffres-cles" aria-label="Notre action en chiffres">
        <ul class="dedans">
            <?php foreach ($chiffres as $ch): ?>
                <li>
                    <span class="chiffre"><?php echo e($ch['nombre']); ?></span>
                    <span class="chiffre-label"><?php echo e($ch['label']); ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php endif; ?>

<article class="corps">
    <?php echo apply_filters('page_content', $page['body'], $meta); ?>
</article>

<?php if ($autres): ?>
    <section class="actions" aria-labelledby="actions-titre">
        <h2 id="actions-titre">Nos actions</h2>

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

<?php if (count($raccourcis) > 2): ?>
    <section class="engagement" aria-labelledby="engagement-titre">
        <div class="dedans">
            <h2 id="engagement-titre">Nous aider</h2>

            <ul class="voies">
                <?php foreach (array_slice($raccourcis, 2) as $r): ?>
                    <li><a href="<?php echo e(cms_url($r['link'])); ?>"><?php echo e($r['label']); ?></a></li>
                <?php endforeach; ?>
            </ul>

            <p class="fiscalite">
                Votre don ouvre droit à une réduction d'impôt de 66 % de son montant,
                dans la limite de 20 % du revenu imposable.
            </p>
        </div>
    </section>
<?php endif; ?>

<section class="soutien">
    <div class="dedans pratique-colonnes">
        <div>
            <h2>Nous trouver</h2>
            <?php if (!empty($contact['adresse'])): ?><p><?php echo e($contact['adresse']); ?></p><?php endif; ?>
            <?php if (!empty($contact['telephone'])): ?>
                <p><a href="tel:<?php echo e(preg_replace('/\s+/', '', $contact['telephone'])); ?>"><?php echo e($contact['telephone']); ?></a></p>
            <?php endif; ?>
        </div>

        <?php if (!empty($contact['horaires'])): ?>
            <div>
                <h2>Horaires</h2>
                <p><?php echo e($contact['horaires']); ?></p>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include cms_template('footer'); ?>
