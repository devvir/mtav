<?php

return [
    // A conversation whose others all left
    'only_you'          => 'Only you',
    'community'         => 'Community',
    'assistant'         => 'Assistant',
    'assistant_off'     => 'The Assistant is not available here yet. The guide and the (?) next to each title can help in the meantime.',
    'assistant_limit'   => 'Thank you for asking! I have answered all I can for you today. Please write again tomorrow and I will gladly help. Until then, the guide has plenty of answers.',
    'assistant_busy'    => 'I have had a lot of questions today and cannot answer any more. Please try again tomorrow, and I will gladly help. Until then, the guide has plenty of answers.',
    'assistant_trouble' => 'Sorry, I could not answer just now. Please try again in a moment.',
    'assistant_guide'   => 'Open the guide',

    // What the Assistant answers with no model: greetings, thanks, and the questions it offers
    'ready' => [
        'hello_words'    => 'hello|hi|hey|good morning|good afternoon|good evening',
        'thanks_words'   => 'thanks|thank you|thanks a lot|thank you very much|many thanks',
        'hello'          => 'Hello! What would you like to know?',
        'thanks'         => 'You are welcome! I am here whenever you need me.',
        'can_do'         => 'Here you follow your project: its people, its units and the lottery, all in one place.',
        'can_do_rank'    => 'You can also rank the units your family would like.',
        'can_do_manage'  => 'As an admin, you also set the project up and invite people.',
        'can_do_action'  => 'Show me around',
        'lottery'        => 'Each family ranks the units of its type, from the most to the least wanted. On its date, an admin runs the lottery: it first makes the family that would be worst off as well off as possible, then the whole project as satisfied as possible. Only ties are broken at random.',
        'lottery_action' => 'See how it works',
        'rank'           => 'In Lottery you rank the units of your family\'s type, from the most to the least wanted. Every change is saved at once, for the whole family, until the lottery runs. Nobody but your family sees your ranking.',
        'rank_action'    => 'Go to the lottery',
        'invite'         => 'Open your family\'s card and press Invite a family member. Write their email and send: they receive the invitation.',
        'invite_action'  => 'Invite someone',
        'setup_left'     => 'Still to do: :steps.',
        'setup_done'     => 'Everything is ready: nothing is left to set up.',
        'setup_action'   => 'Go to Setup',
        'steps'          => [
            'unit_types' => 'add the unit types',
            'units'      => 'add the units',
            'families'   => 'register the families',
            'members'    => 'invite their members',
            'scheduled'  => 'schedule the lottery',
            'lottery'    => 'run the lottery',
        ],
    ],
];
