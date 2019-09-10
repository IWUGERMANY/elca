BEGIN;
SELECT public.register_patch('20190909-add-new-fields-process-config.sql', 'eLCA');

ALTER TABLE "process_configs" ADD "waste_code" integer NULL;
ALTER TABLE "process_configs" ADD "waste_code_suffix" integer NULL;
ALTER TABLE "process_configs" ADD "lambda_value" numeric NULL;
ALTER TABLE "process_configs" ADD "element_group_a" boolean NULL;
ALTER TABLE "process_configs" ADD "element_group_b" boolean NULL;

COMMIT;
