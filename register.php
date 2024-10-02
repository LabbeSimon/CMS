<?php
session_start();
$users_file = 'users.json';

// Vérifier si un utilisateur existe déjà
if (file_exists($users_file)) {
    $users = json_decode(file_get_contents($users_file), true);
    if (count($users) > 0) {
        echo "Un administrateur existe déjà. Vous ne pouvez pas créer un autre compte.";
        exit;
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];

    // Validation des champs
    if ($password !== $password_confirm) {
        $error = "Les mots de passe ne correspondent pas.";
    } elseif (empty($username) || empty($password)) {
        $error = "Veuillez remplir tous les champs.";
    } else {
        // Enregistrement de l'utilisateur
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $users = [];
        if (file_exists($users_file)) {
            $users = json_decode(file_get_contents($users_file), true);
        }

        // Ajouter le nouvel utilisateur
        $users[] = [
            'username' => $username,
            'password' => $hashed_password,
        ];

        file_put_contents($users_file, json_encode($users));
        echo "Compte administrateur créé avec succès.";
        header('Location: login.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer le premier compte administrateur</title>
</head>
<body>
    <h2>Créer le premier compte administrateur</h2>
    <?php if ($error): ?>
        <p style="color: red;"><?php echo $error; ?></p>
    <?php endif; ?>
    <form action="register.php" method="POST">
        <label for="username">Nom d'utilisateur</label>
        <input type="text" name="username" id="username" required>

        <label for="password">Mot de passe</label>
        <input type="password" name="password" id="password" required>

        <label for="password_confirm">Confirmer le mot de passe</label>
        <input type="password" name="password_confirm" id="password_confirm" required>

        <button type="submit">Créer un compte</button>
    </form>
</body>
</html>
