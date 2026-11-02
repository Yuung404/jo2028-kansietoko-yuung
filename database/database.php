<?php
/**
 * Connexion unique à la base de données via PDO.
 *
 * Les identifiants sont lus dans config.php (hors dépôt Git).
 * Options retenues :
 *  - ERRMODE_EXCEPTION   : toute erreur SQL lève une exception (rien n'échoue en silence) ;
 *  - FETCH_ASSOC         : les lignes sont renvoyées sous forme de tableaux associatifs ;
 *  - EMULATE_PREPARES off : de vraies requêtes préparées côté serveur MySQL (anti-injection SQL).
 */

$config = $config ?? require __DIR__ . '/config.php';

$dsn = sprintf(
    'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
    $config['host'],
    $config['port'],
    $config['dbname']
);

try {
    $connexion = new PDO($dsn, $config['user'], $config['password'], [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    // Le détail technique va dans le journal du serveur, jamais à l'écran du visiteur
    error_log('Connexion à la base impossible : ' . $e->getMessage());
    http_response_code(503);
    exit('Service momentanément indisponible. Merci de réessayer plus tard.');
}
