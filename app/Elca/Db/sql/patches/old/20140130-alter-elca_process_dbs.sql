BEGIN;
SELECT public.register_patch('alter-elca_process_dbs', 'elca');

ALTER TABLE elca.process_dbs ADD "source_uri" varchar(250);
ALTER TABLE elca.process_dbs ADD "is_active" boolean NOT NULL DEFAULT false;

UPDATE elca.process_dbs SET is_active = true;

COMMIT;
