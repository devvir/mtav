<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Líneas de Idioma de Validación
    |--------------------------------------------------------------------------
    |
    | Las siguientes líneas de idioma contienen los mensajes de error por
    | defecto usados por la clase validador. Algunas reglas tienen múltiples
    | versiones como las reglas de tamaño. Siéntete libre de ajustar estos.
    |
    */

    'accepted'        => 'Debes aceptar el campo :attribute.',
    'accepted_if'     => 'Debes aceptar el campo :attribute cuando :other es :value.',
    'active_url'      => 'El campo :attribute debe ser una URL válida.',
    'after'           => 'El campo :attribute debe ser una fecha posterior a :date.',
    'after_or_equal'  => 'El campo :attribute debe ser una fecha posterior o igual a :date.',
    'alpha'           => 'El campo :attribute solo debe contener letras.',
    'alpha_dash'      => 'El campo :attribute solo debe contener letras, números, guiones y guiones bajos.',
    'alpha_num'       => 'El campo :attribute solo debe contener letras y números.',
    'any_of'          => 'El campo :attribute es inválido.',
    'array'           => 'El campo :attribute debe ser una lista.',
    'ascii'           => 'El campo :attribute solo debe contener caracteres alfanuméricos y símbolos de un byte.',
    'before'          => 'El campo :attribute debe ser una fecha anterior a :date.',
    'before_or_equal' => 'El campo :attribute debe ser una fecha anterior o igual a :date.',
    'between'         => [
        'array'   => 'El campo :attribute debe tener entre :min y :max elementos.',
        'file'    => 'El campo :attribute debe tener entre :min y :max kilobytes.',
        'numeric' => 'El campo :attribute debe estar entre :min y :max.',
        'string'  => 'El campo :attribute debe tener entre :min y :max caracteres.',
    ],
    'boolean'           => 'El campo :attribute debe ser verdadero o falso.',
    'can'               => 'El campo :attribute contiene un valor no autorizado.',
    'confirmed'         => 'La confirmación del campo :attribute no coincide.',
    'contains'          => 'El campo :attribute no tiene un valor requerido.',
    'current_password'  => 'La contraseña es incorrecta.',
    'date'              => 'El campo :attribute debe ser una fecha válida.',
    'date_equals'       => 'El campo :attribute debe ser una fecha igual a :date.',
    'date_format'       => 'El campo :attribute debe coincidir con el formato :format.',
    'decimal'           => 'El campo :attribute debe tener :decimal lugares decimales.',
    'declined'          => 'Debes rechazar el campo :attribute.',
    'declined_if'       => 'Debes rechazar el campo :attribute cuando :other es :value.',
    'different'         => 'El campo :attribute y :other deben ser diferentes.',
    'digits'            => 'El campo :attribute debe tener :digits dígitos.',
    'digits_between'    => 'El campo :attribute debe tener entre :min y :max dígitos.',
    'dimensions'        => 'El campo :attribute tiene dimensiones de imagen inválidas.',
    'distinct'          => 'El campo :attribute tiene un valor duplicado.',
    'doesnt_end_with'   => 'El campo :attribute no debe terminar con uno de los siguientes: :values.',
    'doesnt_start_with' => 'El campo :attribute no debe comenzar con uno de los siguientes: :values.',
    'email'             => 'El campo :attribute debe ser un email válido.',
    'ends_with'         => 'El campo :attribute debe terminar con uno de los siguientes: :values.',
    'enum'              => 'El :attribute seleccionado es inválido.',
    'exists'            => 'El :attribute seleccionado es inválido.',
    'extensions'        => 'El campo :attribute debe tener una de las siguientes extensiones: :values.',
    'file'              => 'El campo :attribute debe ser un archivo.',
    'filled'            => 'El campo :attribute debe tener un valor.',
    'gt'                => [
        'array'   => 'El campo :attribute debe tener más de :value elementos.',
        'file'    => 'El campo :attribute debe ser mayor que :value kilobytes.',
        'numeric' => 'El campo :attribute debe ser mayor que :value.',
        'string'  => 'El campo :attribute debe ser mayor que :value caracteres.',
    ],
    'gte' => [
        'array'   => 'El campo :attribute debe tener :value elementos o más.',
        'file'    => 'El campo :attribute debe ser mayor o igual a :value kilobytes.',
        'numeric' => 'El campo :attribute debe ser mayor o igual a :value.',
        'string'  => 'El campo :attribute debe ser mayor o igual a :value caracteres.',
    ],
    'hex_color'     => 'El campo :attribute debe ser un color hexadecimal válido.',
    'image'         => 'El campo :attribute debe ser una imagen.',
    'in'            => 'El :attribute seleccionado es inválido.',
    'in_array'      => 'El campo :attribute debe existir en :other.',
    'in_array_keys' => 'El campo :attribute debe contener al menos una de las siguientes claves: :values.',
    'integer'       => 'El campo :attribute debe ser un entero.',
    'ip'            => 'El campo :attribute debe ser una IP válida.',
    'ipv4'          => 'El campo :attribute debe ser una IPv4 válida.',
    'ipv6'          => 'El campo :attribute debe ser una IPv6 válida.',
    'json'          => 'El campo :attribute debe ser un JSON válido.',
    'list'          => 'El campo :attribute debe ser una lista.',
    'lowercase'     => 'El campo :attribute debe estar en minúsculas.',
    'lt'            => [
        'array'   => 'El campo :attribute debe tener menos de :value elementos.',
        'file'    => 'El campo :attribute debe ser menor que :value kilobytes.',
        'numeric' => 'El campo :attribute debe ser menor que :value.',
        'string'  => 'El campo :attribute debe ser menor que :value caracteres.',
    ],
    'lte' => [
        'array'   => 'El campo :attribute no debe tener más de :value elementos.',
        'file'    => 'El campo :attribute debe ser menor o igual a :value kilobytes.',
        'numeric' => 'El campo :attribute debe ser menor o igual a :value.',
        'string'  => 'El campo :attribute debe ser menor o igual a :value caracteres.',
    ],
    'mac_address' => 'El campo :attribute debe ser una dirección MAC válida.',
    'max'         => [
        'array'   => 'El campo :attribute no debe tener más de :max elementos.',
        'file'    => 'El campo :attribute no debe ser mayor que :max kilobytes.',
        'numeric' => 'El campo :attribute no debe ser mayor que :max.',
        'string'  => 'El campo :attribute no debe ser mayor que :max caracteres.',
    ],
    'max_digits' => 'El campo :attribute no debe tener más de :max dígitos.',
    'mimes'      => 'El campo :attribute debe ser un archivo de tipo: :values.',
    'mimetypes'  => 'El campo :attribute debe ser un archivo de tipo: :values.',
    'min'        => [
        'array'   => 'El campo :attribute debe tener al menos :min elementos.',
        'file'    => 'El campo :attribute debe tener al menos :min kilobytes.',
        'numeric' => 'El campo :attribute debe ser al menos :min.',
        'string'  => 'El campo :attribute debe tener al menos :min caracteres.',
    ],
    'min_digits'       => 'El campo :attribute debe tener al menos :min dígitos.',
    'missing'          => 'El campo :attribute debe estar ausente.',
    'missing_if'       => 'El campo :attribute debe estar ausente cuando :other es :value.',
    'missing_unless'   => 'El campo :attribute debe estar ausente a menos que :other sea :value.',
    'missing_with'     => 'El campo :attribute debe estar ausente cuando :values está presente.',
    'missing_with_all' => 'El campo :attribute debe estar ausente cuando :values están presentes.',
    'multiple_of'      => 'El campo :attribute debe ser un múltiplo de :value.',
    'not_in'           => 'El :attribute seleccionado es inválido.',
    'not_regex'        => 'El formato del campo :attribute es inválido.',
    'numeric'          => 'El campo :attribute debe ser un número.',
    'password'         => [
        'letters'       => 'El campo :attribute debe contener al menos una letra.',
        'mixed'         => 'El campo :attribute debe contener al menos una letra mayúscula y una minúscula.',
        'numbers'       => 'El campo :attribute debe contener al menos un número.',
        'symbols'       => 'El campo :attribute debe contener al menos un símbolo.',
        'uncompromised' => 'El :attribute dado apareció en una filtración de datos. Por favor elige un :attribute diferente.',
    ],
    'present'                => 'El campo :attribute debe estar presente.',
    'present_if'             => 'El campo :attribute debe estar presente cuando :other es :value.',
    'present_unless'         => 'El campo :attribute debe estar presente a menos que :other sea :value.',
    'present_with'           => 'El campo :attribute debe estar presente cuando :values está presente.',
    'present_with_all'       => 'El campo :attribute debe estar presente cuando :values están presentes.',
    'prohibited'             => 'El campo :attribute está prohibido.',
    'prohibited_if'          => 'El campo :attribute está prohibido cuando :other es :value.',
    'prohibited_if_accepted' => 'El campo :attribute está prohibido cuando :other es aceptado.',
    'prohibited_if_declined' => 'El campo :attribute está prohibido cuando :other es rechazado.',
    'prohibited_unless'      => 'El campo :attribute está prohibido a menos que :other esté en :values.',
    'prohibits'              => 'El campo :attribute prohíbe que :other esté presente.',
    'regex'                  => 'El formato del campo :attribute es inválido.',
    'required'               => 'El campo :attribute es obligatorio.',
    'required_array_keys'    => 'El campo :attribute debe contener entradas para: :values.',
    'required_if'            => 'El campo :attribute es obligatorio cuando :other es :value.',
    'required_if_accepted'   => 'El campo :attribute es obligatorio cuando :other es aceptado.',
    'required_if_declined'   => 'El campo :attribute es obligatorio cuando :other es rechazado.',
    'required_unless'        => 'El campo :attribute es obligatorio a menos que :other esté en :values.',
    'required_with'          => 'El campo :attribute es obligatorio cuando :values está presente.',
    'required_with_all'      => 'El campo :attribute es obligatorio cuando :values están presentes.',
    'required_without'       => 'El campo :attribute es obligatorio cuando :values no está presente.',
    'required_without_all'   => 'El campo :attribute es obligatorio cuando ninguno de :values están presentes.',
    'same'                   => 'El campo :attribute debe coincidir con :other.',
    'size'                   => [
        'array'   => 'El campo :attribute debe contener :size elementos.',
        'file'    => 'El campo :attribute debe tener :size kilobytes.',
        'numeric' => 'El campo :attribute debe ser :size.',
        'string'  => 'El campo :attribute debe tener :size caracteres.',
    ],
    'starts_with' => 'El campo :attribute debe comenzar con uno de los siguientes: :values.',
    'string'      => 'El campo :attribute debe ser un string.',
    'timezone'    => 'El campo :attribute debe ser una zona horaria válida.',
    'unique'      => 'El :attribute ya está en uso.',
    'uploaded'    => 'No se pudo subir el :attribute.',
    'uppercase'   => 'El campo :attribute debe estar en mayúsculas.',
    'url'         => 'El campo :attribute debe ser una URL válida.',
    'ulid'        => 'El campo :attribute debe ser un ULID válido.',
    'uuid'        => 'El campo :attribute debe ser un UUID válido.',

    /*
    |--------------------------------------------------------------------------
    | Líneas de Idioma de Validación Personalizadas
    |--------------------------------------------------------------------------
    |
    | Aquí puedes especificar mensajes de validación personalizados para
    | atributos usando la convención "atributo.regla" para nombrar las líneas.
    | Esto hace rápido especificar una línea de idioma personalizada específica.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Nombres de los campos, como se leen
    |--------------------------------------------------------------------------
    */

    'attributes' => [
        'firstname'          => 'nombre',
        'lastname'           => 'apellido',
        'email'              => 'email',
        'password'           => 'contraseña',
        'current_password'   => 'contraseña actual',
        'phone'              => 'teléfono',
        'legal_id'           => 'cédula',
        'about'              => 'sobre ti',
        'name'               => 'nombre',
        'description'        => 'descripción',
        'organization'       => 'organización',
        'identifier'         => 'identificador',
        'unit_type_id'       => 'tipo de vivienda',
        'new_unit_type.name' => 'nombre del tipo de vivienda',
        'family_id'          => 'familia',
        'title'              => 'título',
        'location'           => 'lugar',
        'start_date'         => 'inicio',
        'end_date'           => 'fin',
        'files'              => 'archivos',
        'files.*'            => 'archivo',
        'reason'             => 'motivo',
        'confirmation'       => 'confirmación',
    ],

    /*
    |--------------------------------------------------------------------------
    | Reglas del negocio
    |--------------------------------------------------------------------------
    */

    'already_in_project'        => 'Esta persona ya participa del proyecto con ese rol.',
    'cannot_revoke_self'        => 'No puedes quitarte tu propio rol de superadministrador.',
    'no_account_with_email'     => 'No hay ninguna cuenta con este email.',
    'email_taken'               => 'Otra cuenta ya usa este email.',
    'family_has_unit'           => 'Esta familia ya recibió una vivienda en el sorteo: no se puede quitar.',
    'confirm_with_email'        => 'Escribe tu email exactamente como es.',
    'owner_stays'               => 'Quien es dueño de la plataforma conserva su cuenta.',
    'only_admin_of'             => 'Eres el único administrador de :projects: nombra a otro administrador primero, o borra el proyecto.',
    'last_admin'                => 'Un proyecto necesita al menos un administrador.',
    'member_needs_family'       => 'Quien no es administrador debe pertenecer a una familia.',
    'preferences_foreign_units' => 'Solo puedes ordenar viviendas del tipo de tu familia.',
    'unit_assigned'             => 'Esta vivienda ya fue asignada a una familia en el sorteo: no se puede quitar.',
    'plan_unit_items'           => 'Solo la forma de una vivienda es una vivienda: agrega viviendas desde la paleta del plano o la página de viviendas.',
    'unit_type_in_use'          => 'Todavía hay familias o viviendas con este tipo.',

    /** Límites de los proyectos sin aprobar */
    'limit_families'    => 'Por ahora este proyecto tiene lugar para hasta :max familias: pide ilimitado para quitar el límite.',
    'limit_units'       => 'Por ahora este proyecto tiene lugar para hasta :max viviendas: pide ilimitado para quitar el límite.',
    'limit_members'     => 'Por ahora este proyecto tiene lugar para hasta :max personas: pide ilimitado para quitar el límite.',
    'limit_invitations' => 'Por ahora este proyecto puede enviar hasta :max invitaciones por día: pide ilimitado para quitar el límite.',
    'limit_storage'     => 'Por ahora este proyecto tiene lugar para :max MB de archivos: pide ilimitado para quitar el límite.',
    'image_too_large'   => 'Se pueden compartir imágenes de hasta :max megapíxeles; esta tiene más.',
    'chat_people'       => 'Elige personas del proyecto para conversar.',
    'guests_remain' => 'Quedan invitados (:count, contando invitaciones en camino): primero dales una familia o quítalos.',
    'account_blocked' => 'Esta cuenta está bloqueada: no se la puede invitar.',
    'account_protected' => 'Ni tu propia cuenta ni la de un superadministrador: primero se le quita el rol.',
    'feature_off' => 'Este proyecto tiene desactivado este tipo de archivo.',
];
