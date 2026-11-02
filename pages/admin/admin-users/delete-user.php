<?php
/**
 * Gestion des administrateurs : suppression (POST + CSRF).
 * Règles : on ne peut pas supprimer son propre compte, ni le dernier administrateur.
 */
$racine = '../../../';
require_once __DIR__ . '/../../../includes/bootstrap.php';
require_admin($racine);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify()) {
    flash('error', 'Requête de suppression invalide.');
    redirect('manage-users.php');
}

$idAdmin = input_id(INPUT_POST, 'id_admin');
if ($idAdmin === null) {
    flash('error', 'Identifiant d\'administrateur invalide.');
    redirect('manage-users.php');
}

if ($idAdmin === $_SESSION['id_admin']) {
    flash('error', 'Vous ne pouvez pas supprimer votre propre compte.');
    redirect('manage-users.php');
}

if ((int) $connexion->query('SELECT COUNT(*) FROM ADMINISTRATEUR')->fetchColumn() <= 1) {
    flash('error', 'Impossible de supprimer le dernier administrateur.');
    redirect('manage-users.php');
}

$stmt = $connexion->prepare('DELETE FROM ADMINISTRATEUR WHERE id_admin = :id');
$stmt->execute([':id' => $idAdmin]);

flash($stmt->rowCount() === 1 ? 'success' : 'error',
      $stmt->rowCount() === 1 ? 'L\'administrateur a été supprimé.' : 'Administrateur introuvable (déjà supprimé ?).');
redirect('manage-users.php');
