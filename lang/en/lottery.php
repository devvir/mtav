<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Lottery Translation Lines
    |--------------------------------------------------------------------------
    |
    | Translation lines specific to lottery operations.
    |
    */

    'already_executed_or_executing' => 'The lottery has already been executed or is currently being executed.',
    'not_yet_scheduled'             => 'The lottery cannot be executed before the scheduled date.',
    'no_date_set'                   => 'You must schedule a start date for the lottery before executing it.',
    'cannot_execute_generic'        => 'The lottery cannot be executed. Verify that it meets the necessary conditions.',
    'execution_failed'              => 'An error occurred during lottery execution. Please contact the system administrator.',
    'glpk_execution_failed'         => 'The optimization algorithm failed to execute. Please contact support.',
    'glpk_timeout'                  => 'The optimization algorithm took too long to complete and was stopped. Please try executing the lottery again. If the problem persists, contact support',

    'insufficient_families'       => 'Not enough families to execute the lottery. At least 2 families are required.',
    'unit_family_mismatch_intro'  => 'There are inconsistencies between units and families:',
    'mismatch_excess_units'       => ':unit_type has :units units for :families families',
    'mismatch_insufficient_units' => ':unit_type has :units units for :families families',

    'preferences_locked' => 'Cannot update preferences: lottery execution is in progress or already completed.',
    'lottery_locked'     => 'Cannot update lottery: execution is in progress or already completed.',

    'invalidation_failed'      => 'An error occurred while invalidating the lottery. Please contact the system administrator.',
    'invalidated_successfully' => 'Lottery execution has been successfully invalidated. All unit assignments have been removed.',

    'lottery_updated'             => 'Great! The lottery details were updated.',
    'lottery_preferences_updated' => 'Perfect! Your unit preferences were saved.',

];
