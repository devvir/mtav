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
    'glpk_execution_failed'         => 'El algoritmo de optimización falló al ejecutarse. Por favor contacta a soporte.',
    'glpk_timeout'                  => 'El algoritmo de optimización tardó demasiado en completarse y fue detenido. Por favor intenta ejecutar el sorteo nuevamente. Si el problema persiste, contacta a soporte.',

    'insufficient_families'       => 'No hay suficientes familias para ejecutar el sorteo. Se requieren al menos 2 familias.',
    'unit_family_mismatch_intro'  => 'Hay inconsistencias entre unidades y familias:',
    'mismatch_excess_units'       => ':unit_type tiene :units unidades para :families familias',
    'mismatch_insufficient_units' => ':unit_type tiene :units unidades para :families familias',

    'degenerate_case_requires_greedy_confirmation' => 'La configuración del problema puede causar que el algoritmo de optimización expire su tiempo límite. El sistema utilizará un algoritmo greedy justo en su lugar. Esto mantiene la equidad pero utiliza un enfoque diferente. ¿Confirma esto?',

    'preferences_locked' => 'No se pueden actualizar las preferencias: la ejecución del sorteo está en progreso o ya finalizó.',
    'lottery_locked'     => 'No se puede actualizar el sorteo: la ejecución está en progreso o ya finalizó.',

    'invalidation_failed'      => 'Ocurrió un error al invalidar el sorteo. Por favor, contacta al administrador del sistema.',
    'invalidated_successfully' => 'La ejecución del sorteo ha sido invalidada exitosamente. Todas las asignaciones de unidades fueron removidas.',

    'lottery_updated'             => '¡Excelente! Los detalles del sorteo fueron actualizados.',
    'lottery_preferences_updated' => '¡Perfecto! Tus preferencias de unidades fueron guardadas.',

];
