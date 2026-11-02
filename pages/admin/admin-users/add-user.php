<?php
/** Gestion des administrateurs : ajout (mot de passe haché avec Bcrypt). */
$racine = '../../../';
$titre = 'Ajouter un administrateur';
require_once __DIR__ . '/../../../includes/bootstrap.php';
require_admin($racine);

$nom = $prenom = $login = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash('error', 'Session expirée ou requête invalide. Veuillez réessayer.');
        redirect('add-user.php');
    }

    $nom          = post_text('nom_admin');
    $prenom       = post_text('prenom_admin');
    $login        = post_text('login');
    // Le mot de passe n'est pas « nettoyé » : chaque caractère compte, il n'est ni affiché ni inséré dans le SQL
    $motDePasse   = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    $confirmation = is_string($_POST['password_confirm'] ?? null) ? $_POST['password_confirm'] : '';

    $erreur = valider_texte($nom, 'Le nom')
        ?? valider_texte($prenom, 'Le prénom')
        ?? valider_login($login)
        ?? valider_mot_de_passe($motDePasse, $confirmation);

    if ($erreur === null) {
        $stmt = $connexion->prepare('SELECT COUNT(*) FROM ADMINISTRATEUR WHERE login = :login');
        $stmt->execute([':login' => $login]);
        if ($stmt->fetchColumn() > 0) {
            $erreur = 'Ce login est déjà utilisé.';
        }
    }

    if ($erreur === null) {
        try {
            $stmt = $connexion->prepare(
                'INSERT INTO ADMINISTRATEUR (nom_admin, prenom_admin, login, password)
                 VALUES (:nom, :prenom, :login, :password)'
            );
            $stmt->execute([
                ':nom'      => $nom,
                ':prenom'   => $prenom,
                ':login'    => $login,
                ':password' => password_hash($motDePasse, PASSWORD_BCRYPT), // seule l'empreinte est stockée
            ]);
            flash('success', "L'administrateur « $login » a été créé.");
            redirect('manage-users.php');
        } catch (PDOException $e) {
            if (!est_doublon($e)) {
                throw $e;
            }
            $erreur = 'Ce login est déjà utilisé.';
        }
    }

    flash('error', $erreur);
}

require __DIR__ . '/../../../includes/head.php';
require __DIR__ . '/../../../includes/nav-admin.php';
?>
    <main>
        <h1>Ajouter un administrateur</h1>
        <?php show_flash(); ?>
        <form action="add-user.php" method="post" autocomplete="off">
            <?= csrf_field() ?>
            <label for="nom_admin">Nom :</label>
            <input type="text" name="nom_admin" id="nom_admin" maxlength="255" value="<?= e($nom) ?>" required>
            <label for="prenom_admin">Prénom :</label>
            <input type="text" name="prenom_admin" id="prenom_admin" maxlength="255" value="<?= e($prenom) ?>" required>
            <label for="login">Login :</label>
            <input type="text" name="login" id="login" maxlength="50" pattern="[A-Za-z0-9._\-]{3,50}" value="<?= e($login) ?>" required>
            <small class="field-hint">3 à 50 caractères : lettres sans accent, chiffres, . _ -</small>
            <label for="password">Mot de passe :</label>
            <input type="password" name="password" id="password" minlength="8" autocomplete="new-password" required>
            <small class="field-hint">8 caractères minimum</small>
            <label for="password_confirm">Confirmation du mot de passe :</label>
            <input type="password" name="password_confirm" id="password_confirm" minlength="8" autocomplete="new-password" required>
            <input type="submit" value="Créer l'administrateur">
        </form>
        <p class="paragraph-link"><a class="link-home" href="manage-users.php">Retour à la gestion des administrateurs</a></p>
    </main>
<?php require __DIR__ . '/../../../includes/footer.php'; ?>
