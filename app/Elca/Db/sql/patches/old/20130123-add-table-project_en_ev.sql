BEGIN;
SELECT public.register_patch('add-table-project_en_ev', 'elca');

CREATE TABLE elca.project_en_ev
(
    "project_variant_id"      int             NOT NULL                -- projectVariantId
  , "ngf"                     numeric         NOT NULL                      -- NGF EnEv
  , "version"                 int                                     -- EnEv Version
  , PRIMARY KEY ("project_variant_id")
  , FOREIGN KEY ("project_variant_id") REFERENCES elca.project_variants ("id") ON UPDATE CASCADE ON DELETE CASCADE
);

COMMIT;
