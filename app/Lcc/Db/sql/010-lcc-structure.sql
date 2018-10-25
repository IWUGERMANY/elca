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
CREATE SCHEMA lcc;
COMMIT;

SET search_path = lcc, public;

BEGIN;
-------------------------------------------------------------------------------

CREATE TABLE lcc.versions
(
   "id"                 serial          NOT NULL                -- configId
 , "name"               varchar(200)    NOT NULL                -- name
 , "version"            varchar(100)                            -- version
 , "calc_method"        smallint        NOT NULL                -- calcMethod

 , "rate"               numeric         NOT NULL                -- Zinssatz
 , "common_price_inc"   numeric         NOT NULL                -- Allg. Preissteierung
 , "energy_price_inc"   numeric         NOT NULL                -- Energie Preissteierung
 , "water_price_inc"    numeric         NOT NULL                -- Wasser/ Abwasser Preissteigerung
 , "cleaning_price_inc" numeric         NOT NULL                -- Reinigung Preissteigerung

 , "created"            timestamptz(0)  NOT NULL DEFAULT now()  -- creation time
 , "modified"           timestamptz(0)           DEFAULT now()  -- modification time
 , PRIMARY KEY ("id")
);

CREATE TABLE lcc.energy_source_costs
(
      "id"            serial              NOT NULL
    , "version_id"    int                 NOT NULL
    , "name"          varchar(200)        NOT NULL
    , "costs"         numeric             NOT NULL
    , PRIMARY KEY ("id")
    , FOREIGN KEY ("version_id") REFERENCES lcc.versions ON UPDATE CASCADE ON DELETE CASCADE
);
-------------------------------------------------------------------------------

CREATE TABLE lcc.costs
(
   "id"                         serial       NOT NULL            -- periodicCostId
 , "version_id"                 int                              -- versionId
 , "grouping"                   varchar(100) NOT NULL            -- groups within periodic costs
 , "din276_code"                int          NOT NULL            -- din276 code
 , "label"                      text         NOT NULL            -- label
 , "headline"                   text                             -- optional headline
 , "project_id"                 int                              -- project specific config (not part of the default config)
 , "ident"                      varchar(100)                     -- ident
 , PRIMARY KEY ("id")
 , UNIQUE ("version_id", "grouping", "din276_code", "label")
 , UNIQUE ("version_id", "ident")
 , FOREIGN KEY ("version_id") REFERENCES lcc.versions("id") ON DELETE CASCADE
 , FOREIGN KEY ("project_id") REFERENCES elca.projects("id") ON DELETE CASCADE
);

-------------------------------------------------------------------------------

CREATE TABLE lcc.regular_costs
(
   "cost_id"                    int          NOT NULL            -- costId
 , "ref_value"                  numeric                          -- refValue
 , "ref_unit"                   varchar(30)                      -- refUnit
 , PRIMARY KEY ("cost_id")
 , FOREIGN KEY ("cost_id") REFERENCES lcc.costs("id") ON DELETE CASCADE
);

-------------------------------------------------------------------------------

CREATE TABLE lcc.regular_service_costs
(
   "cost_id"                    int          NOT NULL            -- costId
 , "maintenance_perc"           numeric      NOT NULL            -- maintenance percentage
 , "service_perc"               numeric      NOT NULL            -- service percentage
 , PRIMARY KEY ("cost_id")
 , FOREIGN KEY ("cost_id") REFERENCES lcc.costs("id") ON DELETE CASCADE
);

-------------------------------------------------------------------------------

CREATE TABLE lcc.irregular_costs
(
   "cost_id"                    int          NOT NULL            -- costId
 , "life_time"                  int          NOT NULL            -- life_time
 , PRIMARY KEY ("cost_id")
 , FOREIGN KEY ("cost_id") REFERENCES lcc.costs("id") ON DELETE CASCADE
);



-------------------------------------------------------------------------------
-- project data
-------------------------------------------------------------------------------

CREATE OR REPLACE FUNCTION lcc.on_version_update_also_update_project_costs() RETURNS trigger
AS $$

BEGIN
    IF OLD.version_id <> NEW.version_id THEN

        UPDATE lcc.project_costs p
           SET cost_id = (SELECT id
                            FROM lcc.costs c
                           WHERE (c.version_id, c.grouping, c.din276_code, c.label) = (NEW.version_id, o.grouping, o.din276_code, o.label)
                         )
          FROM lcc.costs o
         WHERE o.id = p.cost_id
           AND p.project_variant_id = OLD.project_variant_id
           AND o.version_id = OLD.version_id;
    END IF;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-------------------------------------------------------------------------------

CREATE TABLE lcc.project_versions
(
   "project_variant_id"         int       NOT NULL                 -- projectVariantId
 , "calc_method"                smallint  NOT NULL                 -- calcMethod
 , "version_id"                 int       NOT NULL                 -- versionId
 , "category"                   smallint  NOT NULL DEFAULT 1       -- Sonderbedingungen Kategorie 1 oder 2
 , "costs_300"                  numeric                            -- Bauwerk- Baukonstruktion
 , "costs_400"                  numeric                            -- Bauwerk-Technische Anlagen
 , "costs_500"                  numeric                            -- Technische Anlagen in Aussenanlagen
 , "kgu300_alt"                 numeric                            -- kgu alternative value for kg300
 , "kgu400_alt"                 numeric                            -- kgu alternative value for kg400
 , "kgu500_alt"                 numeric                            -- kgu alternative value for kg500
 , PRIMARY KEY ("project_variant_id", "calc_method")
 , FOREIGN KEY ("project_variant_id") REFERENCES elca.project_variants ("id") ON DELETE CASCADE
 , FOREIGN KEY ("version_id") REFERENCES lcc.versions ("id") ON DELETE CASCADE
);

CREATE TRIGGER trigger_lcc_on_version_update_also_update_project_costs AFTER UPDATE ON lcc.project_versions
   FOR EACH ROW EXECUTE PROCEDURE lcc.on_version_update_also_update_project_costs();

-------------------------------------------------------------------------------

CREATE TABLE lcc.project_costs
(
   "project_variant_id"         int     NOT NULL                 -- projectVersionId
 , "calc_method"                smallint  NOT NULL                 -- calcMethod
 , "cost_id"                    int     NOT NULL                 -- regularCostId
 , "quantity"                   numeric                          -- quantity
 , "ref_value"                  numeric                          -- refValue
 , "energy_source_cost_id"      int                              -- energySourceCostId
 , PRIMARY KEY ("project_variant_id", "calc_method", "cost_id")
 , FOREIGN KEY ("project_variant_id") REFERENCES elca.project_variants ("id") ON DELETE CASCADE
 , FOREIGN KEY ("cost_id") REFERENCES lcc.costs ("id") ON DELETE CASCADE
 , FOREIGN KEY ("energy_source_cost_id") REFERENCES lcc.energy_source_costs ("id") ON UPDATE CASCADE ON DELETE SET NULL
);

-------------------------------------------------------------------------------

CREATE TABLE lcc.project_totals
(
   "project_variant_id"         int          NOT NULL            -- projectVariantId
 , "calc_method"                smallint  NOT NULL                 -- calcMethod
 , "grouping"                   varchar(100) NOT NULL            -- grouping
 , "costs"                      numeric                          -- costs
 , PRIMARY KEY ("project_variant_id", "calc_method", "grouping")
 , FOREIGN KEY ("project_variant_id") REFERENCES elca.project_variants ("id") ON DELETE CASCADE
);

-------------------------------------------------------------------------------

CREATE TABLE lcc.project_cost_progressions
(
   "project_variant_id"         int          NOT NULL            -- projectVariantId
 , "calc_method"                smallint  NOT NULL                 -- calcMethod
 , "grouping"                   varchar(100) NOT NULL            -- grouping
 , "life_time"                  int          NOT NULL            -- lifeTime
 , "quantity"                   numeric      NOT NULL            -- quantity
 , PRIMARY KEY ("project_variant_id", "calc_method", "grouping", "life_time")
 , FOREIGN KEY ("project_variant_id") REFERENCES elca.project_variants ("id") ON DELETE CASCADE
);


-------------------------------------------------------------------------------

CREATE TABLE lcc.element_type_costs
(
      "element_type_node_id"       int     NOT NULL                 -- elementTypeNodeId
    , "project_variant_id"         int          NOT NULL            -- projectVariantId
    , "calculated_quantity"        numeric                          -- calculated quantity
    , PRIMARY KEY ("project_variant_id", "element_type_node_id")
    , FOREIGN KEY ("project_variant_id") REFERENCES elca.project_variants ("id") ON DELETE CASCADE
    , FOREIGN KEY ("element_type_node_id") REFERENCES elca.element_types ("node_id") ON DELETE CASCADE
);

-------------------------------------------------------------------------------

CREATE TABLE lcc.element_costs
(
      "element_id"                 int     NOT NULL                 -- elementId
    , "quantity"                   numeric                          -- quantity
    , "life_time"                  int                              -- lifeTime
    , "calculated_quantity"        numeric                          -- calculated quantity
    , PRIMARY KEY ("element_id")
    , FOREIGN KEY ("element_id") REFERENCES elca.elements ("id") ON DELETE CASCADE
);

-------------------------------------------------------------------------------

CREATE TABLE lcc.element_cost_progressions
(
      "element_id"                 int     NOT NULL            -- elementId
    , "life_time"                  int     NOT NULL            -- lifeTime
    , "quantity"                   numeric NOT NULL            -- quantity
    , PRIMARY KEY ("element_id", "life_time")
    , FOREIGN KEY ("element_id") REFERENCES elca.elements ("id") ON DELETE CASCADE
);
-------------------------------------------------------------------------------

CREATE TABLE lcc.element_component_costs
(
      "element_component_id"       int     NOT NULL                 -- elementComponentId
    , "quantity"                   numeric NOT NULL                 -- quantity
    , PRIMARY KEY ("element_component_id")
    , FOREIGN KEY ("element_component_id") REFERENCES elca.element_components ("id") ON DELETE CASCADE
);

-------------------------------------------------------------------------------

CREATE TABLE lcc.element_component_cost_progressions
(
      "element_component_id"       int     NOT NULL            -- elementComponentId
    , "life_time"                  int     NOT NULL            -- lifeTime
    , "quantity"                   numeric NOT NULL            -- quantity
    , PRIMARY KEY ("element_component_id", "life_time")
    , FOREIGN KEY ("element_component_id") REFERENCES elca.element_components ("id") ON DELETE CASCADE
);

CREATE TABLE lcc.benchmark_thresholds
(
      "id"        serial NOT NULL
    , "benchmark_version_id" int NOT NULL
    , "category"  smallint  NOT NULL
    , "score" int   NOT NULL
    , "value"   numeric    NOT NULL
    , PRIMARY KEY ("id")
    , UNIQUE ("benchmark_version_id", "category", "score")
    , FOREIGN KEY ("benchmark_version_id") REFERENCES elca.benchmark_versions ("id") ON DELETE CASCADE
);

CREATE TABLE lcc.benchmark_groups
(
      "id"        serial  NOT NULL
    , "benchmark_version_id" int NOT NULL
    , "category"  smallint NOT NULL
    , "name"      varchar(200)  NOT NULL
    , PRIMARY KEY ("id")
    , UNIQUE ("benchmark_version_id", "category", "name")
    , FOREIGN KEY ("benchmark_version_id") REFERENCES elca.benchmark_versions ("id") ON DELETE CASCADE
);

CREATE TABLE lcc.benchmark_group_thresholds
(
      "id"        serial NOT NULL
    , "group_id"  int    NOT NULL
    , "score"     int     NOT NULL
    , "caption"   text    NOT NULL
    , PRIMARY KEY ("id")
    , UNIQUE ("group_id", "score")
    , FOREIGN KEY ("group_id") REFERENCES lcc.benchmark_groups ("id") ON DELETE CASCADE
);


-------------------------------------------------------------------------------
COMMIT;
