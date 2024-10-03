<?php
session_start();
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: ../login.php');
    exit;
}

$plugins_directory = '../plugins/';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $plugin_name = preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['plugin_name']);
    $plugin_content = $_POST['plugin_content'];

    if (!empty($plugin_name) && !file_exists($plugins_directory . $plugin_name)) {
        file_put_contents($plugins_directory . $plugin_name, "<?php\n// Plugin: {$plugin_name}\n" . $plugin_content);
        header('Location: plugins.php');
        exit;
    } else {
        echo "Une erreur est survenue : soit le plugin existe déjà, soit le nom est invalide.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un nouveau plugin</title>
</head>
<body>
    <h1>Ajouter un nouveau plugin</h1>
    <form action="add_plugin.php" method="POST">
        <label for="plugin_name">Nom du plugin :</label>
        <input type="text" name="plugin_name" id="plugin_name" required>

        <label for="plugin_content">Contenu du plugin :</label>
        <textarea name="plugin_content" id="plugin_content" rows="10" cols="50" required></textarea>

        <button type="submit">Créer le plugin</button>
    </form>
</body>
</html>
