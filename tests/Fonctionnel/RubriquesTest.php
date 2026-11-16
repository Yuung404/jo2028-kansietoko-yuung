<?php
namespace Tests\Fonctionnel;

use PHPUnit\Framework\Attributes\TestDox;
use Tests\Support\TestFonctionnel;

/**
 * Les autres rubriques de la mission 6 (questions 14 à 19), bâties sur le modèle des sports :
 * ajout, contrôle des doublons, modification et suppression protégée par les clés étrangères.
 */
class RubriquesTest extends TestFonctionnel
{
    private const A = '/pages/admin/';

    protected function setUp(): void
    {
        parent::setUp();
        $this->navigateur->seConnecter();
    }

    #[TestDox('Ajouter un lieu, puis refuser le même nom (question 14)')]
    public function testAjouterUnLieuPuisRefuserLeDoublon(): void
    {
        $lieu = ['nomLieu' => 'Rose Bowl', 'adresseLieu' => '1001 Rose Bowl Dr', 'cpLieu' => '91103', 'villeLieu' => 'Pasadena'];
        $this->navigateur->post(self::A . 'admin-places/add-place.php', $lieu, self::A . 'admin-places/add-place.php');
        $this->assertPageSansErreur();
        $this->assertStringContainsString('Le lieu a été ajouté avec succès.', $this->navigateur->texte());
        $this->navigateur->post(self::A . 'admin-places/add-place.php', $lieu, self::A . 'admin-places/add-place.php');
        $this->assertStringContainsString('Le lieu existe déjà.', $this->navigateur->texte());
        $this->assertSame(1, $this->compter('SELECT COUNT(*) FROM LIEU WHERE nom_lieu = ?', ['Rose Bowl']));
    }

    #[TestDox('Un code postal qui n\'a pas 5 chiffres est refusé')]
    public function testUnCodePostalInvalideEstRefuse(): void
    {
        $this->navigateur->post(self::A . 'admin-places/add-place.php',
            ['nomLieu' => 'Stade test', 'adresseLieu' => '1 rue test', 'cpLieu' => '9110', 'villeLieu' => 'Test'],
            self::A . 'admin-places/add-place.php');
        $this->assertStringContainsString('Le code postal doit comporter exactement 5 chiffres.', $this->navigateur->texte());
        $this->assertSame(0, $this->compter('SELECT COUNT(*) FROM LIEU WHERE nom_lieu = ?', ['Stade test']));
    }

    #[TestDox('Un pays qui a des athlètes ne peut pas être supprimé (question 15)')]
    public function testUnPaysAvecDesAthletesNePeutPasEtreSupprime(): void
    {
        $id = $this->compter("SELECT id_pays FROM PAYS WHERE nom_pays = 'France'");
        $this->navigateur->post(self::A . 'admin-countries/delete-country.php', ['id_pays' => $id], self::A . 'admin-countries/manage-countries.php');
        $this->assertStringContainsString('Suppression impossible : ce pays est utilisé par au moins un athlète.', $this->navigateur->texte());
        $this->assertSame(1, $this->compter('SELECT COUNT(*) FROM PAYS WHERE id_pays = ?', [$id]));
    }

    #[TestDox('Ajouter une épreuve au calendrier, puis refuser le même créneau (question 16)')]
    public function testAjouterUneEpreuvePuisRefuserLeMemeCreneau(): void
    {
        $sport = $this->compter("SELECT id_sport FROM SPORT WHERE nom_sport = 'Boxe'");
        $lieu = $this->compter('SELECT MIN(id_lieu) FROM LIEU');
        $epreuve = ['nomEpreuve' => 'Demi-finale Boxe', 'idSport' => $sport, 'dateEpreuve' => '2028-07-20', 'heureEpreuve' => '05:30', 'idLieu' => $lieu];
        $this->navigateur->post(self::A . 'admin-events/add-event.php', $epreuve, self::A . 'admin-events/add-event.php');
        $this->assertPageSansErreur();
        $this->assertStringContainsString("L'épreuve a été ajoutée avec succès.", $this->navigateur->texte());
        $epreuve['nomEpreuve'] = 'Autre épreuve au même créneau';
        $this->navigateur->post(self::A . 'admin-events/add-event.php', $epreuve, self::A . 'admin-events/add-event.php');
        $this->assertStringContainsString('Une épreuve a déjà lieu à cet endroit, à cette date et à cette heure.', $this->navigateur->texte());
        $this->assertSame(1, $this->compter("SELECT COUNT(*) FROM EPREUVE WHERE date_epreuve = '2028-07-20' AND heure_epreuve = '05:30:00'"));
    }

    #[TestDox('Une date impossible est refusée (31 février)')]
    public function testUneDateImpossibleEstRefusee(): void
    {
        $sport = $this->compter("SELECT id_sport FROM SPORT WHERE nom_sport = 'Boxe'");
        $lieu = $this->compter('SELECT MIN(id_lieu) FROM LIEU');
        $this->navigateur->post(self::A . 'admin-events/add-event.php',
            ['nomEpreuve' => 'Épreuve impossible', 'idSport' => $sport, 'dateEpreuve' => '2028-02-31', 'heureEpreuve' => '10:00', 'idLieu' => $lieu],
            self::A . 'admin-events/add-event.php');
        $this->assertStringContainsString("La date de l'épreuve est invalide.", $this->navigateur->texte());
        $this->assertSame(0, $this->compter("SELECT COUNT(*) FROM EPREUVE WHERE nom_epreuve = 'Épreuve impossible'"));
    }

    #[TestDox('Ajouter un athlète, puis refuser le même athlète pour le même pays (question 17)')]
    public function testAjouterUnAthletePuisRefuserLeDoublon(): void
    {
        $pays = $this->compter("SELECT id_pays FROM PAYS WHERE nom_pays = 'France'");
        $genre = $this->compter("SELECT id_genre FROM GENRE WHERE nom_genre = 'Femme'");
        $athlete = ['nomAthlete' => 'DUPONT', 'prenomAthlete' => 'Léa', 'idPays' => $pays, 'idGenre' => $genre];
        $this->navigateur->post(self::A . 'admin-athletes/add-athlete.php', $athlete, self::A . 'admin-athletes/add-athlete.php');
        $this->assertStringContainsString("L'athlète a été ajouté avec succès.", $this->navigateur->texte());
        $this->navigateur->post(self::A . 'admin-athletes/add-athlete.php', $athlete, self::A . 'admin-athletes/add-athlete.php');
        $this->assertStringContainsString("L'athlète existe déjà pour ce pays.", $this->navigateur->texte());
        $this->assertSame(1, $this->compter("SELECT COUNT(*) FROM ATHLETE WHERE nom_athlete = 'DUPONT'"));
    }

    #[TestDox('Un athlète qui a un résultat ne peut pas être supprimé')]
    public function testUnAthleteAvecUnResultatNePeutPasEtreSupprime(): void
    {
        $id = $this->compter('SELECT MIN(id_athlete) FROM PARTICIPER');
        $this->navigateur->post(self::A . 'admin-athletes/delete-athlete.php', ['id_athlete' => $id], self::A . 'admin-athletes/manage-athletes.php');
        $this->assertStringContainsString('Suppression impossible', $this->navigateur->texte());
        $this->assertSame(1, $this->compter('SELECT COUNT(*) FROM ATHLETE WHERE id_athlete = ?', [$id]));
    }

    #[TestDox('Modifier un genre (question 18)')]
    public function testModifierUnGenre(): void
    {
        $id = $this->compter("SELECT id_genre FROM GENRE WHERE nom_genre = 'Femme'");
        $url = self::A . "admin-genders/modify-gender.php?id_genre=$id";
        $this->navigateur->post($url, ['nomGenre' => 'Femmes'], $url);
        $this->assertStringContainsString('Le genre a été modifié avec succès.', $this->navigateur->texte());
        $this->assertSame('Femmes', $this->pdo->query("SELECT nom_genre FROM GENRE WHERE id_genre = $id")->fetchColumn());
    }

    #[TestDox('Ajouter, modifier puis supprimer un résultat (question 19)')]
    public function testAjouterModifierPuisSupprimerUnResultat(): void
    {
        // un athlète et une épreuve qui n'ont pas encore de résultat ensemble
        $couple = $this->pdo->query('SELECT at.id_athlete, ep.id_epreuve FROM ATHLETE at CROSS JOIN EPREUVE ep
            WHERE NOT EXISTS (SELECT 1 FROM PARTICIPER p WHERE p.id_athlete = at.id_athlete AND p.id_epreuve = ep.id_epreuve) LIMIT 1')->fetch();
        [$at, $ep] = [(int) $couple['id_athlete'], (int) $couple['id_epreuve']];
        $nb = $this->compter('SELECT COUNT(*) FROM PARTICIPER');

        $this->navigateur->post(self::A . 'admin-results/add-result.php', ['idAthlete' => $at, 'idEpreuve' => $ep, 'resultat' => 'Bronze'], self::A . 'admin-results/add-result.php');
        $this->assertStringContainsString('Le résultat a été ajouté avec succès.', $this->navigateur->texte());
        $this->assertSame($nb + 1, $this->compter('SELECT COUNT(*) FROM PARTICIPER'));

        $url = self::A . "admin-results/modify-result.php?id_athlete=$at&id_epreuve=$ep";
        $this->navigateur->post($url, ['resultat' => 'Argent'], $url);
        $this->assertStringContainsString('Le résultat a été modifié avec succès.', $this->navigateur->texte());
        $this->assertSame(1, $this->compter('SELECT COUNT(*) FROM PARTICIPER WHERE id_athlete = ? AND id_epreuve = ? AND resultat = ?', [$at, $ep, 'Argent']));

        $this->navigateur->post(self::A . 'admin-results/delete-result.php', ['id_athlete' => $at, 'id_epreuve' => $ep], self::A . 'admin-results/manage-results.php');
        $this->assertStringContainsString('Le résultat a été supprimé avec succès.', $this->navigateur->texte());
        $this->assertSame($nb, $this->compter('SELECT COUNT(*) FROM PARTICIPER'));
    }
}
