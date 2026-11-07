<?php

return [
    // A conversation whose others all left
    'only_you'          => 'Solo tu',
    'community'         => 'Comunità',
    'assistant'         => 'Assistente',
    'assistant_off'     => 'L’Assistente non è ancora disponibile qui. Nel frattempo possono aiutarti la guida e il (?) accanto a ogni titolo.',
    'assistant_limit'   => 'Grazie per le tue domande. Per oggi ti ho risposto quanto potevo. Riscrivimi domani e ti aiuterò volentieri. Fino ad allora, nella guida trovi molte risposte.',
    'report_limit'      => 'Hai già inviato tutte le segnalazioni possibili per oggi. Domani potrai inviarne un’altra.',
    'assistant_busy'    => 'Oggi ho ricevuto molte domande e non posso più rispondere. Riprova domani e ti aiuterò volentieri. Fino ad allora, nella guida trovi molte risposte.',
    'assistant_trouble' => 'Scusa, ora non sono riuscito a rispondere. Riprova tra un momento.',
    'assistant_resting' => 'Ora non posso rispondere. Riprova tra un po’ e ti aiuterò volentieri. Fino ad allora, nella guida trovi molte risposte.',
    'assistant_guide'   => 'Apri la guida',

    // What the Assistant answers with no model: greetings, thanks, and the questions it offers
    'ready' => [
        'hello_words'    => 'ciao|salve|buongiorno|buonasera',
        'thanks_words'   => 'grazie|grazie mille|molte grazie|ti ringrazio',
        'hello'          => 'Ciao. Cosa vorresti sapere?',
        'thanks'         => 'Di niente. Sono qui quando hai bisogno.',
        'can_do'         => 'Qui segui il tuo progetto: le persone, gli alloggi e il sorteggio, tutto in un unico posto.',
        'can_do_rank'    => 'Puoi anche mettere in ordine gli alloggi che la tua famiglia desidera.',
        'can_do_manage'  => 'Come amministratore, prepari anche il progetto e inviti le persone.',
        'can_do_action'  => 'Fammi vedere',
        'outside'        => 'Fuori da un progetto, ne trovi uno a cui unirti, avvii il tuo o sistemi il tuo account. Apri un progetto per vedere tutto quello che contiene.',
        'outside_action' => 'Trova un progetto',
        'lottery'        => 'Ogni famiglia mette in ordine gli alloggi del proprio tipo, dal più desiderato al meno desiderato. Quando arriva la data, un amministratore fa il sorteggio. Il sorteggio pensa prima alla famiglia che starebbe peggio e le dà il meglio possibile. Poi cerca la maggiore soddisfazione per tutto il progetto. Solo i casi di parità si decidono a sorte.',
        'lottery_action' => 'Vedi come funziona',
        'rank'           => 'In Sorteggio metti in ordine gli alloggi del tipo della tua famiglia, dal più desiderato al meno desiderato. Ogni modifica si salva subito, per tutta la famiglia, fino al sorteggio. Solo la tua famiglia vede il tuo ordine.',
        'rank_action'    => 'Vai al sorteggio',
        'invite'         => 'Apri la scheda della tua famiglia e premi Invita un familiare. Scrivi la sua email e invia. Riceverà l’invito.',
        'invite_action'  => 'Invita qualcuno',
        'setup_left'     => 'Resta da fare: :steps.',
        'setup_done'     => 'È tutto pronto: non resta niente da preparare.',
        'setup_action'   => 'Vai a Preparazione',
        'steps'          => [
            'unit_types' => 'aggiungere i tipi di alloggio',
            'units'      => 'aggiungere gli alloggi',
            'families'   => 'invitare le famiglie',
            'scheduled'  => 'fissare la data del sorteggio',
            'lottery'    => 'fare il sorteggio',
        ],
    ],
];
