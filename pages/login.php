<?php
/** Formulaire de connexion à l'espace d'administration. */
$racine = '../';
$titre = 'Connexion';
require_once __DIR__ . '/../includes/bootstrap.php';

// Un administrateur déjà connecté va directement à l'administration
if (isset($_SESSION['id_admin'])) {
    redirect('admin/admin.php');
}

require __DIR__ . '/../includes/head.php';
require __DIR__ . '/../includes/nav-public.php';
?>
    <main>
        <h1>Connexion</h1>
        <?php show_flash(); ?>
        <form action="../database/auth.php" method="post">
            <?= csrf_field() ?>
            <label for="login">Login :</label>
            <input type="text" name="login" id="login" maxlength="255" autocomplete="username" required>
            <label for="password">Mot de passe :</label>
            <input type="password" name="password" id="password" autocomplete="current-password" required>
            <input type="submit" value="Se connecter">
        </form>
    </main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
