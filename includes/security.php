<?php
/**
 * Fonctions de sécurité et utilitaires communs à toutes les pages.
 *
 *  - e()            : échappement HTML des sorties (protection XSS) ;
 *  - csrf_*()       : jeton anti-CSRF des formulaires ;
 *  - require_admin(): garde d'accès des pages d'administration ;
 *  - flash()        : messages de confirmation / d'erreur transmis entre deux pages ;
 *  - validateurs    : contrôle des saisies côté serveur.
 */

/** Durée d'inactivité (en secondes) au-delà de laquelle l'administrateur est déconnecté. */
const SESSION_INACTIVITE_MAX = 1800;

/** Échappe une valeur avant de l'afficher dans le HTML (protection XSS). */
function e($valeur): string
{
    return htmlspecialchars((string) $valeur, ENT_QUOTES, 'UTF-8');
}

/** Redirige vers une URL et arrête le script. */
function redirect(string $url): void
{
    header('Location: ' . $url);
    exit();
}

/* ---------------------------------------------------------------- */
/* Jeton CSRF                                                        */
/* ---------------------------------------------------------------- */

/** Renvoie le jeton CSRF de la session (le crée au premier appel). */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Champ caché à placer dans chaque formulaire POST. */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/** Vérifie le jeton reçu en POST (comparaison en temps constant). */
function csrf_verify(): bool
{
    $recu = $_POST['csrf_token'] ?? '';
    return is_string($recu)
        && !empty($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $recu);
}

/* ---------------------------------------------------------------- */
/* Garde d'accès administrateur                                      */
/* ---------------------------------------------------------------- */

/**
 * Bloque l'accès si aucun administrateur n'est connecté
 * ou si la session est restée inactive trop longtemps.
 */
function require_admin(string $racine): void
{
    if (!isset($_SESSION['id_admin'])) {
        flash('error', 'Veuillez vous connecter pour accéder à l\'administration.');
        redirect($racine . 'pages/login.php');
    }
    if (time() - ($_SESSION['derniere_activite'] ?? 0) > SESSION_INACTIVITE_MAX) {
        $_SESSION = [];
        session_regenerate_id(true);
        flash('error', 'Session expirée après 30 minutes d\'inactivité. Veuillez vous reconnecter.');
        redirect($racine . 'pages/login.php');
    }
    // Le compte existe-t-il toujours ? (il a pu être supprimé par un autre administrateur)
    global $connexion;
    $stmt = $connexion->prepare('SELECT COUNT(*) FROM ADMINISTRATEUR WHERE id_admin = :id');
    $stmt->execute([':id' => $_SESSION['id_admin']]);
    if ((int) $stmt->fetchColumn() === 0) {
        $_SESSION = [];
        session_regenerate_id(true);
        flash('error', 'Votre compte n\'existe plus. Veuillez contacter un administrateur.');
        redirect($racine . 'pages/login.php');
    }
    $_SESSION['derniere_activite'] = time();
}

/* ---------------------------------------------------------------- */
/* Messages flash                                                    */
/* ---------------------------------------------------------------- */

/** Enregistre un message à afficher sur la prochaine page ('success' ou 'error'). */
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

/** Affiche puis efface les messages en attente. */
function show_flash(): void
{
    foreach ($_SESSION['flash'] ?? [] as $f) {
        $classe = $f['type'] === 'success' ? 'alert-success' : 'alert-error';
        echo '<p class="alert ' . $classe . '" role="alert">' . e($f['message']) . '</p>';
    }
    unset($_SESSION['flash']);
}

/* ---------------------------------------------------------------- */
/* Lecture et validation des saisies                                 */
/* ---------------------------------------------------------------- */

/** Lit un champ texte POST, sans espaces superflus. Renvoie '' s'il est absent. */
function post_text(string $nom): string
{
    $valeur = $_POST[$nom] ?? '';
    return is_string($valeur) ? trim($valeur) : '';
}

/** Lit un identifiant entier positif en POST ou en GET. Renvoie null s'il est invalide. */
function input_id(int $source, string $nom): ?int
{
    $id = filter_input($source, $nom, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return is_int($id) ? $id : null;
}

/** Longueur d'une chaîne UTF-8 (ne dépend pas de l'extension mbstring). */
function longueur(string $texte): int
{
    return preg_match_all('/./us', $texte);
}

/** Contrôle d'un texte obligatoire. Renvoie le message d'erreur, ou null si la saisie est valide. */
function valider_texte(string $valeur, string $libelle, int $max = 255): ?string
{
    if ($valeur === '') {
        return "$libelle est obligatoire.";
    }
    if (longueur($valeur) > $max) {
        return "$libelle ne doit pas dépasser $max caractères.";
    }
    return null;
}

/** Vrai si une date au format AAAA-MM-JJ existe réellement. */
function date_valide(string $date): bool
{
    $d = DateTime::createFromFormat('!Y-m-d', $date);
    return $d !== false && $d->format('Y-m-d') === $date;
}

/** Vrai si une heure au format HH:MM est valide. */
function heure_valide(string $heure): bool
{
    return (bool) preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $heure);
}

/** Vrai si l'exception PDO correspond à une violation de contrainte d'unicité (doublon). */
function est_doublon(PDOException $e): bool
{
    return ($e->errorInfo[1] ?? null) === 1062;
}

/* ---------------------------------------------------------------- */
/* Règles propres aux comptes administrateurs                        */
/* ---------------------------------------------------------------- */

/** Login : 3 à 50 caractères parmi lettres, chiffres, point, tiret et tiret bas. */
function valider_login(string $login): ?string
{
    if ($login === '') {
        return 'Le login est obligatoire.';
    }
    if (!preg_match('/^[A-Za-z0-9._-]{3,50}$/', $login)) {
        return 'Le login doit contenir 3 à 50 caractères : lettres sans accent, chiffres, point, tiret ou tiret bas.';
    }
    return null;
}

/**
 * Mot de passe : 8 caractères minimum, 72 octets maximum.
 * Bcrypt ignore tout ce qui dépasse 72 octets : sans cette limite, deux mots de passe
 * différents au-delà du 72e octet seraient acceptés indifféremment.
 */
function valider_mot_de_passe(string $motDePasse, string $confirmation): ?string
{
    if (longueur($motDePasse) < 8) {
        return 'Le mot de passe doit contenir au moins 8 caractères.';
    }
    if (strlen($motDePasse) > 72) {
        return 'Le mot de passe ne doit pas dépasser 72 octets (limite de l\'algorithme Bcrypt).';
    }
    if (!hash_equals($motDePasse, $confirmation)) {
        return 'Les deux mots de passe ne sont pas identiques.';
    }
    return null;
}

/* ---------------------------------------------------------------- */
/* Règles propres aux lieux                                          */
/* ---------------------------------------------------------------- */

/** Lit et valide les champs d'un lieu. Renvoie [données, message d'erreur ou null]. */
function lire_lieu(): array
{
    $lieu = [
        'nom_lieu'     => post_text('nom_lieu'),
        'adresse_lieu' => post_text('adresse_lieu'),
        'cp_lieu'      => post_text('cp_lieu'),
        'ville_lieu'   => post_text('ville_lieu'),
    ];
    $erreur = valider_texte($lieu['nom_lieu'], 'Le nom du lieu')
        ?? valider_texte($lieu['adresse_lieu'], 'L\'adresse')
        ?? (preg_match('/^\d{5}$/', $lieu['cp_lieu']) ? null : 'Le code postal doit comporter exactement 5 chiffres.')
        ?? valider_texte($lieu['ville_lieu'], 'La ville');
    return [$lieu, $erreur];
}

/* ---------------------------------------------------------------- */
/* Listes déroulantes et clés étrangères                             */
/* ---------------------------------------------------------------- */

/**
 * Génère les <option> d'une liste déroulante (valeurs échappées).
 * $lignes : résultat SQL ; $colId / $colLibelle : colonnes à utiliser ; $selection : id présélectionné.
 */
function options(array $lignes, string $colId, string $colLibelle, ?int $selection): string
{
    $html = '<option value="">-- Choisir --</option>';
    foreach ($lignes as $ligne) {
        $id = (int) $ligne[$colId];
        $html .= '<option value="' . $id . '"' . ($id === $selection ? ' selected' : '') . '>'
               . e($ligne[$colLibelle]) . '</option>';
    }
    return $html;
}

/**
 * Vérifie qu'un identifiant reçu d'une liste déroulante existe vraiment en base.
 * La liste des tables est figée (liste blanche) : aucun nom de table ne vient de l'utilisateur.
 */
function existe(PDO $connexion, string $table, ?int $id): bool
{
    $cles = ['SPORT' => 'id_sport', 'LIEU' => 'id_lieu', 'PAYS' => 'id_pays', 'GENRE' => 'id_genre',
             'ATHLETE' => 'id_athlete', 'EPREUVE' => 'id_epreuve'];
    if ($id === null || !isset($cles[$table])) {
        return false;
    }
    $stmt = $connexion->prepare("SELECT COUNT(*) FROM $table WHERE {$cles[$table]} = :id");
    $stmt->execute([':id' => $id]);
    return (int) $stmt->fetchColumn() === 1;
}

/* ---------------------------------------------------------------- */
/* Règles propres aux épreuves et aux athlètes                       */
/* ---------------------------------------------------------------- */

/** Lit et valide les champs d'une épreuve. Renvoie [données, message d'erreur ou null]. */
function lire_epreuve(PDO $connexion): array
{
    $ep = [
        'nom_epreuve'   => post_text('nom_epreuve'),
        'id_sport'      => input_id(INPUT_POST, 'id_sport'),
        'date_epreuve'  => post_text('date_epreuve'),
        'heure_epreuve' => substr(post_text('heure_epreuve'), 0, 5), // certains navigateurs envoient HH:MM:SS
        'id_lieu'       => input_id(INPUT_POST, 'id_lieu'),
    ];
    $erreur = valider_texte($ep['nom_epreuve'], 'Le nom de l\'épreuve')
        ?? (existe($connexion, 'SPORT', $ep['id_sport']) ? null : 'Veuillez choisir un sport dans la liste.')
        ?? (date_valide($ep['date_epreuve']) ? null : 'La date de l\'épreuve est invalide.')
        ?? (heure_valide($ep['heure_epreuve']) ? null : 'L\'heure de l\'épreuve est invalide (format HH:MM).')
        ?? (existe($connexion, 'LIEU', $ep['id_lieu']) ? null : 'Veuillez choisir un lieu dans la liste.');
    return [$ep, $erreur];
}

/** Lit et valide les champs d'un athlète. Renvoie [données, message d'erreur ou null]. */
function lire_athlete(PDO $connexion): array
{
    $at = [
        'nom_athlete'    => post_text('nom_athlete'),
        'prenom_athlete' => post_text('prenom_athlete'),
        'id_pays'        => input_id(INPUT_POST, 'id_pays'),
        'id_genre'       => input_id(INPUT_POST, 'id_genre'),
    ];
    $erreur = valider_texte($at['nom_athlete'], 'Le nom')
        ?? valider_texte($at['prenom_athlete'], 'Le prénom')
        ?? (existe($connexion, 'PAYS', $at['id_pays']) ? null : 'Veuillez choisir un pays dans la liste.')
        ?? (existe($connexion, 'GENRE', $at['id_genre']) ? null : 'Veuillez choisir un genre dans la liste.');
    return [$at, $erreur];
}

/** Lit et valide les champs d'un résultat (table PARTICIPER). Renvoie [données, message d'erreur ou null]. */
function lire_resultat(PDO $connexion): array
{
    $res = [
        'id_athlete' => input_id(INPUT_POST, 'id_athlete'),
        'id_epreuve' => input_id(INPUT_POST, 'id_epreuve'),
        'resultat'   => post_text('resultat'),
    ];
    $erreur = (existe($connexion, 'ATHLETE', $res['id_athlete']) ? null : 'Veuillez choisir un athlète dans la liste.')
        ?? (existe($connexion, 'EPREUVE', $res['id_epreuve']) ? null : 'Veuillez choisir une épreuve dans la liste.')
        ?? valider_texte($res['resultat'], 'Le résultat', 100);
    return [$res, $erreur];
}
