<?php

return [
    'categories' => [
        'entree' => [
            ['code' => 'apport_capital', 'label' => 'Apport en capital'],
            ['code' => 'encaissement_client', 'label' => 'Encaissement client'],
            ['code' => 'remboursement_avance', 'label' => 'Remboursement d’avance'],
            ['code' => 'transfert_inter_caisse', 'label' => 'Transfert inter-caisse entrant'],
            ['code' => 'subvention', 'label' => 'Subvention / financement'],
            ['code' => 'regularisation_positive', 'label' => 'Régularisation positive'],
            ['code' => 'autre_entree', 'label' => 'Autre entrée'],
        ],
        'sortie' => [
            ['code' => 'paie_employe', 'label' => 'Paie employé'],
            ['code' => 'cnaps', 'label' => 'Paiement CNAPS'],
            ['code' => 'ostie', 'label' => 'Paiement OSTIE'],
            ['code' => 'irsa', 'label' => 'Paiement IRSA'],
            ['code' => 'avance_salaire', 'label' => 'Avance sur salaire'],
            ['code' => 'remboursement_frais', 'label' => 'Remboursement de frais'],
            ['code' => 'achat_fournitures', 'label' => 'Achat de fournitures'],
            ['code' => 'loyer_charges', 'label' => 'Loyer et charges'],
            ['code' => 'transfert_inter_caisse', 'label' => 'Transfert inter-caisse sortant'],
            ['code' => 'regularisation_negative', 'label' => 'Régularisation négative'],
            ['code' => 'autre_sortie', 'label' => 'Autre sortie'],
        ],
    ],
];
