<?php
/** Gestion des lieux : suppression (POST + CSRF), bloquée si des épreuves s'y déroulent. */
$racine = '../../../';
require_once __DIR__ . '/../../../includes/bootstrap.php';
require_admin($racine);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify()) {
    flash('error', 'Requête de suppression invalide.');
    redirect('manage-places.php');
}

$idLieu = input_id(INPUT_POST, 'id_lieu');
if ($idLieu === null) {
    flash('error', 'Identifiant de lieu invalide.');
    redirect('manage-places.php');
}

$stmt = $connexion->prepare('SELECT COUNT(*) FROM EPREUVE WHERE id_lieu = :id');
$stmt->execute([':id' => $idLieu]);
$nbEpreuves = (int) $stmt->fetchColumn();
if ($nbEpreuves > 0) {
    flash('error', "Suppression impossible : $nbEpreuves épreuve(s) se déroulent dans ce lieu. Déplacez ou supprimez d'abord ces épreuves.");
    redirect('manage-places.php');
}

$stmt = $connexion->prepare('DELETE FROM LIEU WHERE id_lieu = :id');
$stmt->execute([':id' => $idLieu]);

flash($stmt->rowCount() === 1 ? 'success' : 'error',
      $stmt->rowCount() === 1 ? 'Le lieu a été supprimé.' : 'Lieu introuvable (déjà supprimé ?).');
redirect('manage-places.php');
