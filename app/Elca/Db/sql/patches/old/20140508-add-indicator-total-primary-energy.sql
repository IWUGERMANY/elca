BEGIN;
SELECT public.register_patch('add-indicator-total-primary-energy', 'elca');

INSERT INTO elca.indicators (id, name, ident, unit, is_excluded, p_order, description, uuid, is_en15804_compliant)
      VALUES (34, 'PE Ges.', 'pet', 'MJ', false, 0, 'Gesamteinsatz Primärenergie', null, true);

UPDATE elca.indicators
   SET p_order = 10
 WHERE ident = 'peNEm';

UPDATE elca.indicators
   SET p_order = 15
 WHERE ident = 'peEm';

COMMIT;