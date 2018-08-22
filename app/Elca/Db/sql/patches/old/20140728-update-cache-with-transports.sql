BEGIN;
SELECT public.register_patch('update-cache-with-transports', 'elca');

CREATE OR REPLACE VIEW elca_cache.project_variant_process_config_mass_v AS
  SELECT e.project_variant_id
    , c.process_config_id
    , p.name
    , sum(cec.mass) AS mass
  FROM elca_cache.element_components cec
    JOIN elca.element_components c ON c.id = cec.element_component_id
    JOIN elca.elements e ON e.id = c.element_id
    JOIN elca.process_configs p ON p.id = c.process_config_id
  GROUP BY e.project_variant_id
    , c.process_config_id
    , p.name;


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


DROP VIEW IF EXISTS elca_cache.transport_means_v CASCADE;
CREATE OR REPLACE VIEW elca_cache.transport_means_v AS
  SELECT i.*
    , t.*
  FROM elca_cache.transport_means t
    JOIN elca_cache.items    i ON i.id = t.item_id;



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

   -- loop through all outdated final_energy_demands
   FOR r IN SELECT item_id
                 , parent_id
              FROM elca_cache.final_energy_demands_v
             WHERE is_outdated = true
   LOOP
        PERFORM elca_cache.update_totals(r.item_id);

        -- append to parents
        parents  := parents || r.parent_id;
   END LOOP;

   -- loop through all outdated transport means
   FOR r IN SELECT item_id
                 , parent_id
              FROM elca_cache.transport_means_v
             WHERE is_outdated = true
   LOOP
      PERFORM elca_cache.update_totals(r.item_id);

      -- append to parent
      parents  := parents || r.parent_id;
   END LOOP;


   -- update tree for each (distinct) parent
   FOR r IN SELECT DISTINCT unnest(parents) AS parent

   LOOP
        PERFORM elca_cache.update_element_type_tree(r.parent);
   END LOOP;

END;
$$ LANGUAGE plpgsql;

DROP VIEW IF EXISTS elca_cache.report_transport_effects_v CASCADE;
CREATE VIEW elca_cache.report_transport_effects_v AS
  SELECT m.id AS transport_mean_id
    , t.id AS transport_id
    , t.project_variant_id
    , cm.quantity AS transport_quantity
    , cm.ref_unit AS transport_ref_unit
    , pc.name AS process_config_name
    , ci.indicator_id AS indicator_id
    , ci.value AS indicator_value
    , i.ident AS indicator_ident
    , i.name AS indicator_name
    , i.unit AS indicator_unit
    , i.p_order AS indicator_p_order
  FROM elca.project_transports            t
    JOIN elca.project_transport_means       m ON t.id = m.project_transport_id
    JOIN elca.process_configs              pc ON pc.id = m.process_config_id
    JOIN elca_cache.transport_means_v      cm ON m.id = cm.transport_mean_id
    JOIN elca_cache.indicators             ci ON cm.item_id = ci.item_id AND ci.life_cycle_ident = 'total'
    JOIN elca.indicators                    i ON i.id = ci.indicator_id;

COMMIT;