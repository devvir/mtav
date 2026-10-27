<?php

return [
    'title'               => 'Sorteo',
    'default_description' => 'El sorteo que le da a cada familia una vivienda, a partir del orden de preferencias que eligieron.',

    'locked'                => 'El sorteo ya se realizó (o se está realizando): esto ya no puede cambiar.',
    'execution_failed'      => 'Algo salió mal al realizar el sorteo. Puedes volver a intentarlo; si sigue fallando, contacta al equipo de la plataforma.',
    'requires_confirmation' => 'Confirma antes de realizar el sorteo.',

    'cannot_run_already_run'      => 'El sorteo ya se realizó.',
    'cannot_run_not_scheduled'    => 'Fija la fecha del sorteo antes de realizarlo.',
    'cannot_run_not_due'          => 'El sorteo no puede realizarse antes de su fecha.',
    'cannot_run_too_few_families' => 'Se necesitan al menos dos familias para realizar el sorteo.',
    'cannot_run_too_large'        => 'Los proyectos sin aprobar pueden sortear hasta :max familias. Pide la aprobación para quitar el límite.',

    'unit_family_mismatch_intro'  => 'La cantidad de viviendas y de familias no coincide:',
    'mismatch_excess_units'       => ':unit_type tiene :units viviendas para :families familias',
    'mismatch_insufficient_units' => ':unit_type tiene :units viviendas para :families familias',

    'glpk_execution_failed' => 'No se pudo calcular la asignación. Contacta al equipo de la plataforma.',
    'glpk_timeout'          => 'El cálculo de la asignación tardó demasiado y se detuvo. Vuelve a intentarlo.',
    'glpk_infeasible'       => 'No existe una asignación válida para las preferencias actuales. Contacta al equipo de la plataforma.',
];
