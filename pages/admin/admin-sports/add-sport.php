<?php
/** Gestion des sports : ajout. */
$racine = '../../../';
$titre = 'Ajouter un sport';
require_once __DIR__ . '/../../../includes/bootstrap.php';
require_admin($racine);

$nomSport = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash('error', 'Session expirée ou requête invalide. Veuillez réessayer.');
        redirect('add-sport.php');
    }

    $nomSport = post_text('nom_sport');
    $erreur = valider_texte($nomSport, 'Le nom du sport');

    // Contrôle des doublons avant l'insertion
    if ($erreur === null) {
        $stmt = $connexion->prepare('SELECT COUNT(*) FROM SPORT WHERE nom_sport = :nom');
        $stmt->execute([':nom' => $nomSport]);
        if ($stmt->fetchColumn() > 0) {
            $erreur = 'Ce sport existe déjà.';
        }
    }

    if ($erreur === null) {
        try {
            $stmt = $connexion->prepare('INSERT INTO SPORT (nom_sport) VALUES (:nom)');
            $stmt->execute([':nom' => $nomSport]);
            flash('success', "Le sport « $nomSport » a été ajouté.");
            redirect('manage-sports.php');
        } catch (PDOException $e) {
            // Filet de sécurité : la contrainte UNIQUE de la base bloque un doublon créé entre-temps
            if (!est_doublon($e)) {
                throw $e;
            }
            $erreur = 'Ce sport existe déjà.';
        }
    }

    flash('error', $erreur);
}

require __DIR__ . '/../../../includes/head.php';
require __DIR__ . '/../../../includes/nav-admin.php';
?>
    <main>
        <h1>Ajouter un sport</h1>
        <?php show_flash(); ?>
        <form action="add-sport.php" method="post">
            <?= csrf_field() ?>
            <label for="nom_sport">Nom du sport :</label>
            <input type="text" name="nom_sport" id="nom_sport" maxlength="255" value="<?= e($nomSport) ?>" required>
            <input type="submit" value="Ajouter le sport">
        </form>
        <p class="paragraph-link"><a class="link-home" href="manage-sports.php">Retour à la gestion des sports</a></p>
    </main>
<?php require __DIR__ . '/../../../includes/footer.php'; ?>
