<?php
/**
 * Gestion des résultats : ajout.
 * Règle de gestion (doublon) : un athlète n'a qu'un résultat par épreuve (clé primaire composée).
 */
$racine = '../../../';
$titre = 'Ajouter un résultat';
require_once __DIR__ . '/../../../includes/bootstrap.php';
require_admin($racine);

$res = ['id_athlete' => null, 'id_epreuve' => null, 'resultat' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash('error', 'Session expirée ou requête invalide. Veuillez réessayer.');
        redirect('add-result.php');
    }

    [$res, $erreur] = lire_resultat($connexion);

    if ($erreur === null) {
        $stmt = $connexion->prepare('SELECT COUNT(*) FROM PARTICIPER WHERE id_athlete = :at AND id_epreuve = :ep');
        $stmt->execute([':at' => $res['id_athlete'], ':ep' => $res['id_epreuve']]);
        if ($stmt->fetchColumn() > 0) {
            $erreur = 'Cet athlète a déjà un résultat pour cette épreuve : modifiez-le plutôt.';
        }
    }

    if ($erreur === null) {
        try {
            $stmt = $connexion->prepare(
                'INSERT INTO PARTICIPER (id_athlete, id_epreuve, resultat) VALUES (:at, :ep, :resultat)'
            );
            $stmt->execute([':at' => $res['id_athlete'], ':ep' => $res['id_epreuve'], ':resultat' => $res['resultat']]);
            flash('success', 'Le résultat a été enregistré.');
            redirect('manage-results.php');
        } catch (PDOException $e) {
            if (!est_doublon($e)) {
                throw $e;
            }
            $erreur = 'Cet athlète a déjà un résultat pour cette épreuve : modifiez-le plutôt.';
        }
    }

    flash('error', $erreur);
}

require __DIR__ . '/../../../includes/head.php';
require __DIR__ . '/../../../includes/nav-admin.php';
?>
    <main>
        <h1>Ajouter un résultat</h1>
        <?php show_flash(); ?>
        <form action="add-result.php" method="post">
<?php require __DIR__ . '/form-result.php'; ?>
            <input type="submit" value="Enregistrer le résultat">
        </form>
        <p class="paragraph-link"><a class="link-home" href="manage-results.php">Retour à la gestion des résultats</a></p>
    </main>
<?php require __DIR__ . '/../../../includes/footer.php'; ?>
