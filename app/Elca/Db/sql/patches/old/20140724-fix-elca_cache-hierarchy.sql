BEGIN;
SELECT public.register_patch('fix-elca_cache-hierarchy', 'elca');

CREATE TABLE elca_cache.project_variants
(
    "item_id"              integer         NOT NULL              -- itemId
  , "project_variant_id"   integer         NOT NULL               -- projectVariantId

  , PRIMARY KEY ("item_id")
  , UNIQUE ("project_variant_id")
  , FOREIGN KEY ("item_id") REFERENCES elca_cache.items("id") ON UPDATE CASCADE ON DELETE CASCADE
  , FOREIGN KEY ("project_variant_id") REFERENCES elca.project_variants("id") ON UPDATE CASCADE ON DELETE CASCADE
);

DROP VIEW IF EXISTS elca_cache.project_variants_v CASCADE;
CREATE OR REPLACE VIEW elca_cache.project_variants_v AS
  SELECT i.*
    , v.*
  FROM elca_cache.project_variants v
    JOIN elca_cache.items    i ON i.id = v.item_id;

DROP VIEW IF EXISTS elca_cache.report_total_effects_v;
CREATE VIEW elca_cache.report_total_effects_v AS
  SELECT ci.item_id
    , ci.indicator_id
    , ci.value
    , i.name AS name
    , i.ident AS ident
    , i.unit AS unit
    , i.p_order AS indicator_p_order
    , v.project_variant_id
    , 'Gesamt'::varchar AS category
  FROM elca_cache.project_variants v
    JOIN elca_cache.indicators ci ON ci.item_id = v.item_id
    JOIN elca.indicators i ON i.id = ci.indicator_id
  WHERE ci.life_cycle_ident = 'total';

DROP VIEW IF EXISTS elca_cache.report_life_cycle_effects_v;
CREATE VIEW elca_cache.report_life_cycle_effects_v AS
  SELECT cv.project_variant_id
    , ci.item_id
    , ci.indicator_id
    , ci.value
    , i.name AS name
    , i.ident AS ident
    , i.unit AS unit
    , i.p_order AS indicator_p_order
    , l.name AS category
    , l.ident AS life_cycle_ident
    , l.phase AS life_cycle_phase
    , l.p_order AS life_cycle_p_order
  FROM elca_cache.project_variants cv
    JOIN elca_cache.indicators ci ON ci.item_id = cv.item_id
    JOIN elca.indicators i ON i.id = ci.indicator_id
    JOIN elca.life_cycles l ON ci.life_cycle_ident = l.ident
  WHERE ci.is_partial = false;

DROP VIEW IF EXISTS elca_cache.ref_project_construction_effects_v CASCADE;
CREATE OR REPLACE VIEW elca_cache.ref_project_construction_effects_v AS
  SELECT p.process_db_id
    , ci.indicator_id
    , avg(ci.value / (p.life_time * c.net_floor_space)) AS avg
    , min(ci.value / (p.life_time * c.net_floor_space)) AS min
    , max(ci.value / (p.life_time * c.net_floor_space)) AS max
    , count(*) AS counter
  FROM elca.projects p
    JOIN elca.project_variants       v ON p.id = v.project_id
    JOIN elca.project_constructions  c ON v.id = c.project_variant_id
    JOIN elca_cache.element_types   ct ON ct.project_variant_id = v.id
    JOIN elca.element_types_v        t ON t.level = 1 AND t.node_id = ct.element_type_node_id
    JOIN elca_cache.indicators      ci ON ci.item_id = ct.item_id AND ci.life_cycle_ident = 'total'
  WHERE p.is_reference = true
        AND ci.value > 0
  GROUP BY p.process_db_id
    , ci.indicator_id;

DROP VIEW IF EXISTS elca_cache.element_type_mass_v CASCADE;
CREATE OR REPLACE VIEW elca_cache.element_type_mass_v AS
  SELECT t.item_id AS element_type_item_id
    , t.parent_id AS element_type_parent_id
    , t.level AS element_type_level
    , sum(coalesce(e.mass, 0)) AS element_type_mass
  FROM elca_cache.element_types_v t
    LEFT JOIN elca_cache.elements_v e ON t.item_id = e.parent_id
  GROUP BY t.item_id
    , t.parent_id
    , t.level;


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


DROP FUNCTION IF EXISTS elca_cache.update_project_variant(int);
CREATE OR REPLACE FUNCTION elca_cache.update_project_variant(IN in_item_id int)
  RETURNS void
--
-- updates all indicator values for the project variant
--
AS $$

DECLARE

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
--------------------------------------------------------------------------------
--------------------------------------------------------------------------------

CREATE FUNCTION elca_cache.fix_hierarchy()
  RETURNS void
AS $$

DECLARE
   r record;
   i_item_id int;
BEGIN

  FOR r IN SELECT id AS element_type_item_id
                , project_variant_id
           FROM elca_cache.element_types_v
           WHERE parent_id IS NULL
  LOOP

    INSERT INTO elca_cache.items (parent_id, type, is_outdated, created)
      VALUES (NULL, 'ElcaCacheProjectVariant', true, now())
    RETURNING id INTO i_item_id;

    INSERT INTO elca_cache.project_variants (item_id, project_variant_id)
      VALUES (i_item_id, r.project_variant_id);

    UPDATE elca_cache.items
      SET parent_id = i_item_id
     WHERE id = r.element_type_item_id;

    -- remove old op-phase entries for root
    DELETE FROM elca_cache.indicators
          WHERE item_id = r.element_type_item_id
            AND life_cycle_ident IN (SELECT ident FROM elca.life_cycles WHERE phase = 'op');

    PERFORM elca_cache.update_totals(r.element_type_item_id);

  END LOOP;

  FOR r IN SELECT cf.item_id AS fed_item_id
             , f.project_variant_id
           FROM elca_cache.final_energy_demands cf
           JOIN elca.project_final_energy_demands f ON f.id = cf.final_energy_demand_id
  LOOP

    SELECT item_id
      INTO i_item_id
      FROM elca_cache.project_variants
     WHERE project_variant_id = r.project_variant_id;

    UPDATE elca_cache.items
       SET parent_id = i_item_id
     WHERE id = r.fed_item_id;
  END LOOP;

  PERFORM elca_cache.update_project_variant(item_id) FROM elca_cache.project_variants;

END;
$$ LANGUAGE plpgsql;

SELECT elca_cache.fix_hierarchy();

DROP FUNCTION elca_cache.fix_hierarchy();

COMMIT;