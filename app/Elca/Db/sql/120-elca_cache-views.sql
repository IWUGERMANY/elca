----------------------------------------------------------------------------------------
-- This file is part of the eLCA project
--
-- eLCA
-- A web based life cycle assessment application
--
-- Copyright (c) 2013 Tobias Lode <tobias@beibob.de>
--               BEIBOB Medienfreunde GbR - http://beibob.de/
--
-- eLCA is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.
--
-- eLCA is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with eLCA. If not, see <http://www.gnu.org/licenses/>.
----------------------------------------------------------------------------------------
SET client_encoding = 'UTF8';
BEGIN;
----------------------------------------------------------------------------------------

DROP VIEW IF EXISTS elca_cache.project_variants_v CASCADE;
CREATE OR REPLACE VIEW elca_cache.project_variants_v AS
  SELECT i.*
       , v.*
    FROM elca_cache.project_variants v
    JOIN elca_cache.items    i ON i.id = v.item_id;


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

DROP VIEW IF EXISTS elca_cache.final_energy_demands_v CASCADE;
CREATE OR REPLACE VIEW elca_cache.final_energy_demands_v AS
  SELECT i.*
       , e.*
   FROM elca_cache.final_energy_demands e
   JOIN elca_cache.items    i ON i.id = e.item_id;

--------------------------------------------------------------------------------

DROP VIEW IF EXISTS elca_cache.final_energy_supplies_v CASCADE;
CREATE OR REPLACE VIEW elca_cache.final_energy_supplies_v AS
  SELECT i.*
    , e.*
  FROM elca_cache.final_energy_supplies e
    JOIN elca_cache.items    i ON i.id = e.item_id;

--------------------------------------------------------------------------------

DROP VIEW IF EXISTS elca_cache.final_energy_ref_models_v CASCADE;
CREATE OR REPLACE VIEW elca_cache.final_energy_ref_models_v AS
    SELECT i.*
         , e.*
      FROM elca_cache.final_energy_ref_models e
      JOIN elca_cache.items    i ON i.id = e.item_id;

--------------------------------------------------------------------------------

DROP VIEW IF EXISTS elca_cache.transport_means_v CASCADE;
CREATE OR REPLACE VIEW elca_cache.transport_means_v AS
  SELECT i.*
       , t.*
  FROM elca_cache.transport_means t
    JOIN elca_cache.items    i ON i.id = t.item_id;

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

DROP VIEW IF EXISTS elca_cache.composite_element_mass_v;
CREATE OR REPLACE VIEW elca_cache.composite_element_mass_v AS
     SELECT composite_item_id
          , sum(coalesce(mass, 0)) AS element_mass
      FROM elca_cache.elements_v
     WHERE composite_item_id IS NOT NULL
   GROUP BY composite_item_id;

--------------------------------------------------------------------------------

DROP VIEW IF EXISTS elca_cache.element_type_mass_v CASCADE;
CREATE OR REPLACE VIEW elca_cache.element_type_mass_v AS
     SELECT t.item_id AS element_type_item_id
          , t.parent_id AS element_type_parent_id
          , t.level AS element_type_level
          , sum(coalesce(e.mass, 0)) AS element_type_mass
      FROM elca_cache.element_types_v t
 LEFT JOIN elca_cache.elements_v e ON t.item_id = e.parent_id AND NOT e.is_virtual
  GROUP BY t.item_id
         , t.parent_id
         , t.level;

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
    WHERE is_virtual = false
 GROUP BY parent_id
        , life_cycle_ident
        , indicator_id;

--------------------------------------------------------------------------------

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

--------------------------------------------------------------------------------

DROP VIEW IF EXISTS elca_cache.indicators_totals_v CASCADE;
CREATE OR REPLACE VIEW elca_cache.indicators_totals_v AS
    SELECT
        i.item_id
        , 'total' :: varchar(20) AS life_cycle_ident
        , i.indicator_id
        , null :: integer        AS process_id
        , sum(i.value)           AS value
        , 1                      AS ratio
        , true                   AS is_partial
    FROM elca_cache.indicators_v i
    WHERE
          life_cycle_ident = ANY ( ARRAY['maint'::varchar] ||
                                       ARRAY(SELECT u.life_cycle_ident
                                             FROM elca.project_life_cycle_usages u
                                             WHERE u.project_id = i.project_id
                                                   AND (u.use_in_construction OR u.use_in_energy_demand))

    )
          AND i.is_partial = false
    GROUP BY i.item_id
        , i.indicator_id;


--------------------------------------------------------------------------------

DROP VIEW IF EXISTS elca_cache.indicator_results_v;
CREATE VIEW elca_cache.indicator_results_v AS
     SELECT ci.item_id
          , ci.life_cycle_ident
          , ci.indicator_id
          , ci.process_id
          , ci.value
          , ci.ratio
          , ci.is_partial
          , p.name_orig
          , l.name AS life_cycle_name
          , l.phase AS life_cycle_phase
          , l.p_order AS life_cycle_p_order
          , i.name AS indicator_name
          , i.ident AS indicator_ident
          , i.is_hidden
          , i.p_order AS indicator_p_order
       FROM elca_cache.indicators ci
       JOIN elca.indicators i ON i.id = ci.indicator_id
       JOIN elca.life_cycles l ON ci.life_cycle_ident = l.ident
  LEFT JOIN elca.processes p ON ci.process_id = p.id;



--------------------------------------------------------------------------------

DROP VIEW IF EXISTS elca_cache.report_assets_v;
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

--------------------------------------------------------------------------------

DROP VIEW IF EXISTS elca_cache.report_top_assets_v;
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

--------------------------------------------------------------------------------

DROP VIEW IF EXISTS elca_cache.report_final_energy_demand_assets_v;
CREATE VIEW elca_cache.report_final_energy_demand_assets_v AS
   SELECT f.id
        , f.project_variant_id
        , pc.name AS process_config_name
        , p.id AS process_id
        , p.process_db_id
        , p.name_orig AS process_name_orig
        , p.scenario_id AS process_scenario_id
        , p.ref_value AS process_ref_value
        , p.ref_unit AS process_ref_unit
        , p.uuid AS process_uuid
        , p.life_cycle_description AS life_cycle_description
        , p.life_cycle_ident AS life_cycle_ident
        , p.life_cycle_phase AS life_cycle_phase
        , p.life_cycle_p_order AS life_cycle_p_order
        , f.ident
        , f.heating
        , f.water
        , f.lighting
        , f.ventilation
        , f.cooling
        , cf.quantity AS total
        , cf.ref_unit AS total_unit
     FROM elca.project_final_energy_demands f
     JOIN elca_cache.final_energy_demands cf ON f.id = cf.final_energy_demand_id
     JOIN elca.process_configs pc ON pc.id = f.process_config_id
     JOIN elca.process_assignments_v p ON p.process_config_id = f.process_config_id AND p.life_cycle_phase = 'op';

DROP VIEW IF EXISTS elca_cache.report_final_energy_supply_assets_v;
CREATE VIEW elca_cache.report_final_energy_supply_assets_v AS
  SELECT f.id
    , f.project_variant_id
    , pc.name AS process_config_name
    , p.id AS process_id
    , p.process_db_id
    , p.name_orig AS process_name_orig
    , p.scenario_id AS process_scenario_id
    , p.ref_value AS process_ref_value
    , p.ref_unit AS process_ref_unit
    , p.uuid AS process_uuid
    , p.life_cycle_description AS life_cycle_description
    , p.life_cycle_ident AS life_cycle_ident
    , p.life_cycle_phase AS life_cycle_phase
    , p.life_cycle_p_order AS life_cycle_p_order
    , f.quantity
    , f.en_ev_ratio
    , cf.quantity AS total
    , cf.ref_unit AS total_unit
  FROM elca.project_final_energy_supplies f
    JOIN elca_cache.final_energy_supplies cf ON f.id = cf.final_energy_supply_id
    JOIN elca.process_configs pc ON pc.id = f.process_config_id
    JOIN elca.process_assignments_v p ON p.process_config_id = f.process_config_id AND p.life_cycle_phase = 'op';


--------------------------------------------------------------------------------

DROP VIEW IF EXISTS elca_cache.report_transport_assets_v;
CREATE VIEW elca_cache.report_transport_assets_v AS
  SELECT tm.id AS transport_mean_id
    , t.project_variant_id
    , t.id AS transport_id
    , t.name AS transport_name
    , t.quantity AS transport_quantity
    , pc.name AS process_config_name
    , p.id AS process_id
    , p.process_db_id
    , p.name_orig AS process_name_orig
    , p.scenario_id AS process_scenario_id
    , p.ref_value AS process_ref_value
    , p.ref_unit AS process_ref_unit
    , p.uuid AS process_uuid
    , p.life_cycle_description AS life_cycle_description
    , p.life_cycle_ident AS life_cycle_ident
    , p.life_cycle_phase AS life_cycle_phase
    , p.life_cycle_p_order AS life_cycle_p_order
    , ct.quantity AS total
    , ct.ref_unit AS total_unit
  FROM elca.project_transport_means tm
    JOIN elca.project_transports t ON t.id = tm.project_transport_id
    JOIN elca_cache.transport_means ct ON tm.id = ct.transport_mean_id
    JOIN elca.process_configs pc ON pc.id = tm.process_config_id
    JOIN elca.process_assignments_v p ON p.process_config_id = tm.process_config_id AND p.life_cycle_ident = 'A4';

--------------------------------------------------------------------------------

DROP VIEW IF EXISTS elca_cache.report_top_process_effects_v CASCADE;
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

--------------------------------------------------------------------------------

DROP VIEW IF EXISTS elca_cache.report_top_process_config_effects_v CASCADE;
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

--------------------------------------------------------------------------------

DROP VIEW IF EXISTS elca_cache.report_element_process_config_effects_v CASCADE;
CREATE VIEW elca_cache.report_element_process_config_effects_v AS
    SELECT c.element_id
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
    FROM elca.element_components       c
        JOIN elca.process_configs          pc ON pc.id = c.process_config_id
        JOIN elca_cache.element_components cc ON c.id  = cc.element_component_id
        JOIN elca_cache.indicators         ci ON cc.item_id = ci.item_id
        JOIN elca.indicators               i  ON i.id = ci.indicator_id
        JOIN elca.life_cycles              l  ON l.ident = ci.life_cycle_ident
    WHERE l.phase IN ('maint', 'prod', 'eol', 'rec', 'total');

DROP VIEW IF EXISTS elca_cache.report_composite_element_process_config_effects_v CASCADE;
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

--------------------------------------------------------------------------------

DROP VIEW IF EXISTS elca_cache.report_effects_v CASCADE;
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

--------------------------------------------------------------------------------

DROP VIEW IF EXISTS elca_cache.report_final_energy_demand_effects_v CASCADE;
CREATE VIEW elca_cache.report_final_energy_demand_effects_v AS
    SELECT f.id
         , f.project_variant_id
         , f.ident
         , cf.quantity AS element_quantity
         , cf.ref_unit AS element_ref_unit
         , pc.name AS element_name
         , ci.indicator_id AS indicator_id
         , ci.value AS indicator_value
         , i.name AS indicator_name
         , i.unit AS indicator_unit
         , i.is_hidden
         , i.p_order AS indicator_p_order
      FROM elca.project_final_energy_demands f
      JOIN elca.process_configs              pc ON pc.id = f.process_config_id
      JOIN elca_cache.final_energy_demands_v cf ON f.id = cf.final_energy_demand_id
      JOIN elca_cache.indicators             ci ON cf.item_id = ci.item_id AND ci.life_cycle_ident = 'total'
      JOIN elca.indicators                    i ON i.id = ci.indicator_id;

DROP VIEW IF EXISTS elca_cache.report_final_energy_supply_effects_v CASCADE;
CREATE VIEW elca_cache.report_final_energy_supply_effects_v AS
  SELECT f.id
    , f.project_variant_id
    , cf.quantity AS element_quantity
    , cf.ref_unit AS element_ref_unit
    , pc.name AS element_name
    , ci.indicator_id AS indicator_id
    , ci.value AS indicator_value
    , i.name AS indicator_name
    , i.unit AS indicator_unit
    , i.is_hidden
    , i.p_order AS indicator_p_order
  FROM elca.project_final_energy_supplies f
    JOIN elca.process_configs              pc ON pc.id = f.process_config_id
    JOIN elca_cache.final_energy_supplies_v cf ON f.id = cf.final_energy_supply_id
    JOIN elca_cache.indicators             ci ON cf.item_id = ci.item_id AND ci.life_cycle_ident = 'D'
    JOIN elca.indicators                    i ON i.id = ci.indicator_id;


--------------------------------------------------------------------------------

DROP VIEW IF EXISTS elca_cache.report_transport_effects_v CASCADE;
CREATE OR REPLACE VIEW elca_cache.report_transport_effects_v AS
  SELECT m.id AS transport_mean_id
    , t.id AS transport_id
    , t.name AS element_name
    , t.project_variant_id
    , cm.quantity AS element_quantity
    , cm.ref_unit AS element_ref_unit
    , pc.name AS process_config_name
    , ci.indicator_id AS indicator_id
    , ci.value AS indicator_value
    , i.ident AS indicator_ident
    , i.name AS indicator_name
    , i.unit AS indicator_unit
    , i.is_hidden
    , i.p_order AS indicator_p_order
  FROM elca.project_transports            t
    JOIN elca.project_transport_means       m ON t.id = m.project_transport_id
    JOIN elca.process_configs              pc ON pc.id = m.process_config_id
    JOIN elca_cache.transport_means_v      cm ON m.id = cm.transport_mean_id
    JOIN elca_cache.indicators             ci ON cm.item_id = ci.item_id
    JOIN elca.indicators                    i ON i.id = ci.indicator_id;

--------------------------------------------------------------------------------

DROP VIEW IF EXISTS elca_cache.report_total_effects_v CASCADE;
CREATE VIEW elca_cache.report_total_effects_v AS
    SELECT ci.item_id
        , ci.indicator_id
        , ci.value
        , i.name AS name
        , i.ident AS ident
        , i.unit AS unit
        , i.is_hidden
        , i.p_order AS indicator_p_order
        , v.project_variant_id
        , 'Gesamt'::varchar AS category
    FROM elca_cache.project_variants v
        JOIN elca_cache.indicators ci ON ci.item_id = v.item_id
        JOIN elca.indicators i ON i.id = ci.indicator_id
        JOIN elca.life_cycles l ON l.ident = ci.life_cycle_ident
    WHERE ci.life_cycle_ident = 'total';


DROP VIEW IF EXISTS elca_cache.report_life_cycle_effects_v CASCADE;
CREATE VIEW elca_cache.report_life_cycle_effects_v AS
    SELECT cv.project_variant_id
        , ci.item_id
        , ci.indicator_id
        , ci.value
        , i.name AS name
        , i.ident AS ident
        , i.unit AS unit
        , i.is_hidden
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

DROP VIEW IF EXISTS elca_cache.report_total_construction_recycling_effects_v;
CREATE VIEW elca_cache.report_total_construction_recycling_effects_v AS
    SELECT ci.item_id
        , ci.indicator_id
        , ci.value
        , i.name AS name
        , i.ident AS ident
        , i.unit AS unit
        , i.is_hidden
        , i.p_order AS indicator_p_order
        , v.project_variant_id
        , 'D stofflich'::varchar AS category
        , l.p_order AS life_cycle_p_order
        , l.ident AS life_cycle_ident
    FROM elca_cache.element_types_v v
        JOIN elca_cache.indicators ci ON ci.item_id = v.item_id
        JOIN elca.indicators i ON i.id = ci.indicator_id
        JOIN elca.life_cycles l ON l.ident = ci.life_cycle_ident
    WHERE v.level = 0
          AND l.phase = 'rec';

DROP VIEW IF EXISTS elca_cache.report_total_energy_recycling_potential;
CREATE VIEW elca_cache.report_total_energy_recycling_potential AS
    SELECT null::int AS item_id
        , t.indicator_id
        , t.name
        , t.ident
        , t.unit
        , t.is_hidden
        , t.indicator_p_order
        , t.project_variant_id
        , 'D energetisch'::varchar AS category
        , r.life_cycle_p_order
        , r.life_cycle_ident
        , t.value - r.value AS value
    FROM elca_cache.report_life_cycle_effects_v t
    JOIN elca_cache.report_total_construction_recycling_effects_v r ON t.project_variant_id = r.project_variant_id
                                                                       AND t.indicator_id = r.indicator_id
   WHERE t.life_cycle_phase = 'rec';


--------------------------------------------------------------------------------


DROP VIEW IF EXISTS elca_cache.report_element_type_effects_v CASCADE;
CREATE OR REPLACE VIEW elca_cache.report_element_type_effects_v AS
    SELECT ct.project_variant_id
        , ci.item_id
        , ci.indicator_id
        , ci.value
        , lc.phase AS life_cycle_phase
        , lc.ident AS life_cycle_ident
        , lc.name AS life_cycle_name
        , i.name AS name
        , i.unit AS unit
        , i.is_hidden
        , i.p_order AS indicator_p_order
        , t.name AS category
        , ct.level
        , coalesce(t.din_code, '000') AS din_code
        , t.node_id AS element_type_node_id
        , tt.id AS parent_element_type_node_id
    FROM elca_cache.element_types_v ct
        JOIN elca_cache.indicators ci ON ci.item_id = ct.item_id
        JOIN elca.element_types t ON t.node_id = ct.element_type_node_id
        JOIN elca.indicators i ON i.id = ci.indicator_id
        JOIN elca.life_cycles lc ON lc.ident = ci.life_cycle_ident
        LEFT JOIN elca.element_types_v tt ON ct.lft BETWEEN tt.lft AND tt.rgt AND tt.level = ct.level - 1;

--------------------------------------------------------------------------------

DROP VIEW IF EXISTS elca_cache.report_construction_total_effects_v CASCADE;
CREATE OR REPLACE VIEW elca_cache.report_construction_total_effects_v AS
    SELECT ct.project_variant_id
         , ci.indicator_id
         , ci.value
      FROM elca_cache.element_types   ct
      JOIN elca.element_types_v        t ON t.level = 0 AND t.node_id = ct.element_type_node_id
      JOIN elca_cache.indicators      ci ON ci.item_id = ct.item_id AND ci.life_cycle_ident = 'total';

--------------------------------------------------------------------------------

DROP VIEW IF EXISTS elca_cache.project_variant_process_config_mass_v;
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
    , i.is_hidden
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


DROP VIEW IF EXISTS elca_cache.report_final_energy_ref_model_effects_v CASCADE;
CREATE OR REPLACE VIEW elca_cache.report_final_energy_ref_model_effects_v AS
    SELECT r.project_variant_id
         , ci.indicator_id
         , sum(ci.value) AS value
    FROM elca_cache.final_energy_ref_models cr
    JOIN elca.project_final_energy_ref_models r ON r.id = cr.final_energy_ref_model_id
    JOIN elca_cache.indicators              ci ON cr.item_id = ci.item_id AND ci.life_cycle_ident = 'total'
   GROUP BY r.project_variant_id
          , ci.indicator_id;


DROP VIEW IF EXISTS elca_cache.ref_project_construction_effects_v CASCADE;
CREATE OR REPLACE VIEW elca_cache.ref_project_construction_effects_v AS
    SELECT p.benchmark_version_id
        , ci.indicator_id
        , avg(ci.value / (p.life_time * c.net_floor_space)) AS avg
        , min(ci.value / (p.life_time * c.net_floor_space)) AS min
        , max(ci.value / (p.life_time * c.net_floor_space)) AS max
        , count(*) AS counter
    FROM elca.projects p
        JOIN elca.project_variants       v ON v.project_id = p.id
        JOIN elca.project_constructions  c ON v.id = c.project_variant_id
        JOIN elca_cache.element_types   ct ON ct.project_variant_id = v.id
        JOIN elca.element_types_v        t ON t.level = 0 AND t.node_id = ct.element_type_node_id
        JOIN elca_cache.indicators      ci ON ci.item_id = ct.item_id AND ci.life_cycle_ident = 'total'
    WHERE p.is_reference = true
          AND ci.value > 0
    GROUP BY p.benchmark_version_id
        , ci.indicator_id;




DROP MATERIALIZED VIEW IF EXISTS elca_cache.reference_projects_effects_v;
CREATE MATERIALIZED VIEW elca_cache.reference_projects_effects_v AS
    SELECT p.benchmark_version_id
        , element_type_node_id
        , din_code
        , ci.indicator_id
        , avg(ci.value / (p.life_time * c.net_floor_space)) AS avg
        , stddev_pop(ci.value / (p.life_time * c.net_floor_space)) AS stddev
        , min(ci.value / (p.life_time * c.net_floor_space)) AS min
        , max(ci.value / (p.life_time * c.net_floor_space)) AS max
        , count(*) AS samples
    FROM elca.projects p
        JOIN elca.project_variants       v ON v.project_id = p.id
        JOIN elca.project_constructions  c ON c.project_variant_id = v.id
        JOIN elca_cache.element_types   ct ON ct.project_variant_id = p.current_variant_id
        JOIN elca.element_types_v        t ON t.level > 0 AND t.node_id = ct.element_type_node_id
        JOIN elca_cache.indicators      ci ON ci.item_id = ct.item_id AND ci.life_cycle_ident = 'total'
    WHERE p.benchmark_version_id IS NOT NULL
          AND p.is_reference = true
          AND ci.value > 0
    GROUP BY p.benchmark_version_id
        , element_type_node_id
        , din_code
        , ci.indicator_id;



--------------------------------------------------------------------------------
COMMIT;
