BEGIN;
SELECT
    public.register_patch('20180301-alter-elements.sql', 'eLCA');

DROP VIEW IF EXISTS elca.composite_elements_v;
DROP VIEW IF EXISTS elca.element_components_v;
DROP VIEW IF EXISTS elca_cache.project_variant_process_config_mass_v;
DROP VIEW IF EXISTS elca_cache.report_composite_element_process_config_effects_v;
DROP VIEW IF EXISTS elca_cache.report_effects_v;
DROP VIEW IF EXISTS elca_cache.report_top_process_config_effects_v;
DROP VIEW IF EXISTS lcc.composite_element_cost_progressions_v;
DROP VIEW IF EXISTS lcc.element_cost_totals_v;
DROP VIEW IF EXISTS lcc.element_costs_v;
DROP VIEW IF EXISTS elca.element_search_v;
DROP VIEW IF EXISTS elca.element_extended_search_v;
DROP VIEW IF EXISTS elca.composite_element_extended_search_v;
DROP VIEW IF EXISTS elca_cache.report_top_assets_v;
DROP VIEW IF EXISTS elca_cache.report_assets_v;
DROP VIEW IF EXISTS elca_cache.report_top_process_effects_v;
DROP VIEW IF EXISTS elca.element_process_config_sanities_v;

ALTER TABLE elca.elements ADD is_public boolean NOT NULL DEFAULT false;
UPDATE elca.elements SET is_public = is_reference;
UPDATE elca.elements SET is_reference = false;

CREATE VIEW elca.composite_elements_v AS
    SELECT
        c.composite_element_id
        , c.position
        , c.element_id
        , e.id
        , e.element_type_node_id
        , e.name
        , e.description
        , e.is_reference
        , e.is_public
        , e.access_group_id
        , e.project_variant_id
        , e.quantity
        , e.ref_unit
        , e.copy_of_element_id
        , e.owner_id
        , e.is_composite
        , e.uuid
        , e.created
        , e.modified
    FROM elca.composite_elements c
        JOIN elca.elements e ON e.id = c.element_id;

CREATE VIEW elca.element_components_v AS
    SELECT
        c.*
        , e.name AS element_name
    FROM elca.element_components c
        JOIN elca.elements e ON e.id = c.element_id;

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

CREATE VIEW elca_cache.report_composite_element_process_config_effects_v AS
    SELECT a.composite_element_id
        , e.id AS element_id
        , e.name AS element_name
        , c.id AS element_component_id
        , c.process_config_id
        , c.calc_lca
        , c.is_extant
        , c.is_layer
        , c.layer_position
        , c.layer_area_ratio
        , pc.name AS process_config_name
        , ci.indicator_id AS indicator_id
        , l.phase AS life_cycle_phase
        , l.ident AS life_cycle_ident
        , l.name AS life_cycle_name
        , i.name AS indicator_name
        , i.ident AS indicator_ident
        , i.unit AS indicator_unit
        , i.is_hidden
        , i.p_order AS indicator_p_order
        , cc.ref_unit
        , cc.quantity
        , ci.value AS indicator_value
    FROM elca.composite_elements       a
        JOIN elca.elements                 e  ON e.id = a.element_id
        JOIN elca.element_components       c  ON c.element_id = a.element_id
        JOIN elca.process_configs          pc ON pc.id = c.process_config_id
        JOIN elca_cache.element_components cc ON c.id  = cc.element_component_id
        JOIN elca_cache.indicators         ci ON cc.item_id = ci.item_id
        JOIN elca.indicators               i  ON i.id = ci.indicator_id
        JOIN elca.life_cycles              l  ON l.ident = ci.life_cycle_ident
    WHERE l.phase IN ('maint', 'prod', 'eol', 'rec', 'total');

CREATE VIEW elca_cache.report_effects_v AS
    SELECT e.id AS element_id
        , e.project_variant_id
        , e.name AS element_name
        , e.quantity AS element_quantity
        , e.ref_unit AS element_ref_unit
        , e.element_type_node_id
        , e.is_composite
        , t.din_code AS element_type_din_code
        , t.name AS element_type_name
        , t.is_constructional AS element_type_is_constructional
        , t.pref_has_element_image AS has_element_image
        , tt.name AS element_type_parent_name
        , tt.din_code AS element_type_parent_din_code
        , l.phase AS life_cycle_phase
        , ci.indicator_id AS indicator_id
        , i.name AS indicator_name
        , i.unit AS indicator_unit
        , i.is_hidden
        , i.p_order AS indicator_p_order
        , sum(ci.value) AS indicator_value
    FROM elca.elements e
        JOIN elca.element_types_v   t ON e.element_type_node_id = t.node_id
        JOIN elca.element_types_v  tt ON t.lft BETWEEN tt.lft AND tt.rgt AND tt.level = t.level - 1
        JOIN elca_cache.elements_v ce ON e.id = ce.element_id
        JOIN elca_cache.indicators ci ON ce.item_id = ci.item_id
        JOIN elca.life_cycles       l ON l.ident = ci.life_cycle_ident
        JOIN elca.indicators        i ON i.id = ci.indicator_id
    WHERE l.phase IN ('total', 'prod', 'maint', 'eol', 'rec')
    GROUP BY e.id
        , e.project_variant_id
        , e.name
        , e.quantity
        , e.ref_unit
        , e.element_type_node_id
        , e.is_composite
        , t.din_code
        , t.name
        , t.is_constructional
        , t.pref_has_element_image
        , tt.name
        , tt.din_code
        , l.phase
        , ci.indicator_id
        , i.name
        , i.unit
        , i.is_hidden
        , i.p_order;

CREATE VIEW elca_cache.report_top_process_config_effects_v AS
    SELECT e.project_variant_id
        , c.process_config_id
        , pc.name AS process_config_name
        , ci.indicator_id AS indicator_id
        , l.phase AS life_cycle_phase
        , i.name AS indicator_name
        , i.unit AS indicator_unit
        , i.is_hidden
        , i.p_order AS indicator_p_order
        , sum(cc.quantity) AS quantity
        , sum(ci.value)    AS indicator_value
    FROM elca.elements                 e
        JOIN elca.element_components       c  ON c.element_id  = e.id
        JOIN elca.process_configs          pc ON pc.id = c.process_config_id
        JOIN elca_cache.element_components cc ON c.id  = cc.element_component_id
        JOIN elca_cache.indicators         ci ON cc.item_id = ci.item_id
        JOIN elca.indicators               i  ON i.id = ci.indicator_id
        JOIN elca.life_cycles              l  ON l.ident = ci.life_cycle_ident
    WHERE l.phase = 'total'
    GROUP BY e.project_variant_id
        , c.process_config_id
        , pc.name
        , ci.indicator_id
        , l.phase
        , i.name
        , i.unit
        , i.is_hidden
        , i.p_order;

CREATE VIEW lcc.composite_element_cost_progressions_v AS
    SELECT c.composite_element_id
        , c.element_id
        , c.position
        , e.name
        , ec.life_time
        , ec.quantity
    FROM elca.composite_elements c
        JOIN elca.elements e ON e.id = c.element_id
        LEFT JOIN lcc.element_cost_progressions ec ON c.element_id = ec.element_id;

CREATE OR REPLACE VIEW lcc.element_cost_totals_v AS
    WITH calculated AS (
        SELECT
            element_id,
            sum(p.quantity) AS sum
        FROM
            lcc.element_component_cost_progressions_v p
        WHERE p.quantity IS NOT NULL
              AND p.life_time > 0
        GROUP BY element_id
    ),
            edited AS (
            SELECT
                element_id,
                sum(p.quantity) AS sum
            FROM
                lcc.element_cost_progressions p
            WHERE p.life_time > 0
            GROUP BY element_id
        ),
            elements AS (
            SELECT id AS element_id
                , project_variant_id
            FROM elca.elements
        )
    SELECT
        e.element_id,
        e.project_variant_id,
        coalesce(ce.sum, c.sum) AS quantity
    FROM elements e
        LEFT JOIN (calculated c
            FULL OUTER JOIN edited ce USING (element_id)
            ) ON e.element_id IN (c.element_id, ce.element_id);

CREATE VIEW lcc.element_costs_v AS
    SELECT c.*
        , e.project_variant_id
        , e.element_type_node_id
        , e.quantity AS element_quantity
        , e.ref_unit
        , e.is_composite
    FROM lcc.element_costs c
        JOIN elca.elements e ON e.id = c.element_id;

CREATE OR REPLACE VIEW elca.element_extended_search_v AS
    SELECT
        e.id
        , e.element_type_node_id
        , e.name
        , e.description
        , e.is_reference
        , e.is_public
        , e.access_group_id
        , e.project_variant_id
        , e.quantity
        , e.ref_unit
        , e.copy_of_element_id
        , e.owner_id
        , e.is_composite
        , e.uuid
        , e.created
        , e.modified
        , to_tsvector('german', e.id || ' ' || e.name || ' ' || coalesce(e.description || ' ', '') ||
                                array_to_string(array_agg(coalesce(pc.name, '')), ' ')) AS search_vector
        , array_intersect_agg(pc.process_db_ids) AS process_db_ids
    FROM elca.elements e
        LEFT JOIN elca.element_components c ON e.id = c.element_id
        LEFT JOIN elca.process_config_process_dbs_view pc ON pc.id = c.process_config_id
    WHERE is_composite = false
    GROUP BY e.id
        , e.element_type_node_id
        , e.name
        , e.description
        , e.is_reference
        , e.is_public
        , e.access_group_id
        , e.project_variant_id
        , e.quantity
        , e.ref_unit
        , e.copy_of_element_id
        , e.owner_id
        , e.is_composite
        , e.uuid
        , e.created
        , e.modified;

CREATE OR REPLACE VIEW elca.element_search_v AS
    SELECT
        e.id
        , e.name
        , e.element_type_node_id
        , e.project_variant_id
        , e.access_group_id
        , e.is_reference
        , e.is_public
        , t.din_code || ' ' || t.name AS element_type_node_name
        , e.process_db_ids
    FROM elca.element_extended_search_v e
        JOIN elca.element_types t ON t.node_id = e.element_type_node_id;

CREATE OR REPLACE VIEW elca.composite_element_extended_search_v AS
    SELECT
        e.id
        , e.element_type_node_id
        , e.name
        , e.description
        , e.is_reference
        , e.is_public
        , e.access_group_id
        , e.project_variant_id
        , e.quantity
        , e.ref_unit
        , e.copy_of_element_id
        , e.owner_id
        , e.is_composite
        , e.uuid
        , e.created
        , e.modified
        , to_tsvector('german', e.id || ' ' || e.name || ' ' || coalesce(e.description || ' ', '') ||
                                array_to_string(array_agg(coalesce(pc.name, '')), ' ')) AS search_vector
        , array_intersect_agg(pc.process_db_ids) AS process_db_ids
    FROM elca.elements e
        LEFT JOIN elca.composite_elements a ON e.id = a.composite_element_id
        LEFT JOIN elca.element_components c ON a.element_id = c.element_id
        LEFT JOIN elca.process_config_process_dbs_view pc ON pc.id = c.process_config_id
    WHERE is_composite = true
    GROUP BY e.id
        , e.element_type_node_id
        , e.name
        , e.description
        , e.is_reference
        , e.is_public
        , e.access_group_id
        , e.project_variant_id
        , e.quantity
        , e.ref_unit
        , e.copy_of_element_id
        , e.owner_id
        , e.is_composite
        , e.uuid
        , e.created
        , e.modified;

CREATE VIEW elca_cache.report_top_assets_v AS
    SELECT e.project_variant_id
        , t.din_code AS element_type_din_code
        , t.name AS element_type_name
        , e.id   AS element_id
        , e.name AS element_name
        , e.quantity AS element_quantity
        , e.ref_unit AS element_ref_unit
        , ce.quantity AS cache_element_quantity
        , ce.ref_unit AS cache_element_ref_unit
        , ce.mass AS element_mass
        , c.id AS element_component_id
        , c.is_layer AS component_is_layer
        , c.layer_position AS component_layer_position
        , c.calc_lca AS component_calc_lca
        , c.is_extant AS component_is_extant
        , a.process_db_id
        , a.name_orig     AS process_name_orig
        , a.scenario_id AS process_scenario_id
        , a.ref_value AS process_ref_value
        , a.ref_unit AS process_ref_unit
        , a.life_cycle_description AS process_life_cycle_description
        , a.life_cycle_phase AS process_life_cycle_phase
        , a.ratio AS process_ratio
        , pc.name AS process_config_name
        , cc.quantity AS cache_component_quantity
        , cc.ref_unit AS cache_component_ref_unit
        , cc.mass AS cache_component_mass
        , cc.num_replacements AS cache_component_num_replacements
    FROM elca.elements e
        JOIN elca_cache.elements        ce ON e.id = ce.element_id
        JOIN elca.element_types_v       t  ON e.element_type_node_id = t.node_id
        JOIN elca.element_components    c  ON e.id = c.element_id
        JOIN elca_cache.element_components cc ON c.id = cc.element_component_id
        JOIN elca.process_assignments_v a  ON c.process_config_id = a.process_config_id
        JOIN elca.process_configs       pc ON pc.id = a.process_config_id;

CREATE VIEW elca_cache.report_assets_v AS
    SELECT e.project_variant_id
        , t.din_code AS element_type_din_code
        , t.name AS element_type_name
        , tt.name AS element_type_parent_name
        , tt.din_code AS element_type_parent_din_code
        , t.is_constructional AS element_type_is_constructional
        , t.pref_has_element_image AS has_element_image
        , e.id   AS element_id
        , e.name AS element_name
        , e.quantity AS element_quantity
        , e.ref_unit AS element_ref_unit
        , ce.quantity AS cache_element_quantity
        , ce.ref_unit AS cache_element_ref_unit
        , ce.mass AS element_mass
        , c.id AS element_component_id
        , c.is_layer AS component_is_layer
        , c.calc_lca AS component_calc_lca
        , c.is_extant AS component_is_extant
        , c.layer_size AS component_size
        , c.quantity AS component_quantity
        , c.life_time AS component_life_time
        , c.life_time_delay AS component_life_time_delay
        , c.life_time_info AS component_life_time_info
        , c.layer_position AS component_layer_position
        , c.layer_area_ratio AS component_layer_area_ratio
        , a.process_db_id
        , a.name_orig     AS process_name_orig
        , a.scenario_id AS process_scenario_id
        , a.ref_value AS process_ref_value
        , a.ref_unit AS process_ref_unit
        , a.uuid     AS process_uuid
        , a.life_cycle_description AS process_life_cycle_description
        , a.life_cycle_ident AS process_life_cycle_ident
        , a.life_cycle_p_order AS process_life_cycle_p_order
        , a.ratio AS process_ratio
        , pc.name AS process_config_name
        , cc.quantity AS cache_component_quantity
        , cc.ref_unit AS cache_component_ref_unit
        , cc.num_replacements AS cache_component_num_replacements
        , pc.min_life_time, pc.avg_life_time, pc.max_life_time
        , c.life_time NOT IN (coalesce(pc.min_life_time, 0), coalesce(pc.avg_life_time, 0), coalesce(pc.max_life_time, 0)) AS has_non_default_life_time
    FROM elca.elements e
        JOIN elca_cache.elements        ce ON e.id = ce.element_id
        JOIN elca.element_types_v       t  ON e.element_type_node_id = t.node_id
        JOIN elca.element_types_v       tt ON t.lft BETWEEN tt.lft AND tt.rgt AND tt.level = t.level - 1
        JOIN elca.element_components    c  ON e.id = c.element_id
        JOIN elca_cache.element_components cc ON c.id = cc.element_component_id
        JOIN elca.process_assignments_v a  ON c.process_config_id = a.process_config_id
        JOIN elca.process_configs       pc ON pc.id = a.process_config_id;

CREATE VIEW elca_cache.report_top_process_effects_v AS
    SELECT e.project_variant_id
        , a.process_db_id
        , a.id AS process_id
        , a.name_orig AS process_name_orig
        , a.scenario_id AS process_scenario_id
        , ci.indicator_id AS indicator_id
        , l.ident
        , i.name AS indicator_name
        , i.unit AS indicator_unit
        , i.is_hidden
        , i.p_order AS indicator_p_order
        , cc.ref_unit
        , sum(cc.quantity) AS quantity
        , sum(ci.value) AS indicator_value
    FROM elca_cache.element_components cc
        JOIN elca_cache.indicators      ci ON cc.item_id = ci.item_id
        JOIN elca.element_components     c ON c.id = cc.element_component_id
        JOIN elca.elements               e ON e.id = c.element_id
        JOIN elca.process_assignments_v a  ON c.process_config_id = a.process_config_id
        JOIN elca.process_configs       pc ON pc.id = a.process_config_id
        JOIN elca.life_cycles           l  ON l.ident = ci.life_cycle_ident AND l.phase = 'total'
        JOIN elca.indicators            i  ON i.id = ci.indicator_id
    GROUP BY e.project_variant_id
        , a.process_db_id
        , a.id
        , a.name_orig
        , a.scenario_id
        , cc.ref_unit
        , ci.indicator_id
        , l.ident
        , i.name
        , i.unit
        , i.is_hidden
        , i.p_order;

CREATE OR REPLACE VIEW elca.element_process_config_sanities_v AS
    SELECT
          e.id    AS element_id
        , e.name  AS element_name
        , t.din_code
        , CASE WHEN c.is_layer
        THEN c.layer_position
          ELSE NULL
          END     AS layer_position
        , pc.name AS process_config_name
        , e.access_group_id
    FROM
        elca.process_configs pc
        JOIN
        elca.element_components c ON pc.id = c.process_config_id
        JOIN
        elca.elements e ON e.id = c.element_id
        JOIN
        elca.element_types t ON t.node_id = e.element_type_node_id

    WHERE pc.is_stale = true OR pc.uuid = 'ffffffff-ffff-ffff-ffff-ffffffffffff'
                                AND e.project_variant_id IS NULL;

COMMIT;
