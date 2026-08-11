<?php
/** Reglages du site : personne ne devrait avoir a ouvrir un fichier. */
require_once __DIR__ . '/../includes/bootstrap.php';
cms_require_admin('../login.php');

$config  = cms_config();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $themes = cms_themes_list();
    $choisi = isset($_POST['theme']) ? (string) $_POST['theme'] : 'standard';
    $config['theme'] = isset($themes[$choisi]) ? $choisi : 'standard';

    $config['page_title']       = trim((string) $_POST['page_title']);
    $config['meta_description'] = trim((string) $_POST['meta_description']);
    $config['footer_text']      = trim((string) $_POST['footer_text']);

    // Menu : on repart des lignes envoyees, les vides sont ignorees
    $navbar   = [];
    $libelles = isset($_POST['nav_label']) ? (array) $_POST['nav_label'] : [];
    $liens    = isset($_POST['nav_link']) ? (array) $_POST['nav_link'] : [];

    foreach ($libelles as $i => $libelle) {
        $libelle = trim((string) $libelle);
        $lien    = isset($liens[$i]) ? trim((string) $liens[$i]) : '';

        if ($libelle !== '' && $lien !== '') {
            $navbar[$libelle] = $lien;
        }
    }
    $config['navbar'] = $navbar;

    $config['rayor_connect'] = [
        'enabled'   => !empty($_POST['rayor_enabled']),
        'client_id' => trim((string) $_POST['rayor_client_id']),
        'scopes'    => trim((string) $_POST['rayor_scopes']) !== ''
            ? trim((string) $_POST['rayor_scopes'])
            : 'openid profile email',
    ];

    $ecrit   = cms_config_save($config);
    $message = $ecrit
        ? 'Réglages enregistrés.'
        : 'Écriture impossible : vérifiez les droits sur config.json.';

    if (!empty($_POST['autosave'])) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'ok'     => $ecrit,
            'erreur' => $ecrit ? '' : $message,
            'heure'  => date('H:i'),
        ]);
        exit;
    }

    header('Location: settings.php?ok=' . urlencode($message));
    exit;
}

$navbar = (array) cms_config_get('navbar', []);
$rayor  = cms_rayor_config();

$adminTitle   = 'Réglages';
$adminSection = 'reglages';
require __DIR__ . '/../includes/admin_header.php';
?>

<?php if (isset($_GET['ok'])): ?>
    <p class="avis"><?php echo e($_GET['ok']); ?></p>
<?php endif; ?>

<form action="settings.php" method="POST" data-autosave>
    <?php echo csrf_field(); ?>

    <p class="statut" data-statut role="status" aria-live="polite"></p>

    <h2>Apparence</h2>

    <p class="aide">
        Un thème change tout l'habillage du site : couleurs, typographie, mais aussi
        la structure de l'en-tête, du menu et du pied de page. Vos pages, elles, ne
        bougent pas — vous pouvez en changer à tout moment sans rien réécrire.
    </p>

    <div class="themes">
        <?php $actif = cms_current_theme(); ?>
        <?php foreach (cms_themes_list() as $slug => $theme): ?>
            <label class="theme-carte<?php echo $slug === $actif ? ' actif' : ''; ?>">
                <input type="radio" name="theme" value="<?php echo e($slug); ?>"
                    <?php echo $slug === $actif ? 'checked' : ''; ?>>

                <span class="pastilles" aria-hidden="true">
                    <?php foreach ($theme['colors'] as $couleur): ?>
                        <span style="background: <?php echo e($couleur); ?>"></span>
                    <?php endforeach; ?>
                </span>

                <strong><?php echo e($theme['name']); ?></strong>
                <span class="theme-description"><?php echo e($theme['description']); ?></span>
            </label>
        <?php endforeach; ?>
    </div>

    <h2>Identité du site</h2>

    <label for="page_title">Titre du site</label>
    <input type="text" name="page_title" id="page_title" required
           value="<?php echo e(cms_config_get('page_title', '')); ?>">

    <label for="meta_description">Description du site (référencement)</label>
    <input type="text" name="meta_description" id="meta_description" maxlength="200"
           value="<?php echo e(cms_config_get('meta_description', '')); ?>">

    <label for="footer_text">Texte du pied de page</label>
    <input type="text" name="footer_text" id="footer_text"
           value="<?php echo e(cms_config_get('footer_text', '')); ?>">

    <h2>Menu</h2>

    <p>Laissez une ligne vide pour retirer une entrée. Un lien comme <code>page1</code>
       pointe vers une page du site ; une adresse complète est acceptée telle quelle.</p>

    <table>
        <thead>
            <tr><th>Libellé</th><th>Lien</th></tr>
        </thead>
        <tbody>
        <?php
        $lignes = [];
        foreach ($navbar as $libelle => $lien) {
            $lignes[] = [$libelle, $lien];
        }
        // Trois lignes vides pour ajouter sans manipulation
        for ($i = 0; $i < 3; $i++) {
            $lignes[] = ['', ''];
        }
        ?>
        <?php foreach ($lignes as $i => $ligne): ?>
            <tr>
                <td>
                    <label class="sr" for="nav_label_<?php echo $i; ?>">Libellé</label>
                    <input type="text" name="nav_label[]" id="nav_label_<?php echo $i; ?>"
                           value="<?php echo e($ligne[0]); ?>">
                </td>
                <td>
                    <label class="sr" for="nav_link_<?php echo $i; ?>">Lien</label>
                    <input type="text" name="nav_link[]" id="nav_link_<?php echo $i; ?>"
                           value="<?php echo e($ligne[1]); ?>">
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <details class="bloc">
        <summary>Connexion Rayor Connect</summary>

        <label class="case">
            <input type="checkbox" name="rayor_enabled" value="1"
                <?php echo cms_rayor_enabled() ? 'checked' : ''; ?>>
            Proposer « Se connecter avec Rayor »
        </label>

        <label for="rayor_client_id">Domaine déclaré (vide = domaine servi, <?php echo e(cms_rayor_client_id()); ?>)</label>
        <input type="text" name="rayor_client_id" id="rayor_client_id"
               value="<?php echo e(isset($rayor['client_id']) ? $rayor['client_id'] : ''); ?>">

        <label for="rayor_scopes">Données demandées</label>
        <input type="text" name="rayor_scopes" id="rayor_scopes"
               value="<?php echo e(cms_rayor_scopes()); ?>">
    </details>

    <button type="submit" class="si-sans-js">Enregistrer</button>
</form>

<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
