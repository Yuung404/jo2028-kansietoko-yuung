<?php
namespace Tests\Unitaire;

use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires des protections XSS, CSRF et du hachage Bcrypt.
 */
class SecuriteTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
        $_POST = [];
    }

    #[TestDox('L\'échappement HTML neutralise une balise script (XSS)')]
    public function testLEchappementNeutraliseUnScript(): void
    {
        $this->assertSame('&lt;script&gt;alert(1)&lt;/script&gt;', e('<script>alert(1)</script>'));
        $this->assertSame('L&#039;&quot;équipe&quot;', e('L\'"équipe"'));   // guillemets simples et doubles
    }

    #[TestDox('Le jeton CSRF est aléatoire (64 caractères) et stable pendant la session')]
    public function testLeJetonCsrfEstAleatoireEtStable(): void
    {
        $jeton = csrf_token();
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $jeton);   // 32 octets aléatoires
        $this->assertSame($jeton, csrf_token());                              // identique pendant la session
    }

    #[TestDox('Une requête sans jeton CSRF est refusée')]
    public function testUneRequeteSansJetonEstRefusee(): void
    {
        csrf_token();
        $this->assertFalse(csrf_verify());
    }

    #[TestDox('Une requête avec un faux jeton CSRF est refusée')]
    public function testUneRequeteAvecUnFauxJetonEstRefusee(): void
    {
        csrf_token();
        $_POST['csrf_token'] = str_repeat('a', 64);
        $this->assertFalse(csrf_verify());
    }

    #[TestDox('Une requête avec le bon jeton CSRF est acceptée')]
    public function testUneRequeteAvecLeBonJetonEstAcceptee(): void
    {
        $_POST['csrf_token'] = csrf_token();
        $this->assertTrue(csrf_verify());
    }

    #[TestDox('Bcrypt produit une empreinte salée et vérifiable')]
    public function testBcryptProduitUneEmpreinteSaleeEtVerifiable(): void
    {
        $empreinte1 = password_hash('admin123', PASSWORD_BCRYPT);
        $empreinte2 = password_hash('admin123', PASSWORD_BCRYPT);
        $this->assertStringStartsWith('$2y$', $empreinte1);
        $this->assertSame(60, strlen($empreinte1));
        $this->assertNotSame($empreinte1, $empreinte2);                 // sel aléatoire : empreintes différentes
        $this->assertTrue(password_verify('admin123', $empreinte1));
        $this->assertFalse(password_verify('Admin123', $empreinte1));
    }

    #[TestDox('Les empreintes du jeu d\'essai correspondent aux mots de passe de l\'annexe 2')]
    public function testLesEmpreintesDuJeuDEssaiCorrespondentAuxMotsDePasseFournis(): void
    {
        $sql = file_get_contents(__DIR__ . '/../../database/test-data-jo.sql');
        preg_match_all("/'(admin|john_doe|jane_doe)', '(\\$2y\\$[^']+)'/", $sql, $m, PREG_SET_ORDER);
        $attendus = ['admin' => 'admin123', 'john_doe' => 'password123', 'jane_doe' => 'pass123'];
        $this->assertCount(3, $m);
        foreach ($m as [, $login, $empreinte]) {
            $this->assertTrue(password_verify($attendus[$login], $empreinte), "Empreinte invalide pour $login");
        }
    }
}
