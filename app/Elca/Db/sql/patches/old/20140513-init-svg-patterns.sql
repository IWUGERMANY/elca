BEGIN;
SELECT public.register_patch('init-svg_patterns', 'elca');

-- patch from blibs
ALTER TABLE public.media ADD COLUMN "extension" varchar(20);
ALTER TABLE public.media ADD COLUMN "source_media_id" integer;
ALTER TABLE public.media ADD FOREIGN KEY ("source_media_id") REFERENCES public.media("id") ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE public.media ADD COLUMN "ident" varchar(100);
ALTER TABLE public.media ADD UNIQUE ("source_media_id", "ident");

-- modify existing table svg patterns

ALTER TABLE elca.svg_patterns ADD "image_id" int;
ALTER TABLE elca.svg_patterns ADD FOREIGN KEY ("image_id") REFERENCES public.media ("id") ON UPDATE CASCADE ON DELETE SET NULL;

-- modify process configs
ALTER TABLE elca.process_configs ADD "svg_pattern_id" int;
ALTER TABLE elca.process_configs ADD FOREIGN KEY ("svg_pattern_id") REFERENCES elca.svg_patterns ("id") ON UPDATE CASCADE ON DELETE SET NULL;

-- modify views
DROP VIEW IF EXISTS elca.process_configs_extended_search_v;
CREATE VIEW elca.process_configs_extended_search_v AS
  SELECT pc.id
    , pc.process_category_node_id
    , pc.name
    , pc.life_time_info
    , pc.min_life_time
    , pc.min_life_time_info
    , pc.avg_life_time
    , pc.avg_life_time_info
    , pc.max_life_time
    , pc.max_life_time_info
    , pc.density
    , pc.thermal_conductivity
    , pc.thermal_resistance
    , pc.is_reference
    , pc.f_hs_hi
    , pc.uuid
    , pc.svg_pattern_id
    , pc.created
    , pc.modified
    , to_tsvector('german', pc.name ||' '||
                            coalesce(array_to_string(array_agg(DISTINCT p.uuid::text), ' '), '') ||' '||
                            coalesce(array_to_string(array_agg(DISTINCT p.name_orig), ' '), '')) AS search_vector
  FROM elca.process_configs pc
    LEFT JOIN elca.process_assignments_v p ON pc.id = p.process_config_id
  GROUP BY pc.id
    , pc.process_category_node_id
    , pc.name
    , pc.life_time_info
    , pc.min_life_time
    , pc.min_life_time_info
    , pc.avg_life_time
    , pc.avg_life_time_info
    , pc.max_life_time
    , pc.max_life_time_info
    , pc.density
    , pc.thermal_conductivity
    , pc.thermal_resistance
    , pc.is_reference
    , pc.f_hs_hi
    , pc.uuid
    , pc.created
    , pc.modified;


COMMIT;

