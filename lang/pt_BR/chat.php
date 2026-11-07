<?php

return [
    // A conversation whose others all left
    'only_you'          => 'Só você',
    'community'         => 'Comunidade',
    'assistant'         => 'Assistente',
    'assistant_off'     => 'O Assistente ainda não está disponível aqui. Enquanto isso, o guia e o (?) ao lado de cada título podem ajudar.',
    'assistant_limit'   => 'Obrigado por perguntar. Por hoje já respondi tudo o que podia. Escreva de novo amanhã e ajudo com prazer. Até lá, o guia tem muitas respostas.',
    'report_limit'      => 'Você já enviou todos os relatos possíveis hoje. Amanhã pode enviar outro.',
    'assistant_busy'    => 'Recebi muitas perguntas hoje e não consigo responder mais. Tente de novo amanhã e ajudo com prazer. Até lá, o guia tem muitas respostas.',
    'assistant_trouble' => 'Desculpe, não consegui responder agora. Tente de novo em instantes.',
    'assistant_resting' => 'Agora não consigo responder. Tente de novo daqui a pouco e ajudo com prazer. Até lá, o guia tem muitas respostas.',
    'assistant_guide'   => 'Abrir o guia',

    // What the Assistant answers with no model: greetings, thanks, and the questions it offers
    'ready' => [
        'hello_words'    => 'ola|oi|bom dia|boa tarde|boa noite',
        'thanks_words'   => 'obrigado|obrigada|muito obrigado|muito obrigada|valeu',
        'hello'          => 'Olá! O que você quer saber?',
        'thanks'         => 'De nada. Estou aqui quando você precisar.',
        'can_do'         => 'Aqui você acompanha o seu projeto: as pessoas, as moradias e o sorteio, tudo em um só lugar.',
        'can_do_rank'    => 'Você também pode ordenar as moradias que a sua família gostaria de receber.',
        'can_do_manage'  => 'Como administrador, você também prepara o projeto e convida as pessoas.',
        'can_do_action'  => 'Mostre mais',
        'outside'        => 'Fora de um projeto, você encontra um para participar, começa o seu ou ajusta a sua conta. Abra um projeto para ver tudo o que ele tem.',
        'outside_action' => 'Encontrar um projeto',
        'lottery'        => 'Cada família ordena as moradias do seu tipo, da que mais quer para a que menos quer. Na data marcada, um administrador faz o sorteio. O sorteio cuida primeiro da família que ficaria em pior situação e dá a ela o melhor possível. Depois, busca a maior satisfação de todo o projeto. Só os empates são decididos ao acaso.',
        'lottery_action' => 'Ver como funciona',
        'rank'           => 'Em Sorteio você ordena as moradias do tipo da sua família, da que mais quer para a que menos quer. Cada mudança é salva na hora, para a família inteira, até o sorteio ser feito. Só a sua família vê a sua ordem.',
        'rank_action'    => 'Ir ao sorteio',
        'invite'         => 'Abra o cartão da sua família e toque em Convidar um familiar. Escreva o e-mail e envie. A pessoa recebe o convite.',
        'invite_action'  => 'Convidar alguém',
        'setup_left'     => 'Falta: :steps.',
        'setup_done'     => 'Está tudo pronto. Não falta preparar nada.',
        'setup_action'   => 'Ir para Preparação',
        'steps'          => [
            'unit_types' => 'adicionar os tipos de moradia',
            'units'      => 'adicionar as moradias',
            'families'   => 'convidar as famílias',
            'scheduled'  => 'marcar a data do sorteio',
            'lottery'    => 'fazer o sorteio',
        ],
    ],
];
