# JO 2028 — Site web des Jeux Olympiques de Los Angeles

Projet de l'épreuve E6 du BTS SIO option SLAM (Lycée La Tournelle, La Garenne-Colombes).

Application web en PHP / MySQL destinée au comité d'organisation des JO de Los Angeles 2028 : consultation publique des sports, du calendrier des épreuves et des résultats, et back-office d'administration (CRUD) réservé aux administrateurs authentifiés.

**Auteur :** Yuung Addi KANSIETOKO

## Livrables

| Livrable | Emplacement | État |
|---|---|---|
| Cahier des charges techniques | [`docs/cahier-des-charges-jo2028.pdf`](docs/cahier-des-charges-jo2028.pdf) | Livré (v1.0) |
| Code source du site | racine du dépôt | En cours |
| Tests unitaires (PHPUnit) | `tests/` | À venir |
| Site en ligne (InfinityFree) | lien ajouté à la mise en production | À venir |

## Stack technique

- HTML5, CSS3 (responsive), JavaScript côté client uniquement
- PHP 8 avec PDO (requêtes préparées)
- MySQL (InnoDB, utf8mb4)
- Environnement local : MAMP — production : InfinityFree

## Sécurité

- Mots de passe administrateurs hachés avec Bcrypt (`password_hash` / `password_verify`)
- Requêtes préparées PDO sur tous les accès à la base (injection SQL)
- Échappement des sorties avec `htmlspecialchars` (XSS)
- Jeton CSRF sur les formulaires du back-office
- Pages d'administration protégées par session
- Identifiants de base de données exclus du dépôt (`.gitignore`)
