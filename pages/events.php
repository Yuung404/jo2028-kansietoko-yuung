<?php
/**
 * Page publique : calendrier des épreuves.
 * Correctif : noms de tables en majuscules (EPREUVE, SPORT, LIEU) comme dans le schéma,
 * indispensable sur un serveur Linux où MySQL distingue majuscules et minuscules.
 */
$racine = '../';
$titre = 'Calendrier des épreuves';
require_once __DIR__ . '/../includes/bootstrap.php';

$epreuves = $connexion->query(
    "SELECT ep.nom_epreuve,
            DATE_FORMAT(ep.date_epreuve, '%d/%m/%Y') AS date_fr,
            DATE_FORMAT(ep.heure_epreuve, '%H:%i')   AS heure,
            sp.nom_sport, li.nom_lieu, li.ville_lieu
     FROM EPREUVE ep
     INNER JOIN SPORT sp ON sp.id_sport = ep.id_sport
     INNER JOIN LIEU  li ON li.id_lieu  = ep.id_lieu
     ORDER BY ep.date_epreuve, ep.heure_epreuve"
)->fetchAll();

require __DIR__ . '/../includes/head.php';
require __DIR__ . '/../includes/nav-public.php';
?>
    <main>
        <h1>Calendrier des épreuves</h1>
        <?php if ($epreuves): ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Sport</th><th>Épreuve</th><th>Date</th><th>Heure</th><th>Lieu</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($epreuves as $ep): ?>
                            <tr>
                                <td><?= e($ep['nom_sport']) ?></td>
                                <td><?= e($ep['nom_epreuve']) ?></td>
                                <td><?= e($ep['date_fr']) ?></td>
                                <td><?= e($ep['heure']) ?></td>
                                <td><?= e($ep['nom_lieu']) ?> (<?= e($ep['ville_lieu']) ?>)</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p>Aucune épreuve programmée pour le moment.</p>
        <?php endif; ?>
        <p class="paragraph-link"><a class="link-home" href="../index.php">Retour accueil</a></p>
    </main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
