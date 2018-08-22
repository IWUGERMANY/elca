BEGIN;
SELECT public.register_patch('fix-2009-indicators', 'elca');


ALTER TABLE elca.process_indicators
        ADD old_indicator_id int;

ALTER TABLE elca.process_indicators
     DROP CONSTRAINT process_indicators_process_id_indicator_id_key;

UPDATE elca.process_indicators
   SET old_indicator_id = indicator_id
 WHERE process_id IN (SELECT id FROM elca.processes WHERE process_db_id = (SELECT id FROM elca.process_dbs WHERE version = '2009'));


-- Fix GWP (9) currently EP (12)
UPDATE elca.process_indicators x
   SET indicator_id = (SELECT id FROM elca.indicators WHERE ident = 'gwp')
 WHERE old_indicator_id = (SELECT id FROM elca.indicators WHERE ident = 'ep');

UPDATE elca.process_indicators x
   SET indicator_id = (SELECT id FROM elca.indicators WHERE ident = 'ep')
 WHERE old_indicator_id = (SELECT id FROM elca.indicators WHERE ident = 'gwp');

-- Fix AP (10) currently ODP (13)
UPDATE elca.process_indicators x
   SET indicator_id = (SELECT id FROM elca.indicators WHERE ident = 'ap')
 WHERE old_indicator_id = (SELECT id FROM elca.indicators WHERE ident = 'odp');

UPDATE elca.process_indicators x
   SET indicator_id = (SELECT id FROM elca.indicators WHERE ident = 'odp')
 WHERE old_indicator_id = (SELECT id FROM elca.indicators WHERE ident = 'ap');

ALTER TABLE elca.process_indicators
    ADD UNIQUE (process_id, indicator_id);

--ALTER TABLE elca.process_indicators
--   DROP COLUMN old_indicator_id;


COMMIT;
