<?php
/**
 * Gestion des athlètes : ajout.
 * Règle de gestion (doublon) : un athlète est identifié par son nom, son prénom et son pays.
 */
$racine = '../../../';
$titre = 'Ajouter un athlète';
require_once __DIR__ . '/../../../includes/bootstrap.php';
require_admin($racine);

$at = ['nom_athlete' => '', 'prenom_athlete' => '', 'id_pays' => null, 'id_genre' => null];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash('error', 'Session expirée ou requête invalide. Veuillez réessayer.');
        redirect('add-athlete.php');
    }

    [$at, $erreur] = lire_athlete($connexion);

    if ($erreur === null) {
        $stmt = $connexion->prepare(
            'SELECT COUNT(*) FROM ATHLETE WHERE nom_athlete = :nom AND prenom_athlete = :prenom AND id_pays = :pays'
        );
        $stmt->execute([':nom' => $at['nom_athlete'], ':prenom' => $at['prenom_athlete'], ':pays' => $at['id_pays']]);
        if ($stmt->fetchColumn() > 0) {
            $erreur = 'Cet athlète est déjà enregistré pour ce pays.';
        }
    }

    if ($erreur === null) {
        try {
            $stmt = $connexion->prepare(
                'INSERT INTO ATHLETE (nom_athlete, prenom_athlete, id_pays, id_genre)
                 VALUES (:nom, :prenom, :pays, :genre)'
            );
            $stmt->execute([
                ':nom'  => $at['nom_athlete'], ':prenom' => $at['prenom_athlete'],
                ':pays' => $at['id_pays'],     ':genre'  => $at['id_genre'],
            ]);
            flash('success', "L'athlète « {$at['prenom_athlete']} {$at['nom_athlete']} » a été ajouté.");
            redirect('manage-athletes.php');
        } catch (PDOException $e) {
            if (!est_doublon($e)) {
                throw $e;
            }
            $erreur = 'Cet athlète est déjà enregistré pour ce pays.';
        }
    }

    flash('error', $erreur);
}

require __DIR__ . '/../../../includes/head.php';
require __DIR__ . '/../../../includes/nav-admin.php';
?>
    <main>
        <h1>Ajouter un athlète</h1>
        <?php show_flash(); ?>
        <form action="add-athlete.php" method="post">
<?php require __DIR__ . '/form-athlete.php'; ?>
            <input type="submit" value="Ajouter l'athlète">
        </form>
        <p class="paragraph-link"><a class="link-home" href="manage-athletes.php">Retour à la gestion des athlètes</a></p>
    </main>
<?php require __DIR__ . '/../../../includes/footer.php'; ?>
