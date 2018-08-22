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
-------------------------------------------------------------------------------

DROP VIEW IF EXISTS soda4lca.databases_v;
CREATE VIEW soda4lca.databases_v AS
  SELECT d.*
       , i.id AS import_id
       , i.status
       , i.date_of_import
       , i.data_stock
    FROM elca.process_dbs d
    JOIN soda4lca.imports i ON d.id = i.process_db_id;

-------------------------------------------------------------------------------

DROP VIEW IF EXISTS soda4lca.processes_v;
CREATE VIEW soda4lca.processes_v AS
       SELECT DISTINCT p.import_id
            , p.version
            , p.latest_version
            , p.status
            , p.error_code
            , p.uuid
            , p.details
            , COALESCE(c.ref_num ||' '|| a.name_orig, p.class_id||' '||p.name) AS name
            , CASE WHEN count(DISTINCT a.life_cycle_name) > 0 THEN array_to_string(array_agg(DISTINCT a.life_cycle_name), ', ')
                   ELSE p.epd_modules
              END AS modules
           ,  replace(array_to_string(array_agg(DISTINCT a.epd_type), ', '), ' dataset', '') AS epd_types
         FROM soda4lca.processes         p
    LEFT JOIN elca.process_assignments_v a ON a.uuid = p.uuid
    LEFT JOIN elca.process_categories    c ON c.node_id = a.process_category_node_id
     GROUP BY p.import_id
            , c.ref_num
            , p.name
            , p.uuid
            , p.class_id
            , p.epd_modules
            , a.name_orig
            , p.version
            , p.status
            , p.error_code
            , p.details;

DROP VIEW IF EXISTS soda4lca.processes_with_process_configs_v;
CREATE VIEW soda4lca.processes_with_process_configs_v AS
       SELECT DISTINCT p.import_id
            , p.version
            , p.latest_version
            , p.status
            , p.error_code
            , p.uuid
            , p.details
            , COALESCE(c.ref_num ||' '|| a.name_orig, p.class_id||' '||p.name) AS name
            , array_to_string(array_agg(DISTINCT '"'||pc.name||'"'), ', ') AS process_configs
            , CASE WHEN count(DISTINCT a.life_cycle_name) > 0 THEN array_to_string(array_agg(DISTINCT a.life_cycle_name), ', ')
                   ELSE p.epd_modules
              END AS modules
           ,  replace(array_to_string(array_agg(DISTINCT a.epd_type), ', '), ' dataset', '') AS epd_types
       FROM soda4lca.processes         p
    LEFT JOIN elca.process_assignments_v a ON a.uuid = p.uuid
    LEFT JOIN elca.process_categories    c ON c.node_id = a.process_category_node_id
    LEFT JOIN elca.process_configs      pc ON pc.id = a.process_config_id
     GROUP BY p.import_id
            , c.ref_num
            , p.name
            , p.uuid
            , p.class_id
            , p.epd_modules
            , a.name_orig
            , p.version
            , p.status
            , p.error_code
            , p.details;

-------------------------------------------------------------------------------
COMMIT;
