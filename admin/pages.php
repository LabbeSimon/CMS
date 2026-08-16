<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/transfer.php';
cms_require_admin('../login.php');

$message = '';
$erreurs = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    // --- Suppression ---------------------------------------------------
    if (isset($_POST['delete'])) {
        $slug = basename((string) $_POST['delete']);

        if (!cmsh_slug_ok($slug)) {
            $message = 'Slug invalide.';
        } else {
            $cible     = realpath(cmsh_path($slug));
            $pagesReal = realpath(CMS_PAGES_DIR);

            if ($cible === false || $pagesReal === false
                || strpos($cible, $pagesReal . DIRECTORY_SEPARATOR) !== 0) {
                $message = 'Page introuvable.';
            } elseif (@unlink($cible)) {
                $message = 'Page supprimee.';
            } else {
                $message = 'Suppression impossible : verifiez les droits sur pages/.';
            }
        }
    }

    // --- Import ---------------------------------------------------------
    if (isset($_FILES['fichier'])) {
        list($nb, $erreurs) = cms_transfer_import('page', $_FILES['fichier'], !empty($_POST['ecraser']));
        if ($nb > 0) {
            $message = $nb > 1 ? "$nb pages importees." : '1 page importee.';
        }
    }
}

$pages = cmsh_list();

$adminTitle   = 'Pages';
$adminSection = 'pages';
require __DIR__ . '/../includes/admin_header.php';
?>

<?php if ($message !== ''): ?>
    <p class="avis"><?php echo e($message); ?></p>
<?php endif; ?>

<?php foreach ($erreurs as $erreur): ?>
    <p class="erreur"><?php echo e($erreur); ?></p>
<?php endforeach; ?>

<p class="ligne-actions">
    <a class="bouton" href="add_page.php">Créer une page</a>
    <a class="bouton" href="export.php?type=page&amp;all=1">Tout exporter (.zip)</a>
</p>

<table>
    <thead>
        <tr>
            <th>Titre</th>
            <th>Adresse</th>
            <th>Modifiée</th>
            <th>État</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($pages as $slug => $page): ?>
        <?php $meta = $page['meta']; $manquants = cmsh_missing_plugins($meta); ?>
        <tr>
            <td><?php echo e($meta['title'] !== '' ? $meta['title'] : $slug); ?></td>
            <td><a href="<?php echo e(cms_url($slug)); ?>" target="_blank" rel="noopener">/<?php echo e($slug); ?></a></td>
            <td>
                <?php echo $meta['modified'] ? e(date('d/m/Y H:i', strtotime($meta['modified']))) : '—'; ?>
                <?php if ($meta['modified_by'] !== ''): ?>
                    <br><small><?php echo e($meta['modified_by']); ?></small>
                <?php endif; ?>
            </td>
            <td>
                <?php if ($page['altere']): ?>
                    <span class="etiquette">empreinte</span>
                <?php endif; ?>
                <?php if ($manquants): ?>
                    <span class="etiquette">plugin</span>
                <?php endif; ?>
                <?php if (strpos($meta['meta']['robots'], 'noindex') !== false): ?>
                    <span class="etiquette">noindex</span>
                <?php endif; ?>
            </td>
            <td class="actions">
                <a href="edit_page.php?page=<?php echo urlencode($slug); ?>">Modifier</a>
                <a href="export.php?type=page&amp;name=<?php echo urlencode($slug . CMSH_EXT); ?>">Exporter</a>
                <form action="pages.php" method="POST"
                      onsubmit="return confirm('Supprimer definitivement la page <?php echo e($slug); ?> ?');">
                    <?php echo csrf_field(); ?>
                    <button type="submit" name="delete" value="<?php echo e($slug); ?>">Supprimer</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$pages): ?>
        <tr><td colspan="5">Aucune page pour le moment.</td></tr>
    <?php endif; ?>
    </tbody>
</table>

<h2>Importer</h2>

<p>Un fichier <code>.cmsh</code> exporté d'ici, ou une archive <code>.zip</code> issue d'un export complet.</p>

<form action="pages.php" method="POST" enctype="multipart/form-data">
    <?php echo csrf_field(); ?>

    <label for="fichier">Fichier</label>
    <input type="file" name="fichier" id="fichier" accept=".cmsh,.zip" required>

    <label class="case">
        <input type="checkbox" name="ecraser" value="1">
        Remplacer les pages existantes portant le même nom
    </label>

    <button type="submit">Importer</button>
</form>

<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
