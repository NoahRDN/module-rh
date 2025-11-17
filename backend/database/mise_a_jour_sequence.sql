DO $$
DECLARE
    max_id INTEGER;
    seq_name TEXT;
    tbl RECORD;
BEGIN
    FOR tbl IN
        SELECT table_name, column_name
        FROM information_schema.columns
        WHERE table_schema = 'public'
          AND column_default LIKE 'nextval%'
    LOOP
        BEGIN
            -- Récupérer le MAX(id) + 1
            EXECUTE format(
                'SELECT COALESCE(MAX(%I), 0) + 1 FROM %I',
                tbl.column_name, tbl.table_name
            ) INTO max_id;

            -- Récupérer le nom de la séquence
            SELECT regexp_replace(column_default, 'nextval\(''(.*)''::regclass\)', '\1')
            INTO seq_name
            FROM information_schema.columns
            WHERE table_schema = 'public'
              AND table_name = tbl.table_name
              AND column_name = tbl.column_name;

            -- Mettre à jour la séquence
            IF seq_name IS NOT NULL AND max_id IS NOT NULL THEN
                EXECUTE format('ALTER SEQUENCE %I RESTART WITH %s', seq_name, max_id);
                RAISE NOTICE 'Séquence % mise à jour à %', seq_name, max_id;
            END IF;

        EXCEPTION WHEN OTHERS THEN
            RAISE NOTICE 'Erreur pour %.% : %', tbl.table_name, tbl.column_name, SQLERRM;
        END;
    END LOOP;
END $$;
