<?php
/** Import et export des pages et des plugins */
require_once __DIR__ . '/bootstrap.php';

define('CMS_IMPORT_MAX_SIZE', 512 * 1024); // 512 Ko par fichier

/** Repertoire cible selon le type d'objet */
function cms_transfer_dir($type)
{
    return $type === 'plugin' ? CMS_PLUGINS_DIR : CMS_PAGES_DIR;
}

/** Un nom de fichier acceptable pour ce type d'objet */
function cms_transfer_name_ok($type, $name)
{
    if ($name === '' || $name !== basename($name) || $name[0] === '.') {
        return false;
    }

    if ($type === 'plugin') {
        return (bool) preg_match('/^[A-Za-z0-9][A-Za-z0-9_.-]{0,63}$/', $name);
    }

    // Les pages sont des fichiers .cmsh : du contenu, jamais du code
    return (bool) preg_match('/^[A-Za-z0-9][A-Za-z0-9_-]{0,63}\.cmsh$/', $name);
}

/** Ecrit un fichier importe apres validation */
function cms_transfer_store($type, $name, $contenu, $ecraser = false)
{
    if (!cms_transfer_name_ok($type, $name)) {
        return 'Nom de fichier refuse : ' . $name;
    }

    if (strlen($contenu) > CMS_IMPORT_MAX_SIZE) {
        return $name . ' depasse ' . (CMS_IMPORT_MAX_SIZE / 1024) . ' Ko.';
    }

    $cible = cms_transfer_dir($type) . '/' . $name;

    if (!$ecraser && file_exists($cible)) {
        return $name . ' existe deja (cochez « remplacer » pour l\'ecraser).';
    }

    if (file_put_contents($cible, $contenu, LOCK_EX) === false) {
        return 'Ecriture impossible : verifiez les droits du repertoire.';
    }

    return '';
}

/** Traite un envoi de formulaire : un fichier seul, ou une archive zip */
function cms_transfer_import($type, array $fichier, $ecraser = false)
{
    if (!isset($fichier['error']) || $fichier['error'] !== UPLOAD_ERR_OK) {
        $raisons = [
            UPLOAD_ERR_INI_SIZE   => 'Fichier trop volumineux pour la configuration du serveur.',
            UPLOAD_ERR_FORM_SIZE  => 'Fichier trop volumineux.',
            UPLOAD_ERR_PARTIAL    => 'Envoi interrompu.',
            UPLOAD_ERR_NO_FILE    => 'Aucun fichier selectionne.',
            UPLOAD_ERR_NO_TMP_DIR => 'Repertoire temporaire absent sur le serveur.',
            UPLOAD_ERR_CANT_WRITE => 'Ecriture impossible sur le serveur.',
        ];
        $code = isset($fichier['error']) ? $fichier['error'] : -1;

        return [0, [isset($raisons[$code]) ? $raisons[$code] : 'Envoi echoue.']];
    }

    // Garde-fou : le fichier doit venir d'un vrai envoi HTTP
    if (!is_uploaded_file($fichier['tmp_name'])) {
        return [0, ['Fichier invalide.']];
    }

    $nom = basename((string) $fichier['name']);

    // --- Archive complete ---------------------------------------------
    if (strtolower(substr($nom, -4)) === '.zip') {
        if (!class_exists('ZipArchive')) {
            return [0, ['Les archives zip ne sont pas supportees par ce serveur.']];
        }

        $zip = new ZipArchive();
        if ($zip->open($fichier['tmp_name']) !== true) {
            return [0, ['Archive illisible.']];
        }

        $importes = 0;
        $erreurs  = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $interne = $zip->getNameIndex($i);

            // On ne conserve que le nom de fichier : une archive piegee
            // peut contenir des chemins du type ../../includes/x.php
            $court = basename($interne);

            if (substr($interne, -1) === '/' || $court === '') {
                continue;
            }

            $contenu = $zip->getFromIndex($i);
            if ($contenu === false) {
                $erreurs[] = $court . ' : lecture impossible.';
                continue;
            }

            $erreur = cms_transfer_store($type, $court, $contenu, $ecraser);
            if ($erreur === '') {
                $importes++;
            } else {
                $erreurs[] = $erreur;
            }
        }

        $zip->close();

        return [$importes, $erreurs];
    }

    // --- Fichier seul --------------------------------------------------
    $contenu = file_get_contents($fichier['tmp_name']);
    if ($contenu === false) {
        return [0, ['Lecture du fichier impossible.']];
    }

    $erreur = cms_transfer_store($type, $nom, $contenu, $ecraser);

    return $erreur === '' ? [1, []] : [0, [$erreur]];
}

/** Liste les fichiers exportables d'un type */
function cms_transfer_list($type)
{
    $dir   = cms_transfer_dir($type);
    $liste = [];

    foreach (scandir($dir) as $entree) {
        if ($entree === '.' || $entree === '..' || !is_file($dir . '/' . $entree)) {
            continue;
        }
        if ($type === 'page' && substr($entree, -5) !== '.cmsh') {
            continue;
        }
        $liste[] = $entree;
    }

    sort($liste);

    return $liste;
}
