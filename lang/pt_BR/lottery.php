<?php

return [
    'title'               => 'Sorteio',
    'default_description' => 'O sorteio dá uma moradia a cada família, a partir da ordem de preferência de cada uma.',

    'locked'      => 'O sorteio já foi feito, ou está sendo feito. Isto não pode mais mudar.',
    'family_gone' => 'Família excluída nº :id',

    'running'               => 'O sorteio está em andamento. As moradias e as famílias podem mudar quando ele terminar.',
    'execution_failed'      => 'Algo deu errado ao fazer o sorteio. Tente de novo. Se continuar falhando, fale com a equipe da plataforma.',

    'cannot_run_already_run'      => 'O sorteio já foi feito.',
    'cannot_run_no_worker'        => 'Não é possível fazer o sorteio agora. Já estamos verificando. Tente mais tarde.',
    'cannot_run_not_scheduled'    => 'Marque a data do sorteio antes de fazê-lo.',
    'cannot_run_not_due'          => 'O sorteio não pode ser feito antes da data.',
    'cannot_run_too_few_families' => 'É preciso pelo menos duas famílias para fazer o sorteio.',
    'cannot_run_too_large'        => 'Por enquanto este projeto tem espaço para sorteios de até :max famílias. Peça ilimitado para tirar o limite.',

    'unit_family_mismatch_intro'  => 'O número de moradias e de famílias é diferente:',
    'mismatch_excess_units'       => ':unit_type tem :units moradias para :families famílias',
    'mismatch_insufficient_units' => ':unit_type tem :units moradias para :families famílias',

    'glpk_execution_failed' => 'O sorteio não conseguiu terminar. Fale com a equipe da plataforma.',
    'glpk_timeout'          => 'O sorteio demorou demais e foi interrompido. Tente de novo.',
    'glpk_infeasible'       => 'O sorteio não encontra um resultado com as preferências atuais. Fale com a equipe da plataforma.',
];
