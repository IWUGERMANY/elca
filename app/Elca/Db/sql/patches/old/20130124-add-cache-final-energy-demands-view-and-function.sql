BEGIN;
SELECT public.register_patch('add-cache-final_energy_demand-view-and-function', 'elca');

DROP VIEW IF EXISTS elca_cache.final_energy_demands_v CASCADE;
CREATE OR REPLACE VIEW elca_cache.final_energy_demands_v AS
  SELECT i.*
       , e.*
   FROM elca_cache.final_energy_demands e
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

BEGIN
   -- loop through all outdated components
   -- and rebuild totals
   FOR r IN SELECT item_id
              FROM elca_cache.element_components_v
             WHERE is_outdated = true
   LOOP
        PERFORM elca_cache.update_totals(r.item_id);
   END LOOP;

   -- update all outdated components
   UPDATE elca_cache.items
      SET is_outdated = false
        , modified = now()
    WHERE type = 'ElcaCacheElementComponent'
      AND is_outdated = true;

   -- loop through all outdated elements
   FOR r IN SELECT item_id
                 , parent_id
              FROM elca_cache.elements_v
             WHERE is_outdated = true
   LOOP
        PERFORM elca_cache.aggregate_indicators(r.item_id);

        -- append to parent and outdated items
        parents  := parents || r.parent_id;
        outdated := outdated || r.item_id;
   END LOOP;

   -- loop through all outdated final_energy_demands
   FOR r IN SELECT item_id
                 , parent_id
              FROM elca_cache.final_energy_demands_v
             WHERE is_outdated = true
   LOOP
        PERFORM elca_cache.update_totals(r.item_id);

        -- append to parent and outdated items
        parents  := parents || r.parent_id;
        outdated := outdated || r.item_id;
   END LOOP;

   -- update element mass
   UPDATE elca_cache.elements e
      SET mass = x.element_mass
     FROM elca_cache.element_mass_v x
    WHERE e.item_id = x.element_item_id
      AND e.item_id = ANY (outdated);

   -- update tree for each (distinct) parent
   FOR r IN SELECT DISTINCT unnest(parents) AS parent

   LOOP
        PERFORM elca_cache.update_element_type_tree(r.parent);
   END LOOP;

END;
$$ LANGUAGE plpgsql;


COMMIT;