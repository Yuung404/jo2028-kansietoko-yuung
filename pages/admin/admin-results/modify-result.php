<?php
session_start();
require_once("../../../database/database.php");

// Vérifiez si l'utilisateur est connecté
if (!isset($_SESSION['login'])) {
    header('Location: ../../../index.php');
    exit();
}

// Générer un token CSRF si ce n'est pas déjà fait
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Génère un token CSRF sécurisé
}

// Un résultat est identifié par deux numéros dans l'URL : l'athlète et l'épreuve
if (!isset($_GET['id_athlete'], $_GET['id_epreuve'])) {
    $_SESSION['error'] = "ID du résultat manquant.";
    header("Location: manage-results.php");
    exit();
}

$id_athlete = filter_input(INPUT_GET, 'id_athlete', FILTER_VALIDATE_INT);
$id_epreuve = filter_input(INPUT_GET, 'id_epreuve', FILTER_VALIDATE_INT);

// Vérifiez si les deux ID sont des entiers valides
if (!$id_athlete || !$id_epreuve) {
    $_SESSION['error'] = "ID du résultat invalide.";
    header("Location: manage-results.php");
    exit();
}

// Récupérez le résultat, avec le nom de l'athlète et de l'épreuve pour l'affichage
try {
    $queryResultat = "SELECT part.resultat, at.nom_athlete, at.prenom_athlete, ep.nom_epreuve
    FROM PARTICIPER part
    INNER JOIN ATHLETE at ON part.id_athlete = at.id_athlete
    INNER JOIN EPREUVE ep ON part.id_epreuve = ep.id_epreuve
    WHERE part.id_athlete = :id_athlete AND part.id_epreuve = :id_epreuve";
    $statementResultat = $connexion->prepare($queryResultat);
    $statementResultat->bindParam(":id_athlete", $id_athlete, PDO::PARAM_INT);
    $statementResultat->bindParam(":id_epreuve", $id_epreuve, PDO::PARAM_INT);
    $statementResultat->execute();

    if ($statementResultat->rowCount() > 0) {
        $participation = $statementResultat->fetch(PDO::FETCH_ASSOC);
    } else {
        $_SESSION['error'] = "Résultat non trouvé.";
        header("Location: manage-results.php");
        exit();
    }
} catch (PDOException $e) {
    error_log("Erreur PDO : " . $e->getMessage());
    $_SESSION['error'] = "Erreur lors de la récupération du résultat.";
    header("Location: manage-results.php");
    exit();
}

// Vérifiez si le formulaire est soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Vérification du token CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['error'] = "Token CSRF invalide.";
        header("Location: modify-result.php?id_athlete=$id_athlete&id_epreuve=$id_epreuve");
        exit();
    }

    // Seul le résultat se modifie : l'athlète et l'épreuve forment la clé primaire.
    // Pour changer d'athlète ou d'épreuve, on supprime ce résultat et on en ajoute un autre.
    $resultat = trim((string) filter_input(INPUT_POST, 'resultat'));

    // Vérifiez si le résultat est vide
    if ($resultat === '') {
        $_SESSION['error'] = "Le résultat ne peut pas être vide.";
        header("Location: modify-result.php?id_athlete=$id_athlete&id_epreuve=$id_epreuve");
        exit();
    }

    try {
        // Requête pour mettre à jour le résultat
        $query = "UPDATE PARTICIPER SET resultat = :resultat WHERE id_athlete = :id_athlete AND id_epreuve = :id_epreuve";
        $statement = $connexion->prepare($query);
        $statement->bindParam(":resultat", $resultat, PDO::PARAM_STR);
        $statement->bindParam(":id_athlete", $id_athlete, PDO::PARAM_INT);
        $statement->bindParam(":id_epreuve", $id_epreuve, PDO::PARAM_INT);

        // Exécutez la requête
        if ($statement->execute()) {
            $_SESSION['success'] = "Le résultat a été modifié avec succès.";
            header("Location: manage-results.php");
            exit();
        } else {
            $_SESSION['error'] = "Erreur lors de la modification du résultat.";
            header("Location: modify-result.php?id_athlete=$id_athlete&id_epreuve=$id_epreuve");
            exit();
        }
    } catch (PDOException $e) {
        error_log("Erreur PDO : " . $e->getMessage());
        $_SESSION['error'] = "Erreur lors de la modification du résultat.";
        header("Location: modify-result.php?id_athlete=$id_athlete&id_epreuve=$id_epreuve");
        exit();
    }
}

// Afficher les erreurs en PHP (fonctionne à condition d’avoir activé l’option en local)
error_reporting(E_ALL);
ini_set("display_errors", 1);
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../../css/normalize.css">
    <link rel="stylesheet" href="../../../css/styles-computer.css">
    <link rel="stylesheet" href="../../../css/styles-responsive.css">
    <link rel="shortcut icon" href="../../../img/favicon.ico" type="image/x-icon">
    <title>Modifier un Résultat - Jeux Olympiques - Los Angeles 2028</title>
</head>

<body>
    <header>
        <nav>
            <!-- Menu de l'administration -->
            <ul class="menu">
                <li><a href="../admin.php">Accueil Administration</a></li>
                <li><a href="../admin-users/manage-users.php">Gestion Administrateurs</a></li>
                <li><a href="../admin-sports/manage-sports.php">Gestion Sports</a></li>
                <li><a href="../admin-places/manage-places.php">Gestion Lieux</a></li>
                <li><a href="../admin-countries/manage-countries.php">Gestion Pays</a></li>
                <li><a href="../admin-events/manage-events.php">Gestion Calendrier</a></li>
                <li><a href="../admin-athletes/manage-athletes.php">Gestion Athlètes</a></li>
                <li><a href="../admin-genders/manage-genders.php">Gestion Genres</a></li>
                <li><a href="../admin-results/manage-results.php">Gestion Résultats</a></li>
                <li><a href="../../logout.php">Déconnexion</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <h1>Modifier un Résultat</h1>
        <?php
        // Affichage des messages d'erreur ou de succès
        if (isset($_SESSION['error'])) {
            echo '<p style="color: red;">' . htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8') . '</p>';
            unset($_SESSION['error']);
        }
        if (isset($_SESSION['success'])) {
            echo '<p style="color: green;">' . htmlspecialchars($_SESSION['success'], ENT_QUOTES, 'UTF-8') . '</p>';
            unset($_SESSION['success']);
        }
        ?>

        <p>
            Athlète : <?php echo htmlspecialchars($participation['prenom_athlete'] . " " . $participation['nom_athlete']); ?><br>
            Épreuve : <?php echo htmlspecialchars($participation['nom_epreuve']); ?>
        </p>

        <form action="modify-result.php?id_athlete=<?php echo $id_athlete; ?>&amp;id_epreuve=<?php echo $id_epreuve; ?>" method="post"
            onsubmit="return confirm('Êtes-vous sûr de vouloir modifier ce résultat?')">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <label for="resultat">Résultat :</label>
            <input type="text" name="resultat" id="resultat" maxlength="100"
                value="<?php echo htmlspecialchars($participation['resultat']); ?>" required>
            <input type="submit" value="Modifier le Résultat">
        </form>

        <p class="paragraph-link">
            <a class="link-home" href="manage-results.php">Retour à la gestion des résultats</a>
        </p>
    </main>

    <footer>
        <figure>
            <img src="../../../img/logo-jo.png" alt="logo Jeux Olympiques - Los Angeles 2028">
        </figure>
    </footer>
</body>

</html>
