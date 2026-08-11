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
        $name = basename((string) $_POST['delete']);

        if (!preg_match('/^[A-Za-z0-9_.-]+$/', $name) || $name === '.' || $name === '..') {
            $message = 'Nom de plugin invalide.';
        } else {
            $target      = realpath(CMS_PLUGINS_DIR . '/' . $name);
            $pluginsReal = realpath(CMS_PLUGINS_DIR);

            if ($target === false || $pluginsReal === false
                || strpos($target, $pluginsReal . DIRECTORY_SEPARATOR) !== 0
                || !is_file($target)) {
                $message = 'Plugin introuvable.';
            } elseif (@unlink($target)) {
                $message = 'Plugin supprime.';
            } else {
                $message = 'Suppression impossible : verifiez les droits sur plugins/.';
            }
        }
    }

    // --- Import ---------------------------------------------------------
    if (isset($_FILES['fichier'])) {
        list($nb, $erreurs) = cms_transfer_import('plugin', $_FILES['fichier'], !empty($_POST['ecraser']));
        if ($nb > 0) {
            $message = $nb > 1 ? "$nb plugins importes." : '1 plugin importe.';
        }
    }
}

// Un fichier sans en-tete Plugin: n'est jamais execute.
$entetes = cms_plugins_info();
$plugins = [];

foreach (cms_transfer_list('plugin') as $fichier) {
    $plugins[$fichier] = isset($entetes[$fichier])
        ? $entetes[$fichier]
        : ['plugin' => '', 'version' => '', 'description' => '', 'charge' => false];
}

$adminTitle   = 'Plugins';
$adminSection = 'plugins';
require __DIR__ . '/../includes/admin_header.php';
?>

<?php if ($message !== ''): ?>
    <p class="avis"><?php echo e($message); ?></p>
<?php endif; ?>

<?php foreach ($erreurs as $erreur): ?>
    <p class="erreur"><?php echo e($erreur); ?></p>
<?php endforeach; ?>

<p class="ligne-actions">
    <a class="bouton" href="add_plugin.php">Créer un plugin</a>
    <a class="bouton" href="export.php?type=plugin&amp;all=1">Tout exporter (.zip)</a>
</p>

<table>
    <thead>
        <tr>
            <th>Plugin</th>
            <th>Fichier</th>
            <th>État</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($plugins as $fichier => $infos): ?>
        <tr>
            <td>
                <?php if ($infos['charge']): ?>
                    <strong><?php echo e($infos['plugin']); ?></strong>
                    <?php if ($infos['version'] !== ''): ?>
                        <small>v<?php echo e($infos['version']); ?></small>
                    <?php endif; ?>
                    <?php if ($infos['description'] !== ''): ?>
                        <br><small><?php echo e($infos['description']); ?></small>
                    <?php endif; ?>
                <?php else: ?>
                    <em>non déclaré</em>
                <?php endif; ?>
            </td>
            <td><?php echo e($fichier); ?></td>
            <td>
                <?php if ($infos['charge']): ?>
                    actif
                <?php else: ?>
                    <span class="etiquette">ignoré</span>
                    <br><small>en-tête <code>Plugin:</code> absent</small>
                <?php endif; ?>
            </td>
            <td class="actions">
                <a href="edit_plugin.php?plugin=<?php echo urlencode($fichier); ?>">Modifier</a>
                <a href="export.php?type=plugin&amp;name=<?php echo urlencode($fichier); ?>">Exporter</a>
                <form action="plugins.php" method="POST"
                      onsubmit="return confirm('Supprimer definitivement <?php echo e($fichier); ?> ?');">
                    <?php echo csrf_field(); ?>
                    <button type="submit" name="delete" value="<?php echo e($fichier); ?>">Supprimer</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$plugins): ?>
        <tr><td colspan="4">Aucun plugin pour le moment.</td></tr>
    <?php endif; ?>
    </tbody>
</table>

<h2>Importer</h2>

<p>Un fichier de plugin, ou une archive <code>.zip</code> issue d'un export complet.</p>

<form action="plugins.php" method="POST" enctype="multipart/form-data">
    <?php echo csrf_field(); ?>

    <label for="fichier">Fichier</label>
    <input type="file" name="fichier" id="fichier" required>

    <label class="case">
        <input type="checkbox" name="ecraser" value="1">
        Remplacer les plugins existants portant le même nom
    </label>

    <button type="submit">Importer</button>
</form>

<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
