<?php
/** Menu des pages publiques. Variable attendue : $racine. */
$p = $racine . 'pages/';
?>
    <header>
        <nav aria-label="Menu principal">
            <ul class="menu">
                <li><a href="<?= e($racine) ?>index.php">Accueil</a></li>
                <li><a href="<?= e($p) ?>sports.php">Sports</a></li>
                <li><a href="<?= e($p) ?>events.php">Calendrier des épreuves</a></li>
                <li><a href="<?= e($p) ?>results.php">Résultats</a></li>
                <li><a href="<?= e($p) ?>login.php">Accès administrateur</a></li>
            </ul>
        </nav>
    </header>
