<?php
/** Gestion des sports : modification. */
$racine = '../../../';
$titre = 'Modifier un sport';
require_once __DIR__ . '/../../../includes/bootstrap.php';
require_admin($racine);

$idSport = input_id(INPUT_GET, 'id_sport');
if ($idSport === null) {
    flash('error', 'Identifiant de sport invalide.');
    redirect('manage-sports.php');
}

// Chargement du sport à modifier
$stmt = $connexion->prepare('SELECT nom_sport FROM SPORT WHERE id_sport = :id');
$stmt->execute([':id' => $idSport]);
$sport = $stmt->fetch();
if (!$sport) {
    flash('error', 'Sport introuvable.');
    redirect('manage-sports.php');
}
$nomSport = $sport['nom_sport'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash('error', 'Session expirée ou requête invalide. Veuillez réessayer.');
        redirect("modify-sport.php?id_sport=$idSport");
    }

    $nomSport = post_text('nom_sport');
    $erreur = valider_texte($nomSport, 'Le nom du sport');

    // Doublon : un AUTRE sport (id différent) porte déjà ce nom
    if ($erreur === null) {
        $stmt = $connexion->prepare('SELECT COUNT(*) FROM SPORT WHERE nom_sport = :nom AND id_sport <> :id');
        $stmt->execute([':nom' => $nomSport, ':id' => $idSport]);
        if ($stmt->fetchColumn() > 0) {
            $erreur = 'Un autre sport porte déjà ce nom.';
        }
    }

    if ($erreur === null) {
        try {
            $stmt = $connexion->prepare('UPDATE SPORT SET nom_sport = :nom WHERE id_sport = :id');
            $stmt->execute([':nom' => $nomSport, ':id' => $idSport]);
            flash('success', "Le sport « $nomSport » a été modifié.");
            redirect('manage-sports.php');
        } catch (PDOException $e) {
            if (!est_doublon($e)) {
                throw $e;
            }
            $erreur = 'Un autre sport porte déjà ce nom.';
        }
    }

    flash('error', $erreur);
}

require __DIR__ . '/../../../includes/head.php';
require __DIR__ . '/../../../includes/nav-admin.php';
?>
    <main>
        <h1>Modifier un sport</h1>
        <?php show_flash(); ?>
        <form action="modify-sport.php?id_sport=<?= $idSport ?>" method="post">
            <?= csrf_field() ?>
            <label for="nom_sport">Nom du sport :</label>
            <input type="text" name="nom_sport" id="nom_sport" maxlength="255" value="<?= e($nomSport) ?>" required>
            <input type="submit" value="Enregistrer les modifications">
        </form>
        <p class="paragraph-link"><a class="link-home" href="manage-sports.php">Retour à la gestion des sports</a></p>
    </main>
<?php require __DIR__ . '/../../../includes/footer.php'; ?>
