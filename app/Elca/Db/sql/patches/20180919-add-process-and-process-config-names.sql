BEGIN;
SELECT
    public.register_patch('20180919-add-process-and-process-config-names.sql', 'elca');


CREATE TABLE elca.process_names
(
      "process_id"             int             NOT NULL
    , "lang"                   varchar(3)      NOT NULL
    , "name"                   varchar(250)    NOT NULL
    , "created"                timestamptz(0)  NOT NULL DEFAULT now()
    , "modified"               timestamptz(0)           DEFAULT now()
    , PRIMARY KEY ("process_id", "lang")
    , FOREIGN KEY ("process_id") REFERENCES elca.processes ("id") ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE TABLE elca.process_config_names
(
      "process_config_id"      int             NOT NULL
    , "lang"                   varchar(3)      NOT NULL
    , "name"                   varchar(250)    NOT NULL
    , "created"                timestamptz(0)  NOT NULL DEFAULT now()
    , "modified"               timestamptz(0)           DEFAULT now()
    , PRIMARY KEY ("process_config_id", "lang")
    , FOREIGN KEY ("process_config_id") REFERENCES elca.process_configs ("id") ON UPDATE CASCADE ON DELETE CASCADE
);


COMMIT;