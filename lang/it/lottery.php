<?php

return [
    'title'               => 'Sorteggio',
    'default_description' => 'Il sorteggio dà un alloggio a ogni famiglia, in base agli ordini di preferenza scelti dalle famiglie.',

    'locked'      => 'Il sorteggio è già stato fatto, o è in corso. Questo non si può più cambiare.',
    'family_gone' => 'Famiglia eliminata n. :id',

    'running'          => 'Il sorteggio è in corso. Alloggi e famiglie potranno cambiare quando finisce.',
    'execution_failed' => 'Qualcosa è andato storto durante il sorteggio. Riprova. Se continua a non riuscire, contatta il team della piattaforma.',

    'cannot_run_already_run'      => 'Il sorteggio è già stato fatto.',
    'cannot_run_no_worker'        => 'Ora il sorteggio non si può fare. Stiamo controllando. Riprova più tardi.',
    'cannot_run_not_scheduled'    => 'Fissa la data del sorteggio prima di farlo.',
    'cannot_run_not_due'          => 'Il sorteggio non si può fare prima della sua data.',
    'cannot_run_too_few_families' => 'Servono almeno due famiglie per fare il sorteggio.',
    'cannot_run_too_large'        => 'Per ora questo progetto ha spazio per sorteggi fino a :max famiglie. Chiedi di togliere i limiti.',

    'unit_family_mismatch_intro'  => 'Il numero di alloggi e quello di famiglie non coincidono:',
    'mismatch_excess_units'       => ':unit_type ha :units alloggi per :families famiglie',
    'mismatch_insufficient_units' => ':unit_type ha :units alloggi per :families famiglie',

    'glpk_execution_failed' => 'Il sorteggio non è riuscito a finire. Contatta il team della piattaforma.',
    'glpk_timeout'          => 'Il sorteggio ha richiesto troppo tempo ed è stato interrotto. Riprova.',
    'glpk_infeasible'       => 'Il sorteggio non trova un risultato con le preferenze attuali. Contatta il team della piattaforma.',
];
