<?php

return [
    // A conversation whose others all left
    'only_you'          => 'Solo vos',
    'community'         => 'Comunidad',
    'assistant'         => 'Asistente',
    'assistant_off'     => 'Por ahora el Asistente no está disponible acá. Mientras tanto, la guía y el (?) junto a cada título pueden ayudarte.',
    'assistant_limit'   => 'Gracias por preguntar. Por hoy ya te respondí todo lo que podía. Escribime mañana y con gusto te ayudo. Hasta entonces, en la guía vas a encontrar muchas respuestas.',
    'assistant_busy'    => 'Hoy recibí muchas preguntas y ya no puedo responder más. Probá de nuevo mañana y con gusto te ayudo. Hasta entonces, en la guía vas a encontrar muchas respuestas.',
    'assistant_trouble' => 'Perdón, ahora no pude responder. Probá de nuevo en un momento.',
    'assistant_resting' => 'Ahora no puedo responder. Probá de nuevo dentro de un rato y con gusto te ayudo. Hasta entonces, en la guía vas a encontrar muchas respuestas.',
    'assistant_guide'   => 'Abrir la guía',

    // What the Assistant answers with no model: greetings, thanks, and the questions it offers
    'ready' => [
        'hello_words'    => 'hola|buenas|buen dia|buenos dias|buenas tardes|buenas noches',
        'thanks_words'   => 'gracias|muchas gracias|mil gracias|muchisimas gracias',
        'hello'          => '¡Hola! ¿Qué querés saber?',
        'thanks'         => 'De nada. Acá estoy cuando me necesites.',
        'can_do'         => 'Acá seguís tu proyecto: su gente, sus viviendas y el sorteo, todo en un mismo lugar.',
        'can_do_rank'    => 'También podés ordenar las viviendas que le gustarían a tu familia.',
        'can_do_manage'  => 'Como administrador, además ponés a punto el proyecto e invitás a la gente.',
        'can_do_action'  => 'Mostrame cómo es',
        'lottery'        => 'Cada familia ordena las viviendas de su tipo, de la que más quiere a la que menos. Cuando llega la fecha, un administrador hace el sorteo. El sorteo primero cuida a la familia que quedaría peor, y le da lo mejor posible. Después busca la mayor conformidad de todo el proyecto. Solo los empates se resuelven al azar.',
        'lottery_action' => 'Ver cómo funciona',
        'rank'           => 'En Sorteo ordenás las viviendas del tipo de tu familia, de la que más querés a la que menos. Cada cambio se guarda al momento, para toda la familia, hasta que se haga el sorteo. Solo tu familia ve tu orden.',
        'rank_action'    => 'Ir al sorteo',
        'invite'         => 'Abrí la tarjeta de tu familia y tocá Invitar a un familiar. Escribí su correo y mandala. Le llega la invitación.',
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
