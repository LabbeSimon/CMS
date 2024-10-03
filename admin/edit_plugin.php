<?php
session_start();
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: ../login.php');
    exit;
}

$plugins_directory = '../plugins/';

if (!isset($_GET['plugin'])) {
    header('Location: plugins.php');
    exit;
}

$plugin_name = basename($_GET['plugin']);
$plugin_file = $plugins_directory . $plugin_name;

if (!file_exists($plugin_file)) {
    echo "Plugin introuvable.";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $plugin_content = $_POST['plugin_content'];
    file_put_contents($plugin_file, "<?php\n// Plugin: {$plugin_name}\n" . $plugin_content);
    header('Location: plugins.php');
    exit;
}

// Lire le contenu du plugin existant
$plugin_content = file_get_contents($plugin_file);
$plugin_content = preg_replace('/<\?php.*\n/', '', $plugin_content); // Retirer la déclaration PHP pour l'édition

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier le plugin <?php echo $plugin_name; ?></title>
</head>
<body>
    <h1>Modifier le plugin : <?php echo $plugin_name; ?></h1>
    <form action="edit_plugin.php?plugin=<?php echo $plugin_name; ?>" method="POST">
        <label for="plugin_content">Contenu du plugin :</label>
        <textarea name="plugin_content" id="plugin_content" rows="10" cols="50"><?php echo htmlspecialchars($plugin_content); ?></textarea>

        <button type="submit">Enregistrer les modifications</button>
    </form>
</body>
</html>
