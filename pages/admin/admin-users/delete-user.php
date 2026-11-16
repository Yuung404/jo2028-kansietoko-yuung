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
    header('Location: manage-users.php');
    exit();
}

// Vérifiez si l'ID de l'administrateur est fourni
if (!isset($_POST['id_admin'])) {
    $_SESSION['error'] = "ID de l'administrateur manquant.";
    header("Location: manage-users.php");
    exit();
} else {
    $id_admin = filter_input(INPUT_POST, 'id_admin', FILTER_VALIDATE_INT);

    // Vérifiez si l'ID de l'administrateur est un entier valide
    if ($id_admin === false || $id_admin === null) {
        $_SESSION['error'] = "ID de l'administrateur invalide.";
        header("Location: manage-users.php");
        exit();
    }

    // Un administrateur ne peut pas supprimer son propre compte
    if ($id_admin === (int) $_SESSION['id_admin']) {
        $_SESSION['error'] = "Vous ne pouvez pas supprimer votre propre compte.";
        header("Location: manage-users.php");
        exit();
    }

    try {
        // On garde toujours au moins un administrateur, sinon plus personne ne pourrait se connecter
        $queryCheck = "SELECT COUNT(*) FROM ADMINISTRATEUR";
        $statementCheck = $connexion->prepare($queryCheck);
        $statementCheck->execute();

        if ($statementCheck->fetchColumn() <= 1) {
            $_SESSION['error'] = "Suppression impossible : il doit rester au moins un administrateur.";
            header('Location: manage-users.php');
            exit();
        }

        // Préparez la requête SQL pour supprimer l'administrateur
        $sql = "DELETE FROM ADMINISTRATEUR WHERE id_admin = :id_admin";
        // Exécutez la requête SQL avec le paramètre
        $statement = $connexion->prepare($sql);
        $statement->bindParam(':id_admin', $id_admin, PDO::PARAM_INT);
        $statement->execute();

        // Message de succès
        $_SESSION['success'] = "L'administrateur a été supprimé avec succès.";

        // Redirigez vers la liste après la suppression
        header('Location: manage-users.php');
        exit();
    } catch (PDOException $e) {
        // Le message SQL va dans le journal du serveur, pas à l'écran
        error_log("Erreur PDO : " . $e->getMessage());
        $_SESSION['error'] = "Erreur lors de la suppression de l'administrateur.";
        header('Location: manage-users.php');
        exit();
    }
}

// Afficher les erreurs en PHP (fonctionne à condition d’avoir activé l’option en local)
error_reporting(E_ALL);
ini_set("display_errors", 1);
?>
