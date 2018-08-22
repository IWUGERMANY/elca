BEGIN;

DROP VIEW IF EXISTS bnb.export_total_effects_v;
CREATE VIEW bnb.export_total_effects_v AS
  SELECT ci.item_id
       , ci.indicator_id
       , ci.value
       , ci.life_cycle_ident
       , i.name AS name
       , i.ident AS ident
       , i.unit AS unit
       , i.p_order AS indicator_p_order
       , t.project_variant_id
    FROM elca_cache.element_types_v t
    JOIN elca_cache.indicators ci ON ci.item_id = t.item_id
    JOIN elca.indicators i ON i.id = ci.indicator_id
   WHERE ci.life_cycle_ident = 'total'
     AND t.level = 0;

DROP VIEW IF EXISTS bnb.export_total_element_type_effects_v;
CREATE VIEW bnb.export_total_element_type_effects_v AS
  SELECT ct.item_id
       , ct.project_variant_id
       , ci.indicator_id
       , ci.value
       , t.din_code
       , t.name AS element_type_name
       , i.name AS name
       , i.ident AS ident
       , i.unit AS unit
       , i.p_order AS indicator_p_order
    FROM elca_cache.element_types_v ct
    JOIN elca_cache.indicators ci ON ci.item_id = ct.item_id
    JOIN elca.element_types t ON t.node_id = ct.element_type_node_id
    JOIN elca.indicators i ON i.id = ci.indicator_id
    WHERE ci.life_cycle_ident = 'total'
     AND level > 0;

DROP VIEW IF EXISTS bnb.export_life_cycle_effects_v;
CREATE VIEW bnb.export_life_cycle_effects_v AS
  SELECT ci.item_id
       , ci.indicator_id
       , ci.value
       , i.name AS name
       , i.ident AS ident
       , i.unit AS unit
       , i.p_order AS indicator_p_order
       , l.ident AS life_cycle_ident
       , l.phase AS life_cycle_phase
       , l.p_order AS life_cycle_p_order
    FROM elca_cache.indicators ci
    JOIN elca.indicators i ON i.id = ci.indicator_id
    JOIN elca.life_cycles l ON ci.life_cycle_ident = l.ident
   WHERE ci.is_partial = false;

COMMIT;
