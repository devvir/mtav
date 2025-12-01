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

    'insufficient_families'       => 'Not enough families to execute the lottery. At least 2 families are required.',
    'unit_family_mismatch_intro'  => 'There are inconsistencies between units and families:',
    'mismatch_excess_units'       => ':unit_type has :units units for :families families (excess units)',
    'mismatch_insufficient_units' => ':unit_type has :units units for :families families (insufficient units)',

    'preferences_locked' => 'Cannot update preferences: lottery execution is in progress or already completed.',
    'lottery_locked'     => 'Cannot update lottery: execution is in progress or already completed.',

];
