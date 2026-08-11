<?php
require_once __DIR__ . '/includes/bootstrap.php';

// Deja connecte : inutile de repasser par le formulaire
if (cms_is_admin()) {
    header('Location: admin/index.php');
    exit;
}

$error = '';

// Hash factice : egalise le temps de reponse quand le compte n'existe pas.
$dummyHash = '$2y$10$usesomesillystringfore7hnbRJHxXVLeakoG8K30M1MlVkd.';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $locked = cms_login_lock_remaining();
    if ($locked > 0) {
        $error = 'Trop de tentatives echouees. Reessayez dans ' . ceil($locked / 60) . ' minute(s).';
    } else {
        $username = isset($_POST['username']) ? trim((string) $_POST['username']) : '';
        $password = isset($_POST['password']) ? (string) $_POST['password'] : '';

        $users     = cms_users();
        $matched   = null;

        foreach ($users as $user) {
            // Seuls les comptes locaux ont un mot de passe : un compte
            // Rayor ne doit pas pouvoir etre attaque par ce formulaire.
            if (empty($user['username']) || !isset($user['password']) || !is_string($user['password'])) {
                continue;
            }
            if (hash_equals($user['username'], $username)) {
                $matched = $user;
                break;
            }
        }

        if ($matched !== null && password_verify($password, $matched['password'])) {
            cms_login_reset();
            cms_login_user($matched['username']);
            header('Location: admin/index.php');
            exit;
        }

        // Comparaison factice pour egaliser le temps de reponse
        if ($matched === null) {
            password_verify($password, $dummyHash);
        }

        cms_login_record_failure();

        // Message volontairement identique dans les deux cas : ne pas
        // indiquer si c'est le nom ou le mot de passe qui est faux.
        $error = "Nom d'utilisateur ou mot de passe incorrect.";

        $remaining = CMS_LOGIN_MAX_ATTEMPTS;
        if (cms_login_lock_remaining() > 0) {
            $error = 'Trop de tentatives echouees. Compte bloque pendant '
                . (CMS_LOGIN_LOCKOUT / 60) . ' minutes.';
        }
        unset($remaining);
    }
}

// Le cas "aucun compte" n'arrive jamais ici : bootstrap.php renvoie
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>Connexion</title>
    <link rel="stylesheet" href="<?php echo e(cms_asset_url('styles.css')); ?>">
    <?php echo cms_theme_link(); ?>
</head>
<body class="page-etroite">
    <header>
        <a href="<?php echo e(cms_base_uri()); ?>">Retour au site</a>
    </header>

    <main id="contenu">
        <h1>Connexion</h1>

        <?php if ($error !== ''): ?>
            <p class="erreur"><?php echo e($error); ?></p>
        <?php endif; ?>

        <?php if (cms_rayor_enabled()): ?>
            <p>
                <a class="bouton" href="<?php echo e(cms_url('auth/rayor.php')); ?>">Se connecter avec Rayor</a>
            </p>
            <p class="aide">ou avec l'identifiant et le mot de passe de ce site :</p>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <?php echo csrf_field(); ?>

            <label for="username">Identifiant</label>
            <input type="text" name="username" id="username" autocomplete="username" required autofocus>

            <label for="password">Mot de passe</label>
            <input type="password" name="password" id="password" autocomplete="current-password" required>

            <button type="submit">Se connecter</button>
        </form>
    </main>
</body>
</html>
