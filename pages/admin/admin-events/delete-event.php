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
    header('Location: manage-events.php');
    exit();
}

// Vérifiez si l'ID de l'épreuve est fourni
if (!isset($_POST['id_epreuve'])) {
    $_SESSION['error'] = "ID de l'épreuve manquant.";
    header("Location: manage-events.php");
    exit();
} else {
    $id_epreuve = filter_input(INPUT_POST, 'id_epreuve', FILTER_VALIDATE_INT);

    // Vérifiez si l'ID de l'épreuve est un entier valide
    if ($id_epreuve === false || $id_epreuve === null) {
        $_SESSION['error'] = "ID de l'épreuve invalide.";
        header("Location: manage-events.php");
        exit();
    } else {
        try {
            // Vérifiez que l'épreuve n'est pas utilisée ailleurs (clé étrangère PARTICIPER.id_epreuve).
            // On compte d'abord, pour afficher un message clair au lieu d'une erreur SQL de clé étrangère.
            $queryCheck = "SELECT COUNT(*) FROM PARTICIPER WHERE id_epreuve = :id_epreuve";
            $statementCheck = $connexion->prepare($queryCheck);
            $statementCheck->bindParam(':id_epreuve', $id_epreuve, PDO::PARAM_INT);
            $statementCheck->execute();

            if ($statementCheck->fetchColumn() > 0) {
                $_SESSION['error'] = "Suppression impossible : cette épreuve a au moins un résultat. Supprimez d'abord ses résultats.";
                header('Location: manage-events.php');
                exit();
            }

            // Préparez la requête SQL pour supprimer l'épreuve
            $sql = "DELETE FROM EPREUVE WHERE id_epreuve = :id_epreuve";
            // Exécutez la requête SQL avec le paramètre
            $statement = $connexion->prepare($sql);
            $statement->bindParam(':id_epreuve', $id_epreuve, PDO::PARAM_INT);
            $statement->execute();

            // Message de succès
            $_SESSION['success'] = "L'épreuve a été supprimée avec succès.";

            // Redirigez vers la liste après la suppression
            header('Location: manage-events.php');
            exit();
        } catch (PDOException $e) {
            // Le message SQL va dans le journal du serveur, pas à l'écran
            error_log("Erreur PDO : " . $e->getMessage());
            $_SESSION['error'] = "Erreur lors de la suppression de l'épreuve.";
            header('Location: manage-events.php');
            exit();
        }
    }
}

// Afficher les erreurs en PHP (fonctionne à condition d’avoir activé l’option en local)
error_reporting(E_ALL);
ini_set("display_errors", 1);
?>
