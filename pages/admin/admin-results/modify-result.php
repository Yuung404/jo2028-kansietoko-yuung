<?php
/**
 * Gestion des résultats : modification.
 * Le résultat est identifié par le couple (id_athlete, id_epreuve) transmis dans l'URL.
 */
$racine = '../../../';
$titre = 'Modifier un résultat';
require_once __DIR__ . '/../../../includes/bootstrap.php';
require_admin($racine);

$idAthlete = input_id(INPUT_GET, 'id_athlete');
$idEpreuve = input_id(INPUT_GET, 'id_epreuve');
if ($idAthlete === null || $idEpreuve === null) {
    flash('error', 'Identifiants de résultat invalides.');
    redirect('manage-results.php');
}

$stmt = $connexion->prepare('SELECT id_athlete, id_epreuve, resultat FROM PARTICIPER WHERE id_athlete = :at AND id_epreuve = :ep');
$stmt->execute([':at' => $idAthlete, ':ep' => $idEpreuve]);
$res = $stmt->fetch();
if (!$res) {
    flash('error', 'Résultat introuvable.');
    redirect('manage-results.php');
}
$res['id_athlete'] = (int) $res['id_athlete'];
$res['id_epreuve'] = (int) $res['id_epreuve'];
$url = "modify-result.php?id_athlete=$idAthlete&id_epreuve=$idEpreuve";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash('error', 'Session expirée ou requête invalide. Veuillez réessayer.');
        redirect($url);
    }

    [$res, $erreur] = lire_resultat($connexion);

    // Si le couple (athlète, épreuve) change, le nouveau couple ne doit pas déjà exister
    $coupleChange = $res['id_athlete'] !== $idAthlete || $res['id_epreuve'] !== $idEpreuve;
    if ($erreur === null && $coupleChange) {
        $stmt = $connexion->prepare('SELECT COUNT(*) FROM PARTICIPER WHERE id_athlete = :at AND id_epreuve = :ep');
        $stmt->execute([':at' => $res['id_athlete'], ':ep' => $res['id_epreuve']]);
        if ($stmt->fetchColumn() > 0) {
            $erreur = 'Cet athlète a déjà un résultat pour cette épreuve.';
        }
    }

    if ($erreur === null) {
        try {
            $stmt = $connexion->prepare(
                'UPDATE PARTICIPER SET id_athlete = :at, id_epreuve = :ep, resultat = :resultat
                 WHERE id_athlete = :ancien_at AND id_epreuve = :ancien_ep'
            );
            $stmt->execute([
                ':at' => $res['id_athlete'], ':ep' => $res['id_epreuve'], ':resultat' => $res['resultat'],
                ':ancien_at' => $idAthlete,  ':ancien_ep' => $idEpreuve,
            ]);
            flash('success', 'Le résultat a été modifié.');
            redirect('manage-results.php');
        } catch (PDOException $e) {
            if (!est_doublon($e)) {
                throw $e;
            }
            $erreur = 'Cet athlète a déjà un résultat pour cette épreuve.';
        }
    }

    flash('error', $erreur);
}

require __DIR__ . '/../../../includes/head.php';
require __DIR__ . '/../../../includes/nav-admin.php';
?>
    <main>
        <h1>Modifier un résultat</h1>
        <?php show_flash(); ?>
        <form action="<?= e($url) ?>" method="post">
<?php require __DIR__ . '/form-result.php'; ?>
            <input type="submit" value="Enregistrer les modifications">
        </form>
        <p class="paragraph-link"><a class="link-home" href="manage-results.php">Retour à la gestion des résultats</a></p>
    </main>
<?php require __DIR__ . '/../../../includes/footer.php'; ?>
