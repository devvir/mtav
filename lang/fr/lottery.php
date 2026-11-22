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

    /** The whole record, downloaded as an archive: its files' headings and its README */
    'audit_family'             => 'Famille',
    'audit_unit'               => 'Logement',
    'audit_place'              => 'Place',
    'audit_of'                 => 'Sur',
    'audit_by'                 => 'Modifié par',
    'audit_from'               => 'Du',
    'audit_until'              => 'Au',
    'audit_ranking'            => 'Classement (le plus souhaité d’abord)',
    'audit_readme_title'       => 'LE TIRAGE DE :project — SON REGISTRE COMPLET',
    'audit_readme_run'         => 'Exécution :uuid, terminée le :date.',
    'audit_readme_fingerprint' => 'Empreinte (SHA-256 de l’entrée et du résultat, tels que la plateforme les conserve) : :fingerprint',
    'audit_readme_files'       => 'Ce qui s’y trouve :
- input.json : ce que le tirage a lu, par type de logement : ses logements et ses familles, le classement de chaque famille, le plus souhaité d’abord.
- result.csv : le logement reçu par chaque famille, et sa place dans le classement de la famille (1 est un premier choix).
- ranking-changes.csv : comment chaque classement a changé avant le tirage : qui l’a laissé comment, et quand.
- models/ : les modèles GLPK avec lesquels le tirage est résolu (GMPL). La phase 1 rend la pire place aussi bonne que possible ; la phase 2 la conserve et rend toutes les places aussi bonnes que possible ensemble.
- solves/ : chaque résolution du solveur, dans l’ordre : ce qui lui a été demandé et ce qu’il a trouvé (solve.json), et les fichiers de modèle, de données et de solution échangés avec GLPK.
- record.json : la trace brute écrite par la plateforme pendant le tirage, jamais modifiée, avec le manifeste (ids), chaque étape et le résultat.',
    'audit_readme_how'     => 'Pour vérifier le résultat, lisez input.json à côté de result.csv : aucune famille ne pouvait recevoir une meilleure pire place, et, cela conservé, pas de meilleures places dans l’ensemble. Pour le relancer, GLPK (glpsol) prend les fichiers de modèle et de données de chaque résolution : glpsol --math modele.mod --data donnees.dat. Là où les classements sont à égalité, le solveur peut choisir une autre affectation aussi bonne que celle-ci.',
    'audit_readme_privacy' => 'Rien ici n’est l’e-mail, le téléphone ou le numéro de pièce d’identité de quiconque : le registre nomme des personnes et des familles, et cela lui suffit.',
];
