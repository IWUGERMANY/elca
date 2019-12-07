BEGIN;
SELECT public.register_patch('20191202-add-new-fields-users-mailreminder.sql', 'public');

ALTER TABLE "users" ADD "deactivated" timestamptz(0) NULL;
ALTER TABLE "users" ADD "deactivatedmail" integer NULL;

COMMIT;
