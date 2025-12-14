<?php

return [
    // Jours travaillés standards (codes Carbon -> mon, tue, wed, thu, fri, sat, sun)
    'working_days' => ['mon', 'tue', 'wed', 'thu', 'fri'],

    // Gestion du samedi : 'normal' (compte comme un jour ouvré) ou 'hs' (majoré)
    'saturday_mode' => 'normal', // valeurs possibles : normal | hs

    // Heure de début théorique pour le retard (24h format)
    'start_hour' => 8,
    'start_minute' => 0,

    // Durée d'une journée normale (pour le calcul des HS)
    'hours_per_day' => 8,

    // Seuil hebdomadaire pour les HS
    'weekly_threshold' => 40,

    // Majoration par type
    'multipliers' => [
        'weekday_first8' => 1.3,
        'weekday_next12' => 1.5,
        'weekday_beyond' => 1.5,
        'saturday'       => 1.4,
        'sunday'         => 1.4,
        'holiday'        => 2.0,
    ],
];
