<?php
/**
 * Début de page HTML commun.
 * Variables attendues : $titre (titre de la page) et $racine (chemin relatif vers la racine du site).
 */
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= e($racine) ?>css/normalize.css">
    <link rel="stylesheet" href="<?= e($racine) ?>css/styles-computer.css">
    <link rel="stylesheet" href="<?= e($racine) ?>css/styles-responsive.css">
    <link rel="stylesheet" href="<?= e($racine) ?>css/styles-admin.css">
    <link rel="icon" type="image/x-icon" href="<?= e($racine) ?>img/favicon.ico">
    <title><?= e($titre) ?> - Jeux Olympiques - Los Angeles 2028</title>
</head>

<body>
