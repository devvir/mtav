<?php

return [
    'title'               => 'Tirage',
    'default_description' => 'Le tirage donne un logement à chaque famille, à partir des classements faits par les familles.',

    'locked'      => 'Le tirage a déjà eu lieu ou est en cours. Cela ne peut plus changer.',
    'family_gone' => 'Famille supprimée n° :id',

    'running'          => 'Le tirage est en cours. Les logements et les familles pourront changer quand il sera terminé.',
    'execution_failed' => 'Un problème est survenu pendant le tirage. Réessayez. Si cela continue, contactez l’équipe de la plateforme.',

    'cannot_run_already_run'      => 'Le tirage a déjà eu lieu.',
    'cannot_run_no_worker'        => 'Le tirage ne peut pas être lancé pour l’instant. Nous nous en occupons. Réessayez plus tard.',
    'cannot_run_not_scheduled'    => 'Fixez la date du tirage avant de le lancer.',
    'cannot_run_not_due'          => 'Le tirage ne peut pas être lancé avant sa date.',
    'cannot_run_too_few_families' => 'Il faut au moins deux familles pour lancer le tirage.',
    'cannot_run_too_large'        => 'Pour l’instant, ce projet permet des tirages jusqu’à :max familles. Demandez un projet illimité pour lever cette limite.',

    'unit_family_mismatch_intro'  => 'Le nombre de logements et le nombre de familles ne sont pas les mêmes :',
    'mismatch_excess_units'       => ':unit_type a :units logements pour :families familles',
    'mismatch_insufficient_units' => ':unit_type a :units logements pour :families familles',

    'glpk_execution_failed' => 'Le tirage n’a pas pu se terminer. Contactez l’équipe de la plateforme.',
    'glpk_timeout'          => 'Le tirage a pris trop de temps et a été arrêté. Réessayez.',
    'glpk_infeasible'       => 'Le tirage ne trouve pas de résultat avec les classements actuels. Contactez l’équipe de la plateforme.',
];
