BEGIN;
SELECT public.register_patch('fix-elca_cache-update-stale-element-types', 'elca');

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
   variants int ARRAY;

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
       variants := variants || elca_cache.update_element_type_tree(r.parent);
   END LOOP;

   -- update tree for element types which do not have child elements anymore
    FOR r IN SELECT DISTINCT unnest(variants) AS variant_item_id
    LOOP
        PERFORM elca_cache.update_element_type_tree(t.id)
           FROM elca_cache.project_variants v
           JOIN elca_cache.element_types_v t ON v.project_variant_id = t.project_variant_id
           LEFT JOIN elca_cache.elements_v e ON e.parent_id = t.item_id
          WHERE e.id IS NULL
            AND t.level = 3
            AND v.item_id = r.variant_item_id;
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

   FOR r IN SELECT DISTINCT unnest(variants) AS variant_item_id
   LOOP
     PERFORM elca_cache.update_project_variant(r.variant_item_id);
   END LOOP;
END;
$$ LANGUAGE plpgsql;

--------------------------------------------------------------------------------

DROP FUNCTION IF EXISTS elca_cache.update_element_type_tree(int);
CREATE OR REPLACE FUNCTION elca_cache.update_element_type_tree(in_item_id int)
    RETURNS int
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

   -- return project variant item id
   RETURN old_parent_item_id;
END;
$$ LANGUAGE plpgsql;

COMMIT;
