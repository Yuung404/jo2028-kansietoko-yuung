<?php
/** Page d'accueil publique. */
$racine = './';
$titre = 'Accueil';
require_once __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/nav-public.php';
?>
    <main>
        <h1 class="sr-only">Jeux Olympiques de Los Angeles 2028</h1>
        <p class="category-site">
            <a class="link-category" href="./pages/sports.php">Sports</a>
            <a class="link-category" href="./pages/events.php">Calendrier des épreuves</a>
            <a class="link-category" href="./pages/results.php">Résultats</a>
        </p>
    </main>
<?php require __DIR__ . '/includes/footer.php'; ?>
