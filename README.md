# JO 2028 — Site web des Jeux Olympiques de Los Angeles

Projet de l'épreuve E6 du BTS SIO option SLAM (Lycée La Tournelle, La Garenne-Colombes).

Application web en PHP / MySQL destinée au comité d'organisation des JO de Los Angeles 2028 : consultation publique des sports, du calendrier des épreuves et des résultats, et back-office d'administration (CRUD) réservé aux administrateurs authentifiés.

**Auteur :** Yuung Addi KANSIETOKO

**Site en ligne :** https://yuung404.infinityfree.me

## Livrables

| Livrable | Emplacement | État |
|---|---|---|
| Cahier des charges techniques | [`docs/cahier-des-charges-jo2028.pdf`](docs/cahier-des-charges-jo2028.pdf) | Livré (v1.0) |
| Base de données | [`database/`](database/) | Livrée |
| Site public + back-office (8 rubriques CRUD) | racine du dépôt | Livré |
| Tests PHPUnit (31 tests) + rapport | [`tests/`](tests/), [`docs/tests/`](docs/tests/) | Livrés |
| Mise en ligne (InfinityFree) | [`docs/DEPLOIEMENT.md`](docs/DEPLOIEMENT.md), [`database/deploy-production.sql`](database/deploy-production.sql) | En ligne : https://yuung404.infinityfree.me |

## Installation en local (MAMP)

1. Copier le projet dans `C:\MAMP\htdocs\jo2028-kansietoko-yuung` et démarrer MAMP.
2. Créer et remplir la base, **dans cet ordre** (phpMyAdmin > Importer, ou en ligne de commande) :
   1. `database/install.sql` : crée la base `kansietoko_yuung_jo2028` en UTF-8 (utf8mb4) ;
   2. `database/db-jo.sql` : crée les tables ;
   3. `database/test-data-jo.sql` : insère le jeu d'essai ;
   4. `database/migration-01.sql` : ajoute la colonne `nom_epreuve` et les contraintes d'unicité.
3. Copier `database/config.example.php` en `database/config.php` et y renseigner les identifiants MySQL.
4. Ouvrir http://localhost/jo2028-kansietoko-yuung/

Comptes de test du jeu d'essai, **en local uniquement** : `admin`, `john_doe` et `jane_doe`. Leurs mots de passe de test sont dans `tests/Support/Navigateur.php`. Ils ne doivent jamais servir sur le site en ligne, dont le mot de passe administrateur est personnel et n'est écrit nulle part.

## Tests (PHPUnit)

Installation des dépendances de développement (une seule fois) :

```
php composer.phar install
```

Lancement sous Windows avec MAMP (le script utilise le PHP **et** le php.ini de MAMP, sans lesquels les extensions pdo_mysql et curl manquent) :

```
lancer-tests.bat            :: résultats dans la console
lancer-tests.bat rapport    :: génère aussi le rapport dans docs/tests/
```

- **Tests unitaires** (`tests/Unitaire`) : hachage Bcrypt et empreintes du jeu d'essai (annexe 2).
- **Tests fonctionnels** (`tests/Fonctionnel`) : PHPUnit démarre le serveur intégré de PHP et pilote les vraies pages (connexion, ajout, modification, suppression dans les 8 rubriques), puis vérifie le message affiché **et** l'état de la base.
- Les tests utilisent une base séparée, `kansietoko_yuung_jo2028_test`, recréée avant chaque test : la base réelle n'est jamais modifiée.

Dernier rapport : [`docs/tests/rapport-phpunit.png`](docs/tests/rapport-phpunit.png) (31 tests, 0 échec, PHPUnit 11.5 / PHP 8.3).

## Organisation du code

Le code part de la base fournie par le professeur (accueil, pages publiques, connexion, et la rubrique `admin-sports` comme modèle). Les 7 autres rubriques de la mission 6 sont écrites sur ce modèle, avec la même structure : chaque page est autonome (session, connexion à la base, HTML complet).

```
index.php                 Accueil public (fourni)
pages/                    Pages publiques (sports, calendrier, résultats), connexion, déconnexion (fournies)
pages/admin/admin.php     Accueil de l'administration (fourni)
pages/admin/admin-sports/ Rubrique modèle fournie : manage- (liste), add-, modify-, delete-
pages/admin/admin-xxx/    Les 7 rubriques des questions 13 à 19, sur le modèle des sports
database/database.php     Connexion PDO (fournie) ; identifiants en ligne lus dans config.php
database/auth.php         Traitement de la connexion (fourni)
database/*.sql            Scripts de la base (fournis) + migration et script de production
tests/                    Tests PHPUnit (unitaires et fonctionnels) et outillage de test
```

Dans les fichiers fournis, chaque modification est signalée par un commentaire `// Correction :` ou `// Ajout :` qui explique pourquoi elle a été faite.

## Sécurité (annexe 1)

- Mots de passe administrateurs hachés avec Bcrypt (`password_hash` / `password_verify`), 8 caractères minimum, confirmation obligatoire.
- Pages d'administration inaccessibles sans connexion (`$_SESSION['login']` vérifié en haut de chaque page).
- Requêtes préparées PDO sur tous les accès à la base (injection SQL).
- Sorties échappées avec `htmlspecialchars` ; données stockées telles qu'elles ont été tapées, sans double encodage (XSS).
- Token CSRF sur tous les formulaires ; suppressions uniquement en POST (corrigé : le modèle fourni supprimait par un lien GET).
- Session régénérée à la connexion (fixation de session).
- Contrôle des doublons en PHP **et** contraintes `UNIQUE` en base ; suppression bloquée tant que des données liées existent.
- Messages d'erreur SQL écrits dans le journal du serveur, jamais affichés au visiteur.
- `.htaccess` : scripts SQL, configuration, tests et dépendances inaccessibles depuis le navigateur.
- Identifiants de la base en ligne hors dépôt (`database/config.php` dans `.gitignore`).
