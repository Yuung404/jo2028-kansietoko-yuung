<?php
/**
 * Traitement du formulaire de connexion.
 *
 * Correctifs par rapport au code fourni :
 *  - suppression de FILTER_SANITIZE_STRING (obsolète depuis PHP 8.1 : l'avertissement affiché
 *    empêchait la redirection) et de FILTER_SANITIZE_EMAIL (le login n'est pas un e-mail) ;
 *    le mot de passe est lu tel quel, il n'est jamais affiché ni inséré dans une requête ;
 *  - session_regenerate_id(true) à la connexion (protection contre la fixation de session) ;
 *  - temps de réponse identique que le login existe ou non (pas d'énumération des comptes).
 */
$racine = '../';
require_once __DIR__ . '/../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../pages/login.php');
}

if (!csrf_verify()) {
    flash('error', 'Session expirée ou requête invalide. Veuillez réessayer.');
    redirect('../pages/login.php');
}

$login    = post_text('login');
$password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';

if ($login === '' || $password === '') {
    flash('error', 'Login et mot de passe obligatoires.');
    redirect('../pages/login.php');
}

// Requête préparée : le login n'est jamais concaténé dans le SQL
$stmt = $connexion->prepare(
    'SELECT id_admin, nom_admin, prenom_admin, login, password FROM ADMINISTRATEUR WHERE login = :login'
);
$stmt->execute([':login' => $login]);
$admin = $stmt->fetch();

// Empreinte factice : password_verify() s'exécute même si le login est inconnu,
// ce qui empêche de deviner les logins existants en mesurant le temps de réponse.
$empreinte = $admin['password'] ?? '$2y$10$WFxymbZ/gV2XfGy1We2bB.NZ9owdEU5QKUFWAicOY7qayhbe93ACm';

if ($admin && password_verify($password, $empreinte)) {
    session_regenerate_id(true);                         // nouvel identifiant de session
    $_SESSION['id_admin']          = (int) $admin['id_admin'];
    $_SESSION['nom_admin']         = $admin['nom_admin'];
    $_SESSION['prenom_admin']      = $admin['prenom_admin'];
    $_SESSION['login']             = $admin['login'];
    $_SESSION['derniere_activite'] = time();
    unset($_SESSION['csrf_token']);                      // nouveau jeton CSRF pour la session authentifiée
    redirect('../pages/admin/admin.php');
}

flash('error', 'Login ou mot de passe incorrect.');
redirect('../pages/login.php');
