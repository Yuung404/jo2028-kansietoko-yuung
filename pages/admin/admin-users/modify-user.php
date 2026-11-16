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

// Vérifiez si l'ID de l'administrateur est fourni dans l'URL
if (!isset($_GET['id_admin'])) {
    $_SESSION['error'] = "ID de l'administrateur manquant.";
    header("Location: manage-users.php");
    exit();
}

$id_admin = filter_input(INPUT_GET, 'id_admin', FILTER_VALIDATE_INT);

// Vérifiez si l'ID de l'administrateur est un entier valide
if (!$id_admin) {
    $_SESSION['error'] = "ID de l'administrateur invalide.";
    header("Location: manage-users.php");
    exit();
}

// Récupérez les informations de l'administrateur pour affichage dans le formulaire (jamais le mot de passe)
try {
    $queryAdmin = "SELECT nom_admin, prenom_admin, login FROM ADMINISTRATEUR WHERE id_admin = :id_admin";
    $statementAdmin = $connexion->prepare($queryAdmin);
    $statementAdmin->bindParam(":id_admin", $id_admin, PDO::PARAM_INT);
    $statementAdmin->execute();

    if ($statementAdmin->rowCount() > 0) {
        $admin = $statementAdmin->fetch(PDO::FETCH_ASSOC);
    } else {
        $_SESSION['error'] = "Administrateur non trouvé.";
        header("Location: manage-users.php");
        exit();
    }
} catch (PDOException $e) {
    error_log("Erreur PDO : " . $e->getMessage());
    $_SESSION['error'] = "Erreur lors de la récupération de l'administrateur.";
    header("Location: manage-users.php");
    exit();
}

// Vérifiez si le formulaire est soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Vérification du token CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['error'] = "Token CSRF invalide.";
        header("Location: modify-user.php?id_admin=$id_admin");
        exit();
    }

    // Récupération des champs
    $nomAdmin = trim((string) filter_input(INPUT_POST, 'nomAdmin'));
    $prenomAdmin = trim((string) filter_input(INPUT_POST, 'prenomAdmin'));
    $login = trim((string) filter_input(INPUT_POST, 'login'));
    // Mot de passe facultatif : laissé vide, l'ancien mot de passe est conservé
    $password = (string) filter_input(INPUT_POST, 'password');
    $confirmPassword = (string) filter_input(INPUT_POST, 'confirmPassword');

    // Vérifiez qu'aucun champ obligatoire n'est vide
    if (empty($nomAdmin) || empty($prenomAdmin) || empty($login)) {
        $_SESSION['error'] = "Le nom, le prénom et le login doivent être remplis.";
        header("Location: modify-user.php?id_admin=$id_admin");
        exit();
    }

    // Le login ne contient que des lettres sans accent, des chiffres, le point, le tiret et le tiret bas
    // (la page de connexion lit le login avec FILTER_SANITIZE_EMAIL, qui retirerait les autres caractères)
    if (!preg_match('/^[A-Za-z0-9._-]{3,50}$/', $login)) {
        $_SESSION['error'] = "Le login doit contenir 3 à 50 caractères : lettres sans accent, chiffres, point, tiret ou tiret bas.";
        header("Location: modify-user.php?id_admin=$id_admin");
        exit();
    }

    // Si un nouveau mot de passe est saisi, il doit respecter les mêmes règles qu'à la création
    if ($password !== '') {
        // Le mot de passe doit faire au moins 8 caractères et être confirmé à l'identique
        if (strlen($password) < 8) {
            $_SESSION['error'] = "Le mot de passe doit contenir au moins 8 caractères.";
            header("Location: modify-user.php?id_admin=$id_admin");
            exit();
        }
        if ($password !== $confirmPassword) {
            $_SESSION['error'] = "Les deux mots de passe ne sont pas identiques.";
            header("Location: modify-user.php?id_admin=$id_admin");
            exit();
        }
    }

    try {
        // Vérifiez si un autre administrateur utilise déjà ce login
        $queryCheck = "SELECT id_admin FROM ADMINISTRATEUR WHERE login = :login AND id_admin <> :id_admin";
        $statementCheck = $connexion->prepare($queryCheck);
        $statementCheck->bindParam(":login", $login, PDO::PARAM_STR);
        $statementCheck->bindParam(":id_admin", $id_admin, PDO::PARAM_INT);
        $statementCheck->execute();

        if ($statementCheck->rowCount() > 0) {
            $_SESSION['error'] = "Le login existe déjà.";
            header("Location: modify-user.php?id_admin=$id_admin");
            exit();
        }

        if ($password !== '') {
            // Nouveau mot de passe : il est haché avec Bcrypt, comme à la création
            $passwordHash = password_hash($password, PASSWORD_BCRYPT);
            $query = "UPDATE ADMINISTRATEUR SET nom_admin = :nomAdmin, prenom_admin = :prenomAdmin, login = :login, password = :password WHERE id_admin = :id_admin";
            $statement = $connexion->prepare($query);
            $statement->bindParam(":password", $passwordHash, PDO::PARAM_STR);
        } else {
            // Pas de nouveau mot de passe : la colonne password n'est pas modifiée
            $query = "UPDATE ADMINISTRATEUR SET nom_admin = :nomAdmin, prenom_admin = :prenomAdmin, login = :login WHERE id_admin = :id_admin";
            $statement = $connexion->prepare($query);
        }
        $statement->bindParam(":nomAdmin", $nomAdmin, PDO::PARAM_STR);
        $statement->bindParam(":prenomAdmin", $prenomAdmin, PDO::PARAM_STR);
        $statement->bindParam(":login", $login, PDO::PARAM_STR);
        $statement->bindParam(":id_admin", $id_admin, PDO::PARAM_INT);

        // Exécutez la requête
        if ($statement->execute()) {
            // Si l'administrateur modifie son propre compte, la session est mise à jour (message « Bonjour »)
            if ($id_admin === (int) $_SESSION['id_admin']) {
                $_SESSION['login'] = $login;
                $_SESSION['nom_admin'] = $nomAdmin;
                $_SESSION['prenom_admin'] = $prenomAdmin;
            }
            $_SESSION['success'] = "L'administrateur a été modifié avec succès.";
            header("Location: manage-users.php");
            exit();
        } else {
            $_SESSION['error'] = "Erreur lors de la modification de l'administrateur.";
            header("Location: modify-user.php?id_admin=$id_admin");
            exit();
        }
    } catch (PDOException $e) {
        error_log("Erreur PDO : " . $e->getMessage());
        $_SESSION['error'] = "Erreur lors de la modification de l'administrateur.";
        header("Location: modify-user.php?id_admin=$id_admin");
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
    <title>Modifier un Administrateur - Jeux Olympiques - Los Angeles 2028</title>
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
        <h1>Modifier un Administrateur</h1>
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

        <form action="modify-user.php?id_admin=<?php echo $id_admin; ?>" method="post"
            onsubmit="return confirm('Êtes-vous sûr de vouloir modifier cet administrateur?')">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <label for="nomAdmin">Nom :</label>
            <input type="text" name="nomAdmin" id="nomAdmin" maxlength="255"
                value="<?php echo htmlspecialchars($admin['nom_admin']); ?>" required>
            <label for="prenomAdmin">Prénom :</label>
            <input type="text" name="prenomAdmin" id="prenomAdmin" maxlength="255"
                value="<?php echo htmlspecialchars($admin['prenom_admin']); ?>" required>
            <label for="login">Login :</label>
            <input type="text" name="login" id="login" maxlength="50"
                value="<?php echo htmlspecialchars($admin['login']); ?>" required>
            <label for="password">Nouveau mot de passe (laisser vide pour le garder) :</label>
            <input type="password" name="password" id="password" minlength="8" autocomplete="new-password">
            <label for="confirmPassword">Confirmer le nouveau mot de passe :</label>
            <input type="password" name="confirmPassword" id="confirmPassword" minlength="8" autocomplete="new-password">
            <input type="submit" value="Modifier l'Administrateur">
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
