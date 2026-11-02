<?php
/**
 * Point d'entrée commun, inclus en première ligne de chaque page.
 *
 * 1. Charge la configuration et règle l'affichage des erreurs (local / production).
 * 2. Installe un gestionnaire d'erreurs : en production, aucun détail technique n'est affiché.
 * 3. Démarre une session aux cookies durcis.
 * 4. Charge les fonctions de sécurité et ouvre la connexion PDO ($connexion).
 */

$config = require __DIR__ . '/../database/config.php';

error_reporting(E_ALL);
ini_set('display_errors', $config['debug'] ? '1' : '0');
ini_set('log_errors', '1');

set_exception_handler(function (Throwable $e) use ($config): void {
    error_log('Erreur non gérée : ' . $e->getMessage() . ' dans ' . $e->getFile() . ':' . $e->getLine());
    if (!headers_sent()) {
        http_response_code(500);
    }
    echo '<p class="alert alert-error">Une erreur technique est survenue. Merci de réessayer plus tard.</p>';
    if ($config['debug']) {
        echo '<pre>' . htmlspecialchars((string) $e, ENT_QUOTES, 'UTF-8') . '</pre>';
    }
});

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,                        // cookie supprimé à la fermeture du navigateur
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']), // cookie envoyé uniquement en HTTPS quand il est disponible
        'httponly' => true,                     // inaccessible au JavaScript (limite le vol de session par XSS)
        'samesite' => 'Strict',                 // non envoyé depuis un autre site (renforce la protection CSRF)
    ]);
    session_start();
}

require_once __DIR__ . '/security.php';
require_once __DIR__ . '/../database/database.php';
