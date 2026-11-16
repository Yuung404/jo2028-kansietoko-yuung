<?php
session_start();
require_once("../../../database/database.php");

// Vérifiez si l'utilisateur est connecté
if (!isset($_SESSION['login'])) {
    header('Location: ../../../index.php');
    exit();
}

// Protection CSRF : la suppression n'est acceptée qu'en POST, avec un token CSRF valide
// (un simple lien GET pourrait être déclenché par un autre site à la place de l'administrateur)
if ($_SERVER['REQUEST_METHOD'] != 'POST' || !isset($_POST['csrf_token'], $_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $_SESSION['error'] = "Requête de suppression invalide.";
    header('Location: manage-results.php');
    exit();
}

// Vérifiez si les deux ID du résultat sont fournis (athlète et épreuve)
if (!isset($_POST['id_athlete'], $_POST['id_epreuve'])) {
    $_SESSION['error'] = "ID du résultat manquant.";
    header("Location: manage-results.php");
    exit();
} else {
    $id_athlete = filter_input(INPUT_POST, 'id_athlete', FILTER_VALIDATE_INT);
    $id_epreuve = filter_input(INPUT_POST, 'id_epreuve', FILTER_VALIDATE_INT);

    // Vérifiez si les deux ID sont des entiers valides
    if (!$id_athlete || !$id_epreuve) {
        $_SESSION['error'] = "ID du résultat invalide.";
        header("Location: manage-results.php");
        exit();
    } else {
        try {
            // Préparez la requête SQL pour supprimer le résultat (les deux colonnes de la clé primaire dans le WHERE)
            $sql = "DELETE FROM PARTICIPER WHERE id_athlete = :id_athlete AND id_epreuve = :id_epreuve";
            // Exécutez la requête SQL avec les paramètres
            $statement = $connexion->prepare($sql);
            $statement->bindParam(':id_athlete', $id_athlete, PDO::PARAM_INT);
            $statement->bindParam(':id_epreuve', $id_epreuve, PDO::PARAM_INT);
            $statement->execute();

            // Message de succès
            $_SESSION['success'] = "Le résultat a été supprimé avec succès.";

            // Redirigez vers la liste après la suppression
            header('Location: manage-results.php');
            exit();
        } catch (PDOException $e) {
            // Le message SQL va dans le journal du serveur, pas à l'écran
            error_log("Erreur PDO : " . $e->getMessage());
            $_SESSION['error'] = "Erreur lors de la suppression du résultat.";
            header('Location: manage-results.php');
            exit();
        }
    }
}

// Afficher les erreurs en PHP (fonctionne à condition d’avoir activé l’option en local)
error_reporting(E_ALL);
ini_set("display_errors", 1);
?>
