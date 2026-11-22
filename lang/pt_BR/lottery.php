<?php

return [
    'title'               => 'Sorteio',
    'default_description' => 'O sorteio dá uma moradia a cada família, a partir da ordem de preferência de cada uma.',

    'locked'      => 'O sorteio já foi feito, ou está sendo feito. Isto não pode mais mudar.',
    'family_gone' => 'Família excluída nº :id',

    'running'          => 'O sorteio está em andamento. As moradias e as famílias podem mudar quando ele terminar.',
    'execution_failed' => 'Algo deu errado ao fazer o sorteio. Tente de novo. Se continuar falhando, fale com a equipe da plataforma.',

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

    /** The whole record, downloaded as an archive: its files' headings and its README */
    'audit_family'             => 'Família',
    'audit_unit'               => 'Moradia',
    'audit_place'              => 'Lugar',
    'audit_of'                 => 'De',
    'audit_by'                 => 'Alterado por',
    'audit_from'               => 'De',
    'audit_until'              => 'Até',
    'audit_ranking'            => 'Ordem (a mais desejada primeiro)',
    'audit_readme_title'       => 'O SORTEIO DE :project — SEU REGISTRO COMPLETO',
    'audit_readme_run'         => 'Execução :uuid, terminada em :date.',
    'audit_readme_fingerprint' => 'Impressão digital (SHA-256 da entrada e do resultado, tal como a plataforma os guarda): :fingerprint',
    'audit_readme_files'       => 'O que há aqui:
- input.json: o que o sorteio leu, por tipo de moradia: suas moradias e suas famílias, a ordem de cada família, a mais desejada primeiro.
- result.csv: a moradia que cada família recebeu, e o lugar que ela ocupava na ordem da família (1 é a primeira opção).
- ranking-changes.csv: como cada ordem mudou antes do sorteio: quem a deixou como, e quando.
- models/: os modelos GLPK com que o sorteio é resolvido (GMPL). A fase 1 deixa o pior lugar o melhor possível; a fase 2 o conserva e deixa todos os lugares os melhores possíveis em conjunto.
- solves/: cada resolução do solver, em ordem: o que lhe foi pedido e o que encontrou (solve.json), e os arquivos de modelo, dados e solução trocados com o GLPK.
- record.json: o rastro bruto que a plataforma escreveu enquanto o sorteio rodava, nunca alterado, com o manifesto (ids), cada passo e o resultado.',
    'audit_readme_how'     => 'Para verificar o resultado, leia input.json ao lado de result.csv: a nenhuma família se podia dar um pior lugar melhor e, mantido isso, não há lugares melhores no conjunto. Para rodá-lo de novo, o GLPK (glpsol) toma os arquivos de modelo e dados de cada resolução: glpsol --math modelo.mod --data dados.dat. Onde as ordens empatam, o solver pode escolher outra atribuição tão boa quanto esta.',
    'audit_readme_privacy' => 'Nada aqui é o e-mail, o telefone ou o documento de ninguém: o registro nomeia pessoas e famílias, e isso lhe basta.',
];
