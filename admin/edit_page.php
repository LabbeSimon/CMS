<?php
require_once __DIR__ . '/../includes/bootstrap.php';
cms_require_admin('../login.php');

$slug = isset($_GET['page']) ? basename((string) $_GET['page']) : '';

if (!cmsh_slug_ok($slug)) {
    http_response_code(400);
    exit('Slug invalide.');
}

$page = cmsh_load($slug);

if ($page === null) {
    http_response_code(404);
    exit('Page introuvable.');
}

$meta  = $page['meta'];
$corps = $page['body'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $corps = isset($_POST['body']) ? (string) $_POST['body'] : '';

    $meta['title']               = trim((string) $_POST['title']);
    $meta['meta']['description'] = trim((string) $_POST['description']);
    $meta['meta']['keywords']    = trim((string) $_POST['keywords']);
    $meta['meta']['robots']      = $_POST['robots'] === 'noindex' ? 'noindex, nofollow' : 'index, follow';
    $meta['meta']['canonical']   = trim((string) $_POST['canonical']);
    $meta['meta']['lang']        = preg_replace('/[^a-zA-Z-]/', '', (string) $_POST['lang']);

    $meta['og']['title']       = trim((string) $_POST['og_title']);
    $meta['og']['description'] = trim((string) $_POST['og_description']);
    $meta['og']['image']       = trim((string) $_POST['og_image']);

    $meta['hierarchy']['parent'] = trim((string) $_POST['parent']) !== '' ? trim((string) $_POST['parent']) : null;
    $meta['hierarchy']['order']  = (int) $_POST['order'];
    $meta['hierarchy']['menu']   = !empty($_POST['menu']);

    $meta['comments']['enabled'] = !empty($_POST['comments']);

    // Plugins requis : liste separee par des virgules
    $plugins = array_filter(array_map('trim', explode(',', (string) $_POST['plugins'])));
    $meta['requires']['plugins'] = array_values($plugins);
    $meta['requires']['php']     = trim((string) $_POST['php']);
    $meta['requires']['cms']     = trim((string) $_POST['cms']);

    $error = cmsh_save($slug, $meta, $corps);

    // Enregistrement automatique : on repond en JSON, sans redirection
    if (!empty($_POST['autosave'])) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'ok'     => $error === '',
            'erreur' => $error,
            'heure'  => date('H:i'),
        ]);
        exit;
    }

    if ($error === '') {
        header('Location: edit_page.php?page=' . urlencode($slug) . '&ok=1');
        exit;
    }
}

$manquants = cmsh_missing_plugins($meta);

$adminTitle   = 'Modifier : ' . $slug;
$adminSection = 'pages';
require __DIR__ . '/../includes/admin_header.php';
?>

<?php if (isset($_GET['ok'])): ?>
    <p class="avis">Page enregistrée.</p>
<?php endif; ?>

<?php if ($error !== ''): ?>
    <p class="erreur"><?php echo e($error); ?></p>
<?php endif; ?>

<?php if ($page['altere']): ?>
    <p class="erreur">
        L'empreinte ne correspond plus au contenu : ce fichier a été modifié en dehors
        du CMS. Enregistrez pour recalculer l'empreinte.
    </p>
<?php endif; ?>

<?php if ($manquants): ?>
    <p class="erreur">
        Plugins requis absents de l'installation : <?php echo e(implode(', ', $manquants)); ?>.
    </p>
<?php endif; ?>

<form action="edit_page.php?page=<?php echo urlencode($slug); ?>" method="POST" data-autosave>
    <?php echo csrf_field(); ?>

    <p class="statut" data-statut role="status" aria-live="polite"></p>

    <div class="atelier">
        <div>
            <label for="title">Titre</label>
            <input type="text" name="title" id="title" value="<?php echo e($meta['title']); ?>" required>

            <label for="body">Contenu de la page</label>
            <p class="aide">
                Sélectionnez un morceau de texte puis cliquez sur un bouton pour le mettre
                en forme. Enregistrez pour voir le résultat dans l'aperçu à droite.
            </p>
            <?php echo cms_editor_toolbar('body'); ?>
            <textarea name="body" id="body"><?php echo e($corps); ?></textarea>

            <button type="submit" class="si-sans-js">Enregistrer</button>
            <a href="pages.php">Retour</a>
        </div>

        <div class="apercu">
            <p class="legende">
                <span>Aperçu</span>
                <span>
                    <button type="button" class="lien" data-rafraichir>Rafraîchir</button>
                    <a href="<?php echo e(cms_url($slug)); ?>" target="_blank" rel="noopener">Ouvrir</a>
                </span>
            </p>
            <iframe src="<?php echo e(cms_url($slug)); ?>" title="Aperçu de <?php echo e($slug); ?>" loading="lazy"></iframe>
        </div>
    </div>

    <details class="bloc">
        <summary>Référencement</summary>

        <label for="description">Description</label>
        <input type="text" name="description" id="description" maxlength="200" value="<?php echo e($meta['meta']['description']); ?>">

        <label for="keywords">Mots-clés</label>
        <input type="text" name="keywords" id="keywords" value="<?php echo e($meta['meta']['keywords']); ?>">

        <label for="canonical">URL canonique</label>
        <input type="text" name="canonical" id="canonical" value="<?php echo e($meta['meta']['canonical']); ?>">

        <label for="lang">Langue</label>
        <input type="text" name="lang" id="lang" size="6" value="<?php echo e($meta['meta']['lang']); ?>">

        <label class="case">
            <input type="checkbox" name="robots" value="noindex"
                <?php echo strpos($meta['meta']['robots'], 'noindex') !== false ? 'checked' : ''; ?>>
            Interdire l'indexation par les moteurs (noindex)
        </label>
    </details>

    <details class="bloc">
        <summary>Partage social (Open Graph)</summary>

        <label for="og_title">Titre partagé</label>
        <input type="text" name="og_title" id="og_title" value="<?php echo e($meta['og']['title']); ?>">

        <label for="og_description">Description partagée</label>
        <input type="text" name="og_description" id="og_description" value="<?php echo e($meta['og']['description']); ?>">

        <label for="og_image">Image partagée (URL)</label>
        <input type="text" name="og_image" id="og_image" value="<?php echo e($meta['og']['image']); ?>">
    </details>

    <details class="bloc">
        <summary>Hiérarchie et dépendances</summary>

        <label for="parent">Page parente (slug)</label>
        <input type="text" name="parent" id="parent" value="<?php echo e($meta['hierarchy']['parent']); ?>">

        <label for="order">Ordre dans le menu</label>
        <input type="number" name="order" id="order" size="5" value="<?php echo (int) $meta['hierarchy']['order']; ?>">

        <label class="case">
            <input type="checkbox" name="menu" value="1" <?php echo !empty($meta['hierarchy']['menu']) ? 'checked' : ''; ?>>
            Afficher dans le menu
        </label>

        <label class="case">
            <input type="checkbox" name="comments" value="1" <?php echo !empty($meta['comments']['enabled']) ? 'checked' : ''; ?>>
            Autoriser les commentaires
            (<?php echo (int) $meta['comments']['count']; ?> pour l'instant)
        </label>

        <label for="plugins">Plugins requis (séparés par des virgules)</label>
        <input type="text" name="plugins" id="plugins" value="<?php echo e(implode(', ', (array) $meta['requires']['plugins'])); ?>">

        <label for="cms">Version minimale du CMS</label>
        <input type="text" name="cms" id="cms" size="8" value="<?php echo e($meta['requires']['cms']); ?>">

        <label for="php">Version minimale de PHP</label>
        <input type="text" name="php" id="php" size="8" value="<?php echo e($meta['requires']['php']); ?>">
    </details>
</form>

<details class="bloc">
    <summary>Informations du fichier</summary>

    <table>
        <tr><th>Slug d'origine</th><td><?php echo e($meta['slug']); ?></td></tr>
        <tr><th>Auteur</th><td><?php echo e($meta['author']); ?></td></tr>
        <tr><th>Créée le</th><td><?php echo e($meta['created']); ?></td></tr>
        <tr><th>Dernière modification</th><td><?php echo e($meta['modified']); ?></td></tr>
        <tr><th>Modifiée par</th><td><?php echo e($meta['modified_by']); ?></td></tr>
        <tr><th>Dernier domaine</th><td><?php echo e($meta['domain']); ?></td></tr>
        <tr><th>Empreinte</th><td><code><?php echo e($meta['checksum']); ?></code></td></tr>
        <tr><th>Format</th><td>CMSH/<?php echo (int) $meta['cmsh']; ?></td></tr>
        <tr>
            <th>Médias référencés</th>
            <td>
                <?php if ($meta['assets']['media']): ?>
                    <?php echo e(implode(', ', $meta['assets']['media'])); ?>
                <?php else: ?>
                    aucun
                <?php endif; ?>
            </td>
        </tr>
    </table>
</details>

<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
