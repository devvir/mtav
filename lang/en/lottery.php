<?php

return [
    'title'               => 'Lottery',
    'default_description' => 'The lottery gives each family a unit, from the rankings the families made.',

    'locked'      => 'The lottery already ran, or is running. This can no longer change.',
    'family_gone' => 'Deleted family #:id',

    'running'          => 'The lottery is running. Units and families can change once it ends.',
    'execution_failed' => 'Something went wrong while running the lottery. Try again. If it keeps failing, contact the platform team.',

    'cannot_run_already_run'      => 'The lottery already ran.',
    'cannot_run_no_worker'        => 'The lottery can\'t run right now. We\'re looking into it. Try again later.',
    'cannot_run_not_scheduled'    => 'Set the lottery\'s date before running it.',
    'cannot_run_not_due'          => 'The lottery cannot run before its date.',
    'cannot_run_too_few_families' => 'At least two families are needed to run the lottery.',
    'cannot_run_too_large'        => 'For now this project has room for lotteries of up to :max families. Ask for unlimited to lift it.',

    'unit_family_mismatch_intro'  => 'The number of units and families differ:',
    'mismatch_excess_units'       => ':unit_type has :units units for :families families',
    'mismatch_insufficient_units' => ':unit_type has :units units for :families families',

    'glpk_execution_failed' => 'The lottery could not finish. Contact the platform team.',
    'glpk_timeout'          => 'The lottery took too long and was stopped. Try again.',
    'glpk_infeasible'       => 'The lottery cannot find a result with the current rankings. Contact the platform team.',

    /** The whole record, downloaded as an archive: its files' headings and its README */
    'audit_family'             => 'Family',
    'audit_unit'               => 'Unit',
    'audit_place'              => 'Place',
    'audit_of'                 => 'Of',
    'audit_by'                 => 'Changed by',
    'audit_from'               => 'From',
    'audit_until'              => 'Until',
    'audit_ranking'            => 'Ranking (most wanted first)',
    'audit_readme_title'       => 'THE LOTTERY OF :project — ITS WHOLE RECORD',
    'audit_readme_run'         => 'Run :uuid, finished :date.',
    'audit_readme_fingerprint' => 'Fingerprint (SHA-256 of the input and the result, as the platform stores them): :fingerprint',
    'audit_readme_files'       => 'What is here:
- input.json: what the lottery read, per unit type: its units and its families, each family\'s ranking, most wanted first.
- result.csv: the unit each family received, and where it stood in the family\'s ranking (1 is a first choice).
- ranking-changes.csv: how each ranking changed before the run: who left it as what, and when.
- models/: the GLPK models the lottery is solved with (GMPL). Phase 1 makes the worst place as good as it can; Phase 2 keeps that and makes every place as good as possible together.
- solves/: each resolution of the solver, in order: what it was asked and what it found (solve.json), and the model, data and solution files it exchanged with GLPK.
- record.json: the raw trail the platform wrote as the lottery ran, never changed, with the manifest (ids), each step and the result.',
    'audit_readme_how'     => 'To check the result, read input.json next to result.csv: no family could be given a better worst place, and, that kept, no better places overall. To re-run it, GLPK (glpsol) takes each solve\'s model and data files: glpsol --math model.mod --data data.dat. Where rankings tie, the solver may pick another assignment as good as this one.',
    'audit_readme_privacy' => 'Nothing here is anyone\'s email, phone or ID number: the record names people and families, and that is all it needs.',
];
