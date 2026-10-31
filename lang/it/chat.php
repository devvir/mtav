<?php

return [
    // A conversation whose others all left
    'only_you'          => 'Solo tu',
    'community'         => 'Comunità',
    'assistant'         => 'Assistente',
    'assistant_off'     => 'L’Assistente non è ancora disponibile qui. Nel frattempo possono aiutarti la guida e il (?) accanto a ogni titolo.',
    'assistant_limit'   => 'Grazie per le tue domande! Per oggi ti ho risposto quanto potevo. Riscrivimi domani e ti aiuterò volentieri. Fino ad allora, nella guida trovi molte risposte.',
    'assistant_busy'    => 'Oggi ho ricevuto moltissime domande e non posso più rispondere. Riprova domani e ti aiuterò volentieri. Fino ad allora, nella guida trovi molte risposte.',
    'assistant_trouble' => 'Scusa, ora non sono riuscito a rispondere. Riprova tra un momento.',
    'assistant_guide'   => 'Apri la guida',

    // What the Assistant answers with no model: greetings, thanks, and the questions it offers
    'ready' => [
        'hello_words'    => 'ciao|salve|buongiorno|buonasera',
        'thanks_words'   => 'grazie|grazie mille|molte grazie|ti ringrazio',
        'hello'          => 'Ciao! Che cosa vorresti sapere?',
        'thanks'         => 'Di niente! Sono qui quando hai bisogno.',
        'can_do'         => 'Qui segui il tuo progetto: le persone, le unità e il sorteggio, tutto in un unico posto.',
        'can_do_rank'    => 'Puoi anche ordinare le unità che la tua famiglia desidera.',
        'can_do_manage'  => 'Come admin, prepari anche il progetto e inviti le persone.',
        'can_do_action'  => 'Fammi vedere',
        'lottery'        => 'Ogni famiglia ordina le unità del proprio tipo, dalla più desiderata alla meno desiderata. Alla data stabilita, un admin avvia il sorteggio: prima rende la famiglia che starebbe peggio il più soddisfatta possibile, poi tutto il progetto il più soddisfatto possibile. Solo i pareggi si decidono a caso.',
        'lottery_action' => 'Vedi come funziona',
        'rank'           => 'In Sorteggio ordini le unità del tipo della tua famiglia, dalla più desiderata alla meno desiderata. Ogni modifica si salva subito, per tutta la famiglia, fino al sorteggio. Solo la tua famiglia vede il tuo ordine.',
        'rank_action'    => 'Vai al sorteggio',
        'invite'         => 'Apri la scheda della tua famiglia e premi Invita un familiare. Scrivi la sua email e invia: riceverà l’invito.',
        'invite_action'  => 'Invita qualcuno',
        'setup_left'     => 'Resta da fare: :steps.',
        'setup_done'     => 'È tutto pronto: non resta niente da configurare.',
        'setup_action'   => 'Vai a Preparazione',
        'steps'          => [
            'unit_types' => 'aggiungere i tipi di unità',
            'units'      => 'aggiungere le unità',
            'families'   => 'registrare le famiglie',
            'members'    => 'invitare i loro componenti',
            'scheduled'  => 'fissare la data del sorteggio',
            'lottery'    => 'avviare il sorteggio',
        ],
    ],
];
