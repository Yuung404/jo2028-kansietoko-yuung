<?php
/** Gestion des lieux : ajout. */
$racine = '../../../';
$titre = 'Ajouter un lieu';
require_once __DIR__ . '/../../../includes/bootstrap.php';
require_admin($racine);

$lieu = ['nom_lieu' => '', 'adresse_lieu' => '', 'cp_lieu' => '', 'ville_lieu' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash('error', 'Session expirée ou requête invalide. Veuillez réessayer.');
        redirect('add-place.php');
    }

    [$lieu, $erreur] = lire_lieu();

    if ($erreur === null) {
        $stmt = $connexion->prepare('SELECT COUNT(*) FROM LIEU WHERE nom_lieu = :nom');
        $stmt->execute([':nom' => $lieu['nom_lieu']]);
        if ($stmt->fetchColumn() > 0) {
            $erreur = 'Un lieu porte déjà ce nom.';
        }
    }

    if ($erreur === null) {
        try {
            $stmt = $connexion->prepare(
                'INSERT INTO LIEU (nom_lieu, adresse_lieu, cp_lieu, ville_lieu) VALUES (:nom, :adresse, :cp, :ville)'
            );
            $stmt->execute([
                ':nom' => $lieu['nom_lieu'], ':adresse' => $lieu['adresse_lieu'],
                ':cp'  => $lieu['cp_lieu'],  ':ville'   => $lieu['ville_lieu'],
            ]);
            flash('success', "Le lieu « {$lieu['nom_lieu']} » a été ajouté.");
            redirect('manage-places.php');
        } catch (PDOException $e) {
            if (!est_doublon($e)) {
                throw $e;
            }
            $erreur = 'Un lieu porte déjà ce nom.';
        }
    }

    flash('error', $erreur);
}

require __DIR__ . '/../../../includes/head.php';
require __DIR__ . '/../../../includes/nav-admin.php';
?>
    <main>
        <h1>Ajouter un lieu</h1>
        <?php show_flash(); ?>
        <form action="add-place.php" method="post">
<?php require __DIR__ . '/form-place.php'; ?>
            <input type="submit" value="Ajouter le lieu">
        </form>
        <p class="paragraph-link"><a class="link-home" href="manage-places.php">Retour à la gestion des lieux</a></p>
    </main>
<?php require __DIR__ . '/../../../includes/footer.php'; ?>
