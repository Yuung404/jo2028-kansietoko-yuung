<?php
/** Gestion du calendrier des épreuves : liste. */
$racine = '../../../';
$titre = 'Gestion du calendrier';
require_once __DIR__ . '/../../../includes/bootstrap.php';
require_admin($racine);

$epreuves = $connexion->query(
    "SELECT ep.id_epreuve, ep.nom_epreuve,
            DATE_FORMAT(ep.date_epreuve, '%d/%m/%Y') AS date_fr,
            DATE_FORMAT(ep.heure_epreuve, '%H:%i')   AS heure,
            sp.nom_sport, li.nom_lieu
     FROM EPREUVE ep
     INNER JOIN SPORT sp ON sp.id_sport = ep.id_sport
     INNER JOIN LIEU  li ON li.id_lieu  = ep.id_lieu
     ORDER BY ep.date_epreuve, ep.heure_epreuve"
)->fetchAll();

require __DIR__ . '/../../../includes/head.php';
require __DIR__ . '/../../../includes/nav-admin.php';
?>
    <main>
        <h1>Gestion du calendrier</h1>
        <?php show_flash(); ?>
        <p class="action-buttons"><a class="btn" href="add-event.php">Ajouter une épreuve</a></p>

        <?php if ($epreuves): ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Sport</th><th>Épreuve</th><th>Date</th><th>Heure</th><th>Lieu</th><th>Modifier</th><th>Supprimer</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($epreuves as $ep): ?>
                            <tr>
                                <td><?= e($ep['nom_sport']) ?></td>
                                <td><?= e($ep['nom_epreuve']) ?></td>
                                <td><?= e($ep['date_fr']) ?></td>
                                <td><?= e($ep['heure']) ?></td>
                                <td><?= e($ep['nom_lieu']) ?></td>
                                <td class="actions">
                                    <a class="btn btn-small" href="modify-event.php?id_epreuve=<?= (int) $ep['id_epreuve'] ?>">Modifier</a>
                                </td>
                                <td class="actions">
                                    <form class="inline-form" action="delete-event.php" method="post"
                                          onsubmit="return confirm('Supprimer l\'épreuve « <?= e(addslashes($ep['nom_epreuve'])) ?> » ?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id_epreuve" value="<?= (int) $ep['id_epreuve'] ?>">
                                        <button type="submit" class="btn btn-small btn-danger">Supprimer</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p>Aucune épreuve programmée.</p>
        <?php endif; ?>

        <p class="paragraph-link"><a class="link-home" href="../admin.php">Accueil administration</a></p>
    </main>
<?php require __DIR__ . '/../../../includes/footer.php'; ?>
