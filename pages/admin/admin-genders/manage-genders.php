<?php
/** Gestion des genres : liste. */
$racine = '../../../';
$titre = 'Gestion des genres';
require_once __DIR__ . '/../../../includes/bootstrap.php';
require_admin($racine);

$genreList = $connexion->query('SELECT id_genre, nom_genre FROM GENRE ORDER BY nom_genre')->fetchAll();

require __DIR__ . '/../../../includes/head.php';
require __DIR__ . '/../../../includes/nav-admin.php';
?>
    <main>
        <h1>Gestion des genres</h1>
        <?php show_flash(); ?>
        <p class="action-buttons"><a class="btn" href="add-gender.php">Ajouter un genre</a></p>

        <?php if ($genreList): ?>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Genre</th><th>Modifier</th><th>Supprimer</th></tr></thead>
                    <tbody>
                        <?php foreach ($genreList as $genre): ?>
                            <tr>
                                <td><?= e($genre['nom_genre']) ?></td>
                                <td class="actions">
                                    <a class="btn btn-small" href="modify-gender.php?id_genre=<?= (int) $genre['id_genre'] ?>">Modifier</a>
                                </td>
                                <td class="actions">
                                    <!-- Suppression en POST + jeton CSRF (et non par un simple lien GET) -->
                                    <form class="inline-form" action="delete-gender.php" method="post"
                                          onsubmit="return confirm('Supprimer le genre « <?= e(addslashes($genre['nom_genre'])) ?> » ?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id_genre" value="<?= (int) $genre['id_genre'] ?>">
                                        <button type="submit" class="btn btn-small btn-danger">Supprimer</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p>Aucun genre enregistré.</p>
        <?php endif; ?>

        <p class="paragraph-link"><a class="link-home" href="../admin.php">Accueil administration</a></p>
    </main>
<?php require __DIR__ . '/../../../includes/footer.php'; ?>
