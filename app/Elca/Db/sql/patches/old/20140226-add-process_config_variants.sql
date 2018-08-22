BEGIN;
SELECT public.register_patch('add-process_config_variants', 'elca');

CREATE TABLE elca.process_config_variants
(
   "process_config_id"      int             NOT NULL                -- processConfigId
 , "uuid"                   uuid            NOT NULL                -- uuid
 , "name"                   varchar(250)    NOT NULL                -- name
 , "ref_value"              numeric         NOT NULL DEFAULT 1      -- reference value
 , "ref_unit"               varchar(10)     NOT NULL                -- unit of the reference value
 , PRIMARY KEY ("process_config_id", "uuid")
 , FOREIGN KEY ("process_config_id") REFERENCES elca.process_configs ("id") ON UPDATE CASCADE ON DELETE CASCADE
);

COMMIT;
