BEGIN;
SELECT public.register_patch('init-final-energy-supplies', 'elca');

CREATE TABLE elca.project_final_energy_supplies
(
    "id"                      serial          NOT NULL                -- projectFinalEnergyDemandId
  , "project_variant_id"      int             NOT NULL                -- projectVariantId
  , "process_config_id"       int             NOT NULL                -- process config id
  , "en_ev_ratio"             numeric         NOT NULL DEFAULT 1      -- ratio included in en ev
  , "quantity"                numeric         NOT NULL                -- total in kWh/a
  , "description"             text            NOT NULL                -- description

  , PRIMARY KEY ("id")
  , FOREIGN KEY ("project_variant_id") REFERENCES elca.project_variants ("id") ON UPDATE CASCADE ON DELETE CASCADE
  , FOREIGN KEY ("process_config_id") REFERENCES elca.process_configs ("id") ON UPDATE CASCADE ON DELETE CASCADE
);

ALTER TABLE elca.project_en_ev ADD "unit_demand"             smallint        NOT NULL DEFAULT 0;
ALTER TABLE elca.project_en_ev ADD "unit_supply"             smallint        NOT NULL DEFAULT 0;

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

DROP VIEW IF EXISTS elca_cache.final_energy_supplies_v CASCADE;
CREATE OR REPLACE VIEW elca_cache.final_energy_supplies_v AS
  SELECT i.*
    , e.*
  FROM elca_cache.final_energy_supplies e
    JOIN elca_cache.items    i ON i.id = e.item_id;

DROP FUNCTION IF EXISTS elca_cache.update_cache();
CREATE OR REPLACE FUNCTION elca_cache.update_cache()
  RETURNS void
--
-- Updates all outdated components, elements and its ancestor element types
--
AS $$

DECLARE
   r  record;
   parents int ARRAY;
   outdated int ARRAY;
   composites int ARRAY;

BEGIN
   -- loop through all outdated element components
   -- and rebuild indicator totals
   FOR r IN SELECT item_id
              FROM elca_cache.element_components_v
             WHERE is_outdated = true
   LOOP
        PERFORM elca_cache.update_totals(r.item_id);
   END LOOP;

   -- remove outdated mark on those components
   UPDATE elca_cache.items
      SET is_outdated = false
        , modified = now()
    WHERE type = 'ElcaCacheElementComponent'
      AND is_outdated = true;

   -- loop through all outdated elements
   FOR r IN SELECT item_id
                 , parent_id
                 , CASE WHEN parent_id IS NULL THEN item_id
                        ELSE composite_item_id
                   END AS composite_item_id
              FROM elca_cache.elements_v
             WHERE is_outdated = true
   LOOP
        IF r.parent_id IS NOT NULL THEN
            PERFORM elca_cache.aggregate_indicators(r.item_id);
            parents  := parents || r.parent_id;
            outdated := outdated || r.item_id;

            IF r.composite_item_id IS NOT NULL THEN
                composites := composites || r.composite_item_id;
            END IF;
        ELSE
            composites := composites || r.composite_item_id;
        END IF;
   END LOOP;

   -- aggregate element indicators on composite elements
   FOR r IN SELECT DISTINCT unnest(composites) AS composite_item_id

   LOOP
        PERFORM elca_cache.aggregate_composite_indicators(r.composite_item_id);
   END LOOP;

   -- update element mass on components
   UPDATE elca_cache.elements e
      SET mass = x.element_mass
     FROM elca_cache.element_mass_v x
    WHERE e.item_id = x.element_item_id
      AND e.item_id = ANY (outdated);

   -- update element mass on composite elements
   UPDATE elca_cache.elements e
      SET mass = x.element_mass
     FROM elca_cache.composite_element_mass_v x
    WHERE e.item_id = x.composite_item_id
      AND e.item_id = ANY (composites);

   -- update all outdated composites
   UPDATE elca_cache.items
      SET is_outdated = false
        , modified = now()
    WHERE id = ANY (composites)
      AND is_outdated = true;

    -- update tree for each (distinct) parent
   FOR r IN SELECT DISTINCT unnest(parents) AS parent

   LOOP
     PERFORM elca_cache.update_element_type_tree(r.parent);
   END LOOP;

   -- clear parents
   parents := ARRAY[]::integer[];

   -- loop through all outdated final_energy_demands
   FOR r IN SELECT item_id
                 , parent_id
              FROM elca_cache.final_energy_demands_v
             WHERE is_outdated = true
   LOOP
     PERFORM elca_cache.update_totals(r.item_id);

        -- append to parents
        parents := parents || r.parent_id;
   END LOOP;

  -- loop through all outdated final_energy_supplies
  FOR r IN SELECT item_id
             , parent_id
           FROM elca_cache.final_energy_supplies_v
           WHERE is_outdated = true
  LOOP
    PERFORM elca_cache.update_totals(r.item_id);

  -- append to parents
    parents := parents || r.parent_id;
  END LOOP;


-- loop through all outdated transport means
   FOR r IN SELECT item_id
                 , parent_id
                 , transport_mean_id
              FROM elca_cache.transport_means_v
             WHERE is_outdated = true
   LOOP
     PERFORM elca_cache.update_totals(r.item_id);

      IF r.parent_id IS NOT NULL THEN
        -- append to parent
        parents := parents || r.parent_id;
      ELSE
        parents := parents || (SELECT v.item_id FROM elca.project_transport_means m
                                                JOIN elca.project_transports      t ON t.id = m.project_transport_id
                                                JOIN elca_cache.project_variants  v ON t.project_variant_id = v.project_variant_id
                                WHERE m.id = r.transport_mean_id);
      END IF;
   END LOOP;

   FOR r IN SELECT DISTINCT unnest(parents) AS parent

   LOOP
     PERFORM elca_cache.update_project_variant(r.parent);
   END LOOP;
END;
$$ LANGUAGE plpgsql;


COMMIT;