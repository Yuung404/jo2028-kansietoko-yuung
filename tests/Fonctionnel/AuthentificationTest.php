<?php
namespace Tests\Fonctionnel;

use PHPUnit\Framework\Attributes\TestDox;
use Tests\Support\TestFonctionnel;

/**
 * Connexion à l'espace d'administration et protection des pages.
 */
class AuthentificationTest extends TestFonctionnel
{
    #[TestDox('Les pages d\'administration sont inaccessibles sans connexion')]
    public function testLesPagesAdministrateurSontInaccessiblesSansConnexion(): void
    {
        $this->navigateur->get('/pages/admin/admin-sports/manage-sports.php', false);
        $this->assertSame(302, $this->navigateur->statut);
        $this->assertStringNotContainsString('Gestion des sports', $this->navigateur->html);
    }

    #[TestDox('Un administrateur peut se connecter')]
    public function testUnAdministrateurPeutSeConnecter(): void
    {
        $this->navigateur->seConnecter('admin', 'admin123');
        $this->assertPageSansErreur();
        $this->assertStringContainsString('Bonjour, Super Admin', $this->navigateur->texte());
    }

    #[TestDox('Un mauvais mot de passe est refusé avec un message générique')]
    public function testUnMauvaisMotDePasseEstRefuseAvecUnMessageGenerique(): void
    {
        $this->navigateur->seConnecter('admin', 'mauvais');
        $this->assertStringContainsString('Login ou mot de passe incorrect.', $this->navigateur->texte());
        $this->navigateur->seConnecter('inconnu', 'admin123');
        $this->assertStringContainsString('Login ou mot de passe incorrect.', $this->navigateur->texte());
    }

    #[TestDox('La déconnexion ferme l\'accès au back-office')]
    public function testLaDeconnexionFermeLAccesAuBackOffice(): void
    {
        $this->navigateur->seConnecter();
        $this->navigateur->post('/pages/logout.php', [], '/pages/logout.php');
        $this->navigateur->get('/pages/admin/admin.php', false);
        $this->assertSame(302, $this->navigateur->statut);
    }
}
