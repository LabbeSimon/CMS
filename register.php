<?php
require_once __DIR__ . '/includes/bootstrap.php';

/** Installation : creation du compte administrateur */
define('CMS_MAX_ACCOUNTS', 1);
define('CMS_MIN_PASSWORD_LENGTH', 8);

if (!cms_needs_setup() || count(cms_users()) >= CMS_MAX_ACCOUNTS) {
    http_response_code(403);
    exit('Un administrateur existe deja. Vous ne pouvez pas creer un autre compte.');
}

$mode  = isset($_GET['mode']) ? (string) $_GET['mode'] : '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $mode = 'local';

    $username         = isset($_POST['username']) ? trim((string) $_POST['username']) : '';
    $password         = isset($_POST['password']) ? (string) $_POST['password'] : '';
    $password_confirm = isset($_POST['password_confirm']) ? (string) $_POST['password_confirm'] : '';

    $users = cms_users();

    if ($username === '' || $password === '') {
        $error = 'Veuillez remplir tous les champs.';
    } elseif (!preg_match('/^[A-Za-z0-9_.-]{3,32}$/', $username)) {
        $error = "Le nom d'utilisateur doit faire 3 a 32 caracteres (lettres, chiffres, . _ -).";
    } elseif (strlen($password) < CMS_MIN_PASSWORD_LENGTH) {
        $error = 'Le mot de passe doit faire au moins ' . CMS_MIN_PASSWORD_LENGTH . ' caracteres.';
    } elseif ($password !== $password_confirm) {
        $error = 'Les mots de passe ne correspondent pas.';
    } elseif (count($users) >= CMS_MAX_ACCOUNTS) {
        // Re-verification juste avant l'ecriture (course entre deux requetes)
        $error = 'Un administrateur existe deja.';
    } else {
        $users[] = [
            'username' => $username,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'provider' => 'local',
            'created'  => date('c'),
        ];

        if (!cms_write_json(CMS_USERS_FILE, $users)) {
            $error = "Impossible d'ecrire " . CMS_USERS_FILE . '. Verifiez les droits du repertoire data/.';
        } else {
            // Aucun echo avant cette ligne : sinon les en-tetes sont deja
            // envoyes et la redirection ne part jamais.
            header('Location: ' . cms_url('login.php'));
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>Installation</title>
    <link rel="stylesheet" href="<?php echo e(cms_asset_url('styles.css')); ?>">
</head>
<body>
    <h1>Installation</h1>

    <p>
        Aucun compte n'existe encore sur ce site. Créez le compte administrateur pour
        commencer : tant que ce n'est pas fait, le site reste fermé.
    </p>

    <?php if ($error !== ''): ?>
        <p class="erreur"><?php echo e($error); ?></p>
    <?php endif; ?>

    <?php if ($mode === 'local'): ?>

        <h2>Compte local</h2>

        <form action="<?php echo e(cms_url('register.php')); ?>?mode=local" method="POST">
            <?php echo csrf_field(); ?>

            <label for="username">Nom d'utilisateur</label>
            <input type="text" name="username" id="username" autocomplete="username"
                   pattern="[A-Za-z0-9_.\-]{3,32}" required>

            <label for="password">Mot de passe (<?php echo CMS_MIN_PASSWORD_LENGTH; ?> caractères minimum)</label>
            <input type="password" name="password" id="password" autocomplete="new-password"
                   minlength="<?php echo CMS_MIN_PASSWORD_LENGTH; ?>" required>

            <label for="password_confirm">Confirmer le mot de passe</label>
            <input type="password" name="password_confirm" id="password_confirm"
                   autocomplete="new-password" required>

            <button type="submit">Créer le compte</button>
        </form>

        <p><a href="<?php echo e(cms_url('register.php')); ?>">Revenir au choix</a></p>

    <?php else: ?>

        <div class="choix">
            <section>
                <h2>Compte local</h2>
                <p>
                    Un nom d'utilisateur et un mot de passe, stockés sur ce serveur. Rien
                    à configurer, aucune dépendance extérieure : le site reste utilisable
                    même si tout le reste tombe.
                </p>
                <p><a class="bouton" href="<?php echo e(cms_url('register.php')); ?>?mode=local">Créer un compte local</a></p>
            </section>

            <section>
                <h2>Rayor Connect</h2>
                <p>
                    Connexion avec votre compte Rayor. Aucun mot de passe à retenir, et
                    aucun mot de passe stocké sur ce serveur : le site ne conserve que
                    votre identifiant Rayor.
                </p>
                <?php if (cms_rayor_enabled()): ?>
                    <p><a class="bouton" href="<?php echo e(cms_url('auth/rayor.php')); ?>?intent=setup">Continuer avec Rayor</a></p>
                <?php else: ?>
                    <p><small>Désactivé dans <code>config.json</code> (<code>rayor_connect.enabled</code>).</small></p>
                <?php endif; ?>
            </section>
        </div>

    <?php endif; ?>
</body>
</html>
