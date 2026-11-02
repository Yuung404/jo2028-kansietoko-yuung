<?php
/** Gestion des sports : liste. */
$racine = '../../../';
$titre = 'Gestion des sports';
require_once __DIR__ . '/../../../includes/bootstrap.php';
require_admin($racine);

$sports = $connexion->query('SELECT id_sport, nom_sport FROM SPORT ORDER BY nom_sport')->fetchAll();

require __DIR__ . '/../../../includes/head.php';
require __DIR__ . '/../../../includes/nav-admin.php';
?>
    <main>
        <h1>Gestion des sports</h1>
        <?php show_flash(); ?>
        <p class="action-buttons"><a class="btn" href="add-sport.php">Ajouter un sport</a></p>

        <?php if ($sports): ?>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Sport</th><th>Modifier</th><th>Supprimer</th></tr></thead>
                    <tbody>
                        <?php foreach ($sports as $sport): ?>
                            <tr>
                                <td><?= e($sport['nom_sport']) ?></td>
                                <td class="actions">
                                    <a class="btn btn-small" href="modify-sport.php?id_sport=<?= (int) $sport['id_sport'] ?>">Modifier</a>
                                </td>
                                <td class="actions">
                                    <!-- Suppression en POST + jeton CSRF (et non par un simple lien GET) -->
                                    <form class="inline-form" action="delete-sport.php" method="post"
                                          onsubmit="return confirm('Supprimer le sport « <?= e(addslashes($sport['nom_sport'])) ?> » ?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id_sport" value="<?= (int) $sport['id_sport'] ?>">
                                        <button type="submit" class="btn btn-small btn-danger">Supprimer</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p>Aucun sport enregistré.</p>
        <?php endif; ?>

        <p class="paragraph-link"><a class="link-home" href="../admin.php">Accueil administration</a></p>
    </main>
<?php require __DIR__ . '/../../../includes/footer.php'; ?>
