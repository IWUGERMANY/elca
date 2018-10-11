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

DROP VIEW IF EXISTS elca.process_configs_extended_search_v;
CREATE OR REPLACE VIEW elca.process_configs_extended_search_v AS
    SELECT
        pc.id
         , pc.process_category_node_id
         , pc.name
         , pc.description
         , pc.life_time_info
         , pc.min_life_time
         , pc.min_life_time_info
         , pc.avg_life_time
         , pc.avg_life_time_info
         , pc.max_life_time
         , pc.max_life_time_info
         , pc.density
         , pc.thermal_conductivity
         , pc.thermal_resistance
         , pc.is_reference
         , pc.f_hs_hi
         , pc.default_size
         , pc.uuid
         , pc.svg_pattern_id
         , pc.is_stale
         , pc.created
         , pc.modified
         , to_tsvector('german', pc.name || ' ' ||
                                 coalesce(array_to_string(array_agg(DISTINCT n.name :: text), ' '), '') || ' '
        ) AS search_vector
    FROM elca.process_configs pc
             LEFT JOIN elca.process_config_names n ON pc.id = n.process_config_id
    GROUP BY pc.id
           , pc.process_category_node_id
           , pc.name
           , pc.description
           , pc.life_time_info
           , pc.min_life_time
           , pc.min_life_time_info
           , pc.avg_life_time
           , pc.avg_life_time_info
           , pc.max_life_time
           , pc.max_life_time_info
           , pc.density
           , pc.thermal_conductivity
           , pc.thermal_resistance
           , pc.is_reference
           , pc.f_hs_hi
           , pc.default_size
           , pc.uuid
           , pc.is_stale
           , pc.created
           , pc.modified;

COMMIT;