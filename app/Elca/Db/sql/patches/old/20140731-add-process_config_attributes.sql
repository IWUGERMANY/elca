BEGIN;
SELECT public.register_patch('add-process_config_attributes', 'elca');

CREATE TABLE elca.process_config_attributes
(
    "id"                      serial          NOT NULL            -- processConfigAttributeId
  , "process_config_id"       int             NOT NULL            -- processConfigId
  , "ident"                   varchar(150)    NOT NULL            -- attribute identifier
  , "numeric_value"           numeric                             -- numeric value
  , "text_value"              text                                -- text value
  , PRIMARY KEY ("id")
  , UNIQUE ("process_config_id", "ident")
  , FOREIGN KEY ("process_config_id") REFERENCES elca.process_configs ("id") ON UPDATE CASCADE ON DELETE CASCADE
);


COMMIT;