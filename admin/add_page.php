<?php
session_start();
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: ../login.php');
    exit;
}

$pages_directory = '../pages/';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $page_name = preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['page_name']);
    $page_content = $_POST['page_content'];

    if (!empty($page_name) && !file_exists($pages_directory . $page_name . '.php')) {
        file_put_contents($pages_directory . $page_name . '.php', "<?php\n?><h1>{$page_name}</h1>\n" . $page_content);
        header('Location: pages.php');
        exit;
    } else {
        echo "Une erreur est survenue : soit la page existe déjà, soit le nom est invalide.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter une nouvelle page</title>
</head>
<body>
    <h1>Ajouter une nouvelle page</h1>
    <form action="add_page.php" method="POST">
        <label for="page_name">Nom de la page :</label>
        <input type="text" name="page_name" id="page_name" required>

        <label for="page_content">Contenu de la page :</label>
        <textarea name="page_content" id="page_content" rows="10" cols="50" required></textarea>

        <button type="submit">Créer la page</button>
    </form>
</body>
</html>
