<?php
namespace Tests\Support;

/**
 * Navigateur simulé (cURL) : conserve les cookies de session, suit les redirections
 * et récupère automatiquement le jeton CSRF des formulaires.
 */
final class Navigateur
{
    private string $cookies;
    public int $statut = 0;
    public string $html = '';

    public function __construct(private string $base)
    {
        $this->cookies = tempnam(sys_get_temp_dir(), 'jo2028-cookies-');
    }

    public function __destruct()
    {
        @unlink($this->cookies);
    }

    public function get(string $chemin, bool $suivre = true): string
    {
        return $this->requete($chemin, null, $suivre);
    }

    /** Envoie un formulaire ; si $jetonDepuis est fourni, le jeton CSRF de cette page est ajouté. */
    public function post(string $chemin, array $donnees, ?string $jetonDepuis = null): string
    {
        if ($jetonDepuis !== null) {
            $donnees = ['csrf_token' => $this->jeton($jetonDepuis)] + $donnees;
        }
        return $this->requete($chemin, $donnees, true);
    }

    public function jeton(string $chemin): string
    {
        $html = $this->get($chemin);
        if (!preg_match('/name="csrf_token" value="([0-9a-f]{64})"/', $html, $m)) {
            throw new \RuntimeException("Aucun jeton CSRF sur $chemin");
        }
        return $m[1];
    }

    public function seConnecter(string $login = 'admin', string $motDePasse = 'admin123'): string
    {
        return $this->post('/database/auth.php', ['login' => $login, 'password' => $motDePasse], '/pages/login.php');
    }

    /** Texte visible de la dernière page (balises retirées, entités décodées). */
    public function texte(): string
    {
        return html_entity_decode(strip_tags($this->html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private function requete(string $chemin, ?array $donnees, bool $suivre): string
    {
        $c = curl_init($this->base . $chemin);
        curl_setopt_array($c, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => $suivre,
            CURLOPT_COOKIEJAR      => $this->cookies,
            CURLOPT_COOKIEFILE     => $this->cookies,
            CURLOPT_TIMEOUT        => 10,
        ]);
        if ($donnees !== null) {
            curl_setopt($c, CURLOPT_POST, true);
            curl_setopt($c, CURLOPT_POSTFIELDS, http_build_query($donnees));
        }
        $this->html = (string) curl_exec($c);
        $this->statut = (int) curl_getinfo($c, CURLINFO_HTTP_CODE);
        curl_close($c);
        return $this->html;
    }
}
