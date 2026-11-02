-- ----------------------------------------------------------------------------
-- Migration 01 — à exécuter APRÈS db-jo.sql et test-data-jo.sql
-- ----------------------------------------------------------------------------
-- 1. Ajoute la colonne nom_epreuve, prévue au MCD et au MPD mais absente de db-jo.sql.
-- 2. Ajoute des contraintes d'unicité : les doublons sont refusés par la base elle-même,
--    en plus du contrôle fait en PHP (défense en profondeur).
-- ----------------------------------------------------------------------------

-- 1. Colonne nom_epreuve
ALTER TABLE EPREUVE ADD nom_epreuve VARCHAR(255) NULL AFTER id_epreuve;

-- Nom par défaut pour les épreuves du jeu d'essai
UPDATE EPREUVE e
INNER JOIN SPORT s ON s.id_sport = e.id_sport
SET e.nom_epreuve = CONCAT('Finale ', s.nom_sport)
WHERE e.nom_epreuve IS NULL;

ALTER TABLE EPREUVE MODIFY nom_epreuve VARCHAR(255) NOT NULL;

-- 2. Contraintes d'unicité (règles de gestion « aucun doublon »)
ALTER TABLE SPORT          ADD CONSTRAINT uq_sport_nom   UNIQUE (nom_sport);
ALTER TABLE PAYS           ADD CONSTRAINT uq_pays_nom    UNIQUE (nom_pays);
ALTER TABLE GENRE          ADD CONSTRAINT uq_genre_nom   UNIQUE (nom_genre);
ALTER TABLE LIEU           ADD CONSTRAINT uq_lieu_nom    UNIQUE (nom_lieu);
ALTER TABLE ADMINISTRATEUR ADD CONSTRAINT uq_admin_login UNIQUE (login);
-- Un athlète est identifié par son nom, son prénom et son pays
ALTER TABLE ATHLETE        ADD CONSTRAINT uq_athlete     UNIQUE (nom_athlete, prenom_athlete, id_pays);
-- Un lieu n'accueille qu'une épreuve à une date et une heure données
ALTER TABLE EPREUVE        ADD CONSTRAINT uq_epreuve_creneau UNIQUE (id_lieu, date_epreuve, heure_epreuve);
