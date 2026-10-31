<?php

return [
    // A conversation whose others all left
    'only_you'          => 'Solo tú',
    'community'         => 'Comunidad',
    'assistant'         => 'Asistente',
    'assistant_off'     => 'Por ahora el Asistente no está disponible aquí. Mientras tanto, la guía y el (?) junto a cada título pueden ayudarte.',
    'assistant_limit'   => '¡Gracias por preguntar! Por hoy ya te respondí todo lo que podía. Escríbeme de nuevo mañana y con gusto te ayudo. Hasta entonces, en la guía vas a encontrar muchas respuestas.',
    'assistant_busy'    => 'Hoy recibí muchísimas preguntas y ya no puedo responder más. Vuelve a intentarlo mañana y con gusto te ayudo. Hasta entonces, en la guía vas a encontrar muchas respuestas.',
    'assistant_trouble' => 'Perdón, ahora no pude responder. Vuelve a intentarlo en un momento.',
    'assistant_guide'   => 'Abrir la guía',

    // What the Assistant answers with no model: greetings, thanks, and the questions it offers
    'ready' => [
        'hello_words'    => 'hola|buenas|buen dia|buenos dias|buenas tardes|buenas noches',
        'thanks_words'   => 'gracias|muchas gracias|mil gracias|muchisimas gracias',
        'hello'          => '¡Hola! ¿Qué te gustaría saber?',
        'thanks'         => '¡De nada! Aquí estoy cuando me necesites.',
        'can_do'         => 'Aquí sigues tu proyecto: su gente, sus viviendas y el sorteo, todo en un mismo lugar.',
        'can_do_rank'    => 'También puedes ordenar las viviendas que le gustarían a tu familia.',
        'can_do_manage'  => 'Como admin, además pones a punto el proyecto e invitas a la gente.',
        'can_do_action'  => 'Muéstrame cómo es',
        'lottery'        => 'Cada familia ordena las viviendas de su tipo, de la que más quiere a la que menos. En la fecha, un admin hace el sorteo: primero hace que a la familia peor ubicada le toque lo mejor posible, y después deja al proyecto entero lo más conforme posible. Solo los empates se resuelven al azar.',
        'lottery_action' => 'Ver cómo funciona',
        'rank'           => 'En Sorteo ordenas las viviendas del tipo de tu familia, de la que más quieres a la que menos. Cada cambio se guarda al instante, para toda la familia, hasta que se haga el sorteo. Nadie fuera de tu familia ve tu orden.',
        'rank_action'    => 'Ir al sorteo',
        'invite'         => 'Abre la tarjeta de tu familia y toca Invitar a un familiar. Escribe su correo y envía: le llega la invitación.',
        'invite_action'  => 'Invitar a alguien',
        'setup_left'     => 'Falta: :steps.',
        'setup_done'     => 'Está todo listo: no falta configurar nada.',
        'setup_action'   => 'Ir a Puesta a punto',
        'steps'          => [
            'unit_types' => 'agregar los tipos de vivienda',
            'units'      => 'agregar las viviendas',
            'families'   => 'registrar las familias',
            'members'    => 'invitar a sus integrantes',
            'scheduled'  => 'fijar la fecha del sorteo',
            'lottery'    => 'hacer el sorteo',
        ],
    ],
];
