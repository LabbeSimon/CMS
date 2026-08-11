<?php
require_once __DIR__ . '/../includes/bootstrap.php';
cms_require_admin('../login.php');

$nbPages = 0;
foreach (scandir(CMS_PAGES_DIR) as $entree) {
    if (substr($entree, -4) === '.php' && is_file(CMS_PAGES_DIR . '/' . $entree)) {
        $nbPages++;
    }
}

$nbPlugins = 0;
foreach (scandir(CMS_PLUGINS_DIR) as $entree) {
    if ($entree !== '.' && $entree !== '..' && is_file(CMS_PLUGINS_DIR . '/' . $entree)) {
        $nbPlugins++;
    }
}

$utilisateur = cms_current_user();

$adminTitle   = 'Tableau de bord';
$adminSection = 'tableau';
require __DIR__ . '/../includes/admin_header.php';
?>

<div class="chiffres">
    <a href="pages.php">
        <span class="nombre"><?php echo $nbPages; ?></span>
        <span class="etiquette-chiffre"><?php echo $nbPages > 1 ? 'pages' : 'page'; ?></span>
    </a>
    <a href="plugins.php">
        <span class="nombre"><?php echo $nbPlugins; ?></span>
        <span class="etiquette-chiffre"><?php echo $nbPlugins > 1 ? 'plugins' : 'plugin'; ?></span>
    </a>
</div>

<?php
$sbrActif = function_exists('sbr_config') && !empty(sbr_config()['enabled']);
$sbrEtat  = function_exists('sbr_state') ? sbr_state() : [];
?>

<?php if (!$sbrActif): ?>
    <p class="alerte">
        <strong>Le site n'est pas protégé.</strong> Le filtrage des adresses signalées est
        inactif : tous les visiteurs sont acceptés sans vérification.
        <a href="security.php">Activer la protection</a>
    </p>
<?php elseif (isset($sbrEtat['code']) && in_array($sbrEtat['code'], ['clef-requise', 'quota'], true)): ?>
    <p class="alerte">
        <strong>Protection inopérante.</strong> <?php echo e($sbrEtat['detail']); ?>
        <a href="security.php">Voir la sécurité</a>
    </p>
<?php endif; ?>

<div class="apercu">
    <p class="legende">
        <span>Page d'ouverture</span>
        <a href="<?php echo e(cms_base_uri()); ?>" target="_blank" rel="noopener">Ouvrir dans un onglet</a>
    </p>
    <iframe src="<?php echo e(cms_base_uri()); ?>" title="Aperçu de la page d'ouverture" loading="lazy"></iframe>
</div>

<h2>Compte</h2>

<table>
    <tr>
        <th>Identifiant</th>
        <td><?php echo e(isset($_SESSION['username']) ? $_SESSION['username'] : ''); ?></td>
    </tr>
    <tr>
        <th>Origine</th>
        <td><?php echo isset($utilisateur['provider']) && $utilisateur['provider'] === 'rayor'
            ? 'Rayor Connect' : 'Compte local'; ?></td>
    </tr>
    <?php if (!empty($utilisateur['rayor_id'])): ?>
    <tr>
        <th>Rayor ID</th>
        <td><?php echo e($utilisateur['rayor_id']); ?></td>
    </tr>
    <?php endif; ?>
    <?php if (!empty($utilisateur['email'])): ?>
    <tr>
        <th>E-mail</th>
        <td><?php echo e($utilisateur['email']); ?></td>
    </tr>
    <?php endif; ?>
    <?php if (!empty($utilisateur['created'])): ?>
    <tr>
        <th>Créé le</th>
        <td><?php echo e(date('d/m/Y', strtotime($utilisateur['created']))); ?></td>
    </tr>
    <?php endif; ?>
</table>

<h2>Vie privée</h2>

<p>Ce que ce CMS n'envoie à personne, et qui est vérifiable dans le code :</p>

<ul class="garanties">
    <li>Aucune police téléchargée : la typographie s'appuie sur celles déjà présentes sur la machine du visiteur.</li>
    <li>Aucun CDN, aucun script tiers, aucune balise de mesure d'audience sur les pages publiques.</li>
    <li>Un seul cookie, celui de la session d'administration. Aucun cookie n'est posé aux visiteurs.</li>
    <li>Le choix de thème reste dans le navigateur, il ne voyage pas jusqu'au serveur.</li>
    <li>Aucune base de données : vos contenus restent des fichiers, sur votre serveur.</li>
</ul>

<p>
    Deux exceptions, assumées et visibles : le plugin Secure By Rayor interroge
    <code>secure.rayor.fr</code> pour l'adresse des visiteurs, et l'avatar d'un compte
    Rayor est chargé depuis <code>connect.rayor.fr</code> dans ce panneau — jamais sur
    le site public.
</p>

<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
