<?php
/**
 * Gestion du calendrier : ajout d'une épreuve.
 * Règle de gestion (doublon) : un lieu n'accueille qu'une épreuve à une date et une heure données.
 */
$racine = '../../../';
$titre = 'Ajouter une épreuve';
require_once __DIR__ . '/../../../includes/bootstrap.php';
require_admin($racine);

$ep = ['nom_epreuve' => '', 'id_sport' => null, 'date_epreuve' => '', 'heure_epreuve' => '', 'id_lieu' => null];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash('error', 'Session expirée ou requête invalide. Veuillez réessayer.');
        redirect('add-event.php');
    }

    [$ep, $erreur] = lire_epreuve($connexion);

    if ($erreur === null) {
        $stmt = $connexion->prepare(
            'SELECT COUNT(*) FROM EPREUVE WHERE id_lieu = :lieu AND date_epreuve = :date AND heure_epreuve = :heure'
        );
        $stmt->execute([':lieu' => $ep['id_lieu'], ':date' => $ep['date_epreuve'], ':heure' => $ep['heure_epreuve']]);
        if ($stmt->fetchColumn() > 0) {
            $erreur = 'Une épreuve est déjà programmée dans ce lieu à cette date et à cette heure.';
        }
    }

    if ($erreur === null) {
        try {
            $stmt = $connexion->prepare(
                'INSERT INTO EPREUVE (nom_epreuve, date_epreuve, heure_epreuve, id_lieu, id_sport)
                 VALUES (:nom, :date, :heure, :lieu, :sport)'
            );
            $stmt->execute([
                ':nom'  => $ep['nom_epreuve'], ':date' => $ep['date_epreuve'], ':heure' => $ep['heure_epreuve'],
                ':lieu' => $ep['id_lieu'],     ':sport' => $ep['id_sport'],
            ]);
            flash('success', "L'épreuve « {$ep['nom_epreuve']} » a été ajoutée au calendrier.");
            redirect('manage-events.php');
        } catch (PDOException $e) {
            if (!est_doublon($e)) {
                throw $e;
            }
            $erreur = 'Une épreuve est déjà programmée dans ce lieu à cette date et à cette heure.';
        }
    }

    flash('error', $erreur);
}

require __DIR__ . '/../../../includes/head.php';
require __DIR__ . '/../../../includes/nav-admin.php';
?>
    <main>
        <h1>Ajouter une épreuve</h1>
        <?php show_flash(); ?>
        <form action="add-event.php" method="post">
<?php require __DIR__ . '/form-event.php'; ?>
            <input type="submit" value="Ajouter l'épreuve">
        </form>
        <p class="paragraph-link"><a class="link-home" href="manage-events.php">Retour au calendrier</a></p>
    </main>
<?php require __DIR__ . '/../../../includes/footer.php'; ?>
