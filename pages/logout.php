<?php
/** Déconnexion : confirmation (GET) puis destruction de la session (POST protégé par CSRF). */
$racine = '../';
$titre = 'Déconnexion';
require_once __DIR__ . '/../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash('error', 'Requête invalide.');
        redirect('logout.php');
    }
    $_SESSION = [];
    // Suppression du cookie de session côté navigateur
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    session_destroy();
    redirect('../index.php');
}

require __DIR__ . '/../includes/head.php';
require __DIR__ . '/../includes/nav-public.php';
?>
    <main>
        <h1>Déconnexion</h1>
        <?php show_flash(); ?>
        <?php if (isset($_SESSION['id_admin'])): ?>
            <form action="logout.php" method="post">
                <?= csrf_field() ?>
                <p>Vous êtes connecté en tant que <strong><?= e($_SESSION['login']) ?></strong>.</p>
                <input type="submit" value="Se déconnecter">
            </form>
        <?php else: ?>
            <p>Vous n'êtes pas connecté.</p>
            <p class="paragraph-link"><a class="link-home" href="../index.php">Retour accueil</a></p>
        <?php endif; ?>
    </main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
