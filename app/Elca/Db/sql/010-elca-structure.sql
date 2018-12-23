----------------------------------------------------------------------------------------
-- This file is part of the eLCA project
--
-- eLCA
-- A web based life cycle assessment application
--
-- Copyright (c) 2013 Tobias Lode <tobias@beibob.de>
--               BEIBOB Medienfreunde GbR - http://beibob.de/
--
-- eLCA is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.
--
-- eLCA is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with eLCA. If not, see <http://www.gnu.org/licenses/>.
----------------------------------------------------------------------------------------
SET client_encoding = 'UTF8';

BEGIN;
CREATE SCHEMA elca;
COMMIT;

SET search_path = elca, public;

BEGIN;
-------------------------------------------------------------------------------
-- common objects
-------------------------------------------------------------------------------
-- indicators

CREATE TABLE elca.indicators
(
   "id"                    smallint        NOT NULL                -- indicatorId
 , "name"                  varchar(150)    NOT NULL                -- a pretty name
 , "ident"                 varchar(20)     NOT NULL                -- indicator short name
 , "unit"                  varchar(50)     NOT NULL                -- unit of measure
 , "is_hidden"             boolean         NOT NULL DEFAULT false  -- hide this indicator on screen
 , "is_excluded"           boolean         NOT NULL DEFAULT false  -- exclude from lca
 , "p_order"               smallint                                -- presentation order
 , "description"           text                                    -- description
 , "uuid"                  uuid                                    -- uuid
 , "is_en15804_compliant"  boolean         NOT NULL DEFAULT false  -- isEn15804Compliant
 , PRIMARY KEY ("id")
 , UNIQUE ("uuid")
 , UNIQUE ("ident")
);

-------------------------------------------------------------------------------
-- life cycles

CREATE TABLE elca.life_cycles
(
   "ident"              varchar(20)     NOT NULL                -- life cycle short name
 , "name"               varchar(150)    NOT NULL                -- a pretty name
 , "phase"              varchar(50)     NOT NULL                -- associated phase
 , "p_order"            smallint                                -- presentation order
 , "description"        text                                    -- description
 , PRIMARY KEY ("ident")
);

-------------------------------------------------------------------------------

CREATE TABLE elca.constr_catalogs
(
   "id"                     serial          NOT NULL                -- constrCatalogId
 , "name"                   varchar(250)    NOT NULL                -- name of catalog
 , "ident"                  varchar(100)                            -- internal short name
 , PRIMARY KEY ("id")
);

-------------------------------------------------------------------------------

CREATE TABLE elca.constr_designs
(
   "id"                     serial          NOT NULL                -- constrDesignId
 , "name"                   varchar(250)    NOT NULL                -- name of construction design
 , "ident"                  varchar(100)                            -- internal short name
 , PRIMARY KEY ("id")
);

-------------------------------------------------------------------------------
-- bauwerkszuordnungen bwz

CREATE TABLE elca.constr_classes
(
    "id"                      serial          NOT NULL                -- constrClassId
  , "name"                    text            NOT NULL                -- name
  , "ref_num"                 int             NOT NULL                -- reference number
  , "ref_area"                varchar(20)                             -- reference area
  , PRIMARY KEY ("id")
);

-------------------------------------------------------------------------------
-- svg patterns

CREATE TABLE elca.svg_patterns
(
    "id"                          serial          NOT NULL                -- svgPatternId
  , "name"                        varchar(150)    NOT NULL                -- pattern name
  , "description"                 text                                    -- description
  , "width"                       numeric         NOT NULL                -- width
  , "height"                      numeric         NOT NULL                -- height
  , "image_id"                    int                                     -- imageId
  , "created"                     timestamptz(0)  NOT NULL DEFAULT now()  -- creation time
  , "modified"                    timestamptz(0)           DEFAULT now()  -- modification time
  , PRIMARY KEY ("id")
  , FOREIGN KEY ("image_id") REFERENCES public.media ("id") ON UPDATE CASCADE ON DELETE SET NULL
);


-------------------------------------------------------------------------------
-- processes
-------------------------------------------------------------------------------
-- process databases

CREATE TABLE elca.process_dbs
(
   "id"                     serial          NOT NULL                -- processDbId
 , "name"                   varchar(150)    NOT NULL                -- name
 , "version"                varchar(50)                             -- version string
 , "uuid"                   uuid            NOT NULL DEFAULT uuid_generate_v4() -- uuid of the process db
 , "source_uri"             varchar(250)                            -- source uri
 , "is_active"              boolean         NOT NULL DEFAULT false  -- flags the database as active
 , "is_en15804_compliant"   boolean         NOT NULL DEFAULT true   -- isEn15804Compliant
 , "created"                timestamptz(0)  NOT NULL DEFAULT now()  -- creation time
 , "modified"               timestamptz(0)           DEFAULT now()  -- modification time
 , PRIMARY KEY ("id")
 , UNIQUE ("uuid")
);

-------------------------------------------------------------------------------
-- process categories

CREATE TABLE elca.process_categories
(
   "node_id"    int             NOT NULL                -- processCategoryId
 , "name"       varchar(150)    NOT NULL                -- name
 , "ref_num"    varchar(50)                             -- reference number
 , "svg_pattern_id" int                                 -- referenced svg pattern
 , PRIMARY KEY ("node_id")
 , FOREIGN KEY ("node_id") REFERENCES public.nested_nodes ("id") ON UPDATE CASCADE ON DELETE CASCADE
 , FOREIGN KEY ("svg_pattern_id") REFERENCES elca.svg_patterns ("id") ON UPDATE CASCADE ON DELETE SET NULL
);

CREATE INDEX ix_elca_process_categories_ref_num on elca.process_categories(ref_num);

-------------------------------------------------------------------------------
-- process configs

CREATE TABLE elca.process_configs
(
   "id"                     serial          NOT NULL                -- processConfigId
 , "name"                   varchar(250)    NOT NULL                -- name
 , "process_category_node_id" int           NOT NULL                -- processCategoryNodeId
 , "description"            text                                    -- description
 , "density"                numeric                                 -- density
 , "thermal_conductivity"   numeric                                 -- thermal conductivity
 , "thermal_resistance"     numeric                                 -- thermal resistance
 , "is_reference"           boolean         NOT NULL DEFAULT true   -- is reference
 , "f_hs_hi"                numeric                                 -- factor hs/hi
 , "default_size"           numeric                                 -- default size
 , "min_life_time"          int                                     -- min life time in years
 , "avg_life_time"          int                                     -- avg life time in years
 , "max_life_time"          int                                     -- max life time in years
 , "life_time_info"         text                                    -- life time info
 , "min_life_time_info"     text                                    -- min life time info
 , "avg_life_time_info"     text                                    -- default life time info
 , "max_life_time_info"     text                                    -- max life time info
 , "uuid"                   uuid            NOT NULL DEFAULT uuid_generate_v4() -- uuid of the processConfig
 , "svg_pattern_id"         int                                     -- referenced svg pattern
 , "is_stale"               boolean         NOT NULL DEFAULT false  -- is stale
 , "created"                timestamptz(0)  NOT NULL DEFAULT now()  -- creation time
 , "modified"               timestamptz(0)           DEFAULT now()  -- modification time
 , PRIMARY KEY ("id")
 , UNIQUE ("uuid")
 , FOREIGN KEY ("process_category_node_id") REFERENCES elca.process_categories ("node_id") ON UPDATE CASCADE ON DELETE CASCADE
 , FOREIGN KEY ("svg_pattern_id") REFERENCES elca.svg_patterns ("id") ON UPDATE CASCADE ON DELETE SET NULL
);

CREATE OR REPLACE FUNCTION elca.update_process_config_is_stale()
    RETURNS trigger
AS $$

DECLARE
    proc_info record;
BEGIN
    SELECT pc.id
         , a.id AS assignment_id
      INTO proc_info
    FROM
        elca.process_configs pc
    LEFT JOIN
        elca.process_life_cycle_assignments a ON pc.id = a.process_config_id
    WHERE pc.id = CASE WHEN TG_OP = 'INSERT' THEN NEW.process_config_id ELSE OLD.process_config_id END
    LIMIT 1;

    IF proc_info.assignment_id IS NULL THEN
        UPDATE elca.process_configs SET is_stale = true WHERE id = proc_info.id;
    ELSE
        UPDATE elca.process_configs SET is_stale = false WHERE id = proc_info.id;
    END IF;

    RETURN null;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_update_process_config_is_stale AFTER INSERT OR DELETE  ON elca.process_life_cycle_assignments
    FOR EACH ROW EXECUTE PROCEDURE elca.update_process_config_is_stale();
-------------------------------------------------------------------------------

CREATE TABLE elca.process_config_variants
(
   "process_config_id"      int             NOT NULL                -- processConfigId
 , "uuid"                   uuid            NOT NULL                -- uuid
 , "name"                   varchar(250)    NOT NULL                -- name
 , "ref_value"              numeric         NOT NULL DEFAULT 1      -- reference value
 , "ref_unit"               varchar(10)     NOT NULL                -- unit of the reference value
 , "is_vendor_specific"     boolean         NOT NULL DEFAULT false  -- indicates a vendor specific product 
 , "specific_process_config_id"  int                                -- reference to specific process config
 , PRIMARY KEY ("process_config_id", "uuid")
 , FOREIGN KEY ("process_config_id") REFERENCES elca.process_configs ("id") ON UPDATE CASCADE ON DELETE CASCADE
 , FOREIGN KEY ("specific_process_config_id") REFERENCES elca.process_configs ("id") ON UPDATE CASCADE ON DELETE SET NULL
);

-------------------------------------------------------------------------------

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

-------------------------------------------------------------------------------

CREATE TABLE elca.process_scenarios
(
   "id"                 serial          NOT NULL                -- scenarioId
 , "process_config_id"  int             NOT NULL                -- processConfigId
 , "ident"              varchar(250)    NOT NULL                -- ident
 , "group_ident"        varchar(250)                            -- groupIdent
 , "is_default"         boolean         NOT NULL DEFAULT false  -- default scenario for the specified group
 , "description"        text                                    -- scenario description
 , PRIMARY KEY ("id")
 , UNIQUE ("process_config_id", "ident")
 , FOREIGN KEY ("process_config_id") REFERENCES elca.process_configs ("id") ON UPDATE CASCADE ON DELETE CASCADE
);     

-------------------------------------------------------------------------------
-- processes

CREATE TABLE elca.processes
(
   "id"                     serial          NOT NULL                -- processId
 , "process_db_id"          int             NOT NULL                -- database id
 , "process_category_node_id" int           NOT NULL                -- category node id
 , "name"                   varchar(250)    NOT NULL                -- name
 , "name_orig"              varchar(250)    NOT NULL                -- original name
 , "uuid"                   uuid            NOT NULL                -- uuid of the process
 , "version"                varchar(50)                             -- version string
 , "date_of_last_revision"  timestamptz(0)                          -- date of last revision
 , "life_cycle_ident"       varchar(20)     NOT NULL                -- life cycle ident
 , "ref_value"              numeric         NOT NULL DEFAULT 1      -- reference value
 , "ref_unit"               varchar(10)     NOT NULL                -- unit of the reference value
 , "is_reference"           boolean         NOT NULL DEFAULT true   -- is reference process
 , "scenario_id"            int                                     -- scenarioId
 , "description"            text                                    -- some description
 , "epd_type"               varchar(30)                             -- epd sub type
 , "created"                timestamptz(0)  NOT NULL DEFAULT now()  -- creation time
 , "modified"               timestamptz(0)           DEFAULT now()  -- modification time
 , PRIMARY KEY ("id")
 , UNIQUE ("process_db_id", "uuid", "life_cycle_ident", "scenario_id")
 , FOREIGN KEY ("process_db_id") REFERENCES elca.process_dbs ("id") ON UPDATE CASCADE ON DELETE CASCADE
 , FOREIGN KEY ("process_category_node_id") REFERENCES elca.process_categories ("node_id") ON UPDATE CASCADE ON DELETE CASCADE
 , FOREIGN KEY ("life_cycle_ident") REFERENCES elca.life_cycles ("ident") ON UPDATE CASCADE ON DELETE CASCADE
 , FOREIGN KEY ("scenario_id") REFERENCES elca.process_scenarios ("id") ON UPDATE CASCADE ON DELETE SET NULL
);

CREATE INDEX IX_elca_processes_process_db_id ON elca.processes (process_db_id);

-------------------------------------------------------------------------------
-- process indicators values

CREATE TABLE elca.process_indicators
(
   "id"                     serial          NOT NULL                -- processIndicatorId
 , "process_id"             int             NOT NULL                -- process id
 , "indicator_id"           smallint        NOT NULL                -- indicator id
 , "value"                  numeric         NOT NULL                -- indicator value
 , PRIMARY KEY ("id")
 , UNIQUE ("process_id", "indicator_id")
 , FOREIGN KEY ("process_id") REFERENCES elca.processes ("id") ON UPDATE CASCADE ON DELETE CASCADE
 , FOREIGN KEY ("indicator_id") REFERENCES elca.indicators ("id") ON UPDATE CASCADE ON DELETE CASCADE
);


-------------------------------------------------------------------------------
-- process life cycle assignments

CREATE TABLE elca.process_life_cycle_assignments
(
   "id"                     serial          NOT NULL                -- processLifeCycleAssignmentId
 , "process_config_id"      int             NOT NULL                -- processConfigId
 , "process_id"             int             NOT NULL                -- processId
 , "ratio"                  numeric         NOT NULL                -- ratio
 , "created"                timestamptz(0)  NOT NULL DEFAULT now()  -- creation time
 , "modified"               timestamptz(0)           DEFAULT now()  -- modification time
 , PRIMARY KEY ("id")
 , FOREIGN KEY ("process_config_id") REFERENCES elca.process_configs ("id") ON UPDATE CASCADE ON DELETE CASCADE
 , FOREIGN KEY ("process_id") REFERENCES elca.processes ("id") ON UPDATE CASCADE ON DELETE CASCADE
);


-------------------------------------------------------------------------------
-- process conversions

CREATE TABLE elca.process_conversions
(
   "id"                     serial          NOT NULL                -- processLifeCycleAssignmentId
 , "process_config_id"      int             NOT NULL                -- processConfigId
 , "in_unit"                varchar(10)     NOT NULL                -- input unit of measure
 , "out_unit"               varchar(10)     NOT NULL                -- output unit of measure
 , "factor"                 numeric         NOT NULL                -- conversion factor
 , "ident"                  varchar(20)                             -- internal ident
 , "created"                timestamptz(0)  NOT NULL DEFAULT now()  -- creation time
 , "modified"               timestamptz(0)           DEFAULT now()  -- modification time
 , PRIMARY KEY ("id")
 , UNIQUE ("process_config_id", "ident")
 , FOREIGN KEY ("process_config_id") REFERENCES elca.process_configs ("id") ON UPDATE CASCADE ON DELETE CASCADE
);

-------------------------------------------------------------------------------

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

CREATE INDEX IX_elca_process_config_sanities_process_db_id ON elca.process_config_sanities (process_db_id);


----------------------------------------------------------------------------------------
-- benchmarks
----------------------------------------------------------------------------------------

CREATE TABLE elca.benchmark_systems
(
      "id"                    serial          NOT NULL                -- benchmarkSystemId
    , "name"                  varchar(150)    NOT NULL                -- system name
    , "is_active"             boolean         NOT NULL DEFAULT false  -- active flag
    , "description"           text                                    -- description
    , "model_class"           varchar(250)    NOT NULL
    , PRIMARY KEY ("id")
);

----------------------------------------------------------------------------------------

CREATE TABLE elca.benchmark_versions
(
      "id"                    serial          NOT NULL                -- benchmarkVersionId
    , "benchmark_system_id"   int             NOT NULL                -- benchmarkSystemId
    , "name"                  varchar(150)    NOT NULL                -- system name
    , "process_db_id"         int                                     -- processDbId
    , "is_active"             boolean         NOT NULL DEFAULT false  -- active flag
    , "use_reference_model"   boolean         NOT NULL DEFAULT false  -- useReferenceModel
    , "project_life_time"     int                                     -- projectLifeTime
    , PRIMARY KEY ("id")
    , FOREIGN KEY ("benchmark_system_id") REFERENCES elca.benchmark_systems ("id") ON UPDATE CASCADE ON DELETE CASCADE
    , FOREIGN KEY ("process_db_id") REFERENCES elca.process_dbs ("id") ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE INDEX IX_elca_benchmark_versions_process_db_id ON elca.benchmark_versions (process_db_id);

----------------------------------------------------------------------------------------

CREATE TABLE elca.benchmark_thresholds
(
      "id"                    serial        NOT NULL                  -- benchmarkThresholdId
    , "benchmark_version_id"  int           NOT NULL                  -- benchmarkVersionId
    , "indicator_id"          int           NOT NULL                  -- indicatorId
    , "score"                 int           NOT NULL                  -- score value
    , "value"                 numeric       NOT NULL                  -- threshold value
    , PRIMARY KEY ("id")
    , UNIQUE ("benchmark_version_id", "indicator_id", "score")
    , FOREIGN KEY ("benchmark_version_id") REFERENCES elca.benchmark_versions ("id") ON UPDATE CASCADE ON DELETE CASCADE
    , FOREIGN KEY ("indicator_id") REFERENCES elca.indicators ("id") ON UPDATE CASCADE ON DELETE CASCADE
);

----------------------------------------------------------------------------------------

CREATE TABLE elca.benchmark_ref_process_configs
(
      "benchmark_version_id"  int           NOT NULL                  -- benchmarkVersionId
    , "ident"                 varchar(30)   NOT NULL                  -- ident
    , "process_config_id"     int           NOT NULL                  -- reference process config
    , PRIMARY KEY ("benchmark_version_id", "ident")
    , FOREIGN KEY ("benchmark_version_id") REFERENCES elca.benchmark_versions ("id") ON UPDATE CASCADE ON DELETE CASCADE
    , FOREIGN KEY ("process_config_id") REFERENCES elca.process_configs ("id") ON UPDATE CASCADE ON DELETE CASCADE
);

----------------------------------------------------------------------------------------

CREATE TABLE elca.benchmark_ref_construction_values
(
      "benchmark_version_id"  int           NOT NULL                  -- benchmarkVersionId
    , "indicator_id"          int           NOT NULL                  -- indicatorId
    , "value"                 numeric                                 -- reference construction value
    , PRIMARY KEY ("benchmark_version_id", "indicator_id")
    , FOREIGN KEY ("benchmark_version_id") REFERENCES elca.benchmark_versions ("id") ON UPDATE CASCADE ON DELETE CASCADE
    , FOREIGN KEY ("indicator_id") REFERENCES elca.indicators ("id") ON UPDATE CASCADE ON DELETE CASCADE
);

----------------------------------------------------------------------------------------

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

CREATE TABLE elca.benchmark_version_constr_classes
(
      "id"                     serial          NOT NULL                -- benchmarkLifeCycleUsageSpecificationId
    , "benchmark_version_id"   int             NOT NULL                -- benchmarkVersionId
    , "constr_class_id"        int             NOT NULL
    , PRIMARY KEY ("id")
    , UNIQUE ("benchmark_version_id", "constr_class_id")
    , FOREIGN KEY ("benchmark_version_id") REFERENCES elca.benchmark_versions ("id") ON DELETE CASCADE
    , FOREIGN KEY ("constr_class_id") REFERENCES elca.constr_classes("id") ON DELETE CASCADE
);

CREATE TABLE elca.benchmark_groups
(
      "id"        serial  NOT NULL
    , "benchmark_version_id" int NOT NULL
    , "name"      varchar(200)  NOT NULL
    , PRIMARY KEY ("id")
    , UNIQUE ("benchmark_version_id", "name")
    , FOREIGN KEY ("benchmark_version_id") REFERENCES elca.benchmark_versions ("id") ON DELETE CASCADE
);

CREATE TABLE elca.benchmark_group_indicators
(
      "group_id"  int  NOT NULL
    , "indicator_id"      int NOT NULL
    , PRIMARY KEY ("group_id", "indicator_id")
    , FOREIGN KEY ("group_id") REFERENCES elca.benchmark_groups ("id") ON DELETE CASCADE
    , FOREIGN KEY ("indicator_id") REFERENCES elca.indicators("id") ON DELETE CASCADE
);

CREATE TABLE elca.benchmark_group_thresholds
(
      "id"        serial NOT NULL
    , "group_id"  int    NOT NULL
    , "score"     int     NOT NULL
    , "caption"   text    NOT NULL
    , PRIMARY KEY ("id")
    , FOREIGN KEY ("group_id") REFERENCES elca.benchmark_groups ("id") ON DELETE CASCADE
);


-------------------------------------------------------------------------------
-- projects
-------------------------------------------------------------------------------

CREATE TABLE elca.project_phases
(
   "id"                     serial          NOT NULL                -- projectPhaseId
 , "name"                   varchar(200)    NOT NULL                -- name
 , "ident"                  varchar(100)                            -- internal short name
 , "constr_measure"         smallint        NOT NULL                -- construction measure
 , "step"                   int             NOT NULL DEFAULT 1      -- step
 , PRIMARY KEY ("id")
);

-------------------------------------------------------------------------------

CREATE TABLE elca.projects
(
   "id"                     serial          NOT NULL                -- projectId
 , "process_db_id"          int             NOT NULL                -- process data sets to base lca on
 , "current_variant_id"     int                                     -- the most recent project variant id
 , "access_group_id"        int             NOT NULL                -- the group id that is allowed accessing this project
 , "name"                   varchar(250)    NOT NULL                -- project title
 , "description"            text                                    -- description
 , "project_nr"             varchar(200)                            -- project number
 , "constr_measure"         smallint        NOT NULL DEFAULT 0      -- construction measure
 , "life_time"              int             NOT NULL                -- project life time
 , "constr_class_id"        int                                     -- construction class
 , "editor"                 varchar(250)                            -- editor name
 , "is_reference"           boolean         NOT NULL DEFAULT false  -- marks a reference project
 , "benchmark_version_id"   int                                     -- benchmark version to use
 , "password"               varchar(60)
 , "created"                timestamptz(0)  NOT NULL DEFAULT now()  -- creation time
 , "modified"               timestamptz(0)           DEFAULT now()  -- modification time
 , PRIMARY KEY ("id")
 , FOREIGN KEY ("process_db_id") REFERENCES elca.process_dbs ("id") ON UPDATE CASCADE ON DELETE CASCADE
 , FOREIGN KEY ("benchmark_version_id") REFERENCES elca.benchmark_versions ("id") ON UPDATE CASCADE ON DELETE SET NULL
 , FOREIGN KEY ("access_group_id") REFERENCES public.groups ("id") ON UPDATE CASCADE ON DELETE RESTRICT
 , FOREIGN KEY ("constr_class_id") REFERENCES elca.constr_classes ("id") ON UPDATE CASCADE ON DELETE SET NULL
);

-------------------------------------------------------------------------------

CREATE TABLE elca.project_attributes
(
    "id"                      serial          NOT NULL            -- projectAttributeId
  , "project_id"              int             NOT NULL            -- projectId
  , "ident"                   varchar(150)    NOT NULL            -- attribute identifier
  , "caption"                 varchar(150)    NOT NULL            -- attribute caption
  , "numeric_value"           numeric                             -- numeric value
  , "text_value"              text                                -- text value
  , PRIMARY KEY ("id")
  , UNIQUE ("project_id", "ident")
  , FOREIGN KEY ("project_id") REFERENCES elca.projects ("id") ON UPDATE CASCADE ON DELETE CASCADE
);

-------------------------------------------------------------------------------

CREATE TABLE elca.project_variants
(
   "id"                     serial          NOT NULL                -- projectVariantId
 , "project_id"             int             NOT NULL                -- project id
 , "phase_id"               int             NOT NULL                -- project phase id
 , "name"                   varchar(250)    NOT NULL                -- name
 , "description"            text                                    -- description
 , "created"                timestamptz(0)  NOT NULL DEFAULT now()  -- creation time
 , "modified"               timestamptz(0)           DEFAULT now()  -- modification time
 , PRIMARY KEY ("id")
 , FOREIGN KEY ("project_id") REFERENCES elca.projects ("id") ON UPDATE CASCADE ON DELETE CASCADE
 , FOREIGN KEY ("phase_id") REFERENCES elca.project_phases ("id") ON UPDATE CASCADE ON DELETE CASCADE
);

ALTER TABLE elca.projects ADD FOREIGN KEY ("current_variant_id") REFERENCES elca.project_variants ("id") ON UPDATE CASCADE ON DELETE SET NULL;

CREATE TABLE elca.project_variant_attributes
(
      "id"                      serial          NOT NULL            -- projectAttributeId
    , "project_variant_id"      int             NOT NULL            -- projectVariantId
    , "ident"                   varchar(150)    NOT NULL            -- attribute identifier
    , "caption"                 varchar(150)    NOT NULL            -- attribute caption
    , "numeric_value"           numeric                             -- numeric value
    , "text_value"              text                                -- text value
    , PRIMARY KEY ("id")
    , UNIQUE ("project_variant_id", "ident")
    , FOREIGN KEY ("project_variant_id") REFERENCES elca.project_variants ("id") ON UPDATE CASCADE ON DELETE CASCADE
);


-------------------------------------------------------------------------------

CREATE TABLE elca.project_locations
(
   "project_variant_id"    int             NOT NULL                -- projectVariantId
 , "street"                varchar(250)                            -- street
 , "postcode"              varchar(10)                             -- postcode
 , "city"                  varchar(250)                            -- city
 , "country"               varchar(250)                            -- country
 , "geo_location"          point                                   -- geo location
 , PRIMARY KEY ("project_variant_id")
 , FOREIGN KEY ("project_variant_id") REFERENCES elca.project_variants ("id") ON DELETE CASCADE ON UPDATE CASCADE
);

-------------------------------------------------------------------------------

CREATE TABLE elca.project_constructions
(
   "project_variant_id"      int             NOT NULL                -- projectVariantId

 , "constr_catalog_id"       int                                     -- default constr catalog id
 , "constr_design_id"        int                                     -- default constr design id

 , "is_extant_building"     boolean         NOT NULL DEFAULT false  -- indicates an extant building

  , "gross_floor_space"       numeric                                     -- Bruttogeschossfläche in m2
 , "net_floor_space"         numeric                                     -- Nettogrundfläche in m2
 , "floor_space"             numeric                                     -- Nutzfläche in m2
 , "property_size"           numeric                                     -- property size in m2

 , PRIMARY KEY ("project_variant_id")
 , FOREIGN KEY ("project_variant_id") REFERENCES elca.project_variants ("id") ON UPDATE CASCADE ON DELETE CASCADE
 , FOREIGN KEY ("constr_catalog_id") REFERENCES elca.constr_catalogs ("id") ON UPDATE CASCADE ON DELETE SET NULL
 , FOREIGN KEY ("constr_design_id") REFERENCES elca.constr_designs ("id") ON UPDATE CASCADE ON DELETE SET NULL
);

-------------------------------------------------------------------------------

CREATE TABLE elca.project_final_energy_demands
(
   "id"                      serial          NOT NULL                -- projectFinalEnergyDemandId
 , "project_variant_id"      int             NOT NULL                -- projectVariantId
 , "process_config_id"       int             NOT NULL                -- process config id
 , "ident"                   varchar(30)                             -- ident

 , "heating"                 numeric                                 -- heating in kWh/(m2*a)
 , "water"                   numeric                                 -- water in kWh/(m2*a)
 , "lighting"                numeric                                 -- lighting in kWh/(m2*a)
 , "ventilation"             numeric                                 -- ventilation in kWh/(m2*a)
 , "cooling"                 numeric                                 -- cooling in kWh/(m2*a)

 , PRIMARY KEY ("id")
 , FOREIGN KEY ("project_variant_id") REFERENCES elca.project_variants ("id") ON UPDATE CASCADE ON DELETE CASCADE
 , FOREIGN KEY ("process_config_id") REFERENCES elca.process_configs ("id") ON UPDATE CASCADE ON DELETE CASCADE
);

-------------------------------------------------------------------------------

CREATE TABLE elca.project_final_energy_supplies
(
    "id"                      serial          NOT NULL                -- projectFinalEnergyDemandId
  , "project_variant_id"      int             NOT NULL                -- projectVariantId
  , "process_config_id"       int             NOT NULL                -- process config id
  , "en_ev_ratio"             numeric         NOT NULL DEFAULT 1      -- ratio included in en ev
  , "quantity"                numeric         NOT NULL                -- total in kWh/a
  , "description"             text            NOT NULL                -- description

  , PRIMARY KEY ("id")
  , FOREIGN KEY ("project_variant_id") REFERENCES elca.project_variants ("id") ON UPDATE CASCADE ON DELETE CASCADE
  , FOREIGN KEY ("process_config_id") REFERENCES elca.process_configs ("id") ON UPDATE CASCADE ON DELETE CASCADE
);

-------------------------------------------------------------------------------

CREATE TABLE elca.project_final_energy_ref_models
(
      "id"                      serial          NOT NULL                -- projectFinalEnergyRefModelId
    , "project_variant_id"      int             NOT NULL                -- projectVariantId
    , "ident"                   varchar(30)     NOT NULL                -- ref model ident

    , "heating"                 numeric                                 -- heating in kWh/(m2*a)
    , "water"                   numeric                                 -- water in kWh/(m2*a)
    , "lighting"                numeric                                 -- lighting in kWh/(m2*a)
    , "ventilation"             numeric                                 -- ventilation in kWh/(m2*a)
    , "cooling"                 numeric                                 -- cooling in kWh/(m2*a)

    , PRIMARY KEY ("id")
    , UNIQUE ("project_variant_id", "ident")
    , FOREIGN KEY ("project_variant_id") REFERENCES elca.project_variants ("id") ON UPDATE CASCADE ON DELETE CASCADE
);

-------------------------------------------------------------------------------

CREATE TABLE elca.project_en_ev
(
    "project_variant_id"      int             NOT NULL                -- projectVariantId
  , "ngf"                     numeric         NOT NULL                      -- NGF EnEv
  , "version"                 int                                     -- EnEv Version

  , "unit_demand"             smallint        NOT NULL DEFAULT 0      -- unit for final energy demand
  , "unit_supply"             smallint        NOT NULL DEFAULT 0      -- unit for final energy supply
  , PRIMARY KEY ("project_variant_id")
  , FOREIGN KEY ("project_variant_id") REFERENCES elca.project_variants ("id") ON UPDATE CASCADE ON DELETE CASCADE
);

-------------------------------------------------------------------------------

CREATE TABLE elca.project_indicator_benchmarks
(
    "project_variant_id"      int             NOT NULL                -- projectVariantId
  , "indicator_id"            int             NOT NULL                -- indicatorId
  , "benchmark"               int             NOT NULL                -- benchmark
  , PRIMARY KEY ("project_variant_id", "indicator_id")
  , FOREIGN KEY ("project_variant_id") REFERENCES elca.project_variants ("id") ON UPDATE CASCADE ON DELETE CASCADE
  , FOREIGN KEY ("indicator_id") REFERENCES elca.indicators ("id") ON UPDATE CASCADE ON DELETE CASCADE
);

-------------------------------------------------------------------------------

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

-------------------------------------------------------------------------------

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

-------------------------------------------------------------------------------

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

CREATE TABLE elca.project_access_tokens
(
      "token"                  uuid            NOT NULL                -- projectAccessToken
    , "project_id"             int             NOT NULL                -- projectId
    , "user_id"                int                                     -- userId of user which gets privileges
    , "user_email"             varchar(200)    NOT NULL                -- user email address
    , "can_edit"               boolean         NOT NULL DEFAULT false  -- privilege to edit
    , "is_confirmed"           boolean         NOT NULL DEFAULT false  -- confirmed state
    , "created"                timestamptz(0)  NOT NULL DEFAULT now()  -- creation time
    , "modified"               timestamptz(0)           DEFAULT now()  -- modification time
    , PRIMARY KEY ("token")
    , UNIQUE ("project_id", "user_id")
    , FOREIGN KEY ("project_id") REFERENCES elca.projects ("id") ON DELETE CASCADE
    , FOREIGN KEY ("user_id") REFERENCES public.users ("id")    ON DELETE CASCADE
);

-------------------------------------------------------------------------------
-- elements
-------------------------------------------------------------------------------

CREATE TABLE elca.element_types
(
   "node_id"                    int          NOT NULL                -- elementTypeId
 , "name"                       varchar(200) NOT NULL                -- name
 , "description"                text                                 -- description
 , "din_code"                   int                                  -- din code
 , "is_constructional"          boolean                              -- indicates constructional types
 , "is_opaque"                  boolean                              -- indicates opaque types
 , "pref_ref_unit"              varchar(10)                          -- preferred refUnit
 , "pref_inclination"           int                                  -- preferred inclination
 , "pref_has_element_image"     boolean     NOT NULL DEFAULT false   -- has element image
 , PRIMARY KEY ("node_id")
 , FOREIGN KEY ("node_id") REFERENCES public.nested_nodes ("id") ON UPDATE CASCADE ON DELETE CASCADE
);

-------------------------------------------------------------------------------

CREATE TABLE elca.elements
(
   "id"                     serial          NOT NULL                -- elementId
 , "element_type_node_id"   int             NOT NULL                -- element type node id
 , "name"                   varchar(250)    NOT NULL                -- element name
 , "description"            text                                    -- description
 , "is_reference"           boolean         NOT NULL DEFAULT false  -- indicates a reference element
 , "is_public"              boolean         NOT NULL DEFAULT false  -- indicates a public element
 , "access_group_id"        int                                     -- access group id
 , "project_variant_id"     int                                     -- project variant id
 , "quantity"               numeric                                 -- quantity
 , "ref_unit"               varchar(10)                             -- reference unit of measure
 , "copy_of_element_id"     int                                     -- is a copy of element with id
 , "owner_id"               int                                     -- owner id of this element
 , "is_composite"           boolean         NOT NULL DEFAULT false  -- indicates a composite element
 , "uuid"                   uuid            NOT NULL DEFAULT uuid_generate_v4() -- uuid of the element
 , "created"                timestamptz(0)  NOT NULL DEFAULT now()  -- creation time
 , "modified"               timestamptz(0)           DEFAULT now()  -- modification time
 , PRIMARY KEY ("id")
 , UNIQUE ("uuid")
 , FOREIGN KEY ("element_type_node_id") REFERENCES elca.element_types ("node_id") ON UPDATE CASCADE ON DELETE CASCADE
 , FOREIGN KEY ("access_group_id") REFERENCES public.groups ("id") ON UPDATE CASCADE ON DELETE SET NULL
 , FOREIGN KEY ("project_variant_id") REFERENCES elca.project_variants ("id") ON UPDATE CASCADE ON DELETE CASCADE
 , FOREIGN KEY ("copy_of_element_id") REFERENCES elca.elements ("id") ON UPDATE CASCADE ON DELETE SET NULL
 , FOREIGN KEY ("owner_id") REFERENCES public.users ("id") ON UPDATE CASCADE ON DELETE SET NULL
);

CREATE INDEX IX_elca_elements_element_type_node_id_project_variant_id ON elca.elements (element_type_node_id, project_variant_id);

-------------------------------------------------------------------------------

CREATE TABLE elca.composite_elements
(
   "composite_element_id"           int          NOT NULL                -- compositeElementId
 , "position"                       int          NOT NULL                -- element position within composite
 , "element_id"                     int          NOT NULL                -- element
 , PRIMARY KEY ("composite_element_id", "position")
 , FOREIGN KEY ("composite_element_id") REFERENCES elca.elements ON UPDATE CASCADE ON DELETE CASCADE
 , FOREIGN KEY ("element_id") REFERENCES elca.elements ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE INDEX IX_elca_composite_elements_composite_element_id_element_id ON elca.composite_elements ("composite_element_id", "element_id");

-------------------------------------------------------------------------------

CREATE TABLE elca.element_attributes
(
   "id"                      serial          NOT NULL            -- elementAttributeId
 , "element_id"              int             NOT NULL            -- elementId
 , "ident"                   varchar(150)    NOT NULL            -- attribute identifier
 , "caption"                 varchar(150)    NOT NULL            -- attribute caption
 , "numeric_value"           numeric                             -- numeric value
 , "text_value"              text                                -- text value
 , PRIMARY KEY ("id")
 , UNIQUE ("element_id", "ident")
 , FOREIGN KEY ("element_id") REFERENCES elca.elements ("id") ON UPDATE CASCADE ON DELETE CASCADE
);

-------------------------------------------------------------------------------

CREATE TABLE elca.element_component_attributes
(
      "id"                      serial          NOT NULL            -- elementComponentAttributeId
    , "element_component_id"    int             NOT NULL            -- processConfigId
    , "ident"                   varchar(150)    NOT NULL            -- attribute identifier
    , "numeric_value"           numeric                             -- numeric value
    , "text_value"              text                                -- text value
    , PRIMARY KEY ("id")
    , UNIQUE ("element_component_id", "ident")
    , FOREIGN KEY ("element_component_id") REFERENCES elca.element_components ("id") ON UPDATE CASCADE ON DELETE CASCADE
);

-------------------------------------------------------------------------------

CREATE TABLE elca.element_components
(
   "id"                     serial          NOT NULL                -- elementComponentId
 , "element_id"             int             NOT NULL                -- associated element id
 , "process_config_id"      int             NOT NULL                -- process config id
 , "quantity"               numeric         NOT NULL DEFAULT 1      -- quantity
 , "process_conversion_id"  int             NOT NULL                -- process conversion id
 , "life_time"              int             NOT NULL                -- life time
 , "life_time_delay"        int             NOT NULL DEFAULT 0      -- life time delay
 , "life_time_info"         varchar(200)                            -- life time info
 , "calc_lca"               boolean         NOT NULL DEFAULT true   -- indicates if the lca for this component should be calculated
 , "is_extant"              boolean         NOT NULL DEFAULT false  -- indicates if the component is pre-existing in extant buildings
 , "is_layer"               boolean         NOT NULL                -- indicates if this component is a layer
 , "layer_position"         int                                     -- position of layer
 , "layer_size"             numeric                                 -- size of layer in [m]
 , "layer_sibling_id"       int                                     -- references another component as sibling within the same layer
 , "layer_length"           numeric                  DEFAULT 1      -- length of layer in [m]
 , "layer_width"            numeric                  DEFAULT 1      -- width of layer in [m]
 , "layer_area_ratio"       numeric                  DEFAULT 1      -- proportion of area (only valid with sibling)
 , "created"                timestamptz(0)  NOT NULL DEFAULT now()  -- creation time
 , "modified"               timestamptz(0)           DEFAULT now()  -- modification time
 , PRIMARY KEY ("id")
 , FOREIGN KEY ("element_id") REFERENCES elca.elements ("id") ON UPDATE CASCADE ON DELETE CASCADE
 , FOREIGN KEY ("process_config_id") REFERENCES elca.process_configs ("id") ON UPDATE CASCADE ON DELETE CASCADE
 , FOREIGN KEY ("process_conversion_id") REFERENCES elca.process_conversions ("id") ON UPDATE CASCADE ON DELETE RESTRICT
 , FOREIGN KEY ("layer_sibling_id") REFERENCES elca.element_components ("id") ON UPDATE CASCADE ON DELETE SET NULL
);

CREATE INDEX IX_elca_element_components_element_id_is_layer ON elca.element_components (element_id, is_layer);

-------------------------------------------------------------------------------

CREATE TABLE elca.element_constr_catalogs
(
   "element_id"             int             NOT NULL                -- elementId
 , "constr_catalog_id"      int             NOT NULL                -- constrCatalogId
 , PRIMARY KEY ("element_id", "constr_catalog_id")
 , FOREIGN KEY ("element_id") REFERENCES elca.elements ("id") ON UPDATE CASCADE ON DELETE CASCADE
 , FOREIGN KEY ("constr_catalog_id") REFERENCES elca.constr_catalogs ("id") ON UPDATE CASCADE ON DELETE CASCADE
);

-------------------------------------------------------------------------------

CREATE TABLE elca.element_constr_designs
(
   "element_id"             int             NOT NULL                -- elementId
 , "constr_design_id"       int             NOT NULL                -- constrDesignId
 , PRIMARY KEY ("element_id", "constr_design_id")
 , FOREIGN KEY ("element_id") REFERENCES elca.elements ("id") ON UPDATE CASCADE ON DELETE CASCADE
 , FOREIGN KEY ("constr_design_id") REFERENCES elca.constr_designs ("id") ON UPDATE CASCADE ON DELETE CASCADE
);

-------------------------------------------------------------------------------

CREATE TABLE elca.process_category_svg_patterns
(
    "process_category_node_id"    int             NOT NULL                -- processCategoryId
  , "svg_pattern_id"              int             NOT NULL                -- svgPatternId
  , PRIMARY KEY ("process_category_node_id", "svg_pattern_id")
  , FOREIGN KEY ("process_category_node_id") REFERENCES elca.process_categories ("node_id") ON UPDATE CASCADE ON DELETE CASCADE
  , FOREIGN KEY ("svg_pattern_id") REFERENCES elca.svg_patterns ("id") ON UPDATE CASCADE ON DELETE CASCADE
);

-------------------------------------------------------------------------------

CREATE TABLE elca.settings
(
   "id"                      serial          NOT NULL            -- settingId
 , "section"                 varchar(250)    NOT NULL            -- section name
 , "ident"                   varchar(250)    NOT NULL            -- setting identifier
 , "caption"                 varchar(250)                        -- caption
 , "numeric_value"           numeric                             -- numeric value
 , "text_value"              text                                -- text value
 , "p_order"                 int                                 -- presentation order
 , PRIMARY KEY ("id")
 , UNIQUE ("section", "ident")
);

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



-------------------------------------------------------------------------------
COMMIT;
