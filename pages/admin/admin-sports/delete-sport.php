<?php
/**
 * Gestion des sports : suppression.
 * Uniquement en POST avec jeton CSRF : un lien piégé (GET) ne peut pas déclencher de suppression.
 */
$racine = '../../../';
require_once __DIR__ . '/../../../includes/bootstrap.php';
require_admin($racine);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify()) {
    flash('error', 'Requête de suppression invalide.');
    redirect('manage-sports.php');
}

$idSport = input_id(INPUT_POST, 'id_sport');
if ($idSport === null) {
    flash('error', 'Identifiant de sport invalide.');
    redirect('manage-sports.php');
}

// Intégrité : un sport rattaché à des épreuves ne peut pas être supprimé
$stmt = $connexion->prepare('SELECT COUNT(*) FROM EPREUVE WHERE id_sport = :id');
$stmt->execute([':id' => $idSport]);
$nbEpreuves = (int) $stmt->fetchColumn();
if ($nbEpreuves > 0) {
    flash('error', "Suppression impossible : ce sport est utilisé par $nbEpreuves épreuve(s). Supprimez ou modifiez d'abord ces épreuves.");
    redirect('manage-sports.php');
}

$stmt = $connexion->prepare('DELETE FROM SPORT WHERE id_sport = :id');
$stmt->execute([':id' => $idSport]);

if ($stmt->rowCount() === 1) {
    flash('success', 'Le sport a été supprimé.');
} else {
    flash('error', 'Sport introuvable (déjà supprimé ?).');
}
redirect('manage-sports.php');
