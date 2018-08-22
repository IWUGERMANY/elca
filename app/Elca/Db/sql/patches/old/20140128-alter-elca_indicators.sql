BEGIN;
SELECT public.register_patch('alter-elca_indicators', 'elca');

DROP VIEW IF EXISTS elca_cache.indicators_aggregate_v;
DROP VIEW IF EXISTS elca_cache.composite_indicators_aggregate_v;
DROP VIEW IF EXISTS elca_cache.indicators_totals_v;
DROP VIEW IF EXISTS elca_cache.indicators_v;
DROP VIEW IF EXISTS elca.indicators_v;

--------------------------------------------------------------------------------

ALTER TABLE elca.indicators ADD COLUMN "uuid" uuid;
ALTER TABLE elca.indicators ADD UNIQUE ("uuid");
ALTER TABLE elca.indicators ADD COLUMN "is_en15804_compliant"  boolean NOT NULL DEFAULT false;

UPDATE elca.indicators
   SET is_en15804_compliant = true
  WHERE ident IN ('pere','perm','pert','penre','penrm','penrt','sm','rsf','nrsf','hwd','nhwd','rwd','cru','mfr','mer','eee','eet','gwp','odp','ap','ep','pocp','adpe','adpf');

-- add fw
INSERT INTO elca.indicators (id, name, ident, unit, is_excluded, p_order, description, uuid, is_en15804_compliant)
  VALUES (33, 'FW', 'fw', 'm3', true, 155, 'Einsatz von Süßwasserressourcen', '3cf952c8-f3a4-461d-8c96-96456ca62246', true);

UPDATE elca.indicators SET uuid = '20f32be5-0398-4288-9b6d-accddd195317' WHERE ident = 'pere';
UPDATE elca.indicators SET uuid = 'fb3ec0de-548d-4508-aea5-00b73bf6f702' WHERE ident = 'perm';
UPDATE elca.indicators SET uuid = '53f97275-fa8a-4cdd-9024-65936002acd0' WHERE ident = 'pert';
UPDATE elca.indicators SET uuid = 'ac857178-2b45-46ec-892a-a9a4332f0372' WHERE ident = 'penre';
UPDATE elca.indicators SET uuid = '1421caa0-679d-4bf4-b282-0eb850ccae27' WHERE ident = 'penrm';
UPDATE elca.indicators SET uuid = '06159210-646b-4c8d-8583-da9b3b95a6c1' WHERE ident = 'penrt';
UPDATE elca.indicators SET uuid = 'c6a1f35f-2d09-4f54-8dfb-97e502e1ce92' WHERE ident = 'sm';
UPDATE elca.indicators SET uuid = '64333088-a55f-4aa2-9a31-c10b07816787' WHERE ident = 'rsf';
UPDATE elca.indicators SET uuid = '89def144-d39a-4287-b86f-efde453ddcb2' WHERE ident = 'nrsf';
UPDATE elca.indicators SET uuid = '430f9e0f-59b2-46a0-8e0d-55e0e84948fc' WHERE ident = 'hwd';
UPDATE elca.indicators SET uuid = 'b29ef66b-e286-4afa-949f-62f1a7b4d7fa' WHERE ident = 'nhwd';
UPDATE elca.indicators SET uuid = '3449546e-52ad-4b39-b809-9fb77cea8ff6' WHERE ident = 'rwd';
UPDATE elca.indicators SET uuid = 'a2b32f97-3fc7-4af2-b209-525bc6426f33' WHERE ident = 'cru';
UPDATE elca.indicators SET uuid = 'd7fe48a5-4103-49c8-9aae-b0b5dfdbd6ae' WHERE ident = 'mfr';
UPDATE elca.indicators SET uuid = '59a9181c-3aaf-46ee-8b13-2b3723b6e447' WHERE ident = 'mer';
UPDATE elca.indicators SET uuid = '4da0c987-2b76-40d6-9e9e-82a017aaaf29' WHERE ident = 'eee';
UPDATE elca.indicators SET uuid = '98daf38a-7a79-46d3-9a37-2b7bd0955810' WHERE ident = 'eet';
UPDATE elca.indicators SET uuid = '77e416eb-a363-4258-a04e-171d843a6460' WHERE ident = 'gwp';
UPDATE elca.indicators SET uuid = '06dcd26f-025f-401a-a7c1-5e457eb54637' WHERE ident = 'odp';
UPDATE elca.indicators SET uuid = 'b4274add-93b7-4905-a5e4-2e878c4e4216' WHERE ident = 'ap'; 
UPDATE elca.indicators SET uuid = 'f58827d0-b407-4ec6-be75-8b69efb98a0f' WHERE ident = 'ep'; 
UPDATE elca.indicators SET uuid = '1e84a202-dae6-42aa-9e9d-71ea48b8be00' WHERE ident = 'pocp';
UPDATE elca.indicators SET uuid = 'f7c73bb9-ab1a-4249-9c6d-379a0de6f67e' WHERE ident = 'adpe';
UPDATE elca.indicators SET uuid = '804ebcdf-309d-4098-8ed8-fdaf2f389981' WHERE ident = 'adpf';

--------------------------------------------------------------------------------

CREATE OR REPLACE VIEW elca.indicators_v AS
    SELECT DISTINCT i.*
         , p.process_db_id
    FROM elca.indicators i
    JOIN elca.process_indicators pi ON i.id = pi.indicator_id
    JOIN elca.processes p ON p.id = pi.process_id;

CREATE OR REPLACE VIEW elca_cache.indicators_v AS
  SELECT i.*
       , ii.*
   FROM elca_cache.indicators i
   JOIN elca_cache.items      ii ON ii.id = i.item_id;


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

COMMIT;
