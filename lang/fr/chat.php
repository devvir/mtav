<?php

return [
    // A conversation whose others all left
    'only_you'          => 'Vous seul',
    'community'         => 'Communauté',
    'assistant'         => 'Assistant',
    'assistant_off'     => 'L’Assistant n’est pas encore disponible ici. En attendant, le guide et le (?) à côté de chaque titre peuvent vous aider.',
    'assistant_limit'   => 'Merci pour vos questions. Pour aujourd’hui, j’ai répondu à tout ce que je pouvais. Réécrivez-moi demain et je vous aiderai avec plaisir. D’ici là, le guide contient beaucoup de réponses.',
    'report_limit'      => "Vous avez envoyé tous les signalements possibles aujourd'hui. Vous pourrez en envoyer un autre demain.",
    'assistant_busy'    => 'J’ai reçu beaucoup de questions aujourd’hui et je ne peux plus répondre. Réessayez demain et je vous aiderai avec plaisir. D’ici là, le guide contient beaucoup de réponses.',
    'assistant_trouble' => 'Désolé, je n’ai pas pu répondre pour l’instant. Réessayez dans un moment.',
    'assistant_resting' => 'Je ne peux pas répondre pour le moment. Réessayez un peu plus tard et je vous aiderai avec plaisir. D’ici là, le guide contient beaucoup de réponses.',
    'assistant_guide'   => 'Ouvrir le guide',

    // What the Assistant answers with no model: greetings, thanks, and the questions it offers
    'ready' => [
        'hello_words'    => 'bonjour|salut|bonsoir|coucou',
        'thanks_words'   => 'merci|merci beaucoup|merci bien|grand merci',
        'hello'          => 'Bonjour. Que souhaitez-vous savoir ?',
        'thanks'         => 'Je vous en prie. Je suis là dès que vous avez besoin de moi.',
        'can_do'         => 'Ici, vous suivez votre projet : ses membres, ses logements et le tirage, au même endroit.',
        'can_do_rank'    => 'Vous pouvez aussi classer les logements que votre famille souhaite.',
        'can_do_manage'  => 'En tant qu’administrateur, vous mettez aussi le projet en place et vous invitez les gens.',
        'can_do_action'  => 'Faire le tour',
        'outside'        => 'Hors d’un projet, vous en trouvez un à rejoindre, lancez le vôtre ou réglez votre compte. Ouvrez un projet pour voir tout ce qu’il contient.',
        'outside_action' => 'Trouver un projet',
        'lottery'        => 'Chaque famille classe les logements de son type, du plus souhaité au moins souhaité. À la date prévue, un administrateur lance le tirage. Le tirage donne d’abord le meilleur possible à la famille qui serait la moins bien lotie, puis rend l’ensemble du projet aussi satisfait que possible. Seules les égalités se règlent au hasard.',
        'lottery_action' => 'Voir comment ça marche',
        'rank'           => 'Dans Tirage, vous classez les logements du type de votre famille, du plus souhaité au moins souhaité. Chaque changement s’enregistre aussitôt, pour toute la famille, jusqu’au tirage. Seule votre famille voit votre classement.',
        'rank_action'    => 'Aller au tirage',
        'invite'         => 'Ouvrez la fiche de votre famille et appuyez sur Inviter un membre de la famille. Écrivez son e-mail et envoyez. La personne reçoit l’invitation.',
        'invite_action'  => 'Inviter quelqu’un',
        'setup_left'     => 'Reste à faire : :steps.',
        'setup_done'     => 'Tout est prêt : il ne reste rien à mettre en place.',
        'setup_action'   => 'Aller à Mise en place',
        'steps'          => [
            'unit_types' => 'ajouter les types de logement',
            'units'      => 'ajouter les logements',
            'families'   => 'inviter les familles',
            'scheduled'  => 'fixer la date du tirage',
            'lottery'    => 'lancer le tirage',
        ],
    ],
];
