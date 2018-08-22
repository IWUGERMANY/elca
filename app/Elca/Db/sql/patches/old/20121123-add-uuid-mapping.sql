----------------------------------------------------------------------------------------
-- This file is part of the eLCA project
--
-- eLCA
--
-- Copyright (c) 2012 Patrick Kocurek <patrick@kocurek.de>
--               BEIBOB Medienfreunde GbR - http://beibob.de/
-- Licensed under Creative Commons license CC BY-NC 3.0
-- http://creativecommons.org/licenses/by-nc/3.0/de/
----------------------------------------------------------------------------------------
SET client_encoding = 'UTF8';


SET search_path = elca, public;

BEGIN;
SELECT public.register_patch('add-elca-uuid-mapping', 'elca');

CREATE TABLE elca.uuid_mappings
(
   "id"                     serial          NOT NULL                -- processId
 , "new_uuid"            uuid            NOT NULL                -- source uuid
 , "orig_uuid"              uuid            NOT NULL                -- source uuid mapped to
 );

COMMIT;