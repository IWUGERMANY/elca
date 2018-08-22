BEGIN;
SELECT public.register_patch('init-transports', 'elca');

CREATE TABLE elca.project_transports
(
    "id"                      serial          NOT NULL                -- projectTransportId
  , "project_variant_id"      int             NOT NULL                -- projectVariantId
  , "name"                    varchar(200)    NOT NULL                -- transport short description
  , "quantity"                numeric         NOT NULL                -- quantity in kg
  , "process_config_id"       int                                     -- process config id
  , "calc_lca"                boolean         NOT NULL DEFAULT false  -- calculate lca
  , PRIMARY KEY ("id")
  , FOREIGN KEY ("project_variant_id") REFERENCES elca.project_variants ("id") ON UPDATE CASCADE ON DELETE CASCADE
  , FOREIGN KEY ("process_config_id") REFERENCES elca.process_configs ("id") ON UPDATE CASCADE ON DELETE SET NULL
);

CREATE TABLE elca.project_transport_means
(
    "id"                      serial          NOT NULL                -- projectTransportMeanId
  , "project_transport_id"    int             NOT NULL                -- projectTransportId
  , "process_config_id"       int             NOT NULL                -- processConfigId
  , "distance"                numeric         NOT NULL                -- distance in m
  , "efficiency"              numeric         NOT NULL DEFAULT 1      -- transport efficiency
  , PRIMARY KEY ("id")
  , FOREIGN KEY ("project_transport_id") REFERENCES elca.project_transports ("id") ON UPDATE CASCADE ON DELETE CASCADE
  , FOREIGN KEY ("process_config_id") REFERENCES elca.process_configs ("id") ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE OR REPLACE VIEW elca.project_transport_means_v AS
  SELECT m.*
    , p.name AS process_config_name
  FROM elca.project_transport_means m
    JOIN elca.process_configs         p ON p.id = m.process_config_id;

COMMIT;