<?php
require_once __DIR__ . '/../includes/bootstrap.php';
cms_require_admin('../login.php');


$message = '';
$erreur  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    if (isset($_POST['supprimer'])) {
        $nom = basename((string) $_POST['supprimer']);
        $cible = realpath(CMS_MEDIA_DIR . '/' . $nom);
        $reel  = realpath(CMS_MEDIA_DIR);

        if ($cible !== false && $reel !== false
            && strpos($cible, $reel . DIRECTORY_SEPARATOR) === 0
            && is_file($cible) && @unlink($cible)) {
            $message = 'Image supprimée.';
        } else {
            $erreur = 'Suppression impossible.';
        }
    } elseif (isset($_FILES['image'])) {
        $erreur = cms_media_store($_FILES['image']);
        if ($erreur === '') {
            $message = 'Image ajoutée.';
        }
    }
}

$images = cms_media_list();

$adminTitle   = 'Médias';
$adminSection = 'medias';
require __DIR__ . '/../includes/admin_header.php';
?>

<?php if ($message !== ''): ?><p class="avis"><?php echo e($message); ?></p><?php endif; ?>
<?php if ($erreur !== ''): ?><p class="erreur"><?php echo e($erreur); ?></p><?php endif; ?>

<p class="aide">
    Déposez ici les photos de votre commune, de votre club ou de votre association.
    Elles restent sur votre serveur : aucune n'est envoyée ailleurs. Formats acceptés :
    JPEG, PNG, WebP et AVIF, jusqu'à 4 Mo.
</p>

<form action="media.php" method="POST" enctype="multipart/form-data">
    <?php echo csrf_field(); ?>

    <label for="image">Ajouter une image</label>
    <input type="file" name="image" id="image" accept="image/jpeg,image/png,image/webp,image/avif" required>

    <button type="submit">Envoyer</button>
</form>

<?php if ($images): ?>
    <h2>Bibliothèque</h2>

    <ul class="galerie">
        <?php foreach ($images as $img): ?>
            <li>
                <img src="<?php echo e(cms_media_url($img['nom'])); ?>" alt="" loading="lazy" width="240" height="150">

                <p class="galerie-nom"><?php echo e($img['nom']); ?></p>
                <p class="galerie-info"><?php echo e($img['largeur']); ?>×<?php echo e($img['hauteur']); ?> · <?php echo e(round($img['taille'] / 1024)); ?> Ko</p>

                <p class="galerie-url"><input type="text" readonly value="<?php echo e(cms_media_url($img['nom'])); ?>" onclick="this.select()"></p>

                <form action="media.php" method="POST"
                      onsubmit="return confirm('Supprimer <?php echo e($img['nom']); ?> ?');">
                    <?php echo csrf_field(); ?>
                    <button type="submit" name="supprimer" value="<?php echo e($img['nom']); ?>">Supprimer</button>
                </form>
            </li>
        <?php endforeach; ?>
    </ul>
<?php else: ?>
    <p>Aucune image pour le moment.</p>
<?php endif; ?>

<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
