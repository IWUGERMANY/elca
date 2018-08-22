BEGIN;
SELECT public.register_patch('alter-indicator-structure', 'elca');
ALTER TABLE elca.indicators ADD "is_excluded"        boolean         NOT NULL DEFAULT false;

DROP VIEW IF EXISTS elca.indicators_v;
CREATE OR REPLACE VIEW elca.indicators_v AS
    SELECT DISTINCT i.*
         , p.process_db_id
    FROM elca.indicators i
    JOIN elca.process_indicators pi ON i.id = pi.indicator_id
    JOIN elca.processes p ON p.id = pi.process_id;

COMMIT;

