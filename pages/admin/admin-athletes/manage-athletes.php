<?php
/** Gestion des athlètes : liste. */
$racine = '../../../';
$titre = 'Gestion des athlètes';
require_once __DIR__ . '/../../../includes/bootstrap.php';
require_admin($racine);

$athletes = $connexion->query(
    'SELECT at.id_athlete, at.nom_athlete, at.prenom_athlete, pa.nom_pays, ge.nom_genre
     FROM ATHLETE at
     INNER JOIN PAYS  pa ON pa.id_pays  = at.id_pays
     INNER JOIN GENRE ge ON ge.id_genre = at.id_genre
     ORDER BY at.nom_athlete, at.prenom_athlete'
)->fetchAll();

require __DIR__ . '/../../../includes/head.php';
require __DIR__ . '/../../../includes/nav-admin.php';
?>
    <main>
        <h1>Gestion des athlètes</h1>
        <?php show_flash(); ?>
        <p class="action-buttons"><a class="btn" href="add-athlete.php">Ajouter un athlète</a></p>

        <?php if ($athletes): ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Nom</th><th>Prénom</th><th>Pays</th><th>Genre</th><th>Modifier</th><th>Supprimer</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($athletes as $at): ?>
                            <tr>
                                <td><?= e($at['nom_athlete']) ?></td>
                                <td><?= e($at['prenom_athlete']) ?></td>
                                <td><?= e($at['nom_pays']) ?></td>
                                <td><?= e($at['nom_genre']) ?></td>
                                <td class="actions">
                                    <a class="btn btn-small" href="modify-athlete.php?id_athlete=<?= (int) $at['id_athlete'] ?>">Modifier</a>
                                </td>
                                <td class="actions">
                                    <form class="inline-form" action="delete-athlete.php" method="post"
                                          onsubmit="return confirm('Supprimer l\'athlète « <?= e(addslashes($at['prenom_athlete'] . ' ' . $at['nom_athlete'])) ?> » ?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id_athlete" value="<?= (int) $at['id_athlete'] ?>">
                                        <button type="submit" class="btn btn-small btn-danger">Supprimer</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p>Aucun athlète enregistré.</p>
        <?php endif; ?>

        <p class="paragraph-link"><a class="link-home" href="../admin.php">Accueil administration</a></p>
    </main>
<?php require __DIR__ . '/../../../includes/footer.php'; ?>
