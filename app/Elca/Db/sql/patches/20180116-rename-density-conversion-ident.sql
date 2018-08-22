BEGIN;
SELECT
    public.register_patch('20180116-rename-density-conversion-ident.sql', 'eLCA');

UPDATE elca.process_conversions 
SET ident = 'GROSS_DENSITY'
WHERE ident = 'DENSITY';

COMMIT;