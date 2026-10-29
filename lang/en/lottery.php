<?php

return [
    'title'               => 'Lottery',
    'default_description' => 'The draw that gives each family a unit, from the rankings the families made.',

    'locked' => 'The lottery already ran (or is running): this can no longer change.',

    'running'               => 'The lottery is running: units and families change once it ends.',
    'execution_failed'      => 'Something went wrong while running the lottery. You can try again; if it keeps failing, contact the platform team.',
    'requires_confirmation' => 'Please confirm before running the lottery.',

    'cannot_run_already_run'      => 'The lottery already ran.',
    'cannot_run_not_scheduled'    => 'Set the lottery\'s date before running it.',
    'cannot_run_not_due'          => 'The lottery cannot run before its date.',
    'cannot_run_too_few_families' => 'At least two families are needed to run the lottery.',
    'cannot_run_too_large'        => 'Projects awaiting approval can run lotteries of up to :max families. Ask for approval to lift the limit.',

    'unit_family_mismatch_intro'  => 'The number of units and families differ:',
    'mismatch_excess_units'       => ':unit_type has :units units for :families families',
    'mismatch_insufficient_units' => ':unit_type has :units units for :families families',

    'glpk_execution_failed' => 'The assignment could not be computed. Please contact the platform team.',
    'glpk_timeout'          => 'Computing the assignment took too long and was stopped. Please try again.',
    'glpk_infeasible'       => 'No valid assignment exists for the current rankings. Please contact the platform team.',
];
