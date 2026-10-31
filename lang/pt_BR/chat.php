<?php

return [
    // A conversation whose others all left
    'only_you'          => 'Só você',
    'community'         => 'Comunidade',
    'assistant'         => 'Assistente',
    'assistant_off'     => 'O Assistente ainda não está disponível aqui. Enquanto isso, o guia e o (?) ao lado de cada título podem ajudar.',
    'assistant_limit'   => 'Obrigado por perguntar! Por hoje já respondi tudo o que podia. Escreva de novo amanhã e ajudo com prazer. Até lá, o guia tem muitas respostas.',
    'assistant_busy'    => 'Recebi muitas perguntas hoje e não consigo responder mais. Tente de novo amanhã e ajudo com prazer. Até lá, o guia tem muitas respostas.',
    'assistant_trouble' => 'Desculpe, não consegui responder agora. Tente de novo em instantes.',
    'assistant_guide'   => 'Abrir o guia',

    // What the Assistant answers with no model: greetings, thanks, and the questions it offers
    'ready' => [
        'hello_words'    => 'ola|oi|bom dia|boa tarde|boa noite',
        'thanks_words'   => 'obrigado|obrigada|muito obrigado|muito obrigada|valeu',
        'hello'          => 'Olá! O que você gostaria de saber?',
        'thanks'         => 'De nada! Estou aqui quando precisar.',
        'can_do'         => 'Aqui você acompanha o seu projeto: as pessoas, as unidades e o sorteio, tudo em um só lugar.',
        'can_do_rank'    => 'Você também pode classificar as unidades que a sua família gostaria.',
        'can_do_manage'  => 'Como admin, você também prepara o projeto e convida as pessoas.',
        'can_do_action'  => 'Mostre como é',
        'lottery'        => 'Cada família classifica as unidades do seu tipo, da mais desejada para a menos. Na data marcada, um admin faz o sorteio: primeiro deixa a família que ficaria menos favorecida tão satisfeita quanto possível, depois o projeto inteiro tão satisfeito quanto possível. Só os empates são decididos ao acaso.',
        'lottery_action' => 'Ver como funciona',
        'rank'           => 'Em Sorteio você classifica as unidades do tipo da sua família, da mais desejada para a menos. Cada mudança é salva na hora, para a família inteira, até o sorteio. Só a sua família vê a sua classificação.',
        'rank_action'    => 'Ir ao sorteio',
        'invite'         => 'Abra o cartão da sua família e toque em Convidar um familiar. Escreva o e-mail e envie: o convite chega na hora.',
        'invite_action'  => 'Convidar alguém',
        'setup_left'     => 'Falta: :steps.',
        'setup_done'     => 'Está tudo pronto: não falta configurar nada.',
        'setup_action'   => 'Ir para Preparação',
        'steps'          => [
            'unit_types' => 'adicionar os tipos de unidade',
            'units'      => 'adicionar as unidades',
            'families'   => 'registrar as famílias',
            'members'    => 'convidar os integrantes',
            'scheduled'  => 'marcar a data do sorteio',
            'lottery'    => 'fazer o sorteio',
        ],
    ],
];
