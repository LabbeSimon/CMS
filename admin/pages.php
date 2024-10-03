<?php
session_start();
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: ../login.php');
    exit;
}

$pages_directory = '../pages/';

if (isset($_POST['delete'])) {
    $page_to_delete = basename($_POST['delete']);
    unlink($pages_directory . $page_to_delete . '.php');
    echo "Page supprimée avec succès.";
}

// Lister les pages existantes
$pages = array_diff(scandir($pages_directory), array('.', '..'));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gérer les pages</title>
</head>
<body>
    <h1>Gérer les pages</h1>

    <a href="add_page.php">Ajouter une nouvelle page</a>
    
    <h2>Pages existantes :</h2>
    <ul>
    <?php foreach ($pages as $page): ?>
        <li>
            <?php echo basename($page, '.php'); ?>
            <a href="edit_page.php?page=<?php echo basename($page, '.php'); ?>">Modifier</a>
            <form action="pages.php" method="POST" style="display:inline;">
                <button type="submit" name="delete" value="<?php echo basename($page, '.php'); ?>">Supprimer</button>
            </form>
        </li>
    <?php endforeach; ?>
    </ul>
</body>
</html>
