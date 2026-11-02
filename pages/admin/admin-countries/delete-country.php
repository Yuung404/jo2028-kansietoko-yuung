<?php
/**
 * Gestion des pays : suppression.
 * Uniquement en POST avec jeton CSRF : un lien piégé (GET) ne peut pas déclencher de suppression.
 */
$racine = '../../../';
require_once __DIR__ . '/../../../includes/bootstrap.php';
require_admin($racine);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify()) {
    flash('error', 'Requête de suppression invalide.');
    redirect('manage-countries.php');
}

$idPays = input_id(INPUT_POST, 'id_pays');
if ($idPays === null) {
    flash('error', 'Identifiant de pays invalide.');
    redirect('manage-countries.php');
}

// Intégrité : un pays rattaché à des athlètes ne peut pas être supprimé
$stmt = $connexion->prepare('SELECT COUNT(*) FROM ATHLETE WHERE id_pays = :id');
$stmt->execute([':id' => $idPays]);
$nbAthletes = (int) $stmt->fetchColumn();
if ($nbAthletes > 0) {
    flash('error', "Suppression impossible : ce pays est rattaché à $nbAthletes athlète(s). Modifiez ou supprimez d'abord ces athlètes.");
    redirect('manage-countries.php');
}

$stmt = $connexion->prepare('DELETE FROM PAYS WHERE id_pays = :id');
$stmt->execute([':id' => $idPays]);

if ($stmt->rowCount() === 1) {
    flash('success', 'Le pays a été supprimé.');
} else {
    flash('error', 'Pays introuvable (déjà supprimé ?).');
}
redirect('manage-countries.php');
