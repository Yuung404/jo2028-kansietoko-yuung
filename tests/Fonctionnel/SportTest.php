<?php
namespace Tests\Fonctionnel;

use PHPUnit\Framework\Attributes\TestDox;
use Tests\Support\TestFonctionnel;

/**
 * Fonctionnalités clés du back-office sur l'exemple des sports : ajout, modification, suppression.
 * Chaque test vérifie le message affiché ET l'état réel de la base de données.
 */
class SportTest extends TestFonctionnel
{
    private const M = '/pages/admin/admin-sports/';

    protected function setUp(): void
    {
        parent::setUp();
        $this->navigateur->seConnecter();
    }

    private function idSport(string $nom): int
    {
        return $this->compter('SELECT id_sport FROM SPORT WHERE nom_sport = ?', [$nom]);
    }

    #[TestDox('Ajouter un sport')]
    public function testAjouterUnSport(): void
    {
        $this->navigateur->post(self::M . 'add-sport.php', ['nom_sport' => 'Surf'], self::M . 'add-sport.php');

        $this->assertPageSansErreur();
        $this->assertStringContainsString('Le sport « Surf » a été ajouté.', $this->navigateur->texte());
        $this->assertSame(1, $this->compter('SELECT COUNT(*) FROM SPORT WHERE nom_sport = ?', ['Surf']));
        $this->assertSame(11, $this->compter('SELECT COUNT(*) FROM SPORT'));
    }

    #[TestDox('Un sport en double est refusé (majuscules et espaces ignorés)')]
    public function testUnSportEnDoubleEstRefuse(): void
    {
        $this->navigateur->post(self::M . 'add-sport.php', ['nom_sport' => '  natation '], self::M . 'add-sport.php');

        $this->assertStringContainsString('Ce sport existe déjà.', $this->navigateur->texte());
        $this->assertSame(10, $this->compter('SELECT COUNT(*) FROM SPORT'));
    }

    #[TestDox('Modifier un sport')]
    public function testModifierUnSport(): void
    {
        $id = $this->idSport('VTT');
        $url = self::M . "modify-sport.php?id_sport=$id";
        $this->navigateur->post($url, ['nom_sport' => 'VTT cross-country'], $url);

        $this->assertStringContainsString('Le sport « VTT cross-country » a été modifié.', $this->navigateur->texte());
        $this->assertSame('VTT cross-country', $this->pdo->query("SELECT nom_sport FROM SPORT WHERE id_sport = $id")->fetchColumn());
    }

    #[TestDox('La modification vers un nom déjà utilisé est refusée')]
    public function testLaModificationVersUnNomExistantEstRefusee(): void
    {
        $id = $this->idSport('VTT');
        $url = self::M . "modify-sport.php?id_sport=$id";
        $this->navigateur->post($url, ['nom_sport' => 'Boxe'], $url);

        $this->assertStringContainsString('Un autre sport porte déjà ce nom.', $this->navigateur->texte());
        $this->assertSame(1, $this->compter('SELECT COUNT(*) FROM SPORT WHERE nom_sport = ?', ['VTT']));
    }

    #[TestDox('Supprimer un sport')]
    public function testSupprimerUnSport(): void
    {
        $this->pdo->exec("INSERT INTO SPORT (nom_sport) VALUES ('Breakdance')");
        $id = $this->idSport('Breakdance');

        $this->navigateur->post(self::M . 'delete-sport.php', ['id_sport' => $id], self::M . 'manage-sports.php');

        $this->assertStringContainsString('Le sport a été supprimé.', $this->navigateur->texte());
        $this->assertSame(0, $this->compter('SELECT COUNT(*) FROM SPORT WHERE id_sport = ?', [$id]));
    }

    #[TestDox('Un sport utilisé par une épreuve ne peut pas être supprimé')]
    public function testUnSportUtiliseParUneEpreuveNePeutPasEtreSupprime(): void
    {
        $id = $this->idSport('Natation');
        $this->navigateur->post(self::M . 'delete-sport.php', ['id_sport' => $id], self::M . 'manage-sports.php');

        $this->assertStringContainsString('Suppression impossible', $this->navigateur->texte());
        $this->assertSame(1, $this->compter('SELECT COUNT(*) FROM SPORT WHERE id_sport = ?', [$id]));
    }

    #[TestDox('Une suppression sans jeton CSRF ou par simple lien est refusée')]
    public function testUneSuppressionSansJetonCsrfEstRefusee(): void
    {
        $this->pdo->exec("INSERT INTO SPORT (nom_sport) VALUES ('Breakdance')");
        $id = $this->idSport('Breakdance');

        $this->navigateur->post(self::M . 'delete-sport.php', ['id_sport' => $id, 'csrf_token' => str_repeat('0', 64)]);
        $this->assertStringContainsString('Requête de suppression invalide.', $this->navigateur->texte());

        $this->navigateur->get(self::M . "delete-sport.php?id_sport=$id");     // tentative par simple lien
        $this->assertSame(1, $this->compter('SELECT COUNT(*) FROM SPORT WHERE id_sport = ?', [$id]));
    }

    #[TestDox('Un nom de sport malveillant est affiché sans être exécuté (XSS)')]
    public function testUnNomDeSportMalveillantEstAfficheSansEtreExecute(): void
    {
        $this->navigateur->post(self::M . 'add-sport.php', ['nom_sport' => '<script>alert(1)</script>'], self::M . 'add-sport.php');
        $this->navigateur->get('/pages/sports.php');

        $this->assertStringNotContainsString('<script>alert(1)</script>', $this->navigateur->html);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $this->navigateur->html);
        // stocké tel quel en base (encodage à l'affichage, pas à l'enregistrement)
        $this->assertSame(1, $this->compter('SELECT COUNT(*) FROM SPORT WHERE nom_sport = ?', ['<script>alert(1)</script>']));
    }
}
