BEGIN;
SELECT public.register_patch('20190127-add-project_attribute-elca_pw_date.sql', 'eLCA');

INSERT INTO elca.project_attributes (project_id, ident, caption, text_value)
SELECT p.id,
    'elca.pw.date',
    'Passwort gültig seit',
    '2019-02-01'
FROM elca.projects p
         LEFT JOIN elca.project_attributes a ON p.id = a.project_id AND a.ident = 'elca.pw.date'
WHERE password IS NOT NULL
    AND a.id IS NULL;

COMMIT;