<?php

return [
    // Jours travaillés standards (codes Carbon -> mon, tue, wed, thu, fri, sat, sun)
    'working_days' => ['mon', 'tue', 'wed', 'thu', 'fri'],

    // Gestion du samedi : 'normal' (compte comme un jour ouvré) ou 'hs' (majoré)
    'saturday_mode' => 'hs', // valeurs possibles : normal | hs

    // Heure de début théorique pour le retard (24h format)
    'start_hour' => 8,
    'start_minute' => 0,

    // Durée d'une journée normale (pour le calcul des HS)
    'hours_per_day' => 8,
    // Minutes de pause incluses dans la journée (pour calcul heures normales)
    'pause_minutes' => 60,

    // Plage horaire de nuit et majoration (en %)
    'night_start' => '22:00',
    'night_end'   => '05:00',
    'night_rate'  => 20, // +20%

    // Seuil hebdomadaire pour les HS
    'weekly_threshold' => 40,

    // Majoration par type (valeurs en %)
    'multipliers' => [
        'weekday_first8' => 30,
        'weekday_next12' => 50,
        'weekday_beyond' => 50,
        'saturday'       => 40,
        'sunday'         => 40,
        'holiday'        => 100,
    ],

    // Gestion des prélèvements en cas d'absences/retards
    'deduct_from_leave_balance' => false,  // prélève en priorité sur le solde de congé
    'deduct_from_salary' => false,         // prélève sur le salaire (taux journalier/horaire) si nécessaire
];
