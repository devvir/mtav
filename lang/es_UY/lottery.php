<?php

return [
    'title'               => 'Sorteo',
    'default_description' => 'El sorteo le da a cada familia una vivienda, según el orden de preferencias que eligió.',

    'locked'      => 'El sorteo ya se hizo, o se está haciendo. Esto ya no puede cambiar.',
    'family_gone' => 'Familia eliminada n.º :id',

    'running'          => 'El sorteo está en curso. Las viviendas y las familias pueden cambiar cuando termine.',
    'execution_failed' => 'Algo salió mal al hacer el sorteo. Prueba de nuevo. Si sigue fallando, avísale al equipo de la plataforma.',

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

    /** The whole record, downloaded as an archive: its files' headings and its README */
    'audit_family'             => 'Familia',
    'audit_unit'               => 'Vivienda',
    'audit_place'              => 'Lugar',
    'audit_of'                 => 'De',
    'audit_by'                 => 'Cambiado por',
    'audit_from'               => 'Desde',
    'audit_until'              => 'Hasta',
    'audit_ranking'            => 'Orden (la más deseada primero)',
    'audit_readme_title'       => 'EL SORTEO DE :project — SU REGISTRO COMPLETO',
    'audit_readme_run'         => 'Ejecución :uuid, terminada el :date.',
    'audit_readme_fingerprint' => 'Huella (SHA-256 de la entrada y del resultado, tal como los guarda la plataforma): :fingerprint',
    'audit_readme_files'       => 'Qué hay aquí:
- input.json: lo que leyó el sorteo, por tipo de vivienda: sus viviendas y sus familias, el orden de cada familia, la más deseada primero.
- result.csv: la vivienda que recibió cada familia, y el lugar que ocupaba en su orden (1 es la primera opción).
- ranking-changes.csv: cómo cambió cada orden antes del sorteo: quién lo dejó cómo, y cuándo.
- models/: los modelos de GLPK con los que se resuelve el sorteo (GMPL). La Fase 1 deja el peor lugar lo mejor posible; la Fase 2 lo conserva y deja todos los lugares lo mejor posible en conjunto.
- solves/: cada resolución del solver, en orden: qué se le pidió y qué encontró (solve.json), y los archivos de modelo, datos y solución que intercambió con GLPK.
- record.json: la traza en crudo que la plataforma escribió mientras corría el sorteo, nunca modificada, con el manifiesto (ids), cada paso y el resultado.',
    'audit_readme_how'     => 'Para verificar el resultado, lee input.json junto a result.csv: a ninguna familia se le pudo dar un peor lugar mejor, y, conservado eso, no hay mejores lugares en conjunto. Para volver a correrlo, GLPK (glpsol) toma los archivos de modelo y datos de cada resolución: glpsol --math modelo.mod --data datos.dat. Donde los órdenes empatan, el solver puede elegir otra asignación tan buena como esta.',
    'audit_readme_privacy' => 'Nada de esto es el correo, el teléfono ni la cédula de nadie: el registro nombra personas y familias, y con eso le alcanza.',
];
