BEGIN;
SELECT public.register_patch('add-variant-comparison-reports', 'elca');

UPDATE elca.life_cycles SET p_order = 5 WHERE ident = 'prod';

DROP VIEW IF EXISTS elca_cache.report_compare_total_and_life_cycle_effects_v;
CREATE VIEW elca_cache.report_compare_total_and_life_cycle_effects_v AS
  SELECT cva.project_variant_id AS project_variant_a_id
    , cvb.project_variant_id AS project_variant_b_id
    , i.id AS indicator_id
    , cia.value AS value_a
    , cib.value AS value_b
    , i.name AS name
    , i.ident AS ident
    , i.unit AS unit
    , i.p_order AS indicator_p_order
    , l.name AS category
    , l.ident AS life_cycle_ident
    , l.phase AS life_cycle_phase
    , CASE WHEN l.ident = 'total' THEN 0 ELSE l.p_order END AS life_cycle_p_order
  FROM elca_cache.project_variants cva
    CROSS JOIN elca_cache.project_variants cvb
    JOIN elca_cache.indicators cia ON cia.item_id = cva.item_id
    JOIN elca_cache.indicators cib ON cib.item_id = cvb.item_id
    JOIN elca.indicators i ON i.id = cia.indicator_id AND i.id = cib.indicator_id
    JOIN elca.life_cycles l ON cia.life_cycle_ident = l.ident AND cib.life_cycle_ident = l.ident;


DROP VIEW IF EXISTS elca_cache.report_compare_element_type_effects_v;
CREATE VIEW elca_cache.report_compare_element_type_effects_v AS
  SELECT cta.project_variant_id AS project_variant_a_id
    , ctb.project_variant_id AS project_variant_b_id
    , i.id AS indicator_id
    , cia.value AS value_a
    , cib.value AS value_b
    , lc.ident AS life_cycle_ident
    , lc.name AS life_cycle_name
    , lc.phase AS life_cycle_phase
    , i.name AS name
    , i.unit AS unit
    , i.p_order AS indicator_p_order
    , t.din_code ||' '||t.name AS category
    , cta.level AS level
    , coalesce(t.din_code, '000') AS din_code
    , t.node_id AS element_type_node_id
    , tt.id AS parent_element_type_node_id
  FROM elca_cache.element_types_v cta
    CROSS JOIN elca_cache.element_types_v ctb
    JOIN elca_cache.indicators cia ON cia.item_id = cta.item_id
    JOIN elca_cache.indicators cib ON cib.item_id = ctb.item_id
    JOIN elca.element_types t ON t.node_id = cta.element_type_node_id AND t.node_id = ctb.element_type_node_id
    JOIN elca.element_types_v tt ON cta.lft BETWEEN tt.lft AND tt.rgt AND tt.level = cta.level - 1
                                    AND ctb.lft BETWEEN tt.lft AND tt.rgt AND tt.level = ctb.level - 1
    JOIN elca.indicators i ON i.id = cia.indicator_id AND i.id = cib.indicator_id
    JOIN elca.life_cycles lc ON lc.ident = cia.life_cycle_ident AND cib.life_cycle_ident = lc.ident
  WHERE lc.phase IN ('total', 'prod', 'maint', 'eol')
        AND cta.level = ctb.level
        AND cta.level BETWEEN 1 AND 3;


COMMIT;