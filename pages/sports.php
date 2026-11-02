<?php
/** Page publique : liste des sports. */
$racine = '../';
$titre = 'Sports';
require_once __DIR__ . '/../includes/bootstrap.php';

$sports = $connexion->query('SELECT nom_sport FROM SPORT ORDER BY nom_sport')->fetchAll();

require __DIR__ . '/../includes/head.php';
require __DIR__ . '/../includes/nav-public.php';
?>
    <main>
        <h1>Liste des sports</h1>
        <?php if ($sports): ?>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Sport</th></tr></thead>
                    <tbody>
                        <?php foreach ($sports as $sport): ?>
                            <tr><td><?= e($sport['nom_sport']) ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p>Aucun sport enregistré pour le moment.</p>
        <?php endif; ?>
        <p class="paragraph-link"><a class="link-home" href="../index.php">Retour accueil</a></p>
    </main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
