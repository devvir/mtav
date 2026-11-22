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

    /** The whole record, downloaded as an archive: its files' headings and its README */
    'audit_family'             => 'Famiglia',
    'audit_unit'               => 'Alloggio',
    'audit_place'              => 'Posto',
    'audit_of'                 => 'Su',
    'audit_by'                 => 'Modificato da',
    'audit_from'               => 'Dal',
    'audit_until'              => 'Al',
    'audit_ranking'            => 'Ordine (il più desiderato per primo)',
    'audit_readme_title'       => 'IL SORTEGGIO DI :project — IL SUO REGISTRO COMPLETO',
    'audit_readme_run'         => 'Esecuzione :uuid, terminata il :date.',
    'audit_readme_fingerprint' => 'Impronta (SHA-256 dell’input e del risultato, così come li conserva la piattaforma): :fingerprint',
    'audit_readme_files'       => 'Cosa c’è qui:
- input.json: ciò che il sorteggio ha letto, per tipo di alloggio: i suoi alloggi e le sue famiglie, l’ordine di ogni famiglia, il più desiderato per primo.
- result.csv: l’alloggio ricevuto da ogni famiglia, e il posto che occupava nel suo ordine (1 è la prima scelta).
- ranking-changes.csv: come è cambiato ogni ordine prima del sorteggio: chi l’ha lasciato come, e quando.
- models/: i modelli GLPK con cui si risolve il sorteggio (GMPL). La fase 1 rende il posto peggiore il migliore possibile; la fase 2 lo conserva e rende tutti i posti i migliori possibili nell’insieme.
- solves/: ogni risoluzione del solver, in ordine: cosa gli è stato chiesto e cosa ha trovato (solve.json), e i file di modello, dati e soluzione scambiati con GLPK.
- record.json: la traccia grezza scritta dalla piattaforma mentre il sorteggio girava, mai modificata, con il manifesto (id), ogni passo e il risultato.',
    'audit_readme_how'     => 'Per verificare il risultato, leggi input.json accanto a result.csv: a nessuna famiglia si poteva dare un posto peggiore migliore e, mantenuto questo, non ci sono posti migliori nell’insieme. Per rieseguirlo, GLPK (glpsol) prende i file di modello e dati di ogni risoluzione: glpsol --math modello.mod --data dati.dat. Dove gli ordini sono in parità, il solver può scegliere un’altra assegnazione altrettanto buona.',
    'audit_readme_privacy' => 'Niente di questo è l’email, il telefono o il documento di qualcuno: il registro nomina persone e famiglie, e tanto gli basta.',
];
