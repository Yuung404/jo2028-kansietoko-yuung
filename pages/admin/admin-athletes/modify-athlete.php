<?php
/** Gestion des athlètes : modification. */
$racine = '../../../';
$titre = 'Modifier un athlète';
require_once __DIR__ . '/../../../includes/bootstrap.php';
require_admin($racine);

$idAthlete = input_id(INPUT_GET, 'id_athlete');
if ($idAthlete === null) {
    flash('error', 'Identifiant d\'athlète invalide.');
    redirect('manage-athletes.php');
}

$stmt = $connexion->prepare('SELECT nom_athlete, prenom_athlete, id_pays, id_genre FROM ATHLETE WHERE id_athlete = :id');
$stmt->execute([':id' => $idAthlete]);
$at = $stmt->fetch();
if (!$at) {
    flash('error', 'Athlète introuvable.');
    redirect('manage-athletes.php');
}
$at['id_pays']  = (int) $at['id_pays'];
$at['id_genre'] = (int) $at['id_genre'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash('error', 'Session expirée ou requête invalide. Veuillez réessayer.');
        redirect("modify-athlete.php?id_athlete=$idAthlete");
    }

    [$at, $erreur] = lire_athlete($connexion);

    if ($erreur === null) {
        $stmt = $connexion->prepare(
            'SELECT COUNT(*) FROM ATHLETE
             WHERE nom_athlete = :nom AND prenom_athlete = :prenom AND id_pays = :pays AND id_athlete <> :id'
        );
        $stmt->execute([':nom' => $at['nom_athlete'], ':prenom' => $at['prenom_athlete'],
                        ':pays' => $at['id_pays'], ':id' => $idAthlete]);
        if ($stmt->fetchColumn() > 0) {
            $erreur = 'Un autre athlète porte déjà ce nom et ce prénom pour ce pays.';
        }
    }

    if ($erreur === null) {
        try {
            $stmt = $connexion->prepare(
                'UPDATE ATHLETE SET nom_athlete = :nom, prenom_athlete = :prenom, id_pays = :pays, id_genre = :genre
                 WHERE id_athlete = :id'
            );
            $stmt->execute([
                ':nom'  => $at['nom_athlete'], ':prenom' => $at['prenom_athlete'],
                ':pays' => $at['id_pays'],     ':genre'  => $at['id_genre'], ':id' => $idAthlete,
            ]);
            flash('success', "L'athlète « {$at['prenom_athlete']} {$at['nom_athlete']} » a été modifié.");
            redirect('manage-athletes.php');
        } catch (PDOException $e) {
            if (!est_doublon($e)) {
                throw $e;
            }
            $erreur = 'Un autre athlète porte déjà ce nom et ce prénom pour ce pays.';
        }
    }

    flash('error', $erreur);
}

require __DIR__ . '/../../../includes/head.php';
require __DIR__ . '/../../../includes/nav-admin.php';
?>
    <main>
        <h1>Modifier un athlète</h1>
        <?php show_flash(); ?>
        <form action="modify-athlete.php?id_athlete=<?= $idAthlete ?>" method="post">
<?php require __DIR__ . '/form-athlete.php'; ?>
            <input type="submit" value="Enregistrer les modifications">
        </form>
        <p class="paragraph-link"><a class="link-home" href="manage-athletes.php">Retour à la gestion des athlètes</a></p>
    </main>
<?php require __DIR__ . '/../../../includes/footer.php'; ?>
