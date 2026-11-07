<?php

return [
    'title'               => 'Sorteo',
    'default_description' => 'El sorteo le da a cada familia una vivienda, según el orden de preferencias que eligió.',

    'locked'      => 'El sorteo ya se hizo, o se está haciendo. Esto ya no puede cambiar.',
    'family_gone' => 'Familia eliminada n.º :id',

    'running'               => 'El sorteo está en curso. Las viviendas y las familias pueden cambiar cuando termine.',
    'execution_failed'      => 'Algo salió mal al hacer el sorteo. Prueba de nuevo. Si sigue fallando, avísale al equipo de la plataforma.',
    'requires_confirmation' => 'Confirma antes de hacer el sorteo.',

    'cannot_run_already_run'      => 'El sorteo ya se hizo.',
    'cannot_run_no_worker'        => 'Ahora no se puede hacer el sorteo. Ya lo estamos revisando. Prueba más tarde.',
    'cannot_run_not_scheduled'    => 'Fija la fecha del sorteo antes de hacerlo.',
    'cannot_run_not_due'          => 'El sorteo no se puede hacer antes de su fecha.',
    'cannot_run_too_few_families' => 'Hacen falta al menos dos familias para hacer el sorteo.',
    'cannot_run_too_large'        => 'Por ahora este proyecto tiene lugar para sorteos de hasta :max familias. Pide ilimitado para quitar el límite.',

    'unit_family_mismatch_intro'  => 'La cantidad de viviendas y de familias no coincide:',
    'mismatch_excess_units'       => ':unit_type tiene :units viviendas para :families familias',
    'mismatch_insufficient_units' => ':unit_type tiene :units viviendas para :families familias',

    'glpk_execution_failed' => 'El sorteo no pudo terminar. Avísale al equipo de la plataforma.',
    'glpk_timeout'          => 'El sorteo tardó demasiado y se detuvo. Prueba de nuevo.',
    'glpk_infeasible'       => 'El sorteo no encuentra un resultado con las preferencias actuales. Avísale al equipo de la plataforma.',
];
