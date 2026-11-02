<?php
/** Gestion des genres : modification. */
$racine = '../../../';
$titre = 'Modifier un genre';
require_once __DIR__ . '/../../../includes/bootstrap.php';
require_admin($racine);

$idGenre = input_id(INPUT_GET, 'id_genre');
if ($idGenre === null) {
    flash('error', 'Identifiant de genre invalide.');
    redirect('manage-genders.php');
}

// Chargement du genre à modifier
$stmt = $connexion->prepare('SELECT nom_genre FROM GENRE WHERE id_genre = :id');
$stmt->execute([':id' => $idGenre]);
$genre = $stmt->fetch();
if (!$genre) {
    flash('error', 'Genre introuvable.');
    redirect('manage-genders.php');
}
$nomGenre = $genre['nom_genre'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash('error', 'Session expirée ou requête invalide. Veuillez réessayer.');
        redirect("modify-gender.php?id_genre=$idGenre");
    }

    $nomGenre = post_text('nom_genre');
    $erreur = valider_texte($nomGenre, 'Le nom du genre');

    // Doublon : un AUTRE genre (id différent) porte déjà ce nom
    if ($erreur === null) {
        $stmt = $connexion->prepare('SELECT COUNT(*) FROM GENRE WHERE nom_genre = :nom AND id_genre <> :id');
        $stmt->execute([':nom' => $nomGenre, ':id' => $idGenre]);
        if ($stmt->fetchColumn() > 0) {
            $erreur = 'Un autre genre porte déjà ce nom.';
        }
    }

    if ($erreur === null) {
        try {
            $stmt = $connexion->prepare('UPDATE GENRE SET nom_genre = :nom WHERE id_genre = :id');
            $stmt->execute([':nom' => $nomGenre, ':id' => $idGenre]);
            flash('success', "Le genre « $nomGenre » a été modifié.");
            redirect('manage-genders.php');
        } catch (PDOException $e) {
            if (!est_doublon($e)) {
                throw $e;
            }
            $erreur = 'Un autre genre porte déjà ce nom.';
        }
    }

    flash('error', $erreur);
}

require __DIR__ . '/../../../includes/head.php';
require __DIR__ . '/../../../includes/nav-admin.php';
?>
    <main>
        <h1>Modifier un genre</h1>
        <?php show_flash(); ?>
        <form action="modify-gender.php?id_genre=<?= $idGenre ?>" method="post">
            <?= csrf_field() ?>
            <label for="nom_genre">Nom du genre :</label>
            <input type="text" name="nom_genre" id="nom_genre" maxlength="255" value="<?= e($nomGenre) ?>" required>
            <input type="submit" value="Enregistrer les modifications">
        </form>
        <p class="paragraph-link"><a class="link-home" href="manage-genders.php">Retour à la gestion des genres</a></p>
    </main>
<?php require __DIR__ . '/../../../includes/footer.php'; ?>
