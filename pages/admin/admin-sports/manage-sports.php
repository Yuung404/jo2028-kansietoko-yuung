<?php
session_start();

// Vérifiez si l'utilisateur est connecté
if (!isset($_SESSION['login'])) {
    header('Location: ../../../index.php');
    exit();
}

$login = $_SESSION['login'];
$nom_utilisateur = $_SESSION['prenom_admin'];
$prenom_utilisateur = $_SESSION['nom_admin'];

// Fonction pour vérifier le token CSRF
function checkCSRFToken() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            die('Token CSRF invalide.');
        }
    }
}

// Générer un token CSRF si ce n'est pas déjà fait
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Génère un token CSRF
}
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
    <title>Liste des Sports - Jeux Olympiques - Los Angeles 2028</title>
</head>

<body>
    <header>
        <nav>
            <!-- Correction : liens vers le dossier de chaque rubrique (ils pointaient tous vers admin-sports),
                 ajout des rubriques Administrateurs et Genres (questions 13 et 18) -->
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
        <h1>Liste des Sports</h1>
        <?php
        // Ajout : affichage des messages envoyés par les pages d'ajout, de modification et de suppression
        // (le code fourni les enregistrait dans la session, mais cette page ne les affichait jamais)
        if (isset($_SESSION['error'])) {
            echo '<p style="color: red;">' . htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8') . '</p>';
            unset($_SESSION['error']);
        }
        if (isset($_SESSION['success'])) {
            echo '<p style="color: green;">' . htmlspecialchars($_SESSION['success'], ENT_QUOTES, 'UTF-8') . '</p>';
            unset($_SESSION['success']);
        }
        ?>
        <div class="action-buttons">
            <button onclick="openAddSportForm()">Ajouter un Sport</button>
        </div>
        
        <!-- Tableau des sports -->
        <?php
        require_once("../../../database/database.php");

        try {
            // Requête pour récupérer la liste des sports depuis la base de données
            $query = "SELECT * FROM SPORT ORDER BY nom_sport";
            $statement = $connexion->prepare($query);
            $statement->execute();

            // Vérifier s'il y a des résultats
            if ($statement->rowCount() > 0) {
                echo "<table><tr><th>Sport</th><th>Modifier</th><th>Supprimer</th></tr>";

                // Afficher les données dans un tableau
                while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['nom_sport'], ENT_QUOTES, 'UTF-8') . "</td>";
                    echo "<td><button onclick='openModifySportForm({$row['id_sport']})'>Modifier</button></td>";
                    echo "<td><button onclick='deleteSportConfirmation({$row['id_sport']})'>Supprimer</button></td>";
                    echo "</tr>";
                }

                echo "</table>";
            } else {
                echo "<p>Aucun sport trouvé.</p>";
            }
        } catch (PDOException $e) {
            // Correction : message générique pour le visiteur, détail technique dans le journal du serveur
            // (comme dans les pages publiques sports.php, events.php et results.php)
            echo "<p style='color: red;'>Erreur : Impossible de récupérer les sports. Veuillez réessayer plus tard.</p>";
            error_log("Erreur PDO : " . $e->getMessage());
        }
        ?>
        
        <!-- Correction : formulaire caché, envoyé par deleteSportConfirmation() pour supprimer en POST avec le token CSRF -->
        <form id="form-supprimer" action="delete-sport.php" method="post" style="display: none;">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <input type="hidden" name="id_sport" id="id_sport_supprimer">
        </form>

        <p class="paragraph-link">
            <a class="link-home" href="../admin.php">Accueil administration</a>
        </p>
    </main>

    <footer>
        <figure>
            <img src="../../../img/logo-jo.png" alt="logo Jeux Olympiques - Los Angeles 2028">
        </figure>
    </footer>

    <script>
        function openAddSportForm() {
            window.location.href = 'add-sport.php';
        }

        function openModifySportForm(id_sport) {
            window.location.href = 'modify-sport.php?id_sport=' + id_sport;
        }

        function deleteSportConfirmation(id_sport) {
            if (confirm("Êtes-vous sûr de vouloir supprimer ce sport?")) {
                // Correction : envoi en POST du formulaire caché qui contient le token CSRF,
                // au lieu de window.location.href = 'delete-sport.php?id_sport=' + id_sport (un lien GET, sans protection)
                document.getElementById('id_sport_supprimer').value = id_sport;
                document.getElementById('form-supprimer').submit();
            }
        }
    </script>
</body>

</html>