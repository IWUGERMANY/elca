BEGIN;
SELECT public.register_patch('alter-process_config_variants', 'elca');

ALTER TABLE elca.process_config_variants ADD "is_vendor_specific"     boolean         NOT NULL DEFAULT false;
ALTER TABLE elca.process_config_variants ADD "specific_process_config_id" int;
ALTER TABLE elca.process_config_variants ADD FOREIGN KEY ("specific_process_config_id") REFERENCES elca.process_configs ("id") ON UPDATE CASCADE ON DELETE SET NULL;

COMMIT;
