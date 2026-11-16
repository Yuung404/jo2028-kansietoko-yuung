<?php
namespace Tests\Unitaire;

use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires du hachage Bcrypt des mots de passe (question 13 et annexe 1).
 */
class SecuriteTest extends TestCase
{
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
