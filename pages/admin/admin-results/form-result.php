<?php
/**
 * Champs du formulaire « résultat », communs à l'ajout et à la modification.
 * Variables attendues : $res (données du résultat), $connexion.
 */
if (!isset($res) || !function_exists('csrf_field')) { // fichier partiel : jamais appelé directement
    http_response_code(404);
    exit();
}
$athletes = $connexion->query(
    "SELECT at.id_athlete, CONCAT(at.nom_athlete, ' ', at.prenom_athlete, ' (', pa.nom_pays, ')') AS libelle
     FROM ATHLETE at INNER JOIN PAYS pa ON pa.id_pays = at.id_pays
     ORDER BY at.nom_athlete, at.prenom_athlete"
)->fetchAll();
$epreuves = $connexion->query(
    "SELECT ep.id_epreuve,
            CONCAT(sp.nom_sport, ' - ', ep.nom_epreuve, ' (', DATE_FORMAT(ep.date_epreuve, '%d/%m/%Y'), ')') AS libelle
     FROM EPREUVE ep INNER JOIN SPORT sp ON sp.id_sport = ep.id_sport
     ORDER BY ep.date_epreuve, ep.heure_epreuve"
)->fetchAll();
?>
            <?= csrf_field() ?>
            <label for="id_epreuve">Épreuve :</label>
            <select name="id_epreuve" id="id_epreuve" required><?= options($epreuves, 'id_epreuve', 'libelle', $res['id_epreuve']) ?></select>
            <label for="id_athlete">Athlète :</label>
            <select name="id_athlete" id="id_athlete" required><?= options($athletes, 'id_athlete', 'libelle', $res['id_athlete']) ?></select>
            <label for="resultat">Résultat :</label>
            <input type="text" name="resultat" id="resultat" maxlength="100" value="<?= e($res['resultat']) ?>" required>
            <small class="field-hint">Temps, distance, score ou classement (ex. 9.81, 2:05.3, Médaille d'or)</small>
