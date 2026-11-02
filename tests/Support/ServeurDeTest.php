<?php
namespace Tests\Support;

/**
 * Lance le serveur web intégré de PHP sur le projet, avec JO2028_TEST=1 :
 * l'application utilise alors la base de test. Le serveur est arrêté à la fin des tests.
 */
final class ServeurDeTest
{
    private static $processus = null;
    private static string $url = '';

    public static function url(): string
    {
        if (self::$processus === null) {
            self::demarrer();
        }
        return self::$url;
    }

    private static function demarrer(): void
    {
        // Port libre choisi par le système
        $socket = stream_socket_server('tcp://127.0.0.1:0');
        $port = (int) substr(strrchr(stream_socket_get_name($socket, false), ':'), 1);
        fclose($socket);

        $commande = [PHP_BINARY];
        if (php_ini_loaded_file()) {                      // même php.ini que PHPUnit (extensions pdo_mysql...)
            array_push($commande, '-c', php_ini_loaded_file());
        }
        array_push($commande, '-S', "127.0.0.1:$port", '-t', realpath(__DIR__ . '/../..'));

        $env = getenv();
        $env['JO2028_TEST'] = '1';
        $journal = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'jo2028-serveur-test.log';
        self::$processus = proc_open($commande, [1 => ['file', $journal, 'a'], 2 => ['file', $journal, 'a']], $tubes, null, $env);
        if (!is_resource(self::$processus)) {
            throw new \RuntimeException('Impossible de lancer le serveur de test.');
        }
        self::$url = "http://127.0.0.1:$port";

        for ($i = 0; $i < 50; $i++) {                     // attente du démarrage (5 s max)
            $connexion = @fsockopen('127.0.0.1', $port, $errno, $errstr, 0.1);
            if ($connexion) {
                fclose($connexion);
                register_shutdown_function(fn () => proc_terminate(self::$processus));
                return;
            }
            usleep(100000);
        }
        throw new \RuntimeException("Le serveur de test n'a pas démarré (voir $journal).");
    }
}
