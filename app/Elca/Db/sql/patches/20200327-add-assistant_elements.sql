BEGIN;
SELECT public.register_patch('20200327-add-assistant_elements.sql', 'eLCA');

CREATE TABLE elca.assistant_elements (
      "id"                     serial          NOT NULL                -- assistantElementId
    , "main_element_id"        int             NOT NULL                -- mainElementId
    , "project_variant_id"     int                                     -- project variant id
    , "assistant_ident"        varchar(200)    NOT NULL                -- assistantIdent
    , "config"                 text                                    -- configuration
    , "is_reference"           boolean         NOT NULL DEFAULT false  -- indicates a reference element
    , "is_public"              boolean         NOT NULL DEFAULT false  -- indicates a public element
    , "uuid"                   uuid            NOT NULL DEFAULT uuid_generate_v4() -- uuid of the element
    , "owner_id"               int                                     -- owner id of this element
    , "access_group_id"        int                                     -- access group id
    , "created"                timestamptz(0)  NOT NULL DEFAULT now()  -- creation time
    , "modified"               timestamptz(0)           DEFAULT now()  -- modification time
    , PRIMARY KEY ("id")
    , UNIQUE ("uuid")
    , FOREIGN KEY ("main_element_id") REFERENCES elca.elements ("id") ON UPDATE CASCADE ON DELETE CASCADE
    , FOREIGN KEY ("project_variant_id") REFERENCES elca.project_variants ("id") ON UPDATE CASCADE ON DELETE CASCADE
    , FOREIGN KEY ("owner_id") REFERENCES public.users ("id") ON UPDATE CASCADE ON DELETE SET NULL
    , FOREIGN KEY ("access_group_id") REFERENCES public.groups ("id") ON UPDATE CASCADE ON DELETE SET NULL
);

CREATE TABLE elca.assistant_sub_elements (
      "element_id"            int                 NOT NULL
    , "assistant_element_id"  int                 NOT NULL
    , "ident"                 varchar(200)        NOT NULL
    , PRIMARY KEY ("element_id", "assistant_element_id")
    , FOREIGN KEY ("assistant_element_id") REFERENCES elca.assistant_elements ("id") ON UPDATE CASCADE ON DELETE CASCADE
    , FOREIGN KEY ("element_id") REFERENCES elca.elements ("id") ON UPDATE CASCADE ON DELETE CASCADE
);

COMMIT;
