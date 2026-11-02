<?php
/**
 * Champs du formulaire « lieu », communs à l'ajout et à la modification.
 * Variables attendues : $lieu (tableau nom_lieu, adresse_lieu, cp_lieu, ville_lieu).
 */
if (!isset($lieu) || !function_exists('csrf_field')) { // fichier partiel : jamais appelé directement
    http_response_code(404);
    exit();
}
?>
            <?= csrf_field() ?>
            <label for="nom_lieu">Nom du lieu :</label>
            <input type="text" name="nom_lieu" id="nom_lieu" maxlength="255" value="<?= e($lieu['nom_lieu']) ?>" required>
            <label for="adresse_lieu">Adresse :</label>
            <input type="text" name="adresse_lieu" id="adresse_lieu" maxlength="255" value="<?= e($lieu['adresse_lieu']) ?>" required>
            <label for="cp_lieu">Code postal :</label>
            <input type="text" name="cp_lieu" id="cp_lieu" maxlength="5" pattern="\d{5}" inputmode="numeric" value="<?= e($lieu['cp_lieu']) ?>" required>
            <small class="field-hint">5 chiffres (ex. 90037)</small>
            <label for="ville_lieu">Ville :</label>
            <input type="text" name="ville_lieu" id="ville_lieu" maxlength="255" value="<?= e($lieu['ville_lieu']) ?>" required>
