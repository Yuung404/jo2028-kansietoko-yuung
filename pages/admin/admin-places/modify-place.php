<?php
/** Gestion des lieux : modification. */
$racine = '../../../';
$titre = 'Modifier un lieu';
require_once __DIR__ . '/../../../includes/bootstrap.php';
require_admin($racine);

$idLieu = input_id(INPUT_GET, 'id_lieu');
if ($idLieu === null) {
    flash('error', 'Identifiant de lieu invalide.');
    redirect('manage-places.php');
}

$stmt = $connexion->prepare('SELECT nom_lieu, adresse_lieu, cp_lieu, ville_lieu FROM LIEU WHERE id_lieu = :id');
$stmt->execute([':id' => $idLieu]);
$lieu = $stmt->fetch();
if (!$lieu) {
    flash('error', 'Lieu introuvable.');
    redirect('manage-places.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash('error', 'Session expirée ou requête invalide. Veuillez réessayer.');
        redirect("modify-place.php?id_lieu=$idLieu");
    }

    [$lieu, $erreur] = lire_lieu();

    if ($erreur === null) {
        $stmt = $connexion->prepare('SELECT COUNT(*) FROM LIEU WHERE nom_lieu = :nom AND id_lieu <> :id');
        $stmt->execute([':nom' => $lieu['nom_lieu'], ':id' => $idLieu]);
        if ($stmt->fetchColumn() > 0) {
            $erreur = 'Un autre lieu porte déjà ce nom.';
        }
    }

    if ($erreur === null) {
        try {
            $stmt = $connexion->prepare(
                'UPDATE LIEU SET nom_lieu = :nom, adresse_lieu = :adresse, cp_lieu = :cp, ville_lieu = :ville
                 WHERE id_lieu = :id'
            );
            $stmt->execute([
                ':nom' => $lieu['nom_lieu'], ':adresse' => $lieu['adresse_lieu'],
                ':cp'  => $lieu['cp_lieu'],  ':ville'   => $lieu['ville_lieu'], ':id' => $idLieu,
            ]);
            flash('success', "Le lieu « {$lieu['nom_lieu']} » a été modifié.");
            redirect('manage-places.php');
        } catch (PDOException $e) {
            if (!est_doublon($e)) {
                throw $e;
            }
            $erreur = 'Un autre lieu porte déjà ce nom.';
        }
    }

    flash('error', $erreur);
}

require __DIR__ . '/../../../includes/head.php';
require __DIR__ . '/../../../includes/nav-admin.php';
?>
    <main>
        <h1>Modifier un lieu</h1>
        <?php show_flash(); ?>
        <form action="modify-place.php?id_lieu=<?= $idLieu ?>" method="post">
<?php require __DIR__ . '/form-place.php'; ?>
            <input type="submit" value="Enregistrer les modifications">
        </form>
        <p class="paragraph-link"><a class="link-home" href="manage-places.php">Retour à la gestion des lieux</a></p>
    </main>
<?php require __DIR__ . '/../../../includes/footer.php'; ?>
