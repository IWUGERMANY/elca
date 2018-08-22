BEGIN;
SELECT public.register_patch('fix-elca_cache-update_element_type_tree', 'elca');

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
         WHERE parent_item_id IN (id, parent_id)
           AND is_outdated = true;

        -- the new parent_item_id is the parent's parent_id
        SELECT id
             , parent_id
             , type
          INTO old_parent_item_id, parent_item_id, parent_type
          FROM elca_cache.items
         WHERE id = parent_item_id;

        -- if parent type is not of element types then update the cache root project variant
        IF parent_type = 'ElcaCacheProjectVariant' THEN
          PERFORM elca_cache.update_project_variant(old_parent_item_id);

          -- and exit
          EXIT;

        ELSEIF NOT FOUND THEN
          EXIT;
        END IF;

        -- update mass for the parent type
        UPDATE elca_cache.element_types t
           SET mass = (SELECT sum(coalesce(mass, 0))
                         FROM elca_cache.element_types_v x
                        WHERE x.parent_id = t.item_id)
         WHERE t.item_id = parent_item_id;

   END LOOP;
END;
$$ LANGUAGE plpgsql;

COMMIT;