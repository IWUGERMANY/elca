BEGIN;
SELECT public.register_patch('20170616-alter-update_cache.sql', 'matchmaking');

DROP FUNCTION IF EXISTS elca_cache.update_cache();

CREATE OR REPLACE FUNCTION elca_cache.update_cache(IN in_project_id int)
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
             WHERE project_id = in_project_id AND is_outdated = true
    LOOP
        PERFORM elca_cache.update_totals(r.item_id);
    END LOOP;

    -- remove outdated mark on those components
    UPDATE elca_cache.items
    SET is_outdated = false
        , modified = now()
    WHERE type = 'Elca\Db\ElcaCacheElementComponent'
          AND project_id = in_project_id AND is_outdated = true;

    -- loop through all outdated elements
    FOR r IN SELECT item_id
                 , parent_id
                 , CASE WHEN is_virtual THEN item_id
                   ELSE composite_item_id
                   END AS composite_item_id
                 , is_virtual
             FROM elca_cache.elements_v
             WHERE project_id = in_project_id AND is_outdated = true
    LOOP
        IF NOT r.is_virtual THEN -- it is no composite element
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
    SET mass = (SELECT element_mass FROM elca_cache.element_mass_v x WHERE x.element_item_id = e.item_id)
    WHERE e.item_id = ANY (outdated);

    -- update element mass on composite elements
    UPDATE elca_cache.elements e
    SET mass = (SELECT element_mass FROM elca_cache.composite_element_mass_v x WHERE x.composite_item_id = e.item_id)
    WHERE e.item_id = ANY (composites);

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
             WHERE project_id = in_project_id AND is_outdated = true
    LOOP
        PERFORM elca_cache.update_totals(r.item_id);
        UPDATE elca_cache.items SET is_outdated = true WHERE id = r.parent_id;
    END LOOP;

    -- loop through all outdated final_energy_supplies
    FOR r IN SELECT item_id
                 , parent_id
             FROM elca_cache.final_energy_supplies_v
             WHERE project_id = in_project_id AND is_outdated = true
    LOOP
        PERFORM elca_cache.update_totals(r.item_id);
        UPDATE elca_cache.items SET is_outdated = true WHERE id = r.parent_id;
    END LOOP;

    -- loop through all outdated final_energy_ref_models
    FOR r IN SELECT item_id
             FROM elca_cache.final_energy_ref_models_v
             WHERE project_id = in_project_id AND is_outdated = true
    LOOP
        PERFORM elca_cache.update_totals(r.item_id);
        UPDATE elca_cache.items SET is_outdated = false WHERE id = r.item_id;
    END LOOP;

    -- loop through all outdated transport means
    FOR r IN SELECT item_id
                 , parent_id
                 , is_virtual
                 , transport_mean_id
             FROM elca_cache.transport_means_v
             WHERE project_id = in_project_id AND is_outdated = true
    LOOP
        PERFORM elca_cache.update_totals(r.item_id);

        IF NOT r.is_virtual THEN
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
             WHERE project_id = in_project_id AND is_outdated = true
    LOOP
        PERFORM elca_cache.update_project_variant(r.item_id);
    END LOOP;
END;
$$ LANGUAGE plpgsql;


DROP FUNCTION IF EXISTS elca_cache.update_totals(int);
CREATE OR REPLACE FUNCTION elca_cache.update_totals(IN in_item_id int)
    RETURNS void
    --
    -- updates all indicator totals for the given cache item
    --
AS $$

BEGIN
    -- delete all old indicators
    DELETE FROM elca_cache.indicators WHERE item_id = in_item_id AND life_cycle_ident = 'total';

    INSERT INTO elca_cache.indicators (item_id, life_cycle_ident, indicator_id, is_partial, value)
        SELECT item_id
            , life_cycle_ident
            , indicator_id
            , is_partial
            , value
        FROM elca_cache.indicators_totals_v
        WHERE item_id = in_item_id;
END;
$$ LANGUAGE plpgsql;

COMMIT;