<?php
/** Accueil de l'espace d'administration. */
$racine = '../../';
$titre = 'Administration';
require_once __DIR__ . '/../../includes/bootstrap.php';
require_admin($racine);

require __DIR__ . '/../../includes/head.php';
require __DIR__ . '/../../includes/nav-admin.php';
?>
    <main>
        <?php show_flash(); ?>
        <p class="info-login">
            Bonjour, <?= e($_SESSION['prenom_admin'] . ' ' . $_SESSION['nom_admin']) ?>
        </p>
        <p class="category-site">
            <a class="link-category" href="./admin-users/manage-users.php">Gestion des administrateurs</a>
            <a class="link-category" href="./admin-sports/manage-sports.php">Gestion des sports</a>
            <a class="link-category" href="./admin-places/manage-places.php">Gestion des lieux</a>
            <a class="link-category" href="./admin-countries/manage-countries.php">Gestion des pays</a>
            <a class="link-category" href="./admin-genders/manage-genders.php">Gestion des genres</a>
            <a class="link-category" href="./admin-events/manage-events.php">Gestion du calendrier</a>
            <a class="link-category" href="./admin-athletes/manage-athletes.php">Gestion des athlètes</a>
            <a class="link-category" href="./admin-results/manage-results.php">Gestion des résultats</a>
        </p>
    </main>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
