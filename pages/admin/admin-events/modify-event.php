<?php
/** Gestion du calendrier : modification d'une épreuve. */
$racine = '../../../';
$titre = 'Modifier une épreuve';
require_once __DIR__ . '/../../../includes/bootstrap.php';
require_admin($racine);

$idEpreuve = input_id(INPUT_GET, 'id_epreuve');
if ($idEpreuve === null) {
    flash('error', 'Identifiant d\'épreuve invalide.');
    redirect('manage-events.php');
}

$stmt = $connexion->prepare(
    "SELECT nom_epreuve, id_sport, date_epreuve, DATE_FORMAT(heure_epreuve, '%H:%i') AS heure_epreuve, id_lieu
     FROM EPREUVE WHERE id_epreuve = :id"
);
$stmt->execute([':id' => $idEpreuve]);
$ep = $stmt->fetch();
if (!$ep) {
    flash('error', 'Épreuve introuvable.');
    redirect('manage-events.php');
}
$ep['id_sport'] = (int) $ep['id_sport'];
$ep['id_lieu']  = (int) $ep['id_lieu'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash('error', 'Session expirée ou requête invalide. Veuillez réessayer.');
        redirect("modify-event.php?id_epreuve=$idEpreuve");
    }

    [$ep, $erreur] = lire_epreuve($connexion);

    if ($erreur === null) {
        $stmt = $connexion->prepare(
            'SELECT COUNT(*) FROM EPREUVE
             WHERE id_lieu = :lieu AND date_epreuve = :date AND heure_epreuve = :heure AND id_epreuve <> :id'
        );
        $stmt->execute([':lieu' => $ep['id_lieu'], ':date' => $ep['date_epreuve'],
                        ':heure' => $ep['heure_epreuve'], ':id' => $idEpreuve]);
        if ($stmt->fetchColumn() > 0) {
            $erreur = 'Une autre épreuve est déjà programmée dans ce lieu à cette date et à cette heure.';
        }
    }

    if ($erreur === null) {
        try {
            $stmt = $connexion->prepare(
                'UPDATE EPREUVE SET nom_epreuve = :nom, date_epreuve = :date, heure_epreuve = :heure,
                                    id_lieu = :lieu, id_sport = :sport
                 WHERE id_epreuve = :id'
            );
            $stmt->execute([
                ':nom'  => $ep['nom_epreuve'], ':date'  => $ep['date_epreuve'], ':heure' => $ep['heure_epreuve'],
                ':lieu' => $ep['id_lieu'],     ':sport' => $ep['id_sport'],     ':id'    => $idEpreuve,
            ]);
            flash('success', "L'épreuve « {$ep['nom_epreuve']} » a été modifiée.");
            redirect('manage-events.php');
        } catch (PDOException $e) {
            if (!est_doublon($e)) {
                throw $e;
            }
            $erreur = 'Une autre épreuve est déjà programmée dans ce lieu à cette date et à cette heure.';
        }
    }

    flash('error', $erreur);
}

require __DIR__ . '/../../../includes/head.php';
require __DIR__ . '/../../../includes/nav-admin.php';
?>
    <main>
        <h1>Modifier une épreuve</h1>
        <?php show_flash(); ?>
        <form action="modify-event.php?id_epreuve=<?= $idEpreuve ?>" method="post">
<?php require __DIR__ . '/form-event.php'; ?>
            <input type="submit" value="Enregistrer les modifications">
        </form>
        <p class="paragraph-link"><a class="link-home" href="manage-events.php">Retour au calendrier</a></p>
    </main>
<?php require __DIR__ . '/../../../includes/footer.php'; ?>
