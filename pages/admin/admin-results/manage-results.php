<?php
/** Gestion des résultats : liste. */
$racine = '../../../';
$titre = 'Gestion des résultats';
require_once __DIR__ . '/../../../includes/bootstrap.php';
require_admin($racine);

$resultats = $connexion->query(
    "SELECT pt.id_athlete, pt.id_epreuve, pt.resultat,
            sp.nom_sport, ep.nom_epreuve, at.nom_athlete, at.prenom_athlete, pa.nom_pays
     FROM PARTICIPER pt
     INNER JOIN EPREUVE ep ON ep.id_epreuve = pt.id_epreuve
     INNER JOIN SPORT   sp ON sp.id_sport   = ep.id_sport
     INNER JOIN ATHLETE at ON at.id_athlete = pt.id_athlete
     INNER JOIN PAYS    pa ON pa.id_pays    = at.id_pays
     ORDER BY sp.nom_sport, ep.nom_epreuve, at.nom_athlete"
)->fetchAll();

require __DIR__ . '/../../../includes/head.php';
require __DIR__ . '/../../../includes/nav-admin.php';
?>
    <main>
        <h1>Gestion des résultats</h1>
        <?php show_flash(); ?>
        <p class="action-buttons"><a class="btn" href="add-result.php">Ajouter un résultat</a></p>

        <?php if ($resultats): ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Sport</th><th>Épreuve</th><th>Athlète</th><th>Pays</th><th>Résultat</th><th>Modifier</th><th>Supprimer</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($resultats as $r):
                            // Clé composée : un résultat est identifié par le couple (athlète, épreuve)
                            $cle = 'id_athlete=' . (int) $r['id_athlete'] . '&amp;id_epreuve=' . (int) $r['id_epreuve']; ?>
                            <tr>
                                <td><?= e($r['nom_sport']) ?></td>
                                <td><?= e($r['nom_epreuve']) ?></td>
                                <td><?= e($r['prenom_athlete'] . ' ' . $r['nom_athlete']) ?></td>
                                <td><?= e($r['nom_pays']) ?></td>
                                <td><?= e($r['resultat']) ?></td>
                                <td class="actions">
                                    <a class="btn btn-small" href="modify-result.php?<?= $cle ?>">Modifier</a>
                                </td>
                                <td class="actions">
                                    <form class="inline-form" action="delete-result.php" method="post"
                                          onsubmit="return confirm('Supprimer ce résultat ?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id_athlete" value="<?= (int) $r['id_athlete'] ?>">
                                        <input type="hidden" name="id_epreuve" value="<?= (int) $r['id_epreuve'] ?>">
                                        <button type="submit" class="btn btn-small btn-danger">Supprimer</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p>Aucun résultat enregistré.</p>
        <?php endif; ?>

        <p class="paragraph-link"><a class="link-home" href="../admin.php">Accueil administration</a></p>
    </main>
<?php require __DIR__ . '/../../../includes/footer.php'; ?>
