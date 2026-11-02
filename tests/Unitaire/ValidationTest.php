<?php
namespace Tests\Unitaire;

use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires des fonctions de contrôle des saisies (includes/security.php).
 */
class ValidationTest extends TestCase
{
    #[TestDox('Un texte obligatoire vide est refusé')]
    public function testUnTexteObligatoireVideEstRefuse(): void
    {
        $this->assertSame('Le nom du sport est obligatoire.', valider_texte('', 'Le nom du sport'));
    }

    #[TestDox('Un texte trop long est refusé')]
    public function testUnTexteTropLongEstRefuse(): void
    {
        $this->assertSame('Le résultat ne doit pas dépasser 100 caractères.', valider_texte(str_repeat('x', 101), 'Le résultat', 100));
    }

    #[TestDox('Un texte valide est accepté (y compris 255 caractères accentués)')]
    public function testUnTexteValideEstAccepte(): void
    {
        $this->assertNull(valider_texte('Natation', 'Le nom du sport'));
        $this->assertNull(valider_texte(str_repeat('é', 255), 'Le nom'));      // 255 caractères accentués acceptés
    }

    #[TestDox('La longueur compte les caractères et non les octets')]
    public function testLaLongueurCompteLesCaracteresEtPasLesOctets(): void
    {
        $this->assertSame(7, longueur('Algérie'));   // 8 octets en UTF-8, mais 7 caractères
    }

    #[TestDox('Les dates impossibles sont refusées (31 février, 29 février hors année bissextile)')]
    public function testLesDatesImpossiblesSontRefusees(): void
    {
        $this->assertTrue(date_valide('2028-07-20'));
        $this->assertTrue(date_valide('2028-02-29'));    // 2028 est bissextile
        $this->assertFalse(date_valide('2027-02-29'));
        $this->assertFalse(date_valide('2028-02-31'));
        $this->assertFalse(date_valide('20/07/2028'));
    }

    #[TestDox('Les heures invalides sont refusées')]
    public function testLesHeuresInvalidesSontRefusees(): void
    {
        $this->assertTrue(heure_valide('09:15'));
        $this->assertTrue(heure_valide('23:59'));
        $this->assertFalse(heure_valide('24:00'));
        $this->assertFalse(heure_valide('9:15'));
        $this->assertFalse(heure_valide('14h30'));
    }

    #[TestDox('Le format du login est contrôlé')]
    public function testLeFormatDuLoginEstControle(): void
    {
        $this->assertNull(valider_login('john_doe'));
        $this->assertNull(valider_login('lea.martin-2'));
        $this->assertNotNull(valider_login('ab'));                // trop court
        $this->assertNotNull(valider_login('léa'));               // accent
        $this->assertNotNull(valider_login("admin' OR '1'='1"));  // caractères interdits
    }

    #[TestDox('Les règles du mot de passe sont appliquées (8 caractères min., 72 octets max., confirmation)')]
    public function testLesReglesDuMotDePasseSontAppliquees(): void
    {
        $this->assertNull(valider_mot_de_passe('MotDePasse!2028', 'MotDePasse!2028'));
        $this->assertStringContainsString('au moins 8 caractères', valider_mot_de_passe('court', 'court'));
        $this->assertStringContainsString('pas identiques', valider_mot_de_passe('MotDePasse!2028', 'Autre!2028xx'));
        $long = str_repeat('é', 40);                                 // 40 caractères mais 80 octets
        $this->assertStringContainsString('72 octets', valider_mot_de_passe($long, $long));
    }
}
