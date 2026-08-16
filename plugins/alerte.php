<?php
/**
 * Plugin: Bandeau d'alerte
 * Version: 1.0
 * Description: Affiche un message d'alerte en haut de toutes les pages (canicule, route coupee, fermeture exceptionnelle).
 * Author: CMS
 * CSS-Priority: 64000
 * JS-Priority: 64000
 *
 * Exemple de plugin : il montre les trois mecanismes du moteur — un
 * hook, un fichier CSS et un fichier JS fusionnes selon leur priorite.
 * Priorite 64000 : le bandeau doit passer au-dessus du theme.
 *
 * Reglage dans data/alerte.json :
 *   { "actif": true, "message": "...", "lien": "", "niveau": "info" }
 */

function alerte_config() {
    return array_merge(
        ['actif' => false, 'message' => '', 'lien' => '', 'niveau' => 'info'],
        cms_read_json(CMS_DATA_DIR . '/alerte.json')
    );
}

add_action('head', function () {
    $c = alerte_config();

    if (empty($c['actif']) || trim((string) $c['message']) === '') {
        return;
    }

    // Le bandeau est injecte en tete de body par le script du plugin,
    // pour rester independant du theme actif.
    echo '<meta name="cms-alerte" content="' . e($c['message']) . '"'
        . ' data-niveau="' . e($c['niveau']) . '"'
        . ' data-lien="' . e(cms_url($c['lien'])) . '">';
});
