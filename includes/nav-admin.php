<?php
/** Menu de l'espace d'administration. Variable attendue : $racine. */
$a = $racine . 'pages/admin/';
?>
    <header>
        <nav aria-label="Menu administration">
            <ul class="menu menu-admin">
                <li><a href="<?= e($a) ?>admin.php">Accueil admin</a></li>
                <li><a href="<?= e($a) ?>admin-users/manage-users.php">Administrateurs</a></li>
                <li><a href="<?= e($a) ?>admin-sports/manage-sports.php">Sports</a></li>
                <li><a href="<?= e($a) ?>admin-places/manage-places.php">Lieux</a></li>
                <li><a href="<?= e($a) ?>admin-countries/manage-countries.php">Pays</a></li>
                <li><a href="<?= e($a) ?>admin-genders/manage-genders.php">Genres</a></li>
                <li><a href="<?= e($a) ?>admin-events/manage-events.php">Calendrier</a></li>
                <li><a href="<?= e($a) ?>admin-athletes/manage-athletes.php">Athlètes</a></li>
                <li><a href="<?= e($a) ?>admin-results/manage-results.php">Résultats</a></li>
                <li><a href="<?= e($racine) ?>pages/logout.php">Déconnexion</a></li>
            </ul>
        </nav>
    </header>
