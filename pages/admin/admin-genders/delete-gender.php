<?php
/**
 * Gestion des genres : suppression.
 * Uniquement en POST avec jeton CSRF : un lien piégé (GET) ne peut pas déclencher de suppression.
 */
$racine = '../../../';
require_once __DIR__ . '/../../../includes/bootstrap.php';
require_admin($racine);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify()) {
    flash('error', 'Requête de suppression invalide.');
    redirect('manage-genders.php');
}

$idGenre = input_id(INPUT_POST, 'id_genre');
if ($idGenre === null) {
    flash('error', 'Identifiant de genre invalide.');
    redirect('manage-genders.php');
}

// Intégrité : un genre rattaché à des athlètes ne peut pas être supprimé
$stmt = $connexion->prepare('SELECT COUNT(*) FROM ATHLETE WHERE id_genre = :id');
$stmt->execute([':id' => $idGenre]);
$nbAthletes = (int) $stmt->fetchColumn();
if ($nbAthletes > 0) {
    flash('error', "Suppression impossible : ce genre est rattaché à $nbAthletes athlète(s). Modifiez ou supprimez d'abord ces athlètes.");
    redirect('manage-genders.php');
}

$stmt = $connexion->prepare('DELETE FROM GENRE WHERE id_genre = :id');
$stmt->execute([':id' => $idGenre]);

if ($stmt->rowCount() === 1) {
    flash('success', 'Le genre a été supprimé.');
} else {
    flash('error', 'Genre introuvable (déjà supprimé ?).');
}
redirect('manage-genders.php');
