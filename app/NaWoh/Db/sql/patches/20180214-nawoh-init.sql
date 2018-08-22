BEGIN;
SELECT
    public.register_patch('20180214-nawoh-init.sql', 'nawoh');

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

INSERT INTO nawoh.water_versions (id, name, mit_badewanne, toilette_voll, toilette_spartaste, dusche, badewanne_gesamt, wasserhaehne_bad, wasserhaehne_kueche, waschmaschine, geschirrspueler)
    VALUES (DEFAULT, 'Impact Assessment COM(2007) ohne Badewanne', false, 1.5, 2.75, 3.5, null, 0.8, 0.86, 0.225, 0.4),
           (DEFAULT, 'Impact Assessment COM(2007) mit Badewanne', true,  1.5, 2.75, 2.125, 0.175, 0.8, 0.86, 0.225, 0.4)
    ;

COMMIT;