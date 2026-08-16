<?php
require_once __DIR__ . '/../includes/bootstrap.php';
cms_require_admin('../login.php');

$plugin_present = function_exists('sbr_config');

$message = '';
$test    = null;

if ($plugin_present && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    if (isset($_POST['tester'])) {
        // Adresse d'exemple de la documentation Secure By Rayor
        $ip   = isset($_POST['ip']) && trim($_POST['ip']) !== '' ? trim((string) $_POST['ip']) : '122.161.49.171';
        $test = ['ip' => $ip, 'data' => filter_var($ip, FILTER_VALIDATE_IP) ? sbr_api_check($ip) : null];
    } else {
        $conf = sbr_config();

        $conf['enabled']           = !empty($_POST['enabled']);
        $conf['api_key']           = trim((string) $_POST['api_key']);
        $conf['threshold']         = max(1, min(100, (int) $_POST['threshold']));
        $conf['action']            = $_POST['action'] === 'log' ? 'log' : 'block';
        $conf['cache_ttl']         = max(60, (int) $_POST['cache_ttl']);
        $conf['report_bruteforce'] = !empty($_POST['report_bruteforce']);

        $message = sbr_config_save($conf)
            ? 'Réglages enregistrés.'
            : 'Écriture impossible dans data/.';

        header('Location: security.php?ok=' . urlencode($message));
        exit;
    }
}

$conf  = $plugin_present ? sbr_config() : [];
$etat  = $plugin_present ? sbr_state() : [];
$actif = $plugin_present && !empty($conf['enabled']);

$adminTitle   = 'Sécurité';
$adminSection = 'securite';
require __DIR__ . '/../includes/admin_header.php';
?>

<?php if (isset($_GET['ok'])): ?>
    <p class="avis"><?php echo e($_GET['ok']); ?></p>
<?php endif; ?>

<h2>Secure By Rayor</h2>

<p>
    Premier rempart du site : chaque visiteur est confronté à la base de réputation
    d'adresses IP <a href="https://secure.rayor.fr" target="_blank" rel="noopener">Secure By Rayor</a>
    avant que la moindre page ne soit servie.
</p>

<?php if (!$plugin_present): ?>
    <p class="alerte">
        <strong>Aucune protection.</strong> Le plugin <code>secure-by-rayor.php</code> est
        absent du répertoire <code>plugins/</code>, ou son en-tête <code>Plugin:</code> est
        manquant. Le site accepte actuellement tous les visiteurs sans filtrage.
    </p>
<?php elseif (!$actif): ?>
    <p class="alerte">
        <strong>Protection désactivée.</strong> Aucune adresse n'est vérifiée. Activez-la
        ci-dessous pour rétablir le filtrage.
    </p>
<?php elseif (isset($etat['code']) && $etat['code'] === 'clef-requise'): ?>
    <p class="alerte">
        <strong>Protection inopérante.</strong> L'API a refusé le dernier appel anonyme
        (<?php echo e($etat['detail']); ?>). Le site laisse passer tout le monde tant que
        ce point n'est pas réglé : renseignez une clé ci-dessous.
    </p>
<?php elseif (isset($etat['code']) && $etat['code'] === 'quota'): ?>
    <p class="alerte">
        <strong>Quota atteint.</strong> Les vérifications sont suspendues jusqu'au
        renouvellement. Une clé liée à un compte relève nettement les quotas.
    </p>
<?php elseif (isset($etat['code']) && $etat['code'] === 'ok'): ?>
    <p class="avis">
        Protection active — dernier appel accepté le
        <?php echo e(date('d/m/Y à H:i', strtotime($etat['horodate']))); ?>.
        <?php echo e($etat['detail']); ?>
    </p>
<?php else: ?>
    <p class="avis">
        Protection activée. Aucun appel n'a encore été effectué : le premier visiteur
        externe déclenchera la vérification.
    </p>
<?php endif; ?>

<?php if ($plugin_present && empty($conf['api_key'])): ?>
    <div class="encart">
        <h3>Passer à des quotas supérieurs</h3>
        <p>
            Sans clé, les vérifications restent limitées et peuvent être refusées aux
            heures chargées. Un compte Rayor Connect donne une clé personnelle, des
            quotas nettement plus élevés — qui augmentent encore avec l'ancienneté et
            les signalements validés — et l'accès au signalement d'adresses.
        </p>
        <p>
            <a class="bouton" href="https://secure.rayor.fr" target="_blank" rel="noopener">Créer un compte et obtenir une clé</a>
        </p>
    </div>
<?php endif; ?>

<?php if ($plugin_present): ?>

<form action="security.php" method="POST">
    <?php echo csrf_field(); ?>

    <label class="case">
        <input type="checkbox" name="enabled" value="1" <?php echo !empty($conf['enabled']) ? 'checked' : ''; ?>>
        Activer la protection
    </label>

    <label for="api_key">Clé d'API (facultative — relève les quotas)</label>
    <input type="text" name="api_key" id="api_key" autocomplete="off" spellcheck="false"
           placeholder="rsk_…" value="<?php echo e($conf['api_key']); ?>">

    <label for="threshold">Score d'abus à partir duquel on refuse (1 à 100)</label>
    <input type="number" name="threshold" id="threshold" min="1" max="100" value="<?php echo (int) $conf['threshold']; ?>">

    <label for="action">Que faire d'une adresse signalée</label>
    <select name="action" id="action">
        <option value="block" <?php echo $conf['action'] === 'block' ? 'selected' : ''; ?>>Refuser l'accès</option>
        <option value="log"   <?php echo $conf['action'] === 'log' ? 'selected' : ''; ?>>Journaliser sans bloquer</option>
    </select>

    <label for="cache_ttl">Durée de vie d'un verdict (secondes)</label>
    <input type="number" name="cache_ttl" id="cache_ttl" min="60" value="<?php echo (int) $conf['cache_ttl']; ?>">

    <label class="case">
        <input type="checkbox" name="report_bruteforce" value="1" <?php echo !empty($conf['report_bruteforce']) ? 'checked' : ''; ?>>
        Signaler à la base communautaire les adresses bloquées pour tentatives de connexion
        (nécessite une clé)
    </label>

    <button type="submit">Enregistrer</button>
</form>

<h2>Tester</h2>

<form action="security.php" method="POST">
    <?php echo csrf_field(); ?>

    <label for="ip">Adresse à vérifier</label>
    <input type="text" name="ip" id="ip" placeholder="122.161.49.171">

    <button type="submit" name="tester" value="1">Interroger l'API</button>
</form>

<?php if ($test !== null): ?>
    <?php if ($test['data'] === null): ?>
        <p class="erreur">
            Aucune réponse exploitable pour <code><?php echo e($test['ip']); ?></code>.
            <?php if (isset($etat['detail'])): ?><?php echo e($etat['detail']); ?><?php endif; ?>
        </p>
    <?php else: ?>
        <table>
            <tr><th>Adresse</th><td><?php echo e($test['ip']); ?></td></tr>
            <tr><th>Score d'abus</th><td><?php echo (int) $test['data']['abuseConfidenceScore']; ?>/100</td></tr>
            <tr><th>Malveillante</th><td><?php echo !empty($test['data']['malicious']) ? 'oui' : 'non'; ?></td></tr>
            <tr><th>Pays</th><td><?php echo e(isset($test['data']['countryCode']) ? $test['data']['countryCode'] : '—'); ?></td></tr>
            <tr><th>Signalements</th><td><?php echo e(isset($test['data']['totalReports']) ? $test['data']['totalReports'] : '—'); ?></td></tr>
        </table>
    <?php endif; ?>
<?php endif; ?>

<?php endif; ?>

<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
