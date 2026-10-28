<?php

return [
    'title'               => 'Sorteggio',
    'default_description' => 'Il sorteggio che dà un’unità a ogni famiglia, a partire dalle classifiche fatte dalle famiglie.',

    'locked'                => 'Il sorteggio è già stato eseguito (o è in corso): non si può più cambiare.',
    'execution_failed'      => 'Qualcosa è andato storto durante il sorteggio. Puoi riprovare; se continua a non riuscire, contatta il team della piattaforma.',
    'requires_confirmation' => 'Conferma prima di eseguire il sorteggio.',

    'cannot_run_already_run'      => 'Il sorteggio è già stato eseguito.',
    'cannot_run_not_scheduled'    => 'Fissa la data del sorteggio prima di eseguirlo.',
    'cannot_run_not_due'          => 'Il sorteggio non si può eseguire prima della sua data.',
    'cannot_run_too_few_families' => 'Servono almeno due famiglie per eseguire il sorteggio.',
    'cannot_run_too_large'        => 'I progetti in attesa di approvazione possono eseguire sorteggi fino a :max famiglie. Chiedi l’approvazione per togliere il limite.',

    'unit_family_mismatch_intro'  => 'Il numero di unità e quello di famiglie sono diversi:',
    'mismatch_excess_units'       => ':unit_type ha :units unità per :families famiglie',
    'mismatch_insufficient_units' => ':unit_type ha :units unità per :families famiglie',

    'glpk_execution_failed' => 'Non è stato possibile calcolare l’assegnazione. Contatta il team della piattaforma.',
    'glpk_timeout'          => 'Il calcolo dell’assegnazione ha richiesto troppo tempo ed è stato interrotto. Riprova.',
    'glpk_infeasible'       => 'Non esiste un’assegnazione valida per le classifiche attuali. Contatta il team della piattaforma.',
];
