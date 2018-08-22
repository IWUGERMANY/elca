BEGIN;
SELECT public.register_patch('init-bnb-reference-model', 'elca');

ALTER TABLE elca.benchmark_versions ADD COLUMN "use_reference_model"   boolean          NOT NULL DEFAULT false;

CREATE TABLE elca.benchmark_ref_process_configs
(
      "benchmark_version_id"  int           NOT NULL                  -- benchmarkVersionId
    , "ident"                 varchar(30)   NOT NULL                  -- ident
    , "process_config_id"     int           NOT NULL                  -- reference process config
    , PRIMARY KEY ("benchmark_version_id", "ident")
    , FOREIGN KEY ("benchmark_version_id") REFERENCES elca.benchmark_versions ("id") ON UPDATE CASCADE ON DELETE CASCADE
    , FOREIGN KEY ("process_config_id") REFERENCES elca.process_configs ("id") ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE TABLE elca.benchmark_ref_construction_values
(
      "benchmark_version_id"  int           NOT NULL                  -- benchmarkVersionId
    , "indicator_id"          int           NOT NULL                  -- indicatorId
    , "value"                 numeric                                 -- reference construction value
    , PRIMARY KEY ("benchmark_version_id", "indicator_id")
    , FOREIGN KEY ("benchmark_version_id") REFERENCES elca.benchmark_versions ("id") ON UPDATE CASCADE ON DELETE CASCADE
    , FOREIGN KEY ("indicator_id") REFERENCES elca.indicators ("id") ON UPDATE CASCADE ON DELETE CASCADE
);


CREATE TABLE elca.project_final_energy_ref_models
(
      "id"                      serial          NOT NULL                -- projectFinalEnergyRefModelId
    , "project_variant_id"      int             NOT NULL                -- projectVariantId
    , "ident"                   varchar(30)     NOT NULL                -- ref model ident

    , "heating"                 numeric                                 -- heating in kWh/(m2*a)
    , "water"                   numeric                                 -- water in kWh/(m2*a)
    , "lighting"                numeric                                 -- lighting in kWh/(m2*a)
    , "ventilation"             numeric                                 -- ventilation in kWh/(m2*a)
    , "cooling"                 numeric                                 -- cooling in kWh/(m2*a)

    , PRIMARY KEY ("id")
    , UNIQUE ("project_variant_id", "ident")
    , FOREIGN KEY ("project_variant_id") REFERENCES elca.project_variants ("id") ON UPDATE CASCADE ON DELETE CASCADE
);


CREATE TABLE elca_cache.final_energy_ref_models
(
      "item_id"                integer         NOT NULL              -- itemId
    , "final_energy_ref_model_id" integer      NOT NULL              -- finalEnergyRefModelId

    , "quantity"               numeric                               -- quantity in refUnit / m2[NGF]a
    , "ref_unit"               varchar(10)                           -- refUnit

    , PRIMARY KEY ("item_id")
    , UNIQUE ("final_energy_ref_model_id")
    , FOREIGN KEY ("item_id") REFERENCES elca_cache.items("id") ON UPDATE CASCADE ON DELETE CASCADE
    , FOREIGN KEY ("final_energy_ref_model_id") REFERENCES elca.project_final_energy_ref_models("id") ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE TRIGGER trigger_elca_cache_on_delete_cascade AFTER DELETE ON elca_cache.final_energy_ref_models
FOR EACH ROW EXECUTE PROCEDURE elca_cache.on_delete_cascade();


DROP VIEW IF EXISTS elca_cache.final_energy_ref_models_v CASCADE;
CREATE OR REPLACE VIEW elca_cache.final_energy_ref_models_v AS
    SELECT i.*
        , e.*
    FROM elca_cache.final_energy_ref_models e
        JOIN elca_cache.items    i ON i.id = e.item_id;


DROP VIEW IF EXISTS elca_cache.report_construction_total_effects_v CASCADE;
CREATE OR REPLACE VIEW elca_cache.report_construction_total_effects_v AS
    SELECT ct.project_variant_id
        , ci.indicator_id
        , ci.value
    FROM elca_cache.element_types   ct
        JOIN elca.element_types_v        t ON t.level = 0 AND t.node_id = ct.element_type_node_id
        JOIN elca_cache.indicators      ci ON ci.item_id = ct.item_id AND ci.life_cycle_ident = 'total';

DROP VIEW IF EXISTS elca_cache.report_final_energy_ref_model_effects_v CASCADE;
CREATE OR REPLACE VIEW elca_cache.report_final_energy_ref_model_effects_v AS
    SELECT r.project_variant_id
        , ci.indicator_id
        , sum(ci.value) AS value
    FROM elca_cache.final_energy_ref_models cr
        JOIN elca.project_final_energy_ref_models r ON r.id = cr.final_energy_ref_model_id
        JOIN elca_cache.indicators              ci ON cr.item_id = ci.item_id AND ci.life_cycle_ident = 'total'
    GROUP BY r.project_variant_id
        , ci.indicator_id;


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

   -- loop through all outdated final_energy_demands
   FOR r IN SELECT item_id
                 , parent_id
              FROM elca_cache.final_energy_demands_v
             WHERE is_outdated = true
   LOOP
     PERFORM elca_cache.update_totals(r.item_id);
     UPDATE elca_cache.items SET is_outdated = true WHERE id = r.parent_id;
   END LOOP;

  -- loop through all outdated final_energy_supplies
  FOR r IN SELECT item_id
             , parent_id
           FROM elca_cache.final_energy_supplies_v
           WHERE is_outdated = true
  LOOP
    PERFORM elca_cache.update_totals(r.item_id);
    UPDATE elca_cache.items SET is_outdated = true WHERE id = r.parent_id;
  END LOOP;

  -- loop through all outdated final_energy_ref_models
  FOR r IN SELECT item_id
             FROM elca_cache.final_energy_ref_models_v
            WHERE is_outdated = true
  LOOP
      PERFORM elca_cache.update_totals(r.item_id);
      UPDATE elca_cache.items SET is_outdated = false WHERE id = r.item_id;
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
        UPDATE elca_cache.items SET is_outdated = true WHERE id = r.parent_id;

      ELSE
        UPDATE elca_cache.items
           SET is_outdated = true
         WHERE id = (SELECT v.item_id FROM elca.project_transport_means m
                                      JOIN elca.project_transports      t ON t.id = m.project_transport_id
                                      JOIN elca_cache.project_variants  v ON t.project_variant_id = v.project_variant_id
                                     WHERE m.id = r.transport_mean_id);
      END IF;
   END LOOP;

   FOR r IN SELECT item_id
              FROM elca_cache.project_variants_v
             WHERE is_outdated = true
   LOOP
     PERFORM elca_cache.update_project_variant(r.item_id);
   END LOOP;
END;
$$ LANGUAGE plpgsql;


DROP FUNCTION IF EXISTS elca_cache.update_element_type_tree(int);
CREATE OR REPLACE FUNCTION elca_cache.update_element_type_tree(in_item_id int)
    RETURNS void
--
-- updates all parent nodes for the given element_type item_id
--
AS $$

DECLARE
   indicator      record;
   parent_item_id int := in_item_id;
   old_parent_item_id int;
   parent_type    varchar;

BEGIN
    -- update mass from elements
    UPDATE elca_cache.element_types t
       SET mass = x.element_type_mass
      FROM elca_cache.element_type_mass_v x
     WHERE t.item_id = x.element_type_item_id
       AND t.item_id = in_item_id
       AND x.element_type_level > 0;

    LOOP
        -- aggregate indicators over all children
        PERFORM elca_cache.aggregate_indicators(parent_item_id);

        -- mark all children as up-to-date
        UPDATE elca_cache.items
           SET is_outdated = false
             , modified = now()
         WHERE parent_id = parent_item_id
           AND is_outdated = true
           AND type IN ('ElcaCacheElementType', 'ElcaCacheElement');

        -- the new parent_item_id is the parent's parent_id
        SELECT id
             , parent_id
             , type
          INTO old_parent_item_id, parent_item_id, parent_type
          FROM elca_cache.items
         WHERE id = parent_item_id;

        -- if parent type is not an element type then exit
        EXIT WHEN NOT FOUND OR parent_type <> 'ElcaCacheElementType';

        -- update mass for the parent type
        UPDATE elca_cache.element_types t
           SET mass = (SELECT sum(coalesce(mass, 0))
                         FROM elca_cache.element_types_v x
                        WHERE x.parent_id = t.item_id)
         WHERE t.item_id = parent_item_id;
   END LOOP;

   UPDATE elca_cache.items SET is_outdated = true WHERE id = parent_id;
END;
$$ LANGUAGE plpgsql;


COMMIT;