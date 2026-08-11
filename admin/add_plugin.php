<?php
require_once __DIR__ . '/../includes/bootstrap.php';
cms_require_admin('../login.php');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $plugin_name    = preg_replace('/[^A-Za-z0-9_-]/', '', (string) (isset($_POST['plugin_name']) ? $_POST['plugin_name'] : ''));
    $plugin_content = isset($_POST['plugin_content']) ? (string) $_POST['plugin_content'] : '';

    if ($plugin_name === '') {
        $error = 'Nom de plugin invalide : lettres, chiffres, tiret et underscore uniquement.';
    } elseif (file_exists(CMS_PLUGINS_DIR . '/' . $plugin_name)) {
        $error = 'Ce plugin existe deja.';
    } else {
        $body = "<?php\n// Plugin: " . str_replace(["\r", "\n"], '', $plugin_name) . "\n" . $plugin_content;

        if (file_put_contents(CMS_PLUGINS_DIR . '/' . $plugin_name, $body, LOCK_EX) === false) {
            $error = 'Ecriture impossible : verifiez les droits sur plugins/.';
        } else {
            header('Location: plugins.php');
            exit;
        }
    }
}

$adminTitle   = 'Ajouter un plugin';
$adminSection = 'plugins';
require __DIR__ . '/../includes/admin_header.php';
?>

<?php if ($error !== ''): ?>
    <p class="erreur"><?php echo e($error); ?></p>
<?php endif; ?>

<form action="add_plugin.php" method="POST">
    <?php echo csrf_field(); ?>

    <label for="plugin_name">Nom du plugin</label>
    <input type="text" name="plugin_name" id="plugin_name" pattern="[A-Za-z0-9_\-]+" required>

    <label for="plugin_content">Contenu</label>
    <textarea name="plugin_content" id="plugin_content" required></textarea>

    <button type="submit">Créer le plugin</button>
    <a href="plugins.php">Annuler</a>
</form>

<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
