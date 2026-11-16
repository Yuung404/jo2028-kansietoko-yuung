<?php
namespace Tests\Fonctionnel;

use PHPUnit\Framework\Attributes\TestDox;
use Tests\Support\Navigateur;
use Tests\Support\ServeurDeTest;
use Tests\Support\TestFonctionnel;

/**
 * Gestion des administrateurs (question 13) : le mot de passe est enregistré sous forme d'empreinte Bcrypt.
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
            'nomAdmin' => 'Martin', 'prenomAdmin' => 'Léa', 'login' => 'lea.martin',
            'password' => 'MotDePasse!2028', 'confirmPassword' => 'MotDePasse!2028',
        ], self::M . 'add-user.php');
        $this->assertPageSansErreur();
        $this->assertStringContainsString("L'administrateur a été ajouté avec succès.", $this->navigateur->texte());

        $empreinte = $this->pdo->query("SELECT password FROM ADMINISTRATEUR WHERE login = 'lea.martin'")->fetchColumn();
        $this->assertStringStartsWith('$2y$', $empreinte);
        $this->assertTrue(password_verify('MotDePasse!2028', $empreinte));
        $this->assertStringNotContainsString('MotDePasse', $empreinte);

        // le nouveau compte peut se connecter
        $autre = new Navigateur(ServeurDeTest::url());
        $autre->seConnecter('lea.martin', 'MotDePasse!2028');
        $this->assertStringContainsString('Bonjour, Léa Martin', $autre->texte());
    }

    #[TestDox('Un login déjà utilisé est refusé')]
    public function testUnLoginDejaUtiliseEstRefuse(): void
    {
        $this->navigateur->post(self::M . 'add-user.php', [
            'nomAdmin' => 'Autre', 'prenomAdmin' => 'John', 'login' => 'john_doe',
            'password' => 'MotDePasse!2028', 'confirmPassword' => 'MotDePasse!2028',
        ], self::M . 'add-user.php');
        $this->assertStringContainsString('Le login existe déjà.', $this->navigateur->texte());
        $this->assertSame(1, $this->compter("SELECT COUNT(*) FROM ADMINISTRATEUR WHERE login = 'john_doe'"));
    }

    #[TestDox('Les deux mots de passe doivent être identiques')]
    public function testLesDeuxMotsDePasseDoiventEtreIdentiques(): void
    {
        $this->navigateur->post(self::M . 'add-user.php', [
            'nomAdmin' => 'Martin', 'prenomAdmin' => 'Léa', 'login' => 'lea.martin',
            'password' => 'MotDePasse!2028', 'confirmPassword' => 'AutreChose!2028',
        ], self::M . 'add-user.php');
        $this->assertStringContainsString('Les deux mots de passe ne sont pas identiques.', $this->navigateur->texte());
        $this->assertSame(0, $this->compter("SELECT COUNT(*) FROM ADMINISTRATEUR WHERE login = 'lea.martin'"));
    }

    #[TestDox('Modifier un administrateur sans mot de passe garde l\'ancienne empreinte')]
    public function testModifierSansMotDePasseGardeLAncienneEmpreinte(): void
    {
        $id = $this->compter("SELECT id_admin FROM ADMINISTRATEUR WHERE login = 'john_doe'");
        $avant = $this->pdo->query("SELECT password FROM ADMINISTRATEUR WHERE id_admin = $id")->fetchColumn();
        $url = self::M . "modify-user.php?id_admin=$id";
        $this->navigateur->post($url, ['nomAdmin' => 'Doe', 'prenomAdmin' => 'Johnny', 'login' => 'john_doe',
                                       'password' => '', 'confirmPassword' => ''], $url);
        $this->assertStringContainsString("L'administrateur a été modifié avec succès.", $this->navigateur->texte());
        $this->assertSame('Johnny', $this->pdo->query("SELECT prenom_admin FROM ADMINISTRATEUR WHERE id_admin = $id")->fetchColumn());
        $this->assertSame($avant, $this->pdo->query("SELECT password FROM ADMINISTRATEUR WHERE id_admin = $id")->fetchColumn());
    }

    #[TestDox('Un administrateur ne peut pas supprimer son propre compte')]
    public function testUnAdministrateurNePeutPasSupprimerSonPropreCompte(): void
    {
        $id = $this->compter("SELECT id_admin FROM ADMINISTRATEUR WHERE login = 'admin'");
        $this->navigateur->post(self::M . 'delete-user.php', ['id_admin' => $id], self::M . 'manage-users.php');
        $this->assertStringContainsString('Vous ne pouvez pas supprimer votre propre compte.', $this->navigateur->texte());
        $this->assertSame(1, $this->compter("SELECT COUNT(*) FROM ADMINISTRATEUR WHERE login = 'admin'"));
    }

    #[TestDox('Supprimer un autre administrateur')]
    public function testSupprimerUnAutreAdministrateur(): void
    {
        $id = $this->compter("SELECT id_admin FROM ADMINISTRATEUR WHERE login = 'jane_doe'");
        $this->navigateur->post(self::M . 'delete-user.php', ['id_admin' => $id], self::M . 'manage-users.php');
        $this->assertStringContainsString("L'administrateur a été supprimé avec succès.", $this->navigateur->texte());
        $this->assertSame(0, $this->compter("SELECT COUNT(*) FROM ADMINISTRATEUR WHERE login = 'jane_doe'"));
    }
}
