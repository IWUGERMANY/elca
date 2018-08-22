----------------------------------------------------------------------------------------
-- Stlb structure
----------------------------------------------------------------------------------------
SET client_encoding = 'UTF8';

BEGIN;
CREATE SCHEMA stlb;
COMMIT;

SET search_path = stlb, public;

BEGIN;
-------------------------------------------------------------------------------
-- common objects
-------------------------------------------------------------------------------
-- indicators

CREATE TABLE stlb.elements
(
   "id"                 serial                                  -- indicatorId
 , "project_id"    	    integer         NOT NULL      	        -- elca.projects.id
 , "din_code"     		integer         NOT NULL        	    -- DIN276-1_08
 , "name"               text            NOT NULL                -- kurztext
 , "description"        text     	    NOT NULL                -- langtext
 , "quantity"           numeric		    NOT NULL                -- unit of measure
 , "ref_unit" 		    varchar(20)     NOT NULL 			    -- me in import file
 , "oz"                 varchar(150)    NOT NULL                -- the oz
 , "lb_nr"          	varchar         NOT NULL                -- LB-NR
 , "price_per_unit"     numeric                                 -- Einheitspreis
 , "price"          	numeric                                 -- Gesamtbetrag
 , "is_visible"        	boolean        NOT NULL DEFAULT true    -- display element?
 , "created"          	timestamptz(0) NOT NULL DEFAULT NOW()   -- Creation Time
 , PRIMARY KEY ("id")
 , FOREIGN KEY ("project_id") REFERENCES elca.projects ("id") ON UPDATE CASCADE ON DELETE CASCADE
);


-------------------------------------------------------------------------------
COMMIT;