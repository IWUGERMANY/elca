BEGIN;
SELECT public.register_patch('add-project_attributes', 'elca');

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

COMMIT;
