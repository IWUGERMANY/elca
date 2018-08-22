BEGIN;
SELECT public.register_patch('add-elca-settings', 'elca');

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

COMMIT;
