<?php
session_start();
require_once("../../../database/database.php");

// Vérifiez si l'utilisateur est connecté
if (!isset($_SESSION['login'])) {
    header('Location: ../../../index.php');
    exit();
}

// Protection CSRF
// Correction : la suppression n'est acceptée qu'en POST, avec un token CSRF valide.
// Avant, elle arrivait par un lien (GET) et la vérification, prévue seulement pour le POST, était toujours sautée.
if ($_SERVER['REQUEST_METHOD'] != 'POST' || !isset($_POST['csrf_token'], $_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $_SESSION['error'] = "Requête de suppression invalide.";
    header('Location: manage-sports.php');
    exit();
}

// Vérifiez si l'ID du sport est fourni (il arrive maintenant dans le formulaire POST)
if (!isset($_POST['id_sport'])) {
    $_SESSION['error'] = "ID du sport manquant.";
    header("Location: manage-sports.php");
    exit();
} else {
    $id_sport = filter_input(INPUT_POST, 'id_sport', FILTER_VALIDATE_INT);

    // Vérifiez si l'ID du sport est un entier valide
    if ($id_sport === false) {
        $_SESSION['error'] = "ID du sport invalide.";
        header("Location: manage-sports.php");
        exit();
    } else {
        try {
            // Ajout : un sport utilisé par une épreuve ne peut pas être supprimé (clé étrangère EPREUVE.id_sport).
            // On compte d'abord ses épreuves, pour afficher un message clair au lieu d'une erreur SQL.
            $queryCheck = "SELECT COUNT(*) FROM EPREUVE WHERE id_sport = :id_sport";
            $statementCheck = $connexion->prepare($queryCheck);
            $statementCheck->bindParam(':id_sport', $id_sport, PDO::PARAM_INT);
            $statementCheck->execute();

            if ($statementCheck->fetchColumn() > 0) {
                $_SESSION['error'] = "Suppression impossible : ce sport est utilisé par au moins une épreuve.";
                header('Location: manage-sports.php');
                exit();
            }

            // Préparez la requête SQL pour supprimer le sport
            $sql = "DELETE FROM SPORT WHERE id_sport = :id_sport";
            // Exécutez la requête SQL avec le paramètre
            $statement = $connexion->prepare($sql);
            $statement->bindParam(':id_sport', $id_sport, PDO::PARAM_INT);
            $statement->execute();

            // Message de succès
            $_SESSION['success'] = "Le sport a été supprimé avec succès.";

            // Redirigez vers la page précédente après la suppression
            header('Location: manage-sports.php');
            exit();
        } catch (PDOException $e) {
            // Correction : le message SQL va dans le journal du serveur, pas à l'écran
            error_log("Erreur PDO : " . $e->getMessage());
            $_SESSION['error'] = "Erreur lors de la suppression du sport.";
            header('Location: manage-sports.php');
            exit();
        }
    }
}

// Afficher les erreurs en PHP (fonctionne à condition d’avoir activé l’option en local)
error_reporting(E_ALL);
ini_set("display_errors", 1);
?>
