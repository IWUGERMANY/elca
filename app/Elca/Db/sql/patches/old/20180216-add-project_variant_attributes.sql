BEGIN;
SELECT
    public.register_patch('20180216-add-project_variant_attributes.sql', 'eLCA');

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

COMMIT;