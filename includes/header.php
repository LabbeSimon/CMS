<?php
session_start();


$configPath = __DIR__ . '/../config.json';


if (file_exists($configPath)) {
    $config = json_decode(file_get_contents($configPath), true);
} else {
    die("Fichier de configuration non trouvé.");
}


$pageTitle = isset($config['page_title']) ? $config['page_title'] : 'Change me in config.json';
$metaDescription = isset($config['meta_description']) ? $config['meta_description'] : 'Change this description directly on config.json.';

// every part like page 2 write here it's the default config change that is config.json
$navbarItems = isset($config['navbar']) ? $config['navbar'] : [
    'Accueil' => 'index.php',
    'Page 1' => 'index.php?page=page1',
    'Page 2' => 'index.php?page=page2',
];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars($metaDescription); ?>">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="stylesheet" href="/styles.css">
</head>
<body>
    <header>
        <nav>
            <ul>
                <?php
                // création dynamique de la navbar
                foreach ($navbarItems as $label => $link) {
                    echo '<li><a href="' . htmlspecialchars($link) . '">' . htmlspecialchars($label) . '</a></li>';
                }
                ?>
            </ul>
        </nav>
        <?php
        // gestion zone admin
        if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
            echo '<div class="admin-menu">';
            echo '<a href="admin/index.php">Panneau d\'administration</a> | ';
            echo '<a href="logout.php">Déconnexion</a>';
            echo '</div>';
        } else {
            echo '<a href="login.php">Connexion</a>';
        }
        ?>
    </header>
</body>

