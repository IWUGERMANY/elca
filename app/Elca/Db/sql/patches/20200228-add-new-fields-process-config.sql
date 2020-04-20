BEGIN;
SELECT public.register_patch('20200228-add-new-fields-process-config.sql', 'eLCA');

SET search_path = elca;
ALTER TABLE "process_configs" ADD "element_district_heating" boolean NULL;
ALTER TABLE "process_configs" ADD "element_refrigerant" boolean NULL;
ALTER TABLE "process_configs" ADD "element_flammable" boolean NULL;

COMMIT;
