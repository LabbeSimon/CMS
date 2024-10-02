<?php
include('includes/header.php');

if (isset($_GET['page'])) {
    $page = $_GET['page'];
    $pagePath = "pages/{$page}.php";
    if (file_exists($pagePath)) {
        include($pagePath);
    } else {
        echo "Page non trouvée.";
    }
} else {
    include('pages/home.php');
}

include('includes/footer.php');
?>
