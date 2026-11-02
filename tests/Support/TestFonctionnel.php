<?php
namespace Tests\Support;

use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Classe de base des tests fonctionnels : chaque test part d'une base de test neuve
 * et d'un navigateur sans session.
 */
abstract class TestFonctionnel extends TestCase
{
    protected Navigateur $navigateur;
    protected PDO $pdo;

    protected function setUp(): void
    {
        if (!extension_loaded('curl') || !extension_loaded('pdo_mysql')) {
            $this->markTestSkipped('Extensions curl et pdo_mysql requises (lancer PHP avec le php.ini de MAMP).');
        }
        BaseDeTest::reinitialiser();
        $this->pdo = BaseDeTest::pdo();
        $this->navigateur = new Navigateur(ServeurDeTest::url());
    }

    /** Vérifie qu'aucune erreur PHP n'est affichée dans la page. */
    protected function assertPageSansErreur(): void
    {
        $this->assertSame(200, $this->navigateur->statut, 'Code HTTP inattendu');
        $this->assertDoesNotMatchRegularExpression('/Warning|Notice|Deprecated|Fatal error|Une erreur technique/', $this->navigateur->html);
    }

    protected function compter(string $sql, array $parametres = []): int
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($parametres);
        return (int) $stmt->fetchColumn();
    }
}
