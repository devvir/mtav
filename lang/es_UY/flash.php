<?php

/**
 * Mensajes de confirmación tras una acción.
 */
return [
    'not_allowed' => 'No tienes permiso para hacer eso.',

    /** Cuentas */
    'verification_sent'       => 'Te mandamos un enlace de confirmación nuevo.',
    'email_verified'          => 'Tu correo quedó confirmado. Te damos la bienvenida.',
    'email_verification_sent' => 'Te mandamos un enlace de confirmación a :email. Tu correo cambia cuando lo confirmes.',
    'email_changed'           => 'Tu correo cambió.',
    'profile_updated'         => 'Tu perfil quedó guardado.',
    'password_updated'        => 'Tu contraseña cambió.',
    'password_confirmed'      => 'Listo. Ahora vuelve a hacerlo.',
    'avatar_updated'          => 'Tu foto cambió.',

    /** Proyectos */
    'project_created'    => 'Tu proyecto está listo. Empieza por agregar sus tipos de vivienda.',
    'project_updated'    => 'El proyecto quedó guardado.',
    'project_deleted'    => 'Se borró el proyecto.',
    'project_left'       => 'Saliste de :project.',
    'approval_requested' => 'Le pedimos al equipo de :app que haga ilimitado tu proyecto. Pronto vas a tener novedades.',
    'project_approved'   => ':project está aprobado: ya no tiene límites.',
    'project_restored'   => ':project volvió, tal como estaba.',
    'project_purged'     => ':project se borró para siempre.',

    /** Personas */
    'invitation_sent'     => 'Mandamos la invitación a :email.',
    'invitation_revoked'  => 'Se retiró la invitación.',
    'invitation_accepted' => 'Te damos la bienvenida a :project.',
    'invitation_declined' => 'Rechazaste la invitación.',
    'invitation_sign_in'  => 'Esta invitación es para :email. Entra con esa cuenta para aceptarla.',
    'restored'            => 'Ya está de vuelta.',
    'account_blocked'     => ':name quedó bloqueado: fuera de todos los proyectos y sin poder entrar.',
    'account_unblocked'   => ':name puede volver a entrar.',
    'account_deleted'     => 'Se borró la cuenta de :name.',

    'added_to_family'    => ':name ya está en la familia :family.',
    'join_requested'     => 'Tu pedido llegó a los administradores de :project.',
    'join_withdrawn'     => 'Retiraste tu pedido.',
    'join_accepted'      => ':name ya está en el proyecto.',
    'join_declined'      => 'Rechazaste el pedido de :name.',
    'member_updated'     => 'Guardado.',
    'member_removed'     => 'Se quitó a la persona del proyecto.',
    'superadmin_granted' => ':name ahora es superadministrador.',
    'superadmin_revoked' => ':name ya no es superadministrador.',

    /** Estructura */
    'family_created'          => 'Se agregó la familia.',
    'family_updated'          => 'La familia quedó guardada.',
    'family_deleted'          => 'Se quitó la familia.',
    'family_deleted_for_good' => 'La familia se eliminó definitivamente.',
    'unit_type_created'       => 'Se agregó el tipo de vivienda.',
    'unit_type_updated'       => 'El tipo de vivienda quedó guardado.',
    'unit_type_deleted'       => 'Se quitó el tipo de vivienda.',
    'unit_created'            => 'Se agregó la vivienda.',
    'unit_updated'            => 'La vivienda quedó guardada.',
    'unit_deleted'            => 'Se quitó la vivienda.',
    'plan_updated'            => 'El plano quedó guardado.',

    /** Comunidad */
    'event_created'      => 'Se creó el evento.',
    'event_updated'      => 'El evento quedó guardado.',
    'event_deleted'      => 'Se canceló el evento.',
    'rsvp_attending'     => 'Bien, nos vemos ahí.',
    'rsvp_not_attending' => 'Gracias por avisar.',
    'media_uploaded'     => 'Compartiste tu archivo.|Compartiste tus :count archivos.',
    'media_updated'      => 'La descripción quedó guardada.',
    'media_deleted'      => 'Se quitó el archivo.',

    /** Sorteo */
    'lottery_scheduled'   => 'Se fijó la fecha del sorteo.',
    'lottery_started'     => 'El sorteo está en marcha. Los resultados aparecen acá en un momento.',
    'lottery_invalidated' => 'Se invalidó el sorteo. Todos en el proyecto pueden leer por qué.',

    'notifications_read' => 'Todas las notificaciones quedaron como leídas.',

    'deploy_dev'              => 'Se está desplegando dev desde :branch. Síguelo abajo.',
    'deploy_production'       => 'Se está desplegando producción: espera la aprobación en GitHub.',
    'deploy_rollback'         => ':environment vuelve a la versión anterior.',
    'deploy_fresh'            => ':environment empieza de cero, con los datos de prueba. Síguelo abajo.',
    'deploy_refused'          => 'GitHub no lo inició (:status). Revisa el token de despliegue.',
    'deploy_unreachable'      => 'GitHub no respondió, así que no se inició nada. Prueba de nuevo en un rato.',
    'upload_too_large'        => 'Era demasiado para mandar de una vez. Comparte menos archivos, o más livianos.',
    'platform_settings_saved' => 'Se guardó la configuración de la plataforma.',
    'page_expired'            => 'La página estuvo abierta mucho tiempo. Prueba de nuevo.',
    'too_many_attempts'       => 'Demasiados intentos seguidos. Espera un momento y prueba de nuevo.',
    'changed_meanwhile'       => 'Esto cambió mientras tanto. Míralo y prueba de nuevo.',
    'own_account_deleted'     => 'Tu cuenta fue borrada.',
    'topic_opened'            => 'Tu tema está abierto.',
    'topic_removed'           => 'Se quitó el tema.',
    'error_solved'            => 'Marcado como resuelto.',
    'conversation_left'       => 'Saliste de la conversación.',
    'family_asked'            => 'Pediste sumarte a la familia :family.',
    'family_ask_cancelled'    => 'Se canceló tu pedido.',
    'family_ask_accepted'     => ':name ya está en tu familia.',
    'family_ask_declined'     => 'Rechazaste a :name.',
    'family_ask_undeclined'   => ':name puede volver a pedirlo.',
    'family_left'             => 'Saliste de tu familia.',
    'support_account_ready'   => 'Tu cuenta de soporte está lista.',
];
