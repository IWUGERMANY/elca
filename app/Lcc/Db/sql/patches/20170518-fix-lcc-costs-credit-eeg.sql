BEGIN;
SELECT
    public.register_patch('20170518-fix-lcc-costs-credit-eeg.sql', 'eLCA');

UPDATE lcc.costs
SET ident = 'CREDIT_EEG'
WHERE
    project_id IS NULL 
AND label = 'Eigennutzung nach Ende der Einspeisevergütung (Energiemenge)'
AND ident IS NULL;

COMMIT;