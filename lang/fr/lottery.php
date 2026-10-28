<?php

return [
    'title'               => 'Tirage',
    'default_description' => 'Le tirage qui donne un logement à chaque famille, à partir des classements établis par les familles.',

    'locked'                => 'Le tirage a déjà eu lieu (ou est en cours) : cela ne peut plus changer.',
    'execution_failed'      => 'Un problème est survenu pendant le tirage. Vous pouvez réessayer ; si l’échec persiste, contactez l’équipe de la plateforme.',
    'requires_confirmation' => 'Veuillez confirmer avant de réaliser le tirage.',

    'cannot_run_already_run'      => 'Le tirage a déjà eu lieu.',
    'cannot_run_not_scheduled'    => 'Fixez la date du tirage avant de le réaliser.',
    'cannot_run_not_due'          => 'Le tirage ne peut pas avoir lieu avant sa date.',
    'cannot_run_too_few_families' => 'Il faut au moins deux familles pour réaliser le tirage.',
    'cannot_run_too_large'        => 'Les projets en attente d’approbation peuvent réaliser des tirages d’au plus :max familles. Demandez l’approbation pour lever cette limite.',

    'unit_family_mismatch_intro'  => 'Le nombre de logements et de familles diffère :',
    'mismatch_excess_units'       => ':unit_type a :units logements pour :families familles',
    'mismatch_insufficient_units' => ':unit_type a :units logements pour :families familles',

    'glpk_execution_failed' => 'L’attribution n’a pas pu être calculée. Veuillez contacter l’équipe de la plateforme.',
    'glpk_timeout'          => 'Le calcul de l’attribution a pris trop de temps et a été arrêté. Veuillez réessayer.',
    'glpk_infeasible'       => 'Aucune attribution valable n’existe avec les classements actuels. Veuillez contacter l’équipe de la plateforme.',
];
