# JO 2028 — Site web des Jeux Olympiques de Los Angeles

Projet de l'épreuve E6 du BTS SIO option SLAM (Lycée La Tournelle, La Garenne-Colombes).

Application web en PHP / MySQL destinée au comité d'organisation des JO de Los Angeles 2028 : consultation publique des sports, du calendrier des épreuves et des résultats, et back-office d'administration (CRUD) réservé aux administrateurs authentifiés.

**Auteur :** Yuung Addi KANSIETOKO

## Livrables

| Livrable | Emplacement | État |
|---|---|---|
| Cahier des charges techniques | [`docs/cahier-des-charges-jo2028.pdf`](docs/cahier-des-charges-jo2028.pdf) | Livré (v1.0) |
| Base de données | [`database/`](database/) | Livrée |
| Site public + back-office (8 rubriques CRUD) | racine du dépôt | Livré |
| Tests PHPUnit (29 tests, 77 assertions) + rapport | [`tests/`](tests/), [`docs/tests/`](docs/tests/) | Livrés |
| Mise en ligne (InfinityFree) | [`docs/DEPLOIEMENT.md`](docs/DEPLOIEMENT.md), [`database/deploy-production.sql`](database/deploy-production.sql) | Prête ; adresse du site ajoutée après déploiement |

## Installation en local (MAMP)

1. Copier le projet dans `C:\MAMP\htdocs\jo2028-kansietoko-yuung` et démarrer MAMP.
2. Créer et remplir la base, **dans cet ordre** (phpMyAdmin > Importer, ou en ligne de commande) :
   1. `database/install.sql` : crée la base `kansietoko_yuung_jo2028` en UTF-8 (utf8mb4) ;
   2. `database/db-jo.sql` : crée les tables ;
   3. `database/test-data-jo.sql` : insère le jeu d'essai ;
   4. `database/migration-01.sql` : ajoute la colonne `nom_epreuve` et les contraintes d'unicité.
3. Copier `database/config.example.php` en `database/config.php` et y renseigner les identifiants MySQL.
4. Ouvrir http://localhost/jo2028-kansietoko-yuung/

Comptes de test : `admin` / `admin123`, `john_doe` / `password123`, `jane_doe` / `pass123`.

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

- **Tests unitaires** (`tests/Unitaire`) : validation des saisies, échappement XSS, jeton CSRF, hachage Bcrypt.
- **Tests fonctionnels** (`tests/Fonctionnel`) : PHPUnit démarre le serveur intégré de PHP et pilote les vraies pages (connexion, ajout, modification, suppression), puis vérifie le message affiché **et** l'état de la base.
- Les tests utilisent une base séparée, `kansietoko_yuung_jo2028_test`, recréée avant chaque test : la base réelle n'est jamais modifiée.

Dernier rapport : [`docs/tests/rapport-phpunit.png`](docs/tests/rapport-phpunit.png) (29 tests, 77 assertions, 0 échec, PHPUnit 11.5 / PHP 8.3).

## Organisation du code

```
index.php                 Accueil public
pages/                    Pages publiques (sports, calendrier, résultats), connexion, déconnexion
pages/admin/admin.php     Accueil de l'administration
pages/admin/admin-xxx/    Une rubrique par entité : manage- (liste), add-, modify-, delete-
includes/bootstrap.php    Inclus en tête de chaque page : configuration, session, sécurité, connexion
includes/security.php     Fonctions communes : échappement, CSRF, garde d'accès, validations
includes/*.php            Gabarits partagés (en-tête HTML, menus, pied de page)
tests/                    Tests PHPUnit (unitaires et fonctionnels) et outillage de test
database/                 Connexion PDO, authentification, scripts SQL
css/styles-admin.css      Styles ajoutés : messages, boutons, listes déroulantes, menu admin
```

## Sécurité

- Mots de passe administrateurs hachés avec Bcrypt (`password_hash` / `password_verify`), 8 caractères minimum, 72 octets maximum (limite de Bcrypt).
- Requêtes préparées PDO sur 100 % des accès à la base, émulation désactivée (injection SQL).
- Sorties échappées avec `htmlspecialchars` ; données stockées brutes, sans double encodage (XSS).
- Jeton CSRF sur tous les formulaires ; suppressions uniquement en POST.
- Pages d'administration protégées par session ; déconnexion après 30 minutes d'inactivité ; session coupée si le compte est supprimé.
- Session régénérée à la connexion (fixation de session) ; cookies `HttpOnly` et `SameSite=Strict`.
- Contrôle des doublons en PHP **et** contraintes `UNIQUE` en base ; suppression bloquée tant que des données liées existent.
- `.htaccess` : scripts SQL, configuration, tests, dépendances et gabarits internes inaccessibles depuis le navigateur ; en-têtes anti-clickjacking et nosniff.
- Identifiants de la base hors dépôt (`database/config.php` dans `.gitignore`) ; aucun détail technique affiché en production.
