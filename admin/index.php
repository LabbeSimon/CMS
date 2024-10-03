<?php
session_start();
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: ../login.php');
    exit;
}

echo '<h1>Bienvenue dans le panneau d\'administration</h1>';
echo '<a href="pages.php">Gérer les pages</a> | ';
echo '<a href="plugins.php">Gérer les plugins</a>';
?>
