<?php
session_start();
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: ../login.php');
    exit;
}

$pages_directory = '../pages/';

if (!isset($_GET['page'])) {
    header('Location: pages.php');
    exit;
}

$page_name = basename($_GET['page']);
$page_file = $pages_directory . $page_name . '.php';

if (!file_exists($page_file)) {
    echo "Page introuvable.";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $page_content = $_POST['page_content'];
    file_put_contents($page_file, "<?php\n?>\n" . $page_content);
    header('Location: pages.php');
    exit;
}

// Lire le contenu de la page existante
$page_content = file_get_contents($page_file);
$page_content = preg_replace('/<\?php[^>]*?>/', '', $page_content); // Retirer les balises PHP pour l'édition

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier la page <?php echo $page_name; ?></title>
</head>
<body>
    <h1>Modifier la page : <?php echo $page_name; ?></h1>
    <form action="edit_page.php?page=<?php echo $page_name; ?>" method="POST">
        <label for="page_content">Contenu de la page :</label>
        <textarea name="page_content" id="page_content" rows="10" cols="50"><?php echo htmlspecialchars($page_content); ?></textarea>

        <button type="submit">Enregistrer les modifications</button>
    </form>
</body>
</html>
