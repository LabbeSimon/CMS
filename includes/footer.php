<?php

$configPath = __DIR__ . '/../config.json';


if (file_exists($configPath)) {
    $config = json_decode(file_get_contents($configPath), true);
} else {
    die("Fichier de configuration non trouvé.");
}


$footerText = isset($config['footer_text']) ? $config['footer_text'] : '2024CMS. Tous droits réservés.';
?>

<footer>
    <p>&copy;<?php echo htmlspecialchars($footerText); ?></p>
</footer>
</body>
</html>