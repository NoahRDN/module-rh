-- Grand jeu de données de démo, version SQL PostgreSQL.
-- Équivalent pratique de LargeDashboardDatasetSeeder pour les tables sources.
--
-- Usage Docker :
--   docker compose exec -T postgres psql -U rh -d rh < backend/database/sql/seed_large_dataset.sql
--
-- Après import, rafraîchir les read models Laravel :
--   docker compose exec -T backend php artisan read-models:refresh --annee=2026
--
-- Notes :
-- - Script idempotent pour les données préfixées LOAD-20260501-*.
-- - Nécessite que les données de base existent déjà : departements, postes, types_conges.
-- - Ne génère pas directement dashboard_stats / paie_synthese / caisse_synthese :
--   ces tables doivent rester calculées par les commandes Laravel.

BEGIN;

DO $$
DECLARE
    load_count CONSTANT integer := 1200;
    load_prefix CONSTANT text := 'LOAD-20260501-';
    today date := current_date;
    index_value integer;
    day_offset integer;
    leave_index integer;
    employe_id_value bigint;
    poste_id_value bigint;
    departement_id_value bigint;
    contrat_id_value bigint;
    paye_type_id bigint;
    maladie_type_id bigint;
    deces_type_id bigint;
    poste_count integer;
    departement_count integer;
    date_embauche_value date;
    date_naissance_value date;
    date_fin_value date;
    contrat_type_value text;
    contrat_statut_value text;
    start_date date;
    end_date date;
    pointage_day date;
    late_minutes integer;
    names text[] := ARRAY[
        'Rakoto',
        'Rasoanaivo',
        'Andriamampianina',
        'Randrianarisoa',
        'Raveloson',
        'Rajaonarivelo',
        'Rakotomalala',
        'Razafindrakoto'
    ];
    first_names text[] := ARRAY[
        'Mika',
        'Lova',
        'Hery',
        'Tiana',
        'Soa',
        'Nirina',
        'Fanja',
        'Toky',
        'Mamy',
        'Hanitra'
    ];
BEGIN
    PERFORM setseed(0.20260501);

    SELECT count(*) INTO poste_count FROM postes;
    SELECT count(*) INTO departement_count FROM departements;
    SELECT id INTO paye_type_id FROM types_conges WHERE code = 'PAYE' ORDER BY id LIMIT 1;
    SELECT id INTO maladie_type_id FROM types_conges WHERE code = 'MALADIE' ORDER BY id LIMIT 1;
    SELECT id INTO deces_type_id FROM types_conges WHERE code = 'DECES' ORDER BY id LIMIT 1;

    DELETE FROM users WHERE email LIKE 'load.employee.%@example.test';
    DELETE FROM employes WHERE matricule LIKE load_prefix || '%';

    FOR index_value IN 1..load_count LOOP
        poste_id_value := NULL;
        departement_id_value := NULL;

        IF poste_count > 0 THEN
            SELECT id, departement_id
            INTO poste_id_value, departement_id_value
            FROM postes
            ORDER BY id
            OFFSET ((index_value - 1) % poste_count)
            LIMIT 1;
        END IF;

        IF departement_id_value IS NULL AND departement_count > 0 THEN
            SELECT id
            INTO departement_id_value
            FROM departements
            ORDER BY id
            OFFSET ((index_value - 1) % departement_count)
            LIMIT 1;
        END IF;

        date_embauche_value := today - (20 + floor(random() * 3631))::integer;
        IF index_value % 12 = 0 THEN
            date_embauche_value := today - (1 + floor(random() * 45))::integer;
        END IF;

        date_naissance_value := today
            - ((21 + floor(random() * 40))::integer * interval '1 year')
            - ((floor(random() * 361))::integer * interval '1 day');

        INSERT INTO employes (
            matricule,
            nom,
            prenom,
            email,
            telephone,
            adresse,
            date_naissance,
            poste_id,
            departement_id,
            num_cnaps,
            photo,
            date_embauche,
            created_at,
            updated_at
        )
        VALUES (
            load_prefix || lpad(index_value::text, 4, '0'),
            names[(index_value % array_length(names, 1)) + 1] || ' Test' || index_value,
            first_names[(index_value % array_length(first_names, 1)) + 1],
            'load.employee.' || index_value || '@example.test',
            '+261 34 ' || lpad((1000000 + floor(random() * 9000000))::integer::text, 7, '0'),
            'Lot Test ' || index_value || ', Antananarivo',
            date_naissance_value,
            poste_id_value,
            departement_id_value,
            'LOAD-CNAPS-' || lpad(index_value::text, 5, '0'),
            NULL,
            date_embauche_value,
            now(),
            now()
        )
        RETURNING id INTO employe_id_value;

        INSERT INTO users (
            name,
            email,
            password,
            role,
            employe_id,
            created_at,
            updated_at
        )
        VALUES (
            names[(index_value % array_length(names, 1)) + 1] || ' Test' || index_value || ' ' || first_names[(index_value % array_length(first_names, 1)) + 1],
            'load.employee.' || index_value || '@example.test',
            'password',
            CASE WHEN index_value % 40 = 0 THEN 'manager' ELSE 'employe' END,
            employe_id_value,
            now(),
            now()
        );

        INSERT INTO historique_postes (
            employe_id,
            poste_id,
            departement_id,
            date_changement,
            motif,
            created_at,
            updated_at
        )
        VALUES (
            employe_id_value,
            poste_id_value,
            departement_id_value,
            date_embauche_value,
            'Jeu de test charge dashboard',
            now(),
            now()
        );

        contrat_type_value := CASE
            WHEN index_value % 15 = 0 THEN 'Stage'
            WHEN index_value % 4 = 0 THEN 'CDD'
            ELSE 'CDI'
        END;
        date_fin_value := NULL;
        contrat_statut_value := 'en_cours';

        IF index_value % 18 = 0 THEN
            date_fin_value := today - (5 + floor(random() * 176))::integer;
            contrat_statut_value := 'termine';
        ELSIF contrat_type_value <> 'CDI' OR index_value % 9 = 0 THEN
            date_fin_value := today + CASE
                WHEN index_value % 30 = 0 THEN 0
                ELSE (1 + floor(random() * 180))::integer
            END;
        END IF;

        INSERT INTO contrats (
            numero,
            employe_id,
            type_contrat,
            date_debut,
            date_fin,
            periode_essai_debut,
            periode_essai_fin,
            renouvelable,
            statut,
            salaire_base,
            created_at,
            updated_at
        )
        VALUES (
            'LOAD-CTR-' || lpad(index_value::text, 5, '0'),
            employe_id_value,
            contrat_type_value,
            date_embauche_value,
            date_fin_value,
            CASE WHEN contrat_type_value = 'Stage' OR index_value % 3 = 0 THEN date_embauche_value ELSE NULL END,
            CASE WHEN contrat_type_value = 'Stage' OR index_value % 3 = 0 THEN date_embauche_value + interval '1 month' ELSE NULL END,
            contrat_type_value = 'CDD',
            contrat_statut_value,
            350000 + floor(random() * 2150001),
            now(),
            now()
        )
        RETURNING id INTO contrat_id_value;

        INSERT INTO contrat_historiques (
            contrat_id,
            numero,
            employe_id,
            type_contrat,
            date_debut,
            date_fin,
            periode_essai_debut,
            periode_essai_fin,
            renouvelable,
            salaire_base,
            statut,
            created_at,
            updated_at
        )
        SELECT
            id,
            numero,
            employe_id,
            type_contrat,
            date_debut,
            date_fin,
            periode_essai_debut,
            periode_essai_fin,
            renouvelable,
            salaire_base,
            statut,
            now(),
            now()
        FROM contrats
        WHERE id = contrat_id_value;

        IF index_value % 7 = 0 THEN
            start_date := today - (1 + floor(random() * 20))::integer;
            end_date := start_date + (1 + floor(random() * 4))::integer;
            INSERT INTO demandes_conges (
                employe_id,
                type_conge_id,
                date_debut,
                date_fin,
                jours_demandes,
                statut,
                motif,
                created_at,
                updated_at
            )
            VALUES (
                employe_id_value,
                paye_type_id,
                start_date,
                end_date,
                greatest(1, (SELECT count(*) FROM generate_series(start_date, end_date, interval '1 day') d WHERE extract(isodow from d) < 6)),
                'en_attente',
                'Demande en attente test',
                start_date - (2 + floor(random() * 11))::integer,
                start_date - (floor(random() * 3))::integer
            );
        END IF;

        IF index_value % 11 = 0 THEN
            start_date := today + floor(random() * 8)::integer;
            end_date := start_date + 2;
            INSERT INTO demandes_conges (
                employe_id,
                type_conge_id,
                date_debut,
                date_fin,
                jours_demandes,
                statut,
                motif,
                created_at,
                updated_at
            )
            VALUES (
                employe_id_value,
                paye_type_id,
                start_date,
                end_date,
                greatest(1, (SELECT count(*) FROM generate_series(start_date, end_date, interval '1 day') d WHERE extract(isodow from d) < 6)),
                'en_attente',
                'Congé proche non validé',
                start_date - (2 + floor(random() * 11))::integer,
                start_date - (floor(random() * 3))::integer
            );
        END IF;

        IF index_value % 13 = 0 THEN
            start_date := today - (5 + floor(random() * 86))::integer;
            end_date := start_date + (2 + floor(random() * 7))::integer;
            INSERT INTO demandes_conges (
                employe_id,
                type_conge_id,
                date_debut,
                date_fin,
                jours_demandes,
                statut,
                motif,
                created_at,
                updated_at
            )
            VALUES (
                employe_id_value,
                paye_type_id,
                start_date,
                end_date,
                greatest(1, (SELECT count(*) FROM generate_series(start_date, end_date, interval '1 day') d WHERE extract(isodow from d) < 6)),
                'rh_valide',
                'Congé validé historique',
                start_date - (2 + floor(random() * 11))::integer,
                start_date - (floor(random() * 3))::integer
            );
        END IF;

        IF index_value % 17 = 0 THEN
            FOR leave_index IN 0..3 LOOP
                start_date := today - (12 + (leave_index * 9));
                end_date := start_date;
                INSERT INTO demandes_conges (
                    employe_id,
                    type_conge_id,
                    date_debut,
                    date_fin,
                    jours_demandes,
                    statut,
                    motif,
                    created_at,
                    updated_at
                )
                VALUES (
                    employe_id_value,
                    maladie_type_id,
                    start_date,
                    end_date,
                    greatest(1, (SELECT count(*) FROM generate_series(start_date, end_date, interval '1 day') d WHERE extract(isodow from d) < 6)),
                    'rh_valide',
                    'Maladie répétée test',
                    start_date - (2 + floor(random() * 11))::integer,
                    start_date - (floor(random() * 3))::integer
                );
            END LOOP;
        END IF;

        IF index_value % 23 = 0 THEN
            FOR leave_index IN 0..2 LOOP
                start_date := today - (10 + (leave_index * 20));
                end_date := start_date + 1;
                INSERT INTO demandes_conges (
                    employe_id,
                    type_conge_id,
                    date_debut,
                    date_fin,
                    jours_demandes,
                    statut,
                    motif,
                    created_at,
                    updated_at
                )
                VALUES (
                    employe_id_value,
                    deces_type_id,
                    start_date,
                    end_date,
                    greatest(1, (SELECT count(*) FROM generate_series(start_date, end_date, interval '1 day') d WHERE extract(isodow from d) < 6)),
                    'rh_valide',
                    'Exceptionnel répétée test',
                    start_date - (2 + floor(random() * 11))::integer,
                    start_date - (floor(random() * 3))::integer
                );
            END LOOP;
        END IF;

        IF index_value <= 450 AND index_value % 18 <> 0 THEN
            FOR day_offset IN 1..12 LOOP
                pointage_day := today - day_offset;

                IF extract(isodow from pointage_day) IN (6, 7) THEN
                    CONTINUE;
                END IF;

                IF index_value % 19 = 0 AND day_offset % 4 = 0 THEN
                    CONTINUE;
                END IF;

                late_minutes := CASE
                    WHEN index_value % 10 = 0 THEN 45 + floor(random() * 106)::integer
                    ELSE floor(random() * 21)::integer
                END;

                INSERT INTO pointages (
                    employe_id,
                    type,
                    pointe_a,
                    source,
                    commentaire,
                    created_at,
                    updated_at
                )
                VALUES
                    (
                        employe_id_value,
                        'entree',
                        pointage_day::timestamp + time '08:00' + make_interval(mins => late_minutes),
                        'seed',
                        'Charge dashboard',
                        now(),
                        now()
                    ),
                    (
                        employe_id_value,
                        'pause_debut',
                        pointage_day::timestamp + time '12:00',
                        'seed',
                        'Charge dashboard',
                        now(),
                        now()
                    ),
                    (
                        employe_id_value,
                        'pause_fin',
                        pointage_day::timestamp + time '13:00',
                        'seed',
                        'Charge dashboard',
                        now(),
                        now()
                    ),
                    (
                        employe_id_value,
                        'sortie',
                        pointage_day::timestamp + time '17:00' + make_interval(mins => (-20 + floor(random() * 51))::integer),
                        'seed',
                        'Charge dashboard',
                        now(),
                        now()
                    );
            END LOOP;
        END IF;
    END LOOP;
END $$;

COMMIT;
