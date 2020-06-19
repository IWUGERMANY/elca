BEGIN;
SELECT public.register_patch('20200507-init-ifc-project.sql', 'elca');

CREATE SEQUENCE ifc_id_seq INCREMENT 1 MINVALUE 1 MAXVALUE 9223372036854775807 START 1 CACHE 1;

CREATE TABLE elca.ifc_project
(
      "ifc_project_id" 		  int DEFAULT nextval('ifc_id_seq') NOT NULL	  -- ifcProjectId
	, "projects_id"           int	   		 NOT NULL                 -- projeid
    , "created" 			  timestamptz(0) DEFAULT now() NOT NULL   -- create 
    , PRIMARY KEY ("ifc_project_id")
	
);


COMMIT;