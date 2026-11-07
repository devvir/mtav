<?php

return [
    // A conversation whose others all left
    'only_you'          => 'Solo tú',
    'community'         => 'Comunidad',
    'assistant'         => 'Asistente',
    'assistant_off'     => 'Por ahora el Asistente no está disponible acá. Mientras tanto, la guía y el (?) junto a cada título pueden ayudarte.',
    'assistant_limit'   => 'Gracias por preguntar. Por hoy ya te respondí todo lo que podía. Escríbeme mañana y con gusto te ayudo. Hasta entonces, en la guía vas a encontrar muchas respuestas.',
    'report_limit'      => 'Ya enviaste todos los reportes que puedes hoy. Mañana puedes enviar otro.',
    'assistant_busy'    => 'Hoy recibí muchas preguntas y ya no puedo responder más. Prueba de nuevo mañana y con gusto te ayudo. Hasta entonces, en la guía vas a encontrar muchas respuestas.',
    'assistant_trouble' => 'Perdón, ahora no pude responder. Prueba de nuevo en un momento.',
    'assistant_resting' => 'Ahora no puedo responder. Prueba de nuevo dentro de un rato y con gusto te ayudo. Hasta entonces, en la guía vas a encontrar muchas respuestas.',
    'assistant_guide'   => 'Abrir la guía',

    // What the Assistant answers with no model: greetings, thanks, and the questions it offers
    'ready' => [
        'hello_words'    => 'hola|buenas|buen dia|buenos dias|buenas tardes|buenas noches',
        'thanks_words'   => 'gracias|muchas gracias|mil gracias|muchisimas gracias',
        'hello'          => '¡Hola! ¿Qué quieres saber?',
        'thanks'         => 'De nada. Acá estoy cuando me necesites.',
        'can_do'         => 'Acá sigues tu proyecto: su gente, sus viviendas y el sorteo, todo en un mismo lugar.',
        'can_do_rank'    => 'También puedes ordenar las viviendas que le gustarían a tu familia.',
        'can_do_manage'  => 'Como administrador, además pones a punto el proyecto e invitas a la gente.',
        'can_do_action'  => 'Muéstrame más',
        'outside'        => 'Fuera de un proyecto, puedes encontrar uno para sumarte, empezar el tuyo o ajustar tu cuenta. Abre un proyecto para ver todo lo que tiene.',
        'outside_action' => 'Encontrar un proyecto',
        'lottery'        => 'Cada familia ordena las viviendas de su tipo, de la que más quiere a la que menos. Cuando llega la fecha, un administrador hace el sorteo. El sorteo primero cuida a la familia que quedaría peor, y le da lo mejor posible. Después busca la mayor conformidad de todo el proyecto. Solo los empates se resuelven al azar.',
        'lottery_action' => 'Ver cómo funciona',
        'rank'           => 'En Sorteo ordenas las viviendas del tipo de tu familia, de la que más quieres a la que menos. Cada cambio se guarda al momento, para toda la familia, hasta que se haga el sorteo. Solo tu familia ve tu orden.',
        'rank_action'    => 'Ir al sorteo',
        'invite'         => 'Abre la tarjeta de tu familia y toca Invitar a un familiar. Escribe su correo y mándala. Le llega la invitación.',
        'invite_action'  => 'Invitar a alguien',
        'setup_left'     => 'Falta: :steps.',
        'setup_done'     => 'Está todo listo. No falta nada por poner a punto.',
        'setup_action'   => 'Ir a Puesta a punto',
        'steps'          => [
            'unit_types' => 'agregar los tipos de vivienda',
            'units'      => 'agregar las viviendas',
            'families'   => 'invitar a las familias',
            'scheduled'  => 'fijar la fecha del sorteo',
            'lottery'    => 'hacer el sorteo',
        ],
    ],
];
