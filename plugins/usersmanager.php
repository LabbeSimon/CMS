<?php
session_start();

// retiré si vous shouaitez que n'importe qui puisse acceder a cette page
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: ../../login.php'); 
    exit;
}
//jusque qu'ici


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require dirname(__DIR__) . '/config.php'; 

function getUsers() {
    if (file_exists(USERS_FILE)) {
        return json_decode(file_get_contents(USERS_FILE), true);
    }
    return [];
}

function saveUsers($users) {
    file_put_contents(USERS_FILE, json_encode($users, JSON_PRETTY_PRINT));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $username = trim($_POST['username']);
    $password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);
    
    $users = getUsers();
    $users[] = [
        'username' => $username,
        'password' => $password,
        'permissions' => [
            'perm_all' => false,
            'modify_content' => false,
            'create_content' => false,
            'access_admin' => false
        ]
    ];
    
    saveUsers($users);
    $message = "Utilisateur ajouté avec succès.";
}

// Chargement des old users
$users = getUsers();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Utilisateurs Manager</title>
</head>
<body>
    <h1>Gestion des Utilisateurs</h1>

    <?php if (isset($message)) echo "<p>$message</p>"; ?>

    <form method="POST">
        <input type="text" name="username" placeholder="Nom d'utilisateur" required>
        <input type="password" name="password" placeholder="Mot de passe" required>
        <button type="submit" name="add_user">Créé un utilisateur</button>
    </form>
<!-- Vous pouvez supprimer jusqu'au UL si vous shouaitez retiré l'affichage des anciens users -->
    <h2>Ancien utilisateurs :</h2>
    <ul>
        <?php foreach ($users as $user): ?>
            <li><?php echo htmlspecialchars($user['username']); ?></li>
        <?php endforeach; ?>
    </ul>
</body>
</html>
