BEGIN;
SELECT public.register_patch('add-water-idents', 'lcc');

UPDATE lcc.costs SET ident = 'TAP_WATER' WHERE label = 'Wasser (Wert aus Kriterium 1.2.3)';
UPDATE lcc.costs SET ident = 'WASTE_WATER' WHERE label = 'Abwasser - Schmutzwasser (Wert aus Kriterium 1.2.3)';
UPDATE lcc.costs SET ident = 'RAIN_WATER' WHERE label = 'Abwasser - Niederschlag  (Wert aus Kriterium 1.2.3)';

COMMIT;