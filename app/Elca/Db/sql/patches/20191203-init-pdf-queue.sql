BEGIN;
SELECT public.register_patch('20191203-init-pdf-queue', 'elca');

CREATE SEQUENCE queue_id_seq INCREMENT 1 MINVALUE 1 MAXVALUE 9223372036854775807 START 1 CACHE 1;

CREATE TABLE elca.reports_pdf_queue
(
      "pdf_queue_id"  		  int DEFAULT nextval('queue_id_seq') NOT NULL	  -- pdfQueueId
	, "user_id"           	  int	   		 NOT NULL                 -- nutzert id
    , "projects_id"           int	   		 NOT NULL                 -- projekt id
    , "projects_name"     	  varchar(250)   NOT NULL                 -- project name
	, "projects_filename"     varchar(250)   NOT NULL                 -- project file name (with date)
	, "current_variant_id" 	  int									  -- variant id	
	, "pdf_cmd"				  text 									  -- cmd to create pdf		
	, "created" 			  timestamptz(0) DEFAULT now() NOT NULL   -- create 
    , "ready"                 timestamptz(0) DEFAULT NULL	          -- datetime ready to download status 
	, "key"					  varchar(50)   NOT NULL				  -- identifier random
    , PRIMARY KEY ("pdf_queue_id")
	, UNIQUE ("key")
);


COMMIT;