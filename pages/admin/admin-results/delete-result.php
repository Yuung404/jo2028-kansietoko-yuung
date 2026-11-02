<?php
/** Gestion des résultats : suppression (POST + CSRF). */
$racine = '../../../';
require_once __DIR__ . '/../../../includes/bootstrap.php';
require_admin($racine);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify()) {
    flash('error', 'Requête de suppression invalide.');
    redirect('manage-results.php');
}

$idAthlete = input_id(INPUT_POST, 'id_athlete');
$idEpreuve = input_id(INPUT_POST, 'id_epreuve');
if ($idAthlete === null || $idEpreuve === null) {
    flash('error', 'Identifiants de résultat invalides.');
    redirect('manage-results.php');
}

$stmt = $connexion->prepare('DELETE FROM PARTICIPER WHERE id_athlete = :at AND id_epreuve = :ep');
$stmt->execute([':at' => $idAthlete, ':ep' => $idEpreuve]);

flash($stmt->rowCount() === 1 ? 'success' : 'error',
      $stmt->rowCount() === 1 ? 'Le résultat a été supprimé.' : 'Résultat introuvable (déjà supprimé ?).');
redirect('manage-results.php');
