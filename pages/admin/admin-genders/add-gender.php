<?php
/** Gestion des genres : ajout. */
$racine = '../../../';
$titre = 'Ajouter un genre';
require_once __DIR__ . '/../../../includes/bootstrap.php';
require_admin($racine);

$nomGenre = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash('error', 'Session expirée ou requête invalide. Veuillez réessayer.');
        redirect('add-gender.php');
    }

    $nomGenre = post_text('nom_genre');
    $erreur = valider_texte($nomGenre, 'Le nom du genre');

    // Contrôle des doublons avant l'insertion
    if ($erreur === null) {
        $stmt = $connexion->prepare('SELECT COUNT(*) FROM GENRE WHERE nom_genre = :nom');
        $stmt->execute([':nom' => $nomGenre]);
        if ($stmt->fetchColumn() > 0) {
            $erreur = 'Ce genre existe déjà.';
        }
    }

    if ($erreur === null) {
        try {
            $stmt = $connexion->prepare('INSERT INTO GENRE (nom_genre) VALUES (:nom)');
            $stmt->execute([':nom' => $nomGenre]);
            flash('success', "Le genre « $nomGenre » a été ajouté.");
            redirect('manage-genders.php');
        } catch (PDOException $e) {
            // Filet de sécurité : la contrainte UNIQUE de la base bloque un doublon créé entre-temps
            if (!est_doublon($e)) {
                throw $e;
            }
            $erreur = 'Ce genre existe déjà.';
        }
    }

    flash('error', $erreur);
}

require __DIR__ . '/../../../includes/head.php';
require __DIR__ . '/../../../includes/nav-admin.php';
?>
    <main>
        <h1>Ajouter un genre</h1>
        <?php show_flash(); ?>
        <form action="add-gender.php" method="post">
            <?= csrf_field() ?>
            <label for="nom_genre">Nom du genre :</label>
            <input type="text" name="nom_genre" id="nom_genre" maxlength="255" value="<?= e($nomGenre) ?>" required>
            <input type="submit" value="Ajouter le genre">
        </form>
        <p class="paragraph-link"><a class="link-home" href="manage-genders.php">Retour à la gestion des genres</a></p>
    </main>
<?php require __DIR__ . '/../../../includes/footer.php'; ?>
