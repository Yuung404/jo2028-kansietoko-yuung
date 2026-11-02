<?php
/** Gestion des pays : liste. */
$racine = '../../../';
$titre = 'Gestion des pays';
require_once __DIR__ . '/../../../includes/bootstrap.php';
require_admin($racine);

$paysList = $connexion->query('SELECT id_pays, nom_pays FROM PAYS ORDER BY nom_pays')->fetchAll();

require __DIR__ . '/../../../includes/head.php';
require __DIR__ . '/../../../includes/nav-admin.php';
?>
    <main>
        <h1>Gestion des pays</h1>
        <?php show_flash(); ?>
        <p class="action-buttons"><a class="btn" href="add-country.php">Ajouter un pays</a></p>

        <?php if ($paysList): ?>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Pays</th><th>Modifier</th><th>Supprimer</th></tr></thead>
                    <tbody>
                        <?php foreach ($paysList as $pays): ?>
                            <tr>
                                <td><?= e($pays['nom_pays']) ?></td>
                                <td class="actions">
                                    <a class="btn btn-small" href="modify-country.php?id_pays=<?= (int) $pays['id_pays'] ?>">Modifier</a>
                                </td>
                                <td class="actions">
                                    <!-- Suppression en POST + jeton CSRF (et non par un simple lien GET) -->
                                    <form class="inline-form" action="delete-country.php" method="post"
                                          onsubmit="return confirm('Supprimer le pays « <?= e(addslashes($pays['nom_pays'])) ?> » ?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id_pays" value="<?= (int) $pays['id_pays'] ?>">
                                        <button type="submit" class="btn btn-small btn-danger">Supprimer</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p>Aucun pays enregistré.</p>
        <?php endif; ?>

        <p class="paragraph-link"><a class="link-home" href="../admin.php">Accueil administration</a></p>
    </main>
<?php require __DIR__ . '/../../../includes/footer.php'; ?>
