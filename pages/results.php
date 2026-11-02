<?php
/**
 * Page publique : résultats des épreuves.
 * Correctif : noms de tables en majuscules, conformes au schéma (serveurs Linux).
 */
$racine = '../';
$titre = 'Résultats';
require_once __DIR__ . '/../includes/bootstrap.php';

$resultats = $connexion->query(
    "SELECT sp.nom_sport, ep.nom_epreuve, at.nom_athlete, at.prenom_athlete, pa.nom_pays, pt.resultat
     FROM PARTICIPER pt
     INNER JOIN EPREUVE ep ON ep.id_epreuve = pt.id_epreuve
     INNER JOIN SPORT   sp ON sp.id_sport   = ep.id_sport
     INNER JOIN ATHLETE at ON at.id_athlete = pt.id_athlete
     INNER JOIN PAYS    pa ON pa.id_pays    = at.id_pays
     ORDER BY sp.nom_sport, ep.nom_epreuve, at.nom_athlete, at.prenom_athlete"
)->fetchAll();

require __DIR__ . '/../includes/head.php';
require __DIR__ . '/../includes/nav-public.php';
?>
    <main>
        <h1>Résultats des épreuves</h1>
        <?php if ($resultats): ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Sport</th><th>Épreuve</th><th>Nom</th><th>Prénom</th><th>Pays</th><th>Résultat</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($resultats as $r): ?>
                            <tr>
                                <td><?= e($r['nom_sport']) ?></td>
                                <td><?= e($r['nom_epreuve']) ?></td>
                                <td><?= e($r['nom_athlete']) ?></td>
                                <td><?= e($r['prenom_athlete']) ?></td>
                                <td><?= e($r['nom_pays']) ?></td>
                                <td><?= e($r['resultat']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p>Aucun résultat publié pour le moment.</p>
        <?php endif; ?>
        <p class="paragraph-link"><a class="link-home" href="../index.php">Retour accueil</a></p>
    </main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
