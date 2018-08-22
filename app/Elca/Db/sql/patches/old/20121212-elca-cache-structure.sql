BEGIN;
SELECT public.register_patch('added-elca_cache-structure', 'elca');

SET client_encoding = 'UTF8';

CREATE SCHEMA elca_cache;

CREATE TABLE elca_cache.items
(
    "id"                   serial          NOT NULL               -- itemId
  , "parent_id"            int                                    -- parent item
  , "type"                 varchar(100)    NOT NULL               -- item type

  , "is_outdated"          boolean         NOT NULL DEFAULT false -- if it is outdated, it needs updating

  , "created"              timestamptz(0)  NOT NULL DEFAULT now() -- creation time
  , "modified"             timestamptz(0)           DEFAULT now() -- modification time
  , PRIMARY KEY ("id")
  , FOREIGN KEY ("parent_id") REFERENCES elca_cache.items ("id") ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE INDEX IX_elca_cache_items_type ON elca_cache.items ("type");

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

  , "mass"                 numeric                               -- mass of the element
  , "quantity"             numeric                               -- quantity
  , "ref_unit"             varchar(10)                           -- refUnit

  , PRIMARY KEY ("item_id")
  , UNIQUE ("element_id")
  , FOREIGN KEY ("item_id") REFERENCES elca_cache.items("id") ON UPDATE CASCADE ON DELETE CASCADE
  , FOREIGN KEY ("element_id") REFERENCES elca.elements("id") ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE TRIGGER trigger_elca_cache_on_delete_cascade AFTER DELETE ON elca_cache.elements
   FOR EACH ROW EXECUTE PROCEDURE elca_cache.on_delete_cascade();

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

DROP VIEW IF EXISTS elca_cache.elements_v CASCADE;
CREATE OR REPLACE VIEW elca_cache.elements_v AS
  SELECT i.*
       , e.*
   FROM elca_cache.elements e
   JOIN elca_cache.items    i ON i.id = e.item_id;

--------------------------------------------------------------------------------

DROP VIEW IF EXISTS elca_cache.element_components_v CASCADE;
CREATE OR REPLACE VIEW elca_cache.element_components_v AS
  SELECT i.*
       , e.*
   FROM elca_cache.element_components e
   JOIN elca_cache.items    i ON i.id = e.item_id;

--------------------------------------------------------------------------------

DROP VIEW IF EXISTS elca_cache.element_types_v CASCADE;
CREATE OR REPLACE VIEW elca_cache.element_types_v AS
  SELECT i.*
       , t.*
       , n.lft
       , n.rgt
       , n.level
       , n.ident
   FROM elca_cache.element_types t
   JOIN elca_cache.items         i ON i.id = t.item_id
   JOIN public.nested_nodes      n ON n.id = t.element_type_node_id;

--------------------------------------------------------------------------------

DROP VIEW IF EXISTS elca_cache.indicators_v CASCADE;
CREATE OR REPLACE VIEW elca_cache.indicators_v AS
  SELECT i.*
       , ii.*
   FROM elca_cache.indicators i
   JOIN elca_cache.items      ii ON ii.id = i.item_id;

--------------------------------------------------------------------------------

DROP VIEW IF EXISTS elca_cache.element_mass_v CASCADE;
CREATE OR REPLACE VIEW elca_cache.element_mass_v AS
     SELECT parent_id AS element_item_id
          , sum(coalesce(mass, 0)) AS element_mass
      FROM elca_cache.element_components_v
   GROUP BY parent_id;

--------------------------------------------------------------------------------

DROP VIEW IF EXISTS elca_cache.element_type_mass_v CASCADE;
CREATE OR REPLACE VIEW elca_cache.element_type_mass_v AS
     SELECT parent_id AS element_type_item_id
          , sum(coalesce(mass, 0)) AS element_type_mass
      FROM elca_cache.elements_v
   GROUP BY parent_id;

--------------------------------------------------------------------------------

DROP VIEW IF EXISTS elca_cache.indicators_aggregate_v CASCADE;
CREATE OR REPLACE VIEW elca_cache.indicators_aggregate_v AS
   SELECT parent_id AS item_id
        , life_cycle_ident
        , indicator_id
        , null::int AS process_id
        , sum(value) AS value
        , bool_and(is_partial) AS is_partial
     FROM elca_cache.indicators_v
 GROUP BY parent_id
        , life_cycle_ident
        , indicator_id;

--------------------------------------------------------------------------------

DROP VIEW IF EXISTS elca_cache.indicators_totals_v CASCADE;
CREATE OR REPLACE VIEW elca_cache.indicators_totals_v AS
    SELECT item_id
         , 'total'::varchar(20) AS life_cycle_ident
         , indicator_id
         , null::integer AS process_id
         , sum(value) AS value
         , 1 AS ratio
         , true AS is_partial
      FROM elca_cache.indicators_v
     WHERE is_partial = false
  GROUP BY item_id
         , indicator_id;


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

   -- update element mass
   UPDATE elca_cache.elements e
      SET mass = x.element_mass
     FROM elca_cache.element_mass_v x
    WHERE e.item_id = x.element_item_id
      AND e.item_id = ANY (outdated);

   -- update mass of direct parent element types
   UPDATE elca_cache.element_types t
      SET mass = x.element_type_mass
     FROM elca_cache.element_type_mass_v x
    WHERE t.item_id = x.element_type_item_id
      AND t.item_id = ANY (parents);

   -- update tree for each (distinct) parent
   FOR r IN SELECT DISTINCT unnest(parents) AS parent
   LOOP
        PERFORM elca_cache.update_element_type_tree(r.parent);
   END LOOP;

END;
$$ LANGUAGE plpgsql;

--------------------------------------------------------------------------------

CREATE OR REPLACE FUNCTION elca_cache.update_element_type_tree(in_parent_item_id int)
              RETURNS void
--
-- updates all parent nodes
--
AS $$

DECLARE
   indicator      record;
   parent_item_id int := in_parent_item_id;

BEGIN
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

CREATE OR REPLACE FUNCTION elca_cache.aggregate_indicators(IN in_item_id int)
              RETURNS void
--
-- updates aggregates of all indicators for the given cache item
--
AS $$

BEGIN
    -- build indicators totals (UPDATE / INSERT)
    UPDATE elca_cache.indicators i
       SET value = t.value
      FROM elca_cache.indicators_aggregate_v t
     WHERE (i.item_id, i.life_cycle_ident, i.indicator_id) = (t.item_id, t.life_cycle_ident, t.indicator_id)
       AND i.process_id IS NULL
       AND i.item_id = in_item_id;

    IF NOT FOUND THEN
        INSERT INTO elca_cache.indicators (item_id, life_cycle_ident, indicator_id, is_partial, value)
                 SELECT item_id
                      , life_cycle_ident
                      , indicator_id
                      , is_partial
                      , value
                   FROM elca_cache.indicators_aggregate_v
                  WHERE item_id = in_item_id;
    END IF;
END;
$$ LANGUAGE plpgsql;


COMMIT;