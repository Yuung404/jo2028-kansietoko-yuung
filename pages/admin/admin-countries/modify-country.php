<?php
/** Gestion des pays : modification. */
$racine = '../../../';
$titre = 'Modifier un pays';
require_once __DIR__ . '/../../../includes/bootstrap.php';
require_admin($racine);

$idPays = input_id(INPUT_GET, 'id_pays');
if ($idPays === null) {
    flash('error', 'Identifiant de pays invalide.');
    redirect('manage-countries.php');
}

// Chargement du pays à modifier
$stmt = $connexion->prepare('SELECT nom_pays FROM PAYS WHERE id_pays = :id');
$stmt->execute([':id' => $idPays]);
$pays = $stmt->fetch();
if (!$pays) {
    flash('error', 'Pays introuvable.');
    redirect('manage-countries.php');
}
$nomPays = $pays['nom_pays'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash('error', 'Session expirée ou requête invalide. Veuillez réessayer.');
        redirect("modify-country.php?id_pays=$idPays");
    }

    $nomPays = post_text('nom_pays');
    $erreur = valider_texte($nomPays, 'Le nom du pays');

    // Doublon : un AUTRE pays (id différent) porte déjà ce nom
    if ($erreur === null) {
        $stmt = $connexion->prepare('SELECT COUNT(*) FROM PAYS WHERE nom_pays = :nom AND id_pays <> :id');
        $stmt->execute([':nom' => $nomPays, ':id' => $idPays]);
        if ($stmt->fetchColumn() > 0) {
            $erreur = 'Un autre pays porte déjà ce nom.';
        }
    }

    if ($erreur === null) {
        try {
            $stmt = $connexion->prepare('UPDATE PAYS SET nom_pays = :nom WHERE id_pays = :id');
            $stmt->execute([':nom' => $nomPays, ':id' => $idPays]);
            flash('success', "Le pays « $nomPays » a été modifié.");
            redirect('manage-countries.php');
        } catch (PDOException $e) {
            if (!est_doublon($e)) {
                throw $e;
            }
            $erreur = 'Un autre pays porte déjà ce nom.';
        }
    }

    flash('error', $erreur);
}

require __DIR__ . '/../../../includes/head.php';
require __DIR__ . '/../../../includes/nav-admin.php';
?>
    <main>
        <h1>Modifier un pays</h1>
        <?php show_flash(); ?>
        <form action="modify-country.php?id_pays=<?= $idPays ?>" method="post">
            <?= csrf_field() ?>
            <label for="nom_pays">Nom du pays :</label>
            <input type="text" name="nom_pays" id="nom_pays" maxlength="255" value="<?= e($nomPays) ?>" required>
            <input type="submit" value="Enregistrer les modifications">
        </form>
        <p class="paragraph-link"><a class="link-home" href="manage-countries.php">Retour à la gestion des pays</a></p>
    </main>
<?php require __DIR__ . '/../../../includes/footer.php'; ?>
