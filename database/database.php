<?php
// Paramètres de connexion à la base de données
$host = "localhost";
$port = 3306;
$dbname = "kansietoko_yuung_jo2028"; // Base créée à la question 10 (« nom_prenom_jo2028 »)
$username = "root";
$password = "root";

// Correction : en ligne (InfinityFree), les identifiants ne sont pas les mêmes qu'en local.
// Ils sont lus dans config.php, un fichier exclu de GitHub (.gitignore) : le mot de passe de la base n'est jamais publié.
if (file_exists(__DIR__ . "/config.php")) {
    $config = require __DIR__ . "/config.php";
    $host = $config['host'];
    $port = $config['port'];
    $dbname = $config['dbname'];
    $username = $config['user'];
    $password = $config['password'];
}

// Tests PHPUnit (mission 7) : le serveur de test travaille sur une copie de la base, suffixée par _test
if (getenv("JO2028_TEST") === "1") {
    $dbname .= "_test";
}

try {
    // Création d'une nouvelle connexion PDO
    // Correction : utf8mb4 au lieu de utf8 (dans MySQL, utf8 est un UTF-8 incomplet, limité à 3 octets)
    $connexion = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);

    // Définir l'attribut PDO pour générer des exceptions en cas d'erreur
    $connexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Correction : le message technique (nom du serveur, utilisateur...) n'est plus affiché au visiteur.
    // Il est écrit dans le journal du serveur, et le script s'arrête : sans connexion, aucune page ne peut fonctionner.
    error_log("Erreur de connexion à la base de données : " . $e->getMessage());
    exit("Erreur de connexion à la base de données. Veuillez réessayer plus tard.");
}
// Afficher les erreurs en PHP
// (fonctionne à condition d’avoir activé l’option en local)
error_reporting(E_ALL);
ini_set("display_errors", 1);
?>
