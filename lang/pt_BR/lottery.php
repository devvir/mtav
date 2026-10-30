<?php

return [
    'title'               => 'Sorteio',
    'default_description' => 'O sorteio que dá uma unidade a cada família, a partir das classificações feitas pelas famílias.',

    'locked' => 'O sorteio já foi feito (ou está em andamento): isto não pode mais mudar.',

    'running'               => 'O sorteio está em andamento: unidades e famílias podem mudar quando ele terminar.',
    'execution_failed'      => 'Algo deu errado durante o sorteio. Você pode tentar de novo; se continuar falhando, fale com a equipe da plataforma.',
    'requires_confirmation' => 'Confirme antes de realizar o sorteio.',

    'cannot_run_already_run'      => 'O sorteio já foi feito.',
    'cannot_run_no_worker'        => 'O servidor não consegue realizar o sorteio agora: o trabalho em segundo plano está parado. Já está sendo verificado; tente mais tarde.',
    'cannot_run_not_scheduled'    => 'Defina a data do sorteio antes de realizá-lo.',
    'cannot_run_not_due'          => 'O sorteio não pode ser feito antes da data.',
    'cannot_run_too_few_families' => 'São necessárias pelo menos duas famílias para realizar o sorteio.',
    'cannot_run_too_large'        => 'Por enquanto este projeto tem espaço para sorteios de até :max famílias. Peça ilimitado para tirar o limite.',

    'unit_family_mismatch_intro'  => 'O número de unidades e de famílias é diferente:',
    'mismatch_excess_units'       => ':unit_type tem :units unidades para :families famílias',
    'mismatch_insufficient_units' => ':unit_type tem :units unidades para :families famílias',

    'glpk_execution_failed' => 'Não foi possível calcular a atribuição. Fale com a equipe da plataforma.',
    'glpk_timeout'          => 'O cálculo da atribuição demorou demais e foi interrompido. Tente de novo.',
    'glpk_infeasible'       => 'Não existe atribuição válida para as classificações atuais. Fale com a equipe da plataforma.',
];
