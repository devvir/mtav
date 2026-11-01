<?php

return [
    'title'               => 'Lottery',
    'default_description' => 'The lottery gives each family a unit, from the rankings the families made.',

    'locked' => 'The lottery already ran, or is running. This can no longer change.',

    'running'               => 'The lottery is running. Units and families can change once it ends.',
    'execution_failed'      => 'Something went wrong while running the lottery. Try again. If it keeps failing, contact the platform team.',
    'requires_confirmation' => 'Confirm before running the lottery.',

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
];
