<?php

/**
 * Confirmation messages shown after an action.
 */
return [
    'not_allowed' => 'Vous n’êtes pas autorisé à faire cela.',

    /** Accounts */
    'verification_sent'       => 'Nous vous avons envoyé un nouveau lien de confirmation.',
    'email_verified'          => 'Votre adresse e-mail est confirmée. Bienvenue !',
    'email_verification_sent' => 'Nous avons envoyé un lien de confirmation à :email. Votre adresse change dès que vous la confirmez.',
    'email_changed'           => 'Votre adresse e-mail a été changée.',
    'profile_updated'         => 'Votre profil a été enregistré.',
    'password_updated'        => 'Votre mot de passe a été changé.',
    'avatar_updated'          => 'Votre photo a été changée.',

    /** Projects */
    'project_created'    => 'Votre projet est prêt. Commencez par ajouter ses types de logement.',
    'project_updated'    => 'Le projet a été enregistré.',
    'project_deleted'    => 'Le projet a été supprimé.',
    'project_left'       => 'Vous avez quitté :project.',
    'approval_requested' => 'Nous avons demandé à l’équipe :app de rendre votre projet illimité. Vous aurez bientôt de nos nouvelles.',
    'project_approved'   => ':project est approuvé : ses limites sont levées.',
    'project_restored'   => ':project est de retour, tel qu’il était.',
    'project_purged'     => ':project a été supprimé définitivement.',

    /** People */
    'invitation_sent'     => 'Invitation envoyée à :email.',
    'invitation_revoked'  => 'L’invitation a été retirée.',
    'invitation_accepted' => 'Bienvenue dans :project !',
    'invitation_declined' => 'Vous avez décliné l’invitation.',
    'invitation_sign_in'  => 'Cette invitation est pour :email. Connectez-vous avec ce compte pour l’accepter.',
    'member_updated'      => 'Enregistré.',
    'member_removed'      => 'La personne a été retirée du projet.',
    'superadmin_granted'  => ':name est maintenant superadmin.',
    'superadmin_revoked'  => ':name n’est plus superadmin.',

    /** Structure */
    'family_created'    => 'La famille a été ajoutée.',
    'family_updated'    => 'La famille a été enregistrée.',
    'family_deleted'    => 'La famille a été retirée.',
    'unit_type_created' => 'Le type de logement a été ajouté.',
    'unit_type_updated' => 'Le type de logement a été enregistré.',
    'unit_type_deleted' => 'Le type de logement a été retiré.',
    'unit_created'      => 'Le logement a été ajouté.',
    'unit_updated'      => 'Le logement a été enregistré.',
    'unit_deleted'      => 'Le logement a été retiré.',
    'plan_updated'      => 'Le plan a été enregistré.',

    /** Community */
    'event_created'      => 'L’événement a été créé.',
    'event_updated'      => 'L’événement a été enregistré.',
    'event_deleted'      => 'L’événement a été annulé.',
    'rsvp_attending'     => 'Parfait, à bientôt !',
    'rsvp_not_attending' => 'Merci de nous avoir prévenus.',
    'media_uploaded'     => 'Votre fichier est partagé.|Vos :count fichiers sont partagés.',
    'media_updated'      => 'La description a été enregistrée.',
    'media_deleted'      => 'Le fichier a été retiré.',

    /** Lottery */
    'lottery_scheduled'   => 'Le tirage a été programmé.',
    'lottery_started'     => 'Le tirage est en cours. Les résultats apparaissent ici dans un instant.',
    'lottery_invalidated' => 'Le tirage a été annulé. Tout le monde dans le projet peut lire pourquoi.',

    'notifications_read' => 'Toutes les notifications sont marquées comme lues.',

    'deploy_dev'              => 'Dev est en cours de déploiement depuis :branch. Suivez-le ci-dessous.',
    'deploy_production'       => 'La production est en cours de déploiement : elle attend l’approbation sur GitHub.',
    'deploy_rollback'         => ':environment revient à la version précédente.',
    'deploy_fresh'            => ':environment repart de zéro, avec les données de démo. Suivez-le ci-dessous.',
    'deploy_refused'          => 'GitHub ne l’a pas lancé (:status). Vérifiez le jeton de déploiement.',
    'upload_too_large'        => 'C’était trop à envoyer d’un coup : partagez moins de fichiers, ou des plus légers.',
    'platform_settings_saved' => 'Les réglages de la plateforme ont été enregistrés.',
    'page_expired'            => 'La page est restée ouverte longtemps : réessayez.',
    'account_deleted'         => 'Votre compte a été supprimé.',
    'topic_opened'            => 'Votre sujet est ouvert.',
    'topic_removed'           => 'Le sujet a été retiré.',
    'error_solved'            => 'Marquée comme résolue.',
    'conversation_left'       => 'Vous avez quitté la conversation.',
];
