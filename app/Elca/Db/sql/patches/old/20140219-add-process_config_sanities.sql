BEGIN;
SELECT public.register_patch('add-process_config_sanities', 'elca');

CREATE TABLE elca.process_config_sanities
(
   "id"                     serial          NOT NULL                -- processConfigSanityId
 , "process_config_id"      int             NOT NULL                 -- process_config
 , "status"                 varchar(50)     NOT NULL                 -- status info
 , "process_db_id"          int                                      -- database id
 , "details"                text                                     -- detail info
 , "is_false_positive"      boolean         NOT NULL DEFAULT false   -- flags as false positive
 , "created"                timestamptz(0)  NOT NULL DEFAULT now()   -- creation time
 , "modified"               timestamptz(0)                           -- modification time
 , PRIMARY KEY ("id")
 , UNIQUE ("process_config_id", "status", "process_db_id")
 , FOREIGN KEY ("process_config_id") REFERENCES elca.process_configs ("id") ON UPDATE CASCADE ON DELETE CASCADE
 , FOREIGN KEY ("process_db_id") REFERENCES elca.process_dbs ("id") ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE VIEW elca.process_config_sanities_v AS
       SELECT 'MISSING_LIFE_TIME' AS status
            , id AS process_config_id                   
            , name                  
            , process_category_node_id
            , null::int AS process_db_id
         FROM elca.process_configs
        WHERE coalesce(min_life_time, avg_life_time, max_life_time) IS NULL
   UNION
       SELECT 'MISSING_CONVERSIONS' AS status
            , pc.id AS process_config_id                     
            , pc.name                  
            , pc.process_category_node_id
            , null::int AS process_db_id
         FROM elca.process_configs pc
         JOIN (SELECT DISTINCT process_config_id
                             , a.ref_unit AS in
                             , b.ref_unit AS out
                          FROM elca.process_assignments_v a
                          JOIN elca.process_assignments_v b USING (process_config_id)
                         WHERE 'op' NOT IN (a.life_cycle_phase, b.life_cycle_phase) AND a.ref_unit <> b.ref_unit
              ) a ON pc.id = a.process_config_id
    LEFT JOIN elca.process_conversions c ON pc.id = c.process_config_id AND (a.in, a.out) IN ((c.in_unit, c.out_unit), (c.out_unit, c.in_unit))
         WHERE c.id IS NULL
   UNION
       SELECT 'MISSING_PRODUCTION' AS status
            , pc.id AS process_config_id                     
            , pc.name                  
            , pc.process_category_node_id
            , a.process_db_id
         FROM elca.process_configs pc
         JOIN elca.process_assignments_v a ON pc.id = a.process_config_id 
        WHERE a.life_cycle_phase != 'op'
     GROUP BY pc.id                    
            , pc.name                  
            , pc.process_category_node_id
            , a.process_db_id
       HAVING 'prod' != ALL (array_agg(DISTINCT a.life_cycle_phase));

CREATE OR REPLACE FUNCTION elca.update_process_config_sanities()
              RETURNS void
--
-- Inserts new process config sanities  
--
AS $$

BEGIN

   DELETE FROM elca.process_config_sanities s
         WHERE is_false_positive = false
           AND NOT EXISTS (SELECT v.process_config_id
                             FROM elca.process_config_sanities_v v
                            WHERE s.process_config_id = v.process_config_id
                              AND s.status = v.status 
                              AND s.process_db_id IS NOT DISTINCT FROM v.process_db_id
                          );

   INSERT INTO elca.process_config_sanities (process_config_id, status, process_db_id)
             SELECT v.process_config_id
                  , v.status
                  , v.process_db_id
               FROM elca.process_config_sanities_v v
          LEFT JOIN elca.process_config_sanities   s ON s.process_config_id = v.process_config_id
                                                    AND s.status = v.status 
                                                    AND s.process_db_id IS NOT DISTINCT FROM v.process_db_id
              WHERE s.id IS NULL;

END;
$$ LANGUAGE plpgsql;
      
COMMIT;
