<?php
// Reglages du site : personne ne devrait avoir a ouvrir un fichier
require_once __DIR__ . '/../includes/bootstrap.php';
cms_require_admin('../login.php');

$config  = cms_config();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    /**
     * Une cle n'est reecrite que si son champ est present dans la
     * requete. Sans cette regle, un formulaire incomplet — page coupee,
     * enregistrement automatique parti trop tot — effacerait le menu et
     * les coordonnees en silence.
     */
    $texte = function ($champ, $defaut = '') {
        return array_key_exists($champ, $_POST) ? trim((string) $_POST[$champ]) : $defaut;
    };

    if (array_key_exists('theme', $_POST)) {
        $themes          = cms_themes_list();
        $choisi          = (string) $_POST['theme'];
        $config['theme'] = isset($themes[$choisi]) ? $choisi : 'standard';
    }

    foreach (['page_title', 'meta_description', 'footer_text', 'banner'] as $champ) {
        if (array_key_exists($champ, $_POST)) {
            $config[$champ] = $texte($champ);
        }
    }

    // Menu : on repart des lignes envoyees, les vides sont ignorees
    if (array_key_exists('nav_label', $_POST)) {
        $navbar = [];
        $liens  = isset($_POST['nav_link']) ? (array) $_POST['nav_link'] : [];

        foreach ((array) $_POST['nav_label'] as $i => $libelle) {
            $libelle = trim((string) $libelle);
            $lien    = isset($liens[$i]) ? trim((string) $liens[$i]) : '';

            if ($libelle !== '' && $lien !== '') {
                $navbar[$libelle] = $lien;
            }
        }
        $config['navbar'] = $navbar;
    }

    // Raccourcis de services, affiches sous la banniere
    if (array_key_exists('rac_label', $_POST)) {
        $raccourcis = [];
        $rn = isset($_POST['rac_link']) ? (array) $_POST['rac_link'] : [];

        foreach ((array) $_POST['rac_label'] as $i => $libelle) {
            $libelle = trim((string) $libelle);
            $lien    = isset($rn[$i]) ? trim((string) $rn[$i]) : '';

            if ($libelle !== '' && $lien !== '') {
                $raccourcis[] = ['label' => $libelle, 'link' => $lien];
            }
        }
        $config['shortcuts'] = $raccourcis;
    }

    // Chiffres mis en avant (adherents, adoptions, licencies...)
    if (array_key_exists('chiffre_nombre', $_POST)) {
        $chiffres = [];
        $cl = isset($_POST['chiffre_label']) ? (array) $_POST['chiffre_label'] : [];

        foreach ((array) $_POST['chiffre_nombre'] as $i => $nombre) {
            $nombre  = trim((string) $nombre);
            $libelle = isset($cl[$i]) ? trim((string) $cl[$i]) : '';

            if ($nombre !== '' && $libelle !== '') {
                $chiffres[] = ['nombre' => $nombre, 'label' => $libelle];
            }
        }
        $config['figures'] = $chiffres;
    }

    if (array_key_exists('contact_adresse', $_POST)) {
        $config['contact'] = [
            'adresse'   => $texte('contact_adresse'),
            'telephone' => $texte('contact_telephone'),
            'horaires'  => $texte('contact_horaires'),
            'email'     => $texte('contact_email'),
        ];
    }

    if (array_key_exists('rayor_scopes', $_POST)) {
        $scopes = $texte('rayor_scopes');

        $config['rayor_connect'] = [
            'enabled'   => !empty($_POST['rayor_enabled']),
            'client_id' => $texte('rayor_client_id'),
            'scopes'    => $scopes !== '' ? $scopes : 'openid profile email',
        ];
    }

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
$rayor  = function_exists("cms_rayor_config") ? cms_rayor_config() : [];

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

    <label for="banner">Photo de bannière</label>
    <p class="aide">
        La photo affichée en haut du site : une vue de votre commune, de votre stade,
        de vos locaux. Déposez-la dans <a href="media.php">Médias</a>, puis collez son
        adresse ici. Une page peut avoir sa propre photo, qui prend alors le dessus.
    </p>
    <input type="text" name="banner" id="banner" placeholder="media/ma-photo.jpg"
           value="<?php echo e(cms_config_get('banner', '')); ?>">

    <?php $bibliotheque = cms_media_list(); ?>
    <?php if ($bibliotheque): ?>
        <ul class="galerie-choix">
            <?php foreach (array_slice($bibliotheque, 0, 8) as $img): ?>
                <li>
                    <button type="button" class="lien" onclick="document.getElementById('banner').value='media/<?php echo e($img['nom']); ?>';document.getElementById('banner').dispatchEvent(new Event('input',{bubbles:true}));">
                        <img src="<?php echo e(cms_media_url($img['nom'])); ?>" alt="<?php echo e($img['nom']); ?>" loading="lazy" width="120" height="75">
                    </button>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

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

    <h2>Raccourcis de services</h2>

    <p class="aide">
        Les boutons affichés juste sous la bannière : démarches en ligne, portail famille,
        paiement, contact. C'est ce que les visiteurs viennent chercher en premier.
    </p>

    <table>
        <thead><tr><th>Libellé</th><th>Lien</th></tr></thead>
        <tbody>
        <?php
        // Borne calculee avant la boucle : count() sur un tableau qu'on
        // remplit ne s'arrete jamais.
        $raccourcis = (array) cms_config_get('shortcuts', []);
        $total      = count($raccourcis) + 3;
        for ($i = count($raccourcis); $i < $total; $i++) {
            $raccourcis[] = ['label' => '', 'link' => ''];
        }
        ?>
        <?php foreach ($raccourcis as $i => $r): ?>
            <tr>
                <td>
                    <label class="sr" for="rac_label_<?php echo $i; ?>">Libellé</label>
                    <input type="text" name="rac_label[]" id="rac_label_<?php echo $i; ?>"
                           value="<?php echo e(isset($r['label']) ? $r['label'] : ''); ?>">
                </td>
                <td>
                    <label class="sr" for="rac_link_<?php echo $i; ?>">Lien</label>
                    <input type="text" name="rac_link[]" id="rac_link_<?php echo $i; ?>"
                           value="<?php echo e(isset($r['link']) ? $r['link'] : ''); ?>">
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <h2>Chiffres mis en avant</h2>

    <p class="aide">
        Adoptions réalisées, adhérents, licenciés, années d'existence. Les associations
        et les clubs les affichent en grand : c'est ce qui donne confiance.
    </p>

    <table>
        <thead><tr><th>Nombre</th><th>Libellé</th></tr></thead>
        <tbody>
        <?php
        $chiffres = (array) cms_config_get('figures', []);
        $total    = count($chiffres) + 3;
        for ($i = count($chiffres); $i < $total; $i++) {
            $chiffres[] = ['nombre' => '', 'label' => ''];
        }
        ?>
        <?php foreach ($chiffres as $i => $ch): ?>
            <tr>
                <td>
                    <label class="sr" for="chiffre_nombre_<?php echo $i; ?>">Nombre</label>
                    <input type="text" name="chiffre_nombre[]" id="chiffre_nombre_<?php echo $i; ?>"
                           placeholder="1 450" value="<?php echo e(isset($ch['nombre']) ? $ch['nombre'] : ''); ?>">
                </td>
                <td>
                    <label class="sr" for="chiffre_label_<?php echo $i; ?>">Libellé</label>
                    <input type="text" name="chiffre_label[]" id="chiffre_label_<?php echo $i; ?>"
                           placeholder="adoptions par an" value="<?php echo e(isset($ch['label']) ? $ch['label'] : ''); ?>">
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <h2>Coordonnées</h2>

    <p class="aide">Affichées dans le pied de page. Les horaires sont l'information la plus consultée d'un site de mairie.</p>

    <?php $contact = (array) cms_config_get('contact', []); ?>

    <label for="contact_adresse">Adresse</label>
    <input type="text" name="contact_adresse" id="contact_adresse"
           value="<?php echo e(isset($contact['adresse']) ? $contact['adresse'] : ''); ?>">

    <label for="contact_telephone">Téléphone</label>
    <input type="text" name="contact_telephone" id="contact_telephone"
           value="<?php echo e(isset($contact['telephone']) ? $contact['telephone'] : ''); ?>">

    <label for="contact_email">Adresse e-mail</label>
    <input type="text" name="contact_email" id="contact_email"
           value="<?php echo e(isset($contact['email']) ? $contact['email'] : ''); ?>">

    <label for="contact_horaires">Horaires d'ouverture</label>
    <input type="text" name="contact_horaires" id="contact_horaires"
           placeholder="Du lundi au vendredi, 8h30-12h et 14h-17h30"
           value="<?php echo e(isset($contact['horaires']) ? $contact['horaires'] : ''); ?>">

    <?php if (function_exists("cms_rayor_enabled")): ?>
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
    <?php endif; ?>

    <button type="submit" class="si-sans-js">Enregistrer</button>
</form>

<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
