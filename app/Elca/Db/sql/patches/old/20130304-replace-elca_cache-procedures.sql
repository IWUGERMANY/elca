BEGIN;
SELECT public.register_patch('replace-elca_cache-procedures', 'elca');

DROP VIEW IF EXISTS elca_cache.composite_indicators_aggregate_v CASCADE;
CREATE OR REPLACE VIEW elca_cache.composite_indicators_aggregate_v AS
   SELECT e.composite_item_id
        , i.life_cycle_ident
        , i.indicator_id
        , null::int AS process_id
        , sum(i.value) AS value
        , bool_and(i.is_partial) AS is_partial
     FROM elca_cache.elements_v e
     JOIN elca_cache.indicators_v i ON e.item_id = i.item_id
    WHERE e.composite_item_id IS NOT NULL
 GROUP BY e.composite_item_id
        , life_cycle_ident
        , indicator_id;



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
   -- loop through all outdated components
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

--------------------------------------------------------------------------------

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

BEGIN
    -- update mass from elements
    UPDATE elca_cache.element_types t
       SET mass = x.element_type_mass
      FROM elca_cache.element_type_mass_v x
     WHERE t.item_id = x.element_type_item_id
       AND t.item_id = in_item_id
       AND x.element_type_parent_id IS NOT NULL;

    LOOP
--        RAISE NOTICE 'Working on childs of %', parent_item_id;

        -- aggregate indicators over all children
        FOR indicator IN SELECT t.item_id
                              , i.life_cycle_ident
                              , i.indicator_id
                              , i.is_partial
                              , i.value
                           FROM elca_cache.element_types t
                      LEFT JOIN elca_cache.indicators_aggregate_v i ON t.item_id = i.item_id
                          WHERE t.item_id = parent_item_id
        LOOP

            IF indicator.life_cycle_ident IS NULL THEN
                -- clear indicators
                UPDATE elca_cache.indicators
                   SET value = 0
                 WHERE item_id = parent_item_id;

            ELSE
                -- update or insert values for each indicator
                UPDATE elca_cache.indicators
                   SET value = indicator.value
                 WHERE (item_id, life_cycle_ident, indicator_id) = (parent_item_id, indicator.life_cycle_ident, indicator.indicator_id);

                IF NOT FOUND THEN
                   INSERT INTO elca_cache.indicators (item_id, life_cycle_ident, indicator_id, is_partial, value)
                                            VALUES (parent_item_id
                                                 ,  indicator.life_cycle_ident
                                                 ,  indicator.indicator_id
                                                 ,  indicator.is_partial
                                                 ,  indicator.value);
                END IF;
            END IF;
        END LOOP;

        -- mark all children as up-to-date
        UPDATE elca_cache.items
           SET is_outdated = false
             , modified = now()
         WHERE parent_item_id IN (id, parent_id)
           AND is_outdated = true;

        -- the new parent_item_id is the parent's parent_id
        SELECT INTO parent_item_id
               parent_id
          FROM elca_cache.items
         WHERE id = parent_item_id;

        -- exit if there is no parent
        EXIT WHEN parent_item_id IS NULL;

        -- update mass for the parent type
        UPDATE elca_cache.element_types t
           SET mass = (SELECT sum(coalesce(mass, 0))
                         FROM elca_cache.element_types_v x
                        WHERE x.parent_id = t.item_id)
         WHERE t.item_id = parent_item_id;
   END LOOP;
END;
$$ LANGUAGE plpgsql;

--------------------------------------------------------------------------------

DROP FUNCTION IF EXISTS elca_cache.update_totals(int);
CREATE OR REPLACE FUNCTION elca_cache.update_totals(IN in_item_id int)
              RETURNS void
--
-- updates all indicator totals for the given cache item
--
AS $$

BEGIN
    -- build indicators totals (UPDATE / INSERT)
    UPDATE elca_cache.indicators i
       SET value = t.value
      FROM elca_cache.indicators_totals_v t
     WHERE i.item_id = t.item_id
       AND i.life_cycle_ident = t.life_cycle_ident
       AND i.indicator_id  = t.indicator_id
       AND i.process_id IS NULL
       AND i.item_id = in_item_id;

    IF NOT FOUND THEN
        INSERT INTO elca_cache.indicators (item_id, life_cycle_ident, indicator_id, is_partial, value)
                 SELECT item_id
                      , life_cycle_ident
                      , indicator_id
                      , is_partial
                      , value
                   FROM elca_cache.indicators_totals_v
                  WHERE item_id = in_item_id;
    END IF;
END;
$$ LANGUAGE plpgsql;

--------------------------------------------------------------------------------

DROP FUNCTION IF EXISTS elca_cache.aggregate_indicators(int);
CREATE OR REPLACE FUNCTION elca_cache.aggregate_indicators(IN in_item_id int)
              RETURNS void
--
-- updates aggregates of all indicators for the given cache item
--
AS $$

BEGIN
    -- delete all old indicators
    DELETE FROM elca_cache.indicators WHERE item_id = in_item_id;

    -- insert new indicators
    INSERT INTO elca_cache.indicators (item_id, life_cycle_ident, indicator_id, is_partial, value)
             SELECT item_id
                  , life_cycle_ident
                  , indicator_id
                  , is_partial
                  , value
               FROM elca_cache.indicators_aggregate_v
              WHERE item_id = in_item_id;
END;
$$ LANGUAGE plpgsql;

--------------------------------------------------------------------------------

DROP FUNCTION IF EXISTS elca_cache.aggregate_composite_indicators(int);
CREATE OR REPLACE FUNCTION elca_cache.aggregate_composite_indicators(IN in_item_id int)
              RETURNS void
--
-- updates aggregates of all indicators for the given composite item
--
AS $$

BEGIN
    RAISE NOTICE 'aggregate_composite_indicators(%)', in_item_id;
    -- delete all old indicators
    DELETE FROM elca_cache.indicators WHERE item_id = in_item_id;

    -- insert new indicators
    INSERT INTO elca_cache.indicators (item_id, life_cycle_ident, indicator_id, is_partial, value)
             SELECT composite_item_id
                  , life_cycle_ident
                  , indicator_id
                  , is_partial
                  , value
               FROM elca_cache.composite_indicators_aggregate_v
              WHERE composite_item_id = in_item_id;
END;
$$ LANGUAGE plpgsql;

COMMIT;