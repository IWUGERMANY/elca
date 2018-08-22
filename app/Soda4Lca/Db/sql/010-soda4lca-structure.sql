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
CREATE SCHEMA soda4lca;
COMMIT;

SET search_path = elca, public;

BEGIN;
-------------------------------------------------------------------------------

CREATE TABLE soda4lca.imports
(
   "id"                     serial          NOT NULL                -- importId
 , "process_db_id"          int             NOT NULL                -- processDbId
 , "data_stock"             varchar(250)                            -- dataStock
 , "status"                 varchar(20)     NOT NULL                -- import status
 , "date_of_import"         timestamptz(0)                          -- dateOfImport
 , PRIMARY KEY ("id")
 , FOREIGN KEY ("process_db_id") REFERENCES elca.process_dbs ("id") ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE INDEX IX_soda4lca_import_status ON soda4lca.imports ("status");
CREATE INDEX IX_soda4lca_imports_process_db_id ON soda4lca.imports (process_db_id);

-------------------------------------------------------------------------------

CREATE TABLE soda4lca.processes
(
   "import_id"              int             NOT NULL                -- import id
 , "uuid"                   uuid            NOT NULL                -- process uuid
 , "version"                varchar(50)     NOT NULL                -- process version
 , "name"                   varchar(250)    NOT NULL                -- name
 , "class_id"               varchar(50)     NOT NULL                -- classification
 , "epd_modules"            text                                    -- epdModules
 , "status"                 varchar(20)     NOT NULL                -- import status 
 , "error_code"             int                                     -- errorCode
 , "details"                text                                    -- detail info
 , "created"                timestamptz(0)  NOT NULL DEFAULT now()  -- creation time
 , PRIMARY KEY ("import_id", "uuid")
 , FOREIGN KEY ("import_id") REFERENCES soda4lca.imports ("id") ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE INDEX IX_soda4lca_processes_status ON soda4lca.processes ("status");

-------------------------------------------------------------------------------
COMMIT;
