<?php
namespace Tests\Support;

use PDO;

/**
 * Base de données de test : <base réelle>_test, recréée à l'identique de la procédure d'installation
 * (install + db-jo.sql + test-data-jo.sql + migration-01.sql). La base réelle n'est jamais modifiée.
 */
final class BaseDeTest
{
    private static ?array $config = null;

    public static function config(): array
    {
        if (self::$config === null) {
            $fichier = __DIR__ . '/../../database/config.php';
            if (!is_file($fichier)) {
                throw new \RuntimeException('database/config.php introuvable : copiez config.example.php en config.php.');
            }
            self::$config = require $fichier;
            self::$config['dbname'] .= '_test';
        }
        return self::$config;
    }

    /** Connexion PDO à la base de test. */
    public static function pdo(): PDO
    {
        $c = self::config();
        return new PDO(
            "mysql:host={$c['host']};port={$c['port']};dbname={$c['dbname']};charset=utf8mb4",
            $c['user'], $c['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
    }

    /** Supprime puis recrée la base de test avec le jeu d'essai. */
    public static function reinitialiser(): void
    {
        $c = self::config();
        $serveur = new PDO("mysql:host={$c['host']};port={$c['port']};charset=utf8mb4", $c['user'], $c['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $base = '`' . str_replace('`', '', $c['dbname']) . '`';
        $serveur->exec("DROP DATABASE IF EXISTS $base");
        $serveur->exec("CREATE DATABASE $base CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $serveur->exec("USE $base");
        foreach (['db-jo.sql', 'test-data-jo.sql', 'migration-01.sql'] as $script) {
            foreach (self::instructions(__DIR__ . '/../../database/' . $script) as $sql) {
                $serveur->exec($sql);
            }
        }
    }

    /** Découpe un script SQL en instructions (commentaires « -- » retirés). */
    private static function instructions(string $fichier): array
    {
        $sql = preg_replace('/(^|\s)--\s[^\'\n]*$/m', '', file_get_contents($fichier));
        return array_filter(array_map('trim', explode(';', $sql)), fn ($i) => $i !== '');
    }
}
