BEGIN;
CREATE SCHEMA nawoh;

CREATE TABLE nawoh.water
(
      "project_id"              int             NOT NULL

    , "mit_badewanne"           boolean     NOT NULL
    , "toilette_voll"           numeric
    , "toilette_spartaste"      numeric
    , "dusche"                  numeric
    , "badewanne_gesamt"        numeric
    , "wasserhaehne_bad"        numeric
    , "wasserhaehne_kueche"     numeric
    , "waschmaschine"                  numeric  DEFAULT 40
    , "geschirrspueler"               numeric  DEFAULT 15

    , PRIMARY KEY ("project_id")
    , FOREIGN KEY ("project_id") REFERENCES elca.projects ("id") ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE TABLE nawoh.water_versions
(
      "id"                      serial      NOT NULL
    , "name"                    varchar(255) NOT NULL
    , "mit_badewanne"           boolean     NOT NULL

    , "toilette_voll"           numeric     NOT NULL
    , "toilette_spartaste"      numeric     NOT NULL
    , "dusche"                  numeric     NOT NULL
    , "badewanne_gesamt"        numeric
    , "wasserhaehne_bad"        numeric     NOT NULL
    , "wasserhaehne_kueche"     numeric     NOT NULL
    , "waschmaschine"           numeric     NOT NULL
    , "geschirrspueler"         numeric     NOT NULL

    , PRIMARY KEY ("id")
    , CHECK (mit_badewanne AND badewanne_gesamt IS NOT NULL OR NOT mit_badewanne AND badewanne_gesamt IS NULL)
);


COMMIT;