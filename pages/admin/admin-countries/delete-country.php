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
    header('Location: manage-countries.php');
    exit();
}

// Vérifiez si l'ID du pays est fourni
if (!isset($_POST['id_pays'])) {
    $_SESSION['error'] = "ID du pays manquant.";
    header("Location: manage-countries.php");
    exit();
} else {
    $id_pays = filter_input(INPUT_POST, 'id_pays', FILTER_VALIDATE_INT);

    // Vérifiez si l'ID du pays est un entier valide
    if ($id_pays === false || $id_pays === null) {
        $_SESSION['error'] = "ID du pays invalide.";
        header("Location: manage-countries.php");
        exit();
    } else {
        try {
            // Vérifiez que le pays n'est pas utilisé ailleurs (clé étrangère ATHLETE.id_pays).
            // On compte d'abord, pour afficher un message clair au lieu d'une erreur SQL de clé étrangère.
            $queryCheck = "SELECT COUNT(*) FROM ATHLETE WHERE id_pays = :id_pays";
            $statementCheck = $connexion->prepare($queryCheck);
            $statementCheck->bindParam(':id_pays', $id_pays, PDO::PARAM_INT);
            $statementCheck->execute();

            if ($statementCheck->fetchColumn() > 0) {
                $_SESSION['error'] = "Suppression impossible : ce pays est utilisé par au moins un athlète.";
                header('Location: manage-countries.php');
                exit();
            }

            // Préparez la requête SQL pour supprimer le pays
            $sql = "DELETE FROM PAYS WHERE id_pays = :id_pays";
            // Exécutez la requête SQL avec le paramètre
            $statement = $connexion->prepare($sql);
            $statement->bindParam(':id_pays', $id_pays, PDO::PARAM_INT);
            $statement->execute();

            // Message de succès
            $_SESSION['success'] = "Le pays a été supprimé avec succès.";

            // Redirigez vers la liste après la suppression
            header('Location: manage-countries.php');
            exit();
        } catch (PDOException $e) {
            // Le message SQL va dans le journal du serveur, pas à l'écran
            error_log("Erreur PDO : " . $e->getMessage());
            $_SESSION['error'] = "Erreur lors de la suppression du pays.";
            header('Location: manage-countries.php');
            exit();
        }
    }
}

// Afficher les erreurs en PHP (fonctionne à condition d’avoir activé l’option en local)
error_reporting(E_ALL);
ini_set("display_errors", 1);
?>
