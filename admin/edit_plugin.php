<?php
require_once __DIR__ . '/../includes/bootstrap.php';
cms_require_admin('../login.php');

if (!isset($_GET['plugin'])) {
    header('Location: plugins.php');
    exit;
}

$plugin_name = basename((string) $_GET['plugin']);

// Nom valide avant tout usage : construction du chemin comme affichage
if (!preg_match('/^[A-Za-z0-9_.-]+$/', $plugin_name) || $plugin_name === '.' || $plugin_name === '..') {
    http_response_code(400);
    exit('Nom de plugin invalide.');
}

$plugin_file = realpath(CMS_PLUGINS_DIR . '/' . $plugin_name);
$pluginsReal = realpath(CMS_PLUGINS_DIR);

if ($plugin_file === false || $pluginsReal === false
    || strpos($plugin_file, $pluginsReal . DIRECTORY_SEPARATOR) !== 0
    || !is_file($plugin_file)) {
    http_response_code(404);
    exit('Plugin introuvable.');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $plugin_content = isset($_POST['plugin_content']) ? (string) $_POST['plugin_content'] : '';
    $body = "<?php\n// Plugin: " . str_replace(["\r", "\n"], '', $plugin_name) . "\n" . $plugin_content;

    if (file_put_contents($plugin_file, $body, LOCK_EX) === false) {
        $error = 'Ecriture impossible : verifiez les droits sur plugins/.';
    } else {
        header('Location: plugins.php');
        exit;
    }
}

// Contenu existant, sans la ligne de declaration PHP
$plugin_content = (string) file_get_contents($plugin_file);
$plugin_content = preg_replace('/<\?php.*\n/', '', $plugin_content);

$adminTitle   = 'Modifier : ' . $plugin_name;
$adminSection = 'plugins';
require __DIR__ . '/../includes/admin_header.php';
?>

<?php if ($error !== ''): ?>
    <p class="erreur"><?php echo e($error); ?></p>
<?php endif; ?>

<form action="edit_plugin.php?plugin=<?php echo urlencode($plugin_name); ?>" method="POST">
    <?php echo csrf_field(); ?>

    <label for="plugin_content">Contenu</label>
    <textarea name="plugin_content" id="plugin_content"><?php echo e($plugin_content); ?></textarea>

    <button type="submit">Enregistrer</button>
    <a href="plugins.php">Annuler</a>
</form>

<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
