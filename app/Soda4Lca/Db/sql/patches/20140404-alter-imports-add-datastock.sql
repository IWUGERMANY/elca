BEGIN;
SELECT public.register_patch('alter-imports-structure-add-datastock', 'soda4lca');

ALTER TABLE soda4lca.imports ADD COLUMN "data_stock" varchar(250);

DROP VIEW IF EXISTS soda4lca.databases_v;
CREATE VIEW soda4lca.databases_v AS
  SELECT d.*
    , i.id AS import_id
    , i.status
    , i.date_of_import
    , i.data_stock
  FROM elca.process_dbs d
    JOIN soda4lca.imports i ON d.id = i.process_db_id;

COMMIT;