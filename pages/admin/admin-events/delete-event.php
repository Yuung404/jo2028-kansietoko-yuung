<?php
/** Gestion du calendrier : suppression (POST + CSRF), bloquée si des résultats sont enregistrés. */
$racine = '../../../';
require_once __DIR__ . '/../../../includes/bootstrap.php';
require_admin($racine);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify()) {
    flash('error', 'Requête de suppression invalide.');
    redirect('manage-events.php');
}

$idEpreuve = input_id(INPUT_POST, 'id_epreuve');
if ($idEpreuve === null) {
    flash('error', 'Identifiant d\'épreuve invalide.');
    redirect('manage-events.php');
}

$stmt = $connexion->prepare('SELECT COUNT(*) FROM PARTICIPER WHERE id_epreuve = :id');
$stmt->execute([':id' => $idEpreuve]);
$nbResultats = (int) $stmt->fetchColumn();
if ($nbResultats > 0) {
    flash('error', "Suppression impossible : $nbResultats résultat(s) sont enregistrés pour cette épreuve. Supprimez d'abord ces résultats.");
    redirect('manage-events.php');
}

$stmt = $connexion->prepare('DELETE FROM EPREUVE WHERE id_epreuve = :id');
$stmt->execute([':id' => $idEpreuve]);

flash($stmt->rowCount() === 1 ? 'success' : 'error',
      $stmt->rowCount() === 1 ? 'L\'épreuve a été supprimée du calendrier.' : 'Épreuve introuvable (déjà supprimée ?).');
redirect('manage-events.php');
