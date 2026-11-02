<?php
/**
 * Gestion des administrateurs : modification.
 * Le mot de passe n'est changé que si un nouveau est saisi ; il est alors haché avec Bcrypt.
 */
$racine = '../../../';
$titre = 'Modifier un administrateur';
require_once __DIR__ . '/../../../includes/bootstrap.php';
require_admin($racine);

$idAdmin = input_id(INPUT_GET, 'id_admin');
if ($idAdmin === null) {
    flash('error', 'Identifiant d\'administrateur invalide.');
    redirect('manage-users.php');
}

$stmt = $connexion->prepare('SELECT nom_admin, prenom_admin, login FROM ADMINISTRATEUR WHERE id_admin = :id');
$stmt->execute([':id' => $idAdmin]);
$admin = $stmt->fetch();
if (!$admin) {
    flash('error', 'Administrateur introuvable.');
    redirect('manage-users.php');
}
$nom    = $admin['nom_admin'];
$prenom = $admin['prenom_admin'];
$login  = $admin['login'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash('error', 'Session expirée ou requête invalide. Veuillez réessayer.');
        redirect("modify-user.php?id_admin=$idAdmin");
    }

    $nom          = post_text('nom_admin');
    $prenom       = post_text('prenom_admin');
    $login        = post_text('login');
    $motDePasse   = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    $confirmation = is_string($_POST['password_confirm'] ?? null) ? $_POST['password_confirm'] : '';
    $changerMdp   = $motDePasse !== '' || $confirmation !== '';

    $erreur = valider_texte($nom, 'Le nom')
        ?? valider_texte($prenom, 'Le prénom')
        ?? valider_login($login)
        ?? ($changerMdp ? valider_mot_de_passe($motDePasse, $confirmation) : null);

    if ($erreur === null) {
        $stmt = $connexion->prepare('SELECT COUNT(*) FROM ADMINISTRATEUR WHERE login = :login AND id_admin <> :id');
        $stmt->execute([':login' => $login, ':id' => $idAdmin]);
        if ($stmt->fetchColumn() > 0) {
            $erreur = 'Ce login est déjà utilisé par un autre administrateur.';
        }
    }

    if ($erreur === null) {
        try {
            if ($changerMdp) {
                $stmt = $connexion->prepare(
                    'UPDATE ADMINISTRATEUR
                     SET nom_admin = :nom, prenom_admin = :prenom, login = :login, password = :password
                     WHERE id_admin = :id'
                );
                $stmt->execute([
                    ':nom' => $nom, ':prenom' => $prenom, ':login' => $login, ':id' => $idAdmin,
                    ':password' => password_hash($motDePasse, PASSWORD_BCRYPT),
                ]);
            } else {
                $stmt = $connexion->prepare(
                    'UPDATE ADMINISTRATEUR SET nom_admin = :nom, prenom_admin = :prenom, login = :login WHERE id_admin = :id'
                );
                $stmt->execute([':nom' => $nom, ':prenom' => $prenom, ':login' => $login, ':id' => $idAdmin]);
            }

            // L'administrateur modifie son propre compte : la session est mise à jour
            if ($idAdmin === $_SESSION['id_admin']) {
                $_SESSION['nom_admin']    = $nom;
                $_SESSION['prenom_admin'] = $prenom;
                $_SESSION['login']        = $login;
            }

            flash('success', "L'administrateur « $login » a été modifié" . ($changerMdp ? ' (nouveau mot de passe enregistré).' : '.'));
            redirect('manage-users.php');
        } catch (PDOException $e) {
            if (!est_doublon($e)) {
                throw $e;
            }
            $erreur = 'Ce login est déjà utilisé par un autre administrateur.';
        }
    }

    flash('error', $erreur);
}

require __DIR__ . '/../../../includes/head.php';
require __DIR__ . '/../../../includes/nav-admin.php';
?>
    <main>
        <h1>Modifier un administrateur</h1>
        <?php show_flash(); ?>
        <form action="modify-user.php?id_admin=<?= $idAdmin ?>" method="post" autocomplete="off">
            <?= csrf_field() ?>
            <label for="nom_admin">Nom :</label>
            <input type="text" name="nom_admin" id="nom_admin" maxlength="255" value="<?= e($nom) ?>" required>
            <label for="prenom_admin">Prénom :</label>
            <input type="text" name="prenom_admin" id="prenom_admin" maxlength="255" value="<?= e($prenom) ?>" required>
            <label for="login">Login :</label>
            <input type="text" name="login" id="login" maxlength="50" pattern="[A-Za-z0-9._\-]{3,50}" value="<?= e($login) ?>" required>
            <label for="password">Nouveau mot de passe :</label>
            <input type="password" name="password" id="password" minlength="8" autocomplete="new-password">
            <small class="field-hint">Laisser vide pour conserver le mot de passe actuel</small>
            <label for="password_confirm">Confirmation du nouveau mot de passe :</label>
            <input type="password" name="password_confirm" id="password_confirm" minlength="8" autocomplete="new-password">
            <input type="submit" value="Enregistrer les modifications">
        </form>
        <p class="paragraph-link"><a class="link-home" href="manage-users.php">Retour à la gestion des administrateurs</a></p>
    </main>
<?php require __DIR__ . '/../../../includes/footer.php'; ?>
