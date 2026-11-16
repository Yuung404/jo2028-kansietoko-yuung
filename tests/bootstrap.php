<?php
/**
 * Amorçage des tests : chargement automatique des classes de test.
 */
$autoload = __DIR__ . '/../vendor/autoload.php';
if (is_file($autoload)) {
    require $autoload;
} else {
    // Repli si Composer n'est pas utilisé (PHPUnit installé globalement)
    spl_autoload_register(function (string $classe): void {
        if (strncmp($classe, 'Tests\\', 6) === 0) {
            require __DIR__ . '/' . str_replace('\\', '/', substr($classe, 6)) . '.php';
        }
    });
}
