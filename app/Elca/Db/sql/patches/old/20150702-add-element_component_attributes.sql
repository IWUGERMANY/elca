BEGIN;
SELECT public.register_patch('add-element_component_attributes', 'elca');
CREATE TABLE elca.element_component_attributes
(
      "id"                      serial          NOT NULL            -- elementComponentAttributeId
    , "element_component_id"    int             NOT NULL            -- processConfigId
    , "ident"                   varchar(150)    NOT NULL            -- attribute identifier
    , "numeric_value"           numeric                             -- numeric value
    , "text_value"              text                                -- text value
    , PRIMARY KEY ("id")
    , UNIQUE ("element_component_id", "ident")
    , FOREIGN KEY ("element_component_id") REFERENCES elca.element_components ("id") ON UPDATE CASCADE ON DELETE CASCADE
);
COMMIT;