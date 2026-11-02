<?php
/** Gestion des pays : ajout. */
$racine = '../../../';
$titre = 'Ajouter un pays';
require_once __DIR__ . '/../../../includes/bootstrap.php';
require_admin($racine);

$nomPays = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash('error', 'Session expirée ou requête invalide. Veuillez réessayer.');
        redirect('add-country.php');
    }

    $nomPays = post_text('nom_pays');
    $erreur = valider_texte($nomPays, 'Le nom du pays');

    // Contrôle des doublons avant l'insertion
    if ($erreur === null) {
        $stmt = $connexion->prepare('SELECT COUNT(*) FROM PAYS WHERE nom_pays = :nom');
        $stmt->execute([':nom' => $nomPays]);
        if ($stmt->fetchColumn() > 0) {
            $erreur = 'Ce pays existe déjà.';
        }
    }

    if ($erreur === null) {
        try {
            $stmt = $connexion->prepare('INSERT INTO PAYS (nom_pays) VALUES (:nom)');
            $stmt->execute([':nom' => $nomPays]);
            flash('success', "Le pays « $nomPays » a été ajouté.");
            redirect('manage-countries.php');
        } catch (PDOException $e) {
            // Filet de sécurité : la contrainte UNIQUE de la base bloque un doublon créé entre-temps
            if (!est_doublon($e)) {
                throw $e;
            }
            $erreur = 'Ce pays existe déjà.';
        }
    }

    flash('error', $erreur);
}

require __DIR__ . '/../../../includes/head.php';
require __DIR__ . '/../../../includes/nav-admin.php';
?>
    <main>
        <h1>Ajouter un pays</h1>
        <?php show_flash(); ?>
        <form action="add-country.php" method="post">
            <?= csrf_field() ?>
            <label for="nom_pays">Nom du pays :</label>
            <input type="text" name="nom_pays" id="nom_pays" maxlength="255" value="<?= e($nomPays) ?>" required>
            <input type="submit" value="Ajouter le pays">
        </form>
        <p class="paragraph-link"><a class="link-home" href="manage-countries.php">Retour à la gestion des pays</a></p>
    </main>
<?php require __DIR__ . '/../../../includes/footer.php'; ?>
