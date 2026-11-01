<?php

return [
    'greeting'      => '¡Hola!',
    'greeting_name' => '¡Hola, :name!',

    'invitation_subject'      => 'Te invitan a :project',
    'invitation_family'       => ':inviter te invita a sumarte a la familia :family en :project.',
    'invitation_guest'        => ':inviter te invita a sumarte a :project.',
    'invitation_admin'        => ':inviter te invita a ayudar a gestionar :project.',
    'invitation_admin_family' => ':inviter te invita a ayudar a gestionar :project, como integrante de la familia :family.',
    'invitation_action'       => 'Aceptar la invitación',
    'invitation_expiry'       => 'La invitación vale hasta el :date.',
    'invitation_ignore'       => 'Si no la esperabas, podés ignorar este correo.',

    'joined_project_subject' => 'Ya estás en :project',
    'joined_project'         => 'Los administradores de :project aceptaron tu pedido: ya estás adentro. Podés crear tu familia ahí, o alguien de ella te puede sumar.',
    'joined_family_subject'  => 'Ya estás en la familia :family',
    'joined_family'          => ':name te sumó a la familia :family en :project. No tenés que hacer nada más.',
    'joined_action'          => 'Abrir el proyecto',

    'reset_password_subject' => 'Elegí una contraseña nueva',
    'reset_password_intro'   => 'Alguien pidió cambiar la contraseña de tu cuenta.',
    'reset_password_action'  => 'Elegir una contraseña nueva',
    'reset_password_expiry'  => 'El enlace vale por :minutes minutos.',
    'reset_password_ignore'  => 'Si no fuiste vos, ignorá este correo. Tu contraseña sigue igual.',

    'verify_email_subject'   => 'Confirmá tu correo',
    'verify_email_intro'     => 'Confirmá que este es tu correo.',
    'verify_new_email_intro' => 'Confirmá que este es tu correo nuevo. Tu cuenta sigue usando el anterior hasta que lo hagas.',
    'verify_email_action'    => 'Confirmar mi correo',
    'verify_email_ignore'    => 'Si no creaste una cuenta, ignorá este correo.',

    'email_change_subject' => 'El correo de tu cuenta va a cambiar',
    'email_change_intro'   => 'Alguien pidió cambiar el correo de tu cuenta a :email. Cambia cuando se confirme desde esa dirección.',
    'email_change_ignore'  => 'Si no fuiste vos, cambiá tu contraseña ahora. Hasta que se confirme, tu cuenta sigue con este correo.',

    'new_error_subject'      => 'Algo falló en :app',
    'new_error_intro'        => 'La aplicación tuvo un error que no había visto antes:',
    'new_error_action'       => 'Ver los errores',
    'family_request_subject' => ':name pide sumarse a la familia :family',
    'family_request'         => ':name pide sumarse a la familia :family en :project. Cualquiera de la familia puede responder.',
    'family_request_action'  => 'Responder',
];
