# CMS

Un CMS en PHP, sans base de données, pensé comme une alternative légère à WordPress.

Tout est stocké en fichiers : les pages, la configuration, les comptes. Pas de MySQL à
installer, pas d'assistant en douze étapes, pas de couche d'abstraction à comprendre
avant de publier une page.

## Pour qui

Pour les **petites structures** : mairies, associations, écoles, commerces, cabinets.
Des sites de quelques dizaines de pages, tenus par une ou deux personnes qui ne sont pas
développeurs, souvent hébergés sur du mutualisé sans accès root.

Ce n'est pas un CMS pour multinationale. Pas de flux de validation éditoriale, pas de
gestion de rédactions multiples, pas de montée en charge sur des millions de pages. En
échange : ça s'installe en cinq minutes, ça tient dans un dossier, et ça se sauvegarde
avec une copie de répertoire.

## Le parti pris

WordPress charge une feuille de style et un script **par extension** : quinze plugins,
c'est trente requêtes bloquantes avant le premier rendu.

Ici, le cœur fusionne le CSS et le JS de tous les plugins **en un seul fichier chacun**,
dans un ordre que chaque plugin déclare lui-même. Une requête pour le style, une pour le
script, quel que soit le nombre de plugins installés.

Le reste découle de ce choix :

- **Un cœur minimal** — il gère les comptes et les pages, rien d'autre.
- **Tout le reste est un plugin**, et écrire un plugin doit rester trivial.
- **La performance côté client** est le critère d'arbitrage par défaut.

## Le stockage

Aujourd'hui : aucune base de données. Les pages sont des fichiers, les comptes un JSON,
la configuration un JSON. C'est ce qui permet de déposer le CMS sur n'importe quel
hébergement et de sauvegarder le site avec un `cp -r`.

Les accès aux données passent tous par un petit nombre de fonctions dans
`includes/bootstrap.php`. Un fork qui voudrait passer à **SQLite3** ou **MariaDB** n'aura
donc pas à réécrire le CMS : c'est une piste ouverte, pas un renoncement au principe du
sans-base.

## État actuel

Le projet est jeune. Ce qui est en place aujourd'hui :

| Fonctionnalité                     | État                                                    |
| ---------------------------------- | ------------------------------------------------------- |
| URL propres (réécriture)           | Fonctionnel                                             |
| Compte administrateur, connexion   | Fonctionnel                                             |
| Création / édition / suppression de pages | Fonctionnel                                       |
| Gestion des fichiers de plugins    | Fonctionnel                                             |
| Système de hooks (`add_action`)    | Les fonctions existent, le chargement n'est pas branché |
| Fusion CSS/JS par priorité         | Spécifié ci-dessous, pas encore implémenté              |
| Multi-utilisateurs et permissions  | Prévu                                                   |

## Prérequis

- PHP **7.3** minimum (testé jusqu'à 8.5)
- **Apache** ou **nginx** — les deux sont supportés, la configuration de chacun est
  fournie plus bas
- Droits d'écriture pour le serveur web sur `data/`, `pages/` et `plugins/`

## Installation

1. Déposez les fichiers à la racine de votre site.
2. Donnez les droits d'écriture :

   ```bash
   chmod -R u+w data pages plugins
   ```

3. Configurez votre serveur web (section suivante).
4. Ouvrez `/register.php` et créez le compte administrateur. La page se verrouille
   d'elle-même une fois le compte créé.
5. Connectez-vous sur `/login.php`, le panneau est sur `/admin/`.

## Serveur web

Le CMS a besoin de deux choses de la part du serveur : envoyer toutes les URL inconnues
vers `index.php`, et **refuser de servir `data/` et `includes/`**.

### Apache

Le fichier `.htaccess` fourni fait déjà tout. Vérifiez seulement que `mod_rewrite` est
actif et que le vhost autorise le fichier :

```apache
<Directory /var/www/votre-site>
    AllowOverride All
</Directory>
```

```bash
a2enmod rewrite && systemctl reload apache2
```

### nginx

nginx ne lit pas les `.htaccess` : les règles doivent aller dans le bloc `server`.

```nginx
server {
    root /var/www/votre-site;
    index index.php;

    # Routage : tout ce qui n'existe pas part vers index.php
    location / {
        try_files $uri $uri/ /index.php?page=$uri&$args;
    }

    # Données privées et cœur : jamais servis
    location ~ ^/(data|includes)/        { deny all; }
    location ~ \.(json|md|log|ini|ya?ml)$ { deny all; }
    location ~ /\.                        { deny all; }

    error_page 404 /404.html;
    error_page 500 502 503 /502.html;

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php-fpm.sock;
    }
}
```

## Configuration

Tout ce qui se change sans toucher au code est dans `config.json` :

| Clé                | Rôle                                                        |
| ------------------ | ----------------------------------------------------------- |
| `page_title`       | Titre du site, balise `<title>`                              |
| `meta_description` | Description pour les moteurs de recherche                    |
| `navbar`           | Objet `libellé: lien`, la barre de navigation est générée à partir de lui |
| `footer_text`      | Texte du pied de page                                        |

## Arborescence

```
admin/          Panneau d'administration (pages, plugins)
data/           Données privées : comptes, compteurs. Jamais servi par HTTP.
includes/       Cœur : bootstrap, hooks, en-tête et pied de page
pages/          Une page = un fichier
plugins/        Un plugin = un fichier
.htaccess       Réécriture d'URL et durcissement (Apache)
config.json     Configuration du site
index.php       Point d'entrée unique
```

## Écrire un plugin

> Cette section décrit la cible. Le chargement automatique des plugins et la fusion des
> assets ne sont pas encore implémentés — voir « État actuel ».

Un plugin est **un seul fichier** déposé dans `plugins/`. Il déclare ses métadonnées en
en-tête et enregistre ses fonctions sur des hooks :

```php
<?php
/**
 * Plugin: Bandeau cookies
 * Version: 1.0
 * CSS-Priority: 32000
 * JS-Priority: 64000
 */

add_action('footer', function () {
    echo '<div class="cookie-banner">Ce site utilise des cookies.</div>';
});
```

### La priorité des assets

Chaque plugin choisit **où son CSS et son JS atterrissent** dans le fichier fusionné.
C'est le même principe qu'un `z-index` :

| Priorité      | Effet                                                                    |
| ------------- | ------------------------------------------------------------------------ |
| `1`           | Tout en haut du fichier fusionné                                          |
| `32000`       | Au milieu, valeur par défaut conseillée                                   |
| `64000`       | Tout en bas                                                               |
| `-1`          | L'asset reste dans un fichier séparé, chargé par sa propre requête        |

Pour le CSS, être plus bas signifie **gagner la cascade** à spécificité égale : un thème
qui doit pouvoir écraser les autres se déclare en `64000`, une base de reset en `1`.

Pour le JS, l'ordre du fichier est l'ordre d'exécution : `1` s'exécute en premier,
`64000` en dernier.

La valeur `-1` existe pour les cas où un plugin a réellement besoin d'être isolé. Elle
coûte une requête HTTP supplémentaire par plugin concerné, ce qui va à l'encontre du but
du projet : **à n'utiliser qu'en dernier recours**.

## Sécurité

Quelques règles que le CMS applique et qu'il ne faut pas défaire :

- **`data/` ne doit jamais être accessible par HTTP.** Il contient les comptes et leurs
  mots de passe hachés. La configuration Apache comme nginx ci-dessus le bloque, et
  `data/` a son propre `.htaccess` en second rideau. Si votre hébergement le permet,
  déplacez ce répertoire au-dessus de la racine web en modifiant `CMS_DATA_DIR` dans
  `includes/bootstrap.php`.
- Les formulaires sont protégés par jeton CSRF, les sessions régénérées à la connexion,
  et les tentatives de connexion limitées à cinq par quart d'heure.
- Un seul compte administrateur est autorisé par défaut. Pour en permettre plusieurs,
  augmentez `CMS_MAX_ACCOUNTS` dans `register.php`.
- Le contenu des pages est aujourd'hui du PHP exécuté par le serveur : n'accordez l'accès
  au panneau qu'à des personnes de confiance.
