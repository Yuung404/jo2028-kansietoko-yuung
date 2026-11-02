<?php
/**
 * Champs du formulaire « athlète », communs à l'ajout et à la modification.
 * Variables attendues : $at (données de l'athlète), $connexion.
 */
if (!isset($at) || !function_exists('csrf_field')) { // fichier partiel : jamais appelé directement
    http_response_code(404);
    exit();
}
$pays   = $connexion->query('SELECT id_pays, nom_pays FROM PAYS ORDER BY nom_pays')->fetchAll();
$genres = $connexion->query('SELECT id_genre, nom_genre FROM GENRE ORDER BY nom_genre')->fetchAll();
?>
            <?= csrf_field() ?>
            <label for="nom_athlete">Nom :</label>
            <input type="text" name="nom_athlete" id="nom_athlete" maxlength="255" value="<?= e($at['nom_athlete']) ?>" required>
            <label for="prenom_athlete">Prénom :</label>
            <input type="text" name="prenom_athlete" id="prenom_athlete" maxlength="255" value="<?= e($at['prenom_athlete']) ?>" required>
            <label for="id_pays">Pays :</label>
            <select name="id_pays" id="id_pays" required><?= options($pays, 'id_pays', 'nom_pays', $at['id_pays']) ?></select>
            <label for="id_genre">Genre :</label>
            <select name="id_genre" id="id_genre" required><?= options($genres, 'id_genre', 'nom_genre', $at['id_genre']) ?></select>
