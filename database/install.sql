-- Création de la base, en UTF-8 complet (utf8mb4) pour les accents.
-- Sans cette précision, MySQL 5.7 crée les tables en latin1 par défaut.
CREATE DATABASE IF NOT EXISTS kansietoko_yuung_jo2028
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
