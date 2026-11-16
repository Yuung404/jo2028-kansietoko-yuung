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

// Vérifiez si le formulaire est soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Récupération des champs : le texte est gardé tel qu'il a été tapé, sans les espaces autour (trim).
    // La protection XSS se fait à l'affichage, avec htmlspecialchars().
    $nomEpreuve = trim((string) filter_input(INPUT_POST, 'nomEpreuve'));
    $idSport = filter_input(INPUT_POST, 'idSport', FILTER_VALIDATE_INT);
    $dateEpreuve = trim((string) filter_input(INPUT_POST, 'dateEpreuve'));
    $heureEpreuve = trim((string) filter_input(INPUT_POST, 'heureEpreuve'));
    $idLieu = filter_input(INPUT_POST, 'idLieu', FILTER_VALIDATE_INT);

    // Vérification du token CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['error'] = "Token CSRF invalide.";
        header("Location: add-event.php");
        exit();
    }

    // Vérifiez qu'aucun champ n'est vide (une liste déroulante non choisie compte comme vide)
    if (empty($nomEpreuve) || empty($idSport) || empty($dateEpreuve) || empty($heureEpreuve) || empty($idLieu)) {
        $_SESSION['error'] = "Tous les champs doivent être remplis.";
        header("Location: add-event.php");
        exit();
    }

    // La date doit exister dans le calendrier (le 31/02 est refusé)
    $date = DateTime::createFromFormat('Y-m-d', $dateEpreuve);
    if (!$date || $date->format('Y-m-d') !== $dateEpreuve) {
        $_SESSION['error'] = "La date de l'épreuve est invalide.";
        header("Location: add-event.php");
        exit();
    }

    // L'heure doit être au format HH:MM (certains navigateurs ajoutent les secondes)
    $heureEpreuve = substr($heureEpreuve, 0, 5);
    if (!preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9]$/', $heureEpreuve)) {
        $_SESSION['error'] = "L'heure de l'épreuve est invalide.";
        header("Location: add-event.php");
        exit();
    }

    try {
        // Vérifiez si l'épreuve existe déjà (pas de doublon : deux épreuves ne peuvent pas avoir lieu au même endroit, à la même date et à la même heure)
        $queryCheck = "SELECT id_epreuve FROM EPREUVE WHERE date_epreuve = :dateEpreuve AND heure_epreuve = :heureEpreuve AND id_lieu = :idLieu";
        $statementCheck = $connexion->prepare($queryCheck);
        $statementCheck->bindParam(":dateEpreuve", $dateEpreuve, PDO::PARAM_STR);
        $statementCheck->bindParam(":heureEpreuve", $heureEpreuve, PDO::PARAM_STR);
        $statementCheck->bindParam(":idLieu", $idLieu, PDO::PARAM_INT);
        $statementCheck->execute();

        if ($statementCheck->rowCount() > 0) {
            $_SESSION['error'] = "Une épreuve a déjà lieu à cet endroit, à cette date et à cette heure.";
            header("Location: add-event.php");
            exit();
        } else {
            // Requête pour ajouter une épreuve (requête préparée : les valeurs sont envoyées à part, pas d'injection SQL)
            $query = "INSERT INTO EPREUVE (nom_epreuve, id_sport, date_epreuve, heure_epreuve, id_lieu) VALUES (:nomEpreuve, :idSport, :dateEpreuve, :heureEpreuve, :idLieu)";
            $statement = $connexion->prepare($query);
            $statement->bindParam(":nomEpreuve", $nomEpreuve, PDO::PARAM_STR);
            $statement->bindParam(":idSport", $idSport, PDO::PARAM_INT);
            $statement->bindParam(":dateEpreuve", $dateEpreuve, PDO::PARAM_STR);
            $statement->bindParam(":heureEpreuve", $heureEpreuve, PDO::PARAM_STR);
            $statement->bindParam(":idLieu", $idLieu, PDO::PARAM_INT);

            // Exécutez la requête
            if ($statement->execute()) {
                $_SESSION['success'] = "L'épreuve a été ajoutée avec succès.";
                header("Location: manage-events.php");
                exit();
            } else {
                $_SESSION['error'] = "Erreur lors de l'ajout de l'épreuve.";
                header("Location: add-event.php");
                exit();
            }
        }
    } catch (PDOException $e) {
        // Le message SQL va dans le journal du serveur, pas à l'écran
        error_log("Erreur PDO : " . $e->getMessage());
        $_SESSION['error'] = "Erreur lors de l'ajout de l'épreuve.";
        header("Location: add-event.php");
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
    <title>Ajouter une Épreuve - Jeux Olympiques - Los Angeles 2028</title>
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
        <h1>Ajouter une Épreuve</h1>
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
        <form action="add-event.php" method="post" onsubmit="return confirm('Êtes-vous sûr de vouloir ajouter cette épreuve ?')">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <label for="nomEpreuve">Nom de l'Épreuve :</label>
            <input type="text" name="nomEpreuve" id="nomEpreuve" maxlength="255" required>
            <label for="idSport">Sport :</label>
            <select name="idSport" id="idSport" required>
                <option value="">-- Choisir un sport --</option>
                <?php
                // Liste déroulante : les sports de la table SPORT
                $statementSport = $connexion->query("SELECT id_sport, nom_sport FROM SPORT ORDER BY nom_sport");
                while ($sport = $statementSport->fetch(PDO::FETCH_ASSOC)) {
                    echo "<option value='" . (int) $sport['id_sport'] . "'" . ">" . htmlspecialchars($sport['nom_sport'], ENT_QUOTES, 'UTF-8') . "</option>";
                }
                ?>
            </select>
            <label for="dateEpreuve">Date :</label>
            <input type="date" name="dateEpreuve" id="dateEpreuve" required>
            <label for="heureEpreuve">Heure :</label>
            <input type="time" name="heureEpreuve" id="heureEpreuve" required>
            <label for="idLieu">Lieu :</label>
            <select name="idLieu" id="idLieu" required>
                <option value="">-- Choisir un lieu --</option>
                <?php
                // Liste déroulante : les lieux de la table LIEU
                $statementLieu = $connexion->query("SELECT id_lieu, nom_lieu FROM LIEU ORDER BY nom_lieu");
                while ($lieu = $statementLieu->fetch(PDO::FETCH_ASSOC)) {
                    echo "<option value='" . (int) $lieu['id_lieu'] . "'" . ">" . htmlspecialchars($lieu['nom_lieu'], ENT_QUOTES, 'UTF-8') . "</option>";
                }
                ?>
            </select>
            <input type="submit" value="Ajouter l'Épreuve">
        </form>
        <p class="paragraph-link">
            <a class="link-home" href="manage-events.php">Retour à la gestion des épreuves</a>
        </p>
    </main>

    <footer>
        <figure>
            <img src="../../../img/logo-jo.png" alt="logo Jeux Olympiques - Los Angeles 2028">
        </figure>
    </footer>

</body>

</html>
