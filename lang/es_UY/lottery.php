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

    'already_executed_or_executing' => 'El sorteo ya fue ejecutado o está siendo ejecutado actualmente.',
    'not_yet_scheduled'             => 'El sorteo no puede ser ejecutado antes de la fecha programada.',
    'no_date_set'                   => 'Debes programar una fecha de inicio para el sorteo antes de ejecutarlo.',
    'cannot_execute_generic'        => 'El sorteo no puede ser ejecutado. Verifica que cumpla las condiciones necesarias.',
    'execution_failed'              => 'Ocurrió un error durante la ejecución del sorteo. Por favor, contacta al administrador del sistema.',

    'insufficient_families'       => 'No hay suficientes familias para ejecutar el sorteo. Se requieren al menos 2 familias.',
    'unit_family_mismatch_intro'  => 'Hay inconsistencias entre unidades y familias:',
    'mismatch_excess_units'       => ':unit_type tiene :units unidades para :families familias (sobran unidades)',
    'mismatch_insufficient_units' => ':unit_type tiene :units unidades para :families familias (faltan unidades)',

    'preferences_locked' => 'No se pueden actualizar las preferencias: la ejecución del sorteo está en progreso o ya finalizó.',
    'lottery_locked'     => 'No se puede actualizar el sorteo: la ejecución está en progreso o ya finalizó.',

];
