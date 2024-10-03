<?php
session_start();
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: ../login.php');
    exit;
}

$plugins_directory = '../plugins/';

if (isset($_POST['delete'])) {
    $plugin_to_delete = basename($_POST['delete']);
    unlink($plugins_directory . $plugin_to_delete);
    echo "Plugin supprimé avec succès.";
}

// Lister les plugins existants
$plugins = array_diff(scandir($plugins_directory), array('.', '..'));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gérer les plugins</title>
</head>
<body>
    <h1>Gérer les plugins</h1>

    <a href="add_plugin.php">Ajouter un nouveau plugin</a>
    
    <h2>Plugins existants :</h2>
    <ul>
    <?php foreach ($plugins as $plugin): ?>
        <li>
            <?php echo basename($plugin); ?>
            <a href="edit_plugin.php?plugin=<?php echo basename($plugin); ?>">Modifier</a>
            <form action="plugins.php" method="POST" style="display:inline;">
                <button type="submit" name="delete" value="<?php echo basename($plugin); ?>">Supprimer</button>
            </form>
        </li>
    <?php endforeach; ?>
    </ul>
</body>
</html>
