<?php
/** Export : un fichier seul, ou tout un type dans une archive zip */
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/transfer.php';
cms_require_admin('../login.php');

$type = (isset($_GET['type']) && $_GET['type'] === 'plugin') ? 'plugin' : 'page';
$dir  = cms_transfer_dir($type);

/** En-tetes communs a tous les telechargements */
function cms_envoyer_entetes($nomFichier, $taille = null)
{
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . str_replace('"', '', $nomFichier) . '"');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: no-store');
    if ($taille !== null) {
        header('Content-Length: ' . $taille);
    }
}

// --- Tout exporter : une archive zip ------------------------------------

if (isset($_GET['all'])) {
    if (!class_exists('ZipArchive')) {
        http_response_code(501);
        exit('Les archives zip ne sont pas supportees par ce serveur. Exportez les fichiers un par un.');
    }

    $fichiers = cms_transfer_list($type);
    if (!$fichiers) {
        http_response_code(404);
        exit('Rien a exporter.');
    }

    $tmp = tempnam(sys_get_temp_dir(), 'cmsexport');
    $zip = new ZipArchive();

    if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) {
        @unlink($tmp);
        http_response_code(500);
        exit('Creation de l\'archive impossible.');
    }

    foreach ($fichiers as $fichier) {
        $zip->addFile($dir . '/' . $fichier, $fichier);
    }
    $zip->close();

    $nom = ($type === 'plugin' ? 'plugins' : 'pages') . '-' . date('Y-m-d') . '.zip';

    cms_envoyer_entetes($nom, filesize($tmp));
    readfile($tmp);
    @unlink($tmp);
    exit;
}

// --- Un fichier ---------------------------------------------------------

$nom = isset($_GET['name']) ? basename((string) $_GET['name']) : '';

if (!cms_transfer_name_ok($type, $nom)) {
    http_response_code(400);
    exit('Nom invalide.');
}

$chemin  = realpath($dir . '/' . $nom);
$dirReal = realpath($dir);

if ($chemin === false || $dirReal === false
    || strpos($chemin, $dirReal . DIRECTORY_SEPARATOR) !== 0
    || !is_file($chemin)) {
    http_response_code(404);
    exit('Fichier introuvable.');
}

cms_envoyer_entetes($nom, filesize($chemin));
readfile($chemin);
exit;
