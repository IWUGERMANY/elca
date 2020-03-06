BEGIN;
SELECT public.register_patch('20200221-add-process_conversion_audit.sql', 'elca');

CREATE TABLE elca.process_conversion_audit (
    "id"                     serial          NOT NULL                -- processConversionAuditId
    , "process_config_id"      int             NOT NULL                -- processConfigId
    , "process_db_id"          int             NOT NULL                -- processDbId
    , "conversion_id"          int             NOT NULL                -- conversionId
    , "in_unit"                varchar(10)                     -- input unit of measure
    , "out_unit"               varchar(10)                     -- output unit of measure
    , "factor"                 numeric                         -- conversion factor
    , "ident"                  varchar(20)                             -- ident
    , "old_in_unit"            varchar(10)                     -- old input unit of measure
    , "old_out_unit"           varchar(10)                     -- old output unit of measure
    , "old_factor"             numeric                       -- old conversion factor
    , "old_ident"              varchar(20)                             -- old ident
    , "modified"               timestamptz(0)           DEFAULT now()  -- modification time
    , "modified_by"            varchar(200)
    , PRIMARY KEY ("id")
);

COMMIT;
