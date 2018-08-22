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
SET search_path = elca, public;

BEGIN;
-------------------------------------------------------------------------------
-- common objects
-------------------------------------------------------------------------------
-- indicators

CREATE OR REPLACE VIEW elca.benchmark_thresholds_v AS
  SELECT t.*
       , i.ident AS indicator_ident
    FROM elca.benchmark_thresholds t
    JOIN elca.indicators           i ON i.id = t.indicator_id;


CREATE OR REPLACE VIEW elca.project_transport_means_v AS
  SELECT m.*
       , p.name AS process_config_name
    FROM elca.project_transport_means m
    JOIN elca.process_configs         p ON p.id = m.process_config_id;


COMMIT;