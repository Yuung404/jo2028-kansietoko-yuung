# Mise en ligne sur InfinityFree

Procédure de déploiement du site JO 2028 sur l'offre gratuite d'InfinityFree (Apache, PHP 8, MySQL).

> **Attention :** sur l'offre gratuite, un site sans visiteur pendant un mois est désactivé, puis supprimé s'il le reste trop longtemps. Visiter le site au moins une fois par mois, et toujours la veille de l'épreuve.

## Ce qui est mis en ligne

Seuls les fichiers nécessaires au fonctionnement du site sont envoyés (archive `jo2028-site.zip`) :

| Envoyé | Non envoyé (développement uniquement) |
|---|---|
| `.htaccess`, `index.php`, `css/`, `img/`, `includes/`, `pages/` | `tests/`, `vendor/`, `docs/` |
| `database/auth.php`, `database/database.php`, `database/config.example.php` | scripts SQL, `composer.*`, `phpunit.xml`, `lancer-tests.bat` |

## Étape 1 : créer le compte et l'hébergement

1. Créer un compte sur https://www.infinityfree.com (offre gratuite), puis confirmer l'adresse e-mail.
2. Dans le tableau de bord : **Create Account**, choisir un sous-domaine gratuit (par exemple `jo2028-kansietoko.infinityfreeapp.com`).

*Pourquoi :* le compte d'hébergement fournit l'espace web (dossier `htdocs`), la base MySQL et l'adresse publique du site.
*Résultat :* un espace d'hébergement actif ; la propagation du sous-domaine peut prendre quelques minutes.

## Étape 2 : créer la base de données

1. Dans le panneau du compte : **MySQL Databases**, créer une base (par exemple `jo2028`).
2. Noter les 4 informations affichées : **hôte** (`sqlXXX.infinityfree.com`), **nom de la base** (`if0_XXXXXXXX_jo2028`), **utilisateur** (`if0_XXXXXXXX`) et **mot de passe** (celui du compte d'hébergement, visible dans *Account Details*).

*Pourquoi :* sur un hébergement mutualisé, la base se crée depuis le panneau et non en SQL (`CREATE DATABASE` est interdit).
*Résultat :* une base vide, prête à recevoir les tables.

## Étape 3 : importer les tables et les données

1. Ouvrir **phpMyAdmin** depuis le panneau, sélectionner la base, onglet **Importer**.
2. Choisir le fichier `database/deploy-production.sql`, puis **Exécuter**.

*Pourquoi :* ce script unique regroupe les 3 scripts du projet (tables, jeu d'essai, migration) et convertit les tables en UTF-8, au cas où l'hébergeur crée la base en latin1.
*Résultat :* 8 tables, le jeu d'essai, la colonne `nom_epreuve` et les 13 contraintes (7 d'unicité, 6 clés étrangères). Le script peut être réimporté sans erreur.

## Étape 4 : envoyer les fichiers du site

1. Ouvrir le **gestionnaire de fichiers** (Online File Manager) depuis le panneau, puis entrer dans `htdocs`.
2. Supprimer la page par défaut d'InfinityFree (`index2.html` ou équivalent).
3. Envoyer `jo2028-site.zip`, puis l'extraire dans `htdocs` (ou envoyer le contenu du dossier avec FileZilla, hôte FTP indiqué dans *Account Details*, port 21).

*Pourquoi :* `htdocs` est la racine publique du site ; le fichier `.htaccess` doit s'y trouver pour bloquer l'accès aux dossiers internes.
*Résultat :* l'arborescence `htdocs/index.php`, `htdocs/pages/`, `htdocs/includes/`… est en place.

## Étape 5 : configurer la connexion à la base

1. Dans `htdocs/database/`, dupliquer `config.example.php` sous le nom `config.php`.
2. Le modifier avec les informations de l'étape 2 :

```php
return [
    'host'     => 'sqlXXX.infinityfree.com',   // hôte MySQL de l'étape 2
    'port'     => 3306,
    'dbname'   => 'if0_XXXXXXXX_jo2028',       // nom complet de la base
    'user'     => 'if0_XXXXXXXX',
    'password' => 'mot de passe du compte d\'hébergement',
    'debug'    => false,                       // production : aucun détail technique affiché
];
```

*Pourquoi :* `config.php` n'est jamais publié sur GitHub (il est dans `.gitignore`) ; il est créé directement sur chaque serveur. Avec `debug` à `false`, une erreur affiche un message générique au lieu de détails exploitables par un attaquant.
*Résultat :* le site se connecte à la base de production.

## Étape 6 : activer HTTPS

Dans le panneau : **SSL Certificates**, demander un certificat gratuit pour le sous-domaine, puis l'installer.

*Pourquoi :* le mot de passe administrateur ne doit pas circuler en clair. Le site marque le cookie de session `Secure` dès que le serveur signale une connexion HTTPS (variable `$_SERVER['HTTPS']`) ; à contrôler dans les outils de développement du navigateur (onglet Application > Cookies).
*Résultat :* le site répond en `https://` avec le cadenas.

## Étape 7 : recette en ligne

| Vérification | Résultat attendu |
|---|---|
| Accueil, Sports, Calendrier, Résultats | Pages affichées, accents corrects (« Algérie ») |
| Connexion `admin` / `admin123` | Accès au back-office |
| Ajout, modification, suppression d'un sport | Messages de confirmation, données à jour sur les pages publiques |
| `https://<site>/database/deploy-production.sql` | 403 Accès refusé |
| `https://<site>/includes/bootstrap.php` | 404 Page introuvable |
| `https://<site>/pages/admin/admin.php` sans connexion | Redirection vers la page de connexion |

Une fois la recette validée : reporter l'adresse du site dans la fiche descriptive E6 (rubrique « Site web ») et dans le `README.md`.
