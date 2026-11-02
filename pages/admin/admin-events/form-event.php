<?php
/**
 * Champs du formulaire « épreuve », communs à l'ajout et à la modification.
 * Variables attendues : $ep (données de l'épreuve), $connexion.
 */
if (!isset($ep) || !function_exists('csrf_field')) { // fichier partiel : jamais appelé directement
    http_response_code(404);
    exit();
}
$sports = $connexion->query('SELECT id_sport, nom_sport FROM SPORT ORDER BY nom_sport')->fetchAll();
$lieux  = $connexion->query('SELECT id_lieu, nom_lieu FROM LIEU ORDER BY nom_lieu')->fetchAll();
?>
            <?= csrf_field() ?>
            <label for="nom_epreuve">Nom de l'épreuve :</label>
            <input type="text" name="nom_epreuve" id="nom_epreuve" maxlength="255" value="<?= e($ep['nom_epreuve']) ?>" required>
            <small class="field-hint">Ex. : Finale 100 m hommes</small>
            <label for="id_sport">Sport :</label>
            <select name="id_sport" id="id_sport" required><?= options($sports, 'id_sport', 'nom_sport', $ep['id_sport']) ?></select>
            <label for="date_epreuve">Date :</label>
            <input type="date" name="date_epreuve" id="date_epreuve" value="<?= e($ep['date_epreuve']) ?>" required>
            <label for="heure_epreuve">Heure :</label>
            <input type="time" name="heure_epreuve" id="heure_epreuve" value="<?= e($ep['heure_epreuve']) ?>" required>
            <label for="id_lieu">Lieu :</label>
            <select name="id_lieu" id="id_lieu" required><?= options($lieux, 'id_lieu', 'nom_lieu', $ep['id_lieu']) ?></select>
