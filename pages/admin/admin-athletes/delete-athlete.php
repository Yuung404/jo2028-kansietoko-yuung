<?php
/** Gestion des athlètes : suppression (POST + CSRF), bloquée si l'athlète a des résultats. */
$racine = '../../../';
require_once __DIR__ . '/../../../includes/bootstrap.php';
require_admin($racine);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify()) {
    flash('error', 'Requête de suppression invalide.');
    redirect('manage-athletes.php');
}

$idAthlete = input_id(INPUT_POST, 'id_athlete');
if ($idAthlete === null) {
    flash('error', 'Identifiant d\'athlète invalide.');
    redirect('manage-athletes.php');
}

$stmt = $connexion->prepare('SELECT COUNT(*) FROM PARTICIPER WHERE id_athlete = :id');
$stmt->execute([':id' => $idAthlete]);
$nbResultats = (int) $stmt->fetchColumn();
if ($nbResultats > 0) {
    flash('error', "Suppression impossible : cet athlète a $nbResultats résultat(s) enregistré(s). Supprimez d'abord ces résultats.");
    redirect('manage-athletes.php');
}

$stmt = $connexion->prepare('DELETE FROM ATHLETE WHERE id_athlete = :id');
$stmt->execute([':id' => $idAthlete]);

flash($stmt->rowCount() === 1 ? 'success' : 'error',
      $stmt->rowCount() === 1 ? 'L\'athlète a été supprimé.' : 'Athlète introuvable (déjà supprimé ?).');
redirect('manage-athletes.php');
