----------------------------------------------------------------------------------------
-- This file is part of the eLCA project
--
-- eLCA
-- A web based life cycle assessment application
--
-- Copyright (c) 2013 Tobias Lode <tobias@beibob.de>
--               BEIBOB Medienfreunde GbR - http://beibob.de/
--
-- eLCA is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.
--
-- eLCA is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with eLCA. If not, see <http://www.gnu.org/licenses/>.
----------------------------------------------------------------------------------------
SET client_encoding = 'UTF8';
BEGIN;
CREATE SCHEMA bnb;
COMMIT;

SET search_path = bnb, public;

BEGIN;
-------------------------------------------------------------------------------

CREATE TABLE bnb.water
(
   "project_id"                int             NOT NULL         -- projectId
 , "niederschlagsmenge"        numeric                          -- Niederschlagsmenge am Standort in mm
 , "anzahl_personen"           int                              -- Anzahl Personen

 , "sanitaer_waschtisch"       numeric                          -- Waschtisch in l
 , "sanitaer_wc_spar"          numeric                          -- Spuelung WC Spartaste in l
 , "sanitaer_wc"               numeric                          -- Spuelung WC in l
 , "sanitaer_urinal"           numeric                          -- Urinal
 , "sanitaer_dusche"           numeric                          -- Armatur Dusche
 , "sanitaer_teekueche"        numeric                          -- Armatur Teeküche

 , "reinigung_sanitaer"        numeric                          -- Reinigung Santitaerbereiche
 , "reinigung_lobby"           numeric                          -- Reinigung Lobby
 , "reinigung_verkehrsflaeche" numeric                          -- Reinigung Verkehrsflaeche
 , "reinigung_buero"           numeric                          -- Reinigung Buero
 , "reinigung_keller"          numeric                          -- Reinigung Nebenraum

 , "dach1_flaeche"             numeric                          -- Dach1 Flaeche
 , "dach1_ertragsbeiwert"      numeric                          -- Ertragsbeiwert Dach1
 , "dach2_flaeche"             numeric                          -- Dach2 Flaeche
 , "dach2_ertragsbeiwert"      numeric                          -- Ertragsbeiwert Dach2
 , "dach3_flaeche"             numeric                          -- Dach3 Flaeche
 , "dach3_ertragsbeiwert"      numeric                          -- Ertragsbeiwert Dach3
 , "dach4_flaeche"             numeric                          -- Dach4 Flaeche
 , "dach4_ertragsbeiwert"      numeric                          -- Ertragsbeiwert Dach4

 , "niederschlag_versickert"   numeric                          -- Menge des auf dem Grundstueck versickerten Niederschlagswassers
 , "niederschlag_genutzt"      numeric                          -- Menge des genutzten Niederschlagswasser
 , "niederschlag_genutzt_ohne_wandlung" numeric                          -- Menge des genutzten Niederschlagswasser
 , "niederschlag_kanalisation" numeric                          -- Menge des genutzten Niederschlagswasser
 , "brauchwasser"              numeric                          -- Menge des genutzten Brauchwassers
 , "brauchwasser_gereinigt"    numeric                          -- Menge des auf dem Grundstueck gereinigten Brauchwassers

 , PRIMARY KEY ("project_id")
 , FOREIGN KEY ("project_id") REFERENCES elca.projects ("id") ON UPDATE CASCADE ON DELETE CASCADE
);

-------------------------------------------------------------------------------
COMMIT;
