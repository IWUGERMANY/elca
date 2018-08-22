BEGIN;
SELECT public.register_patch('add-lifecycle-indicator-description', 'elca');

ALTER TABLE elca.life_cycles ADD  "description" text;
ALTER TABLE elca.indicators  ADD  "description" text;

DROP VIEW IF EXISTS elca.indicators_v;
CREATE OR REPLACE VIEW elca.indicators_v AS
    SELECT DISTINCT i.*
         , p.process_db_id
    FROM elca.indicators i
    JOIN elca.process_indicators pi ON i.id = pi.indicator_id
    JOIN elca.processes p ON p.id = pi.process_id;

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
          , i.p_order AS indicator_p_order
       FROM elca_cache.indicators ci
       JOIN elca.indicators i ON i.id = ci.indicator_id
       JOIN elca.life_cycles l ON ci.life_cycle_ident = l.ident
  LEFT JOIN elca.processes p ON ci.process_id = p.id;

DROP VIEW IF EXISTS elca.process_assignments_v;
DROP VIEW IF EXISTS elca.processes_v;
CREATE OR REPLACE VIEW elca.processes_v AS
    SELECT p.*
         , l.name AS life_cycle_name
         , l.phase AS life_cycle_phase
         , l.p_order AS life_cycle_p_order
         , l.description AS life_cycle_description
    FROM elca.processes p
    JOIN elca.life_cycles l ON l.ident = p.life_cycle_ident;

CREATE OR REPLACE VIEW elca.process_assignments_v AS
    SELECT p.*
         , a.id AS process_life_cycle_assignment_id
         , a.process_config_id
         , a.ratio
    FROM elca.processes_v p
    JOIN elca.process_life_cycle_assignments a ON p.id = a.process_id;

COMMIT;
