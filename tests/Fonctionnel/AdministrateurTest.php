<?php
namespace Tests\Fonctionnel;

use PHPUnit\Framework\Attributes\TestDox;
use Tests\Support\TestFonctionnel;

/**
 * Gestion des administrateurs : le mot de passe est enregistré sous forme d'empreinte Bcrypt.
 */
class AdministrateurTest extends TestFonctionnel
{
    private const M = '/pages/admin/admin-users/';

    protected function setUp(): void
    {
        parent::setUp();
        $this->navigateur->seConnecter();
    }

    #[TestDox('Ajouter un administrateur enregistre une empreinte Bcrypt')]
    public function testAjouterUnAdministrateurEnregistreUneEmpreinteBcrypt(): void
    {
        $this->navigateur->post(self::M . 'add-user.php', [
            'nom_admin' => 'Martin', 'prenom_admin' => 'Léa', 'login' => 'lea.martin',
            'password' => 'MotDePasse!2028', 'password_confirm' => 'MotDePasse!2028',
        ], self::M . 'add-user.php');

        $this->assertPageSansErreur();
        $this->assertStringContainsString('a été créé', $this->navigateur->texte());

        $empreinte = $this->pdo->query("SELECT password FROM ADMINISTRATEUR WHERE login = 'lea.martin'")->fetchColumn();
        $this->assertStringStartsWith('$2y$', $empreinte);
        $this->assertTrue(password_verify('MotDePasse!2028', $empreinte));
        $this->assertStringNotContainsString('MotDePasse', $empreinte);

        // le nouveau compte peut se connecter
        $autre = new \Tests\Support\Navigateur(\Tests\Support\ServeurDeTest::url());
        $autre->seConnecter('lea.martin', 'MotDePasse!2028');
        $this->assertStringContainsString('Bonjour, Léa Martin', $autre->texte());
    }

    #[TestDox('Un administrateur ne peut pas supprimer son propre compte')]
    public function testUnAdministrateurNePeutPasSupprimerSonPropreCompte(): void
    {
        $id = $this->compter("SELECT id_admin FROM ADMINISTRATEUR WHERE login = 'admin'");
        $this->navigateur->post(self::M . 'delete-user.php', ['id_admin' => $id], self::M . 'manage-users.php');

        $this->assertStringContainsString('Vous ne pouvez pas supprimer votre propre compte.', $this->navigateur->texte());
        $this->assertSame(1, $this->compter("SELECT COUNT(*) FROM ADMINISTRATEUR WHERE login = 'admin'"));
    }
}
