<?php
require_once __DIR__ . '/../includes/bootstrap.php';
cms_require_admin('../login.php');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $slug  = preg_replace('/[^A-Za-z0-9_-]/', '', (string) (isset($_POST['slug']) ? $_POST['slug'] : ''));
    $titre = trim((string) (isset($_POST['title']) ? $_POST['title'] : ''));
    $corps = isset($_POST['body']) ? (string) $_POST['body'] : '';

    if ($slug === '') {
        $error = 'Slug invalide : lettres, chiffres, tiret et underscore uniquement.';
    } elseif (is_file(cmsh_path($slug))) {
        $error = 'Une page porte deja ce slug.';
    } else {
        $meta                        = cmsh_defaults();
        $meta['title']               = $titre !== '' ? $titre : $slug;
        $meta['meta']['description'] = trim((string) (isset($_POST['description']) ? $_POST['description'] : ''));

        $error = cmsh_save($slug, $meta, $corps);

        if ($error === '') {
            header('Location: edit_page.php?page=' . urlencode($slug));
            exit;
        }
    }
}

$adminTitle   = 'Créer une page';
$adminSection = 'pages';
require __DIR__ . '/../includes/admin_header.php';
?>

<?php if ($error !== ''): ?>
    <p class="erreur"><?php echo e($error); ?></p>
<?php endif; ?>

<form action="add_page.php" method="POST">
    <?php echo csrf_field(); ?>

    <label for="title">Titre</label>
    <input type="text" name="title" id="title" required>

    <label for="slug">Adresse de la page</label>
    <p class="aide">
        Ce qui apparaîtra dans le navigateur après le nom du site, par exemple
        <code>horaires</code> pour <code><?php echo e(cms_url('horaires')); ?></code>.
        Lettres, chiffres et tirets uniquement.
    </p>
    <input type="text" name="slug" id="slug" pattern="[A-Za-z0-9_\-]+" required>

    <label for="description">Description pour les moteurs de recherche</label>
    <p class="aide">Une phrase qui résume la page. Elle s'affiche dans les résultats de recherche.</p>
    <input type="text" name="description" id="description" maxlength="200">

    <label for="body">Contenu de la page</label>
    <p class="aide">
        Sélectionnez un morceau de texte puis cliquez sur un bouton pour le mettre en forme.
    </p>
    <?php echo cms_editor_toolbar('body'); ?>
    <textarea name="body" id="body" placeholder="<p>Votre texte.</p>"></textarea>

    <button type="submit">Créer la page</button>
    <a href="pages.php">Annuler</a>
</form>

<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
