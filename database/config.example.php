<?php
/**
 * Modèle de configuration de la base de données.
 *
 * Copier ce fichier en "config.php" (même dossier) puis renseigner les identifiants.
 * "config.php" est exclu du dépôt Git (.gitignore) : les mots de passe ne sont jamais publiés.
 */
return [
    'host'     => 'localhost',
    'port'     => 3306,
    'dbname'   => 'kansietoko_yuung_jo2028',
    'user'     => 'root',
    'password' => 'root',
    // true en local (affiche le détail des erreurs), false en production
    'debug'    => true,
];
