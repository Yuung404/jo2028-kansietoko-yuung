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
    // Récupération des champs (texte gardé tel qu'il a été tapé, sans les espaces autour)
    $nomAdmin = trim((string) filter_input(INPUT_POST, 'nomAdmin'));
    $prenomAdmin = trim((string) filter_input(INPUT_POST, 'prenomAdmin'));
    $login = trim((string) filter_input(INPUT_POST, 'login'));
    // Le mot de passe est lu tel quel (un espace en fait partie) : il n'est jamais affiché ni stocké en clair
    $password = (string) filter_input(INPUT_POST, 'password');
    $confirmPassword = (string) filter_input(INPUT_POST, 'confirmPassword');

    // Vérification du token CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['error'] = "Token CSRF invalide.";
        header("Location: add-user.php");
        exit();
    }

    // Vérifiez qu'aucun champ n'est vide
    if (empty($nomAdmin) || empty($prenomAdmin) || empty($login) || $password === '') {
        $_SESSION['error'] = "Tous les champs doivent être remplis.";
        header("Location: add-user.php");
        exit();
    }

    // Le login ne contient que des lettres sans accent, des chiffres, le point, le tiret et le tiret bas
    // (la page de connexion lit le login avec FILTER_SANITIZE_EMAIL, qui retirerait les autres caractères)
    if (!preg_match('/^[A-Za-z0-9._-]{3,50}$/', $login)) {
        $_SESSION['error'] = "Le login doit contenir 3 à 50 caractères : lettres sans accent, chiffres, point, tiret ou tiret bas.";
        header("Location: add-user.php");
        exit();
    }

    // Le mot de passe doit faire au moins 8 caractères et être confirmé à l'identique
    if (strlen($password) < 8) {
        $_SESSION['error'] = "Le mot de passe doit contenir au moins 8 caractères.";
        header("Location: add-user.php");
        exit();
    }
    if ($password !== $confirmPassword) {
        $_SESSION['error'] = "Les deux mots de passe ne sont pas identiques.";
        header("Location: add-user.php");
        exit();
    }

    try {
        // Vérifiez si le login existe déjà (deux administrateurs ne peuvent pas avoir le même login)
        $queryCheck = "SELECT id_admin FROM ADMINISTRATEUR WHERE login = :login";
        $statementCheck = $connexion->prepare($queryCheck);
        $statementCheck->bindParam(":login", $login, PDO::PARAM_STR);
        $statementCheck->execute();

        if ($statementCheck->rowCount() > 0) {
            $_SESSION['error'] = "Le login existe déjà.";
            header("Location: add-user.php");
            exit();
        } else {
            // Question 13 : le mot de passe est haché avec Bcrypt avant d'être enregistré.
            // password_hash() ajoute un sel aléatoire : on obtient une empreinte de 60 caractères qui commence par $2y$.
            $passwordHash = password_hash($password, PASSWORD_BCRYPT);

            // Requête pour ajouter un administrateur
            $query = "INSERT INTO ADMINISTRATEUR (nom_admin, prenom_admin, login, password) VALUES (:nomAdmin, :prenomAdmin, :login, :password)";
            $statement = $connexion->prepare($query);
            $statement->bindParam(":nomAdmin", $nomAdmin, PDO::PARAM_STR);
            $statement->bindParam(":prenomAdmin", $prenomAdmin, PDO::PARAM_STR);
            $statement->bindParam(":login", $login, PDO::PARAM_STR);
            $statement->bindParam(":password", $passwordHash, PDO::PARAM_STR);

            // Exécutez la requête
            if ($statement->execute()) {
                $_SESSION['success'] = "L'administrateur a été ajouté avec succès.";
                header("Location: manage-users.php");
                exit();
            } else {
                $_SESSION['error'] = "Erreur lors de l'ajout de l'administrateur.";
                header("Location: add-user.php");
                exit();
            }
        }
    } catch (PDOException $e) {
        // Le message SQL va dans le journal du serveur, pas à l'écran
        error_log("Erreur PDO : " . $e->getMessage());
        $_SESSION['error'] = "Erreur lors de l'ajout de l'administrateur.";
        header("Location: add-user.php");
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
    <title>Ajouter un Administrateur - Jeux Olympiques - Los Angeles 2028</title>
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
        <h1>Ajouter un Administrateur</h1>
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
        <form action="add-user.php" method="post" onsubmit="return confirm('Êtes-vous sûr de vouloir ajouter cet administrateur ?')">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <label for="nomAdmin">Nom :</label>
            <input type="text" name="nomAdmin" id="nomAdmin" maxlength="255" required>
            <label for="prenomAdmin">Prénom :</label>
            <input type="text" name="prenomAdmin" id="prenomAdmin" maxlength="255" required>
            <label for="login">Login :</label>
            <input type="text" name="login" id="login" maxlength="50" required>
            <label for="password">Mot de passe (8 caractères minimum) :</label>
            <input type="password" name="password" id="password" minlength="8" autocomplete="new-password" required>
            <label for="confirmPassword">Confirmer le mot de passe :</label>
            <input type="password" name="confirmPassword" id="confirmPassword" minlength="8" autocomplete="new-password" required>
            <input type="submit" value="Ajouter l'Administrateur">
        </form>
        <p class="paragraph-link">
            <a class="link-home" href="manage-users.php">Retour à la gestion des administrateurs</a>
        </p>
    </main>

    <footer>
        <figure>
            <img src="../../../img/logo-jo.png" alt="logo Jeux Olympiques - Los Angeles 2028">
        </figure>
    </footer>

</body>

</html>
