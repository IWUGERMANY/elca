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
CREATE SCHEMA elca_cache;
COMMIT;

BEGIN;
----------------------------------------------------------------------------------------

CREATE TABLE elca_cache.items
(
    "id"                   serial          NOT NULL               -- itemId
  , "project_id"           int             NOT NULL
  , "parent_id"            int                                    -- parent item
  , "type"                 varchar(100)    NOT NULL               -- item type

  , "is_outdated"          boolean         NOT NULL DEFAULT false -- if it is outdated, it needs updating
  , "is_virtual"           boolean         NOT NULL DEFAULT false

  , "created"              timestamptz(0)  NOT NULL DEFAULT now() -- creation time
  , "modified"             timestamptz(0)           DEFAULT now() -- modification time
  , PRIMARY KEY ("id")
  , FOREIGN KEY ("project_id") REFERENCES elca.projects ("id") ON UPDATE CASCADE ON DELETE CASCADE
  , FOREIGN KEY ("parent_id") REFERENCES elca_cache.items ("id") ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE INDEX IX_elca_cache_items_type ON elca_cache.items ("type");
CREATE INDEX IX_elca_cache_items_is_outdated ON elca_cache.items ("is_outdated");
CREATE INDEX IX_elca_cache_items_parent_id ON elca_cache.items ("parent_id");

----------------------------------------------------------------------------------------

CREATE FUNCTION elca_cache.on_delete_cascade() RETURNS trigger
AS $$

BEGIN
    DELETE FROM elca_cache.items WHERE id = OLD.item_id;
    RETURN OLD;
END;
$$ LANGUAGE plpgsql;

----------------------------------------------------------------------------------------


CREATE TABLE elca_cache.elements
(
    "item_id"              integer         NOT NULL              -- itemId
  , "element_id"           integer         NOT NULL              -- elementId
  , "composite_item_id"    integer                               -- composite element itemId

  , "mass"                 numeric                               -- mass of the element
  , "quantity"             numeric                               -- quantity
  , "ref_unit"             varchar(10)                           -- refUnit

  , PRIMARY KEY ("item_id")
  , UNIQUE ("element_id")
  , FOREIGN KEY ("item_id") REFERENCES elca_cache.items("id") ON UPDATE CASCADE ON DELETE CASCADE
  , FOREIGN KEY ("element_id") REFERENCES elca.elements("id") ON UPDATE CASCADE ON DELETE CASCADE
  , FOREIGN KEY ("composite_item_id") REFERENCES elca_cache.items("id") ON UPDATE CASCADE ON DELETE SET NULL
);

CREATE TRIGGER trigger_elca_cache_on_delete_cascade AFTER DELETE ON elca_cache.elements
   FOR EACH ROW EXECUTE PROCEDURE elca_cache.on_delete_cascade();

CREATE INDEX IX_elca_cache_elements_composite_item_id ON elca_cache.elements ("composite_item_id");

----------------------------------------------------------------------------------------

CREATE TABLE elca_cache.element_components
(
    "item_id"              integer         NOT NULL              -- itemId
  , "element_component_id" integer         NOT NULL              -- elementComponentId

  , "mass"                 numeric                               -- mass of the element
  , "quantity"             numeric                               -- quantity
  , "ref_unit"             varchar(10)                           -- refUnit
  , "num_replacements"     integer                               -- numReplacemenents

  , PRIMARY KEY ("item_id")
  , UNIQUE ("element_component_id")
  , FOREIGN KEY ("item_id") REFERENCES elca_cache.items("id") ON UPDATE CASCADE ON DELETE CASCADE
  , FOREIGN KEY ("element_component_id") REFERENCES elca.element_components("id") ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE TRIGGER trigger_elca_cache_on_delete_cascade AFTER DELETE ON elca_cache.element_components
   FOR EACH ROW EXECUTE PROCEDURE elca_cache.on_delete_cascade();

----------------------------------------------------------------------------------------

CREATE TABLE elca_cache.element_types
(
    "item_id"              integer         NOT NULL              -- itemId
  , "project_variant_id"   integer         NOT NULL               -- projectVariantId
  , "element_type_node_id" integer         NOT NULL              -- elementTypeNodeId

  , "mass"                 numeric                               -- mass aggregation

  , PRIMARY KEY ("item_id")
  , UNIQUE ("project_variant_id", "element_type_node_id")
  , FOREIGN KEY ("item_id") REFERENCES elca_cache.items("id") ON UPDATE CASCADE ON DELETE CASCADE
  , FOREIGN KEY ("project_variant_id") REFERENCES elca.project_variants("id") ON UPDATE CASCADE ON DELETE CASCADE
  , FOREIGN KEY ("element_type_node_id") REFERENCES elca.element_types("node_id") ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE TRIGGER trigger_elca_cache_on_delete_cascade AFTER DELETE ON elca_cache.element_types
   FOR EACH ROW EXECUTE PROCEDURE elca_cache.on_delete_cascade();

----------------------------------------------------------------------------------------

CREATE TABLE elca_cache.project_variants
(
    "item_id"              integer         NOT NULL              -- itemId
  , "project_variant_id"   integer         NOT NULL               -- projectVariantId

  , PRIMARY KEY ("item_id")
  , UNIQUE ("project_variant_id")
  , FOREIGN KEY ("item_id") REFERENCES elca_cache.items("id") ON UPDATE CASCADE ON DELETE CASCADE
  , FOREIGN KEY ("project_variant_id") REFERENCES elca.project_variants("id") ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE TRIGGER trigger_elca_cache_on_delete_cascade AFTER DELETE ON elca_cache.project_variants
FOR EACH ROW EXECUTE PROCEDURE elca_cache.on_delete_cascade();

----------------------------------------------------------------------------------------

CREATE TABLE elca_cache.final_energy_demands
(
    "item_id"                integer         NOT NULL              -- itemId
  , "final_energy_demand_id" integer         NOT NULL              -- finalEnergyDemandId

  , "quantity"               numeric                               -- quantity in refUnit / m2[NGF]a
  , "ref_unit"               varchar(10)                           -- refUnit

  , PRIMARY KEY ("item_id")
  , UNIQUE ("final_energy_demand_id")
  , FOREIGN KEY ("item_id") REFERENCES elca_cache.items("id") ON UPDATE CASCADE ON DELETE CASCADE
  , FOREIGN KEY ("final_energy_demand_id") REFERENCES elca.project_final_energy_demands("id") ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE TRIGGER trigger_elca_cache_on_delete_cascade AFTER DELETE ON elca_cache.final_energy_demands
   FOR EACH ROW EXECUTE PROCEDURE elca_cache.on_delete_cascade();


----------------------------------------------------------------------------------------

CREATE TABLE elca_cache.final_energy_supplies
(
    "item_id"                integer         NOT NULL              -- itemId
  , "final_energy_supply_id" integer         NOT NULL              -- finalEnergySupplyId

  , "quantity"               numeric                               -- quantity in refUnit / m2[NGF]a
  , "ref_unit"               varchar(10)                           -- refUnit

  , PRIMARY KEY ("item_id")
  , UNIQUE ("final_energy_supply_id")
  , FOREIGN KEY ("item_id") REFERENCES elca_cache.items("id") ON UPDATE CASCADE ON DELETE CASCADE
  , FOREIGN KEY ("final_energy_supply_id") REFERENCES elca.project_final_energy_supplies("id") ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE TRIGGER trigger_elca_cache_on_delete_cascade AFTER DELETE ON elca_cache.final_energy_supplies
FOR EACH ROW EXECUTE PROCEDURE elca_cache.on_delete_cascade();

----------------------------------------------------------------------------------------

CREATE TABLE elca_cache.final_energy_ref_models
(
      "item_id"                integer         NOT NULL              -- itemId
    , "final_energy_ref_model_id" integer         NOT NULL              -- finalEnergyRefModelId

    , "quantity"               numeric                               -- quantity in refUnit / m2[NGF]a
    , "ref_unit"               varchar(10)                           -- refUnit

    , PRIMARY KEY ("item_id")
    , UNIQUE ("final_energy_ref_model_id")
    , FOREIGN KEY ("item_id") REFERENCES elca_cache.items("id") ON UPDATE CASCADE ON DELETE CASCADE
    , FOREIGN KEY ("final_energy_ref_model_id") REFERENCES elca.project_final_energy_ref_models("id") ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE TRIGGER trigger_elca_cache_on_delete_cascade AFTER DELETE ON elca_cache.final_energy_ref_models
FOR EACH ROW EXECUTE PROCEDURE elca_cache.on_delete_cascade();

----------------------------------------------------------------------------------------

CREATE TABLE elca_cache.transport_means
(
    "item_id"                integer         NOT NULL              -- itemId
  , "transport_mean_id"      integer         NOT NULL              -- transportMeanId

  , "quantity"               numeric                               -- quantity
  , "ref_unit"               varchar(10)                           -- refUnit

  , PRIMARY KEY ("item_id")
  , UNIQUE ("transport_mean_id")
  , FOREIGN KEY ("item_id") REFERENCES elca_cache.items("id") ON UPDATE CASCADE ON DELETE CASCADE
  , FOREIGN KEY ("transport_mean_id") REFERENCES elca.project_transport_means("id") ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE TRIGGER trigger_elca_cache_on_delete_cascade AFTER DELETE ON elca_cache.transport_means
FOR EACH ROW EXECUTE PROCEDURE elca_cache.on_delete_cascade();

----------------------------------------------------------------------------------------

CREATE TABLE elca_cache.indicators
(
    "item_id"              integer         NOT NULL               -- itemId
  , "life_cycle_ident"     varchar(20)     NOT NULL               -- life cycle ident
  , "indicator_id"         integer         NOT NULL               -- indicator_id
  , "process_id"           integer                                -- process_id
  , "value"                numeric         NOT NULL               -- value

  , "ratio"                numeric         NOT NULL DEFAULT 1     -- info about ratio
  , "is_partial"           boolean         NOT NULL DEFAULT false -- marks the values as part of a series

  , UNIQUE ("item_id", "life_cycle_ident", "indicator_id", "process_id")
  , FOREIGN KEY ("item_id") REFERENCES elca_cache.items("id") ON UPDATE CASCADE ON DELETE CASCADE
  , FOREIGN KEY ("life_cycle_ident") REFERENCES elca.life_cycles("ident") ON UPDATE CASCADE ON DELETE CASCADE
  , FOREIGN KEY ("indicator_id") REFERENCES elca.indicators("id") ON UPDATE CASCADE ON DELETE CASCADE
  , FOREIGN KEY ("process_id") REFERENCES elca.processes("id") ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE INDEX IX_elca_cache_indicators_process_id ON elca_cache.indicators (process_id);

----------------------------------------------------------------------------------------
COMMIT;