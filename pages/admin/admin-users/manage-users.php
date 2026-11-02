<?php
/** Gestion des administrateurs : liste (les empreintes de mot de passe ne sont jamais affichées). */
$racine = '../../../';
$titre = 'Gestion des administrateurs';
require_once __DIR__ . '/../../../includes/bootstrap.php';
require_admin($racine);

$admins = $connexion->query(
    'SELECT id_admin, nom_admin, prenom_admin, login FROM ADMINISTRATEUR ORDER BY nom_admin, prenom_admin'
)->fetchAll();

require __DIR__ . '/../../../includes/head.php';
require __DIR__ . '/../../../includes/nav-admin.php';
?>
    <main>
        <h1>Gestion des administrateurs</h1>
        <?php show_flash(); ?>
        <p class="action-buttons"><a class="btn" href="add-user.php">Ajouter un administrateur</a></p>

        <div class="table-wrap">
            <table>
                <thead><tr><th>Nom</th><th>Prénom</th><th>Login</th><th>Modifier</th><th>Supprimer</th></tr></thead>
                <tbody>
                    <?php foreach ($admins as $admin): ?>
                        <tr>
                            <td><?= e($admin['nom_admin']) ?></td>
                            <td><?= e($admin['prenom_admin']) ?></td>
                            <td><?= e($admin['login']) ?></td>
                            <td class="actions">
                                <a class="btn btn-small" href="modify-user.php?id_admin=<?= (int) $admin['id_admin'] ?>">Modifier</a>
                            </td>
                            <td class="actions">
                                <?php if ((int) $admin['id_admin'] === $_SESSION['id_admin']): ?>
                                    <em>Compte connecté</em>
                                <?php else: ?>
                                    <form class="inline-form" action="delete-user.php" method="post"
                                          onsubmit="return confirm('Supprimer le compte « <?= e(addslashes($admin['login'])) ?> » ?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id_admin" value="<?= (int) $admin['id_admin'] ?>">
                                        <button type="submit" class="btn btn-small btn-danger">Supprimer</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <p class="paragraph-link"><a class="link-home" href="../admin.php">Accueil administration</a></p>
    </main>
<?php require __DIR__ . '/../../../includes/footer.php'; ?>
