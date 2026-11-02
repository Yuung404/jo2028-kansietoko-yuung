<?php
/** Gestion des lieux : liste. */
$racine = '../../../';
$titre = 'Gestion des lieux';
require_once __DIR__ . '/../../../includes/bootstrap.php';
require_admin($racine);

$lieux = $connexion->query(
    'SELECT id_lieu, nom_lieu, adresse_lieu, cp_lieu, ville_lieu FROM LIEU ORDER BY nom_lieu'
)->fetchAll();

require __DIR__ . '/../../../includes/head.php';
require __DIR__ . '/../../../includes/nav-admin.php';
?>
    <main>
        <h1>Gestion des lieux</h1>
        <?php show_flash(); ?>
        <p class="action-buttons"><a class="btn" href="add-place.php">Ajouter un lieu</a></p>

        <?php if ($lieux): ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Lieu</th><th>Adresse</th><th>Code postal</th><th>Ville</th><th>Modifier</th><th>Supprimer</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lieux as $lieu): ?>
                            <tr>
                                <td><?= e($lieu['nom_lieu']) ?></td>
                                <td><?= e($lieu['adresse_lieu']) ?></td>
                                <td><?= e($lieu['cp_lieu']) ?></td>
                                <td><?= e($lieu['ville_lieu']) ?></td>
                                <td class="actions">
                                    <a class="btn btn-small" href="modify-place.php?id_lieu=<?= (int) $lieu['id_lieu'] ?>">Modifier</a>
                                </td>
                                <td class="actions">
                                    <form class="inline-form" action="delete-place.php" method="post"
                                          onsubmit="return confirm('Supprimer le lieu « <?= e(addslashes($lieu['nom_lieu'])) ?> » ?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id_lieu" value="<?= (int) $lieu['id_lieu'] ?>">
                                        <button type="submit" class="btn btn-small btn-danger">Supprimer</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p>Aucun lieu enregistré.</p>
        <?php endif; ?>

        <p class="paragraph-link"><a class="link-home" href="../admin.php">Accueil administration</a></p>
    </main>
<?php require __DIR__ . '/../../../includes/footer.php'; ?>
