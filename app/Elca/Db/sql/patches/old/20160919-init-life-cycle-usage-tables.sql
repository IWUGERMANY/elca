BEGIN;
SELECT public.register_patch('init-life_cycle-usage-tables', 'elca');

CREATE TABLE elca.benchmark_life_cycle_usage_specifications
(
      "id"                     serial          NOT NULL                -- benchmarkLifeCycleUsageSpecificationId
    , "benchmark_version_id"   int             NOT NULL                -- benchmarkVersionId
    , "life_cycle_ident"       varchar(20)     NOT NULL                -- lifeCycleIdent
    , "use_in_construction"    boolean         NOT NULL                -- useInConstruction
    , "use_in_maintenance"     boolean         NOT NULL                -- useInMaintenance
    , "use_in_energy_demand"   boolean         NOT NULL                -- useInEnergyDemand
    , PRIMARY KEY ("id")
    , UNIQUE ("benchmark_version_id", "life_cycle_ident")
    , FOREIGN KEY ("benchmark_version_id") REFERENCES elca.benchmark_versions ("id") ON DELETE CASCADE
);

CREATE TABLE elca.project_life_cycle_usages
(
      "id"                     serial          NOT NULL                -- projectLifeCycleUsageId
    , "project_id"             int             NOT NULL                -- projectId
    , "life_cycle_ident"       varchar(20)     NOT NULL                -- lifeCycleIdent
    , "use_in_construction"    boolean         NOT NULL                -- useInConstruction
    , "use_in_maintenance"     boolean         NOT NULL                -- useInMaintenance
    , "use_in_energy_demand"   boolean         NOT NULL                -- useInEnergyDemand
    , PRIMARY KEY ("id")
    , UNIQUE ("project_id", "life_cycle_ident")
    , FOREIGN KEY ("project_id") REFERENCES elca.projects ("id") ON DELETE CASCADE
);



-- init for all projects
INSERT INTO elca.project_life_cycle_usages ("project_id", "life_cycle_ident", "use_in_construction", "use_in_maintenance", "use_in_energy_demand")
    SELECT p.id AS project_id
         , l.life_cycle_ident
         , l.life_cycle_ident IN ('prod', 'eol', 'A1', 'A2', 'A3', 'A1-3', 'C3', 'C4', 'D')
         , l.life_cycle_ident IN ('prod', 'eol', 'A1', 'A2', 'A3', 'A1-3', 'C3', 'C4', 'D')
         , l.life_cycle_ident IN ('op', 'B6')
      FROM elca.projects p
      JOIN (SELECT DISTINCT
                        process_db_id,
                        life_cycle_ident
                      FROM elca.processes proc
          ) AS l ON l.process_db_id = p.process_db_id
     WHERE l.life_cycle_ident IN ('A1', 'A2', 'A3', 'A1-3', 'B6', 'C3', 'C4', 'D', 'prod', 'eol', 'op');


-- init for all benchmark system versions
INSERT INTO elca.benchmark_life_cycle_usage_specifications ("benchmark_version_id", "life_cycle_ident", "use_in_construction", "use_in_maintenance", "use_in_energy_demand")
    SELECT v.id AS project_id
        , l.life_cycle_ident
        , l.life_cycle_ident IN ('prod', 'eol', 'A1', 'A2', 'A3', 'A1-3', 'C3', 'C4', 'D')
        , l.life_cycle_ident IN ('prod', 'eol', 'A1', 'A2', 'A3', 'A1-3', 'C3', 'C4', 'D')
        , l.life_cycle_ident IN ('op', 'B6')
    FROM elca.benchmark_versions v
        JOIN (SELECT DISTINCT
                  process_db_id,
                  life_cycle_ident
              FROM elca.processes proc
             ) AS l ON l.process_db_id = v.process_db_id
    WHERE l.life_cycle_ident IN ('A1', 'A2', 'A3', 'A1-3', 'B6', 'C3', 'C4', 'D', 'prod', 'eol', 'op');



DROP VIEW IF EXISTS elca_cache.report_total_effects_lc_usage_v;
CREATE VIEW elca_cache.report_total_effects_lc_usage_v AS
    SELECT l.item_id
        , l.indicator_id
        , l.name
        , l.ident
        , l.unit
        , l.indicator_p_order
        , l.project_variant_id
        , 'Gesamt'::varchar AS category
        , sum(l.value) AS value
    FROM elca_cache.report_life_cycle_effects_v l
        JOIN elca.project_variants v ON v.id = l.project_variant_id
        LEFT JOIN elca.project_life_cycle_usages u ON u.project_id = v.project_id AND (u.life_cycle_ident = l.life_cycle_ident)
    WHERE (l.life_cycle_ident = 'maint' OR true IN (use_in_construction, use_in_energy_demand))
    GROUP BY l.item_id
        , l.indicator_id
        , l.name
        , l.ident
        , l.unit
        , l.indicator_p_order
        , l.project_variant_id;


COMMIT;
