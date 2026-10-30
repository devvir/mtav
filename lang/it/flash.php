<?php

/**
 * Confirmation messages shown after an action.
 */
return [
    'not_allowed' => 'Non hai il permesso di farlo.',

    /** Accounts */
    'verification_sent'       => 'Ti abbiamo inviato un nuovo link di conferma.',
    'email_verified'          => 'Il tuo indirizzo email è confermato. Benvenuto!',
    'email_verification_sent' => 'Abbiamo inviato un link di conferma a :email. Il tuo indirizzo cambia quando lo confermi.',
    'email_changed'           => 'Il tuo indirizzo email è stato cambiato.',
    'profile_updated'         => 'Il tuo profilo è stato salvato.',
    'password_updated'        => 'La tua password è stata cambiata.',
    'avatar_updated'          => 'La tua foto è stata cambiata.',

    /** Projects */
    'project_created'    => 'Il tuo progetto è pronto. Inizia aggiungendo i suoi tipi di unità.',
    'project_updated'    => 'Il progetto è stato salvato.',
    'project_deleted'    => 'Il progetto è stato eliminato.',
    'project_left'       => 'Hai lasciato :project.',
    'approval_requested' => 'Abbiamo chiesto al team di :app di rendere illimitato il tuo progetto. Ti faremo sapere presto.',
    'project_approved'   => ':project è approvato: i suoi limiti sono stati tolti.',
    'project_restored'   => ':project è tornato, com’era.',
    'project_purged'     => ':project è stato eliminato definitivamente.',

    /** People */
    'invitation_sent'     => 'Invito inviato a :email.',
    'invitation_revoked'  => 'L’invito è stato ritirato.',
    'invitation_accepted' => 'Benvenuto in :project!',
    'invitation_declined' => 'Hai rifiutato l’invito.',
    'invitation_sign_in'  => 'Questo invito è per :email. Accedi con quell’account per accettarlo.',
    'member_updated'      => 'Salvato.',
    'member_removed'      => 'La persona è stata tolta dal progetto.',
    'superadmin_granted'  => ':name ora è superadmin.',
    'superadmin_revoked'  => ':name non è più superadmin.',

    /** Structure */
    'family_created'    => 'La famiglia è stata aggiunta.',
    'family_updated'    => 'La famiglia è stata salvata.',
    'family_deleted'    => 'La famiglia è stata tolta.',
    'unit_type_created' => 'Il tipo di unità è stato aggiunto.',
    'unit_type_updated' => 'Il tipo di unità è stato salvato.',
    'unit_type_deleted' => 'Il tipo di unità è stato tolto.',
    'unit_created'      => 'L’unità è stata aggiunta.',
    'unit_updated'      => 'L’unità è stata salvata.',
    'unit_deleted'      => 'L’unità è stata tolta.',
    'plan_updated'      => 'La planimetria è stata salvata.',

    /** Community */
    'event_created'      => 'L’evento è stato creato.',
    'event_updated'      => 'L’evento è stato salvato.',
    'event_deleted'      => 'L’evento è stato annullato.',
    'rsvp_attending'     => 'Ottimo, ci vediamo lì!',
    'rsvp_not_attending' => 'Grazie per avercelo detto.',
    'media_uploaded'     => 'Il tuo file è condiviso.|I tuoi :count file sono condivisi.',
    'media_updated'      => 'La descrizione è stata salvata.',
    'media_deleted'      => 'Il file è stato tolto.',

    /** Lottery */
    'lottery_scheduled'   => 'Il sorteggio è stato programmato.',
    'lottery_started'     => 'Il sorteggio è in corso. I risultati compaiono qui tra un momento.',
    'lottery_invalidated' => 'Il sorteggio è stato annullato. Tutti nel progetto possono leggere perché.',

    'notifications_read' => 'Tutte le notifiche sono segnate come lette.',

    'deploy_dev'              => 'Dev è in fase di rilascio da :branch. Seguilo qui sotto.',
    'deploy_production'       => 'La produzione è in fase di rilascio: attende l’approvazione su GitHub.',
    'deploy_rollback'         => ':environment torna alla versione precedente.',
    'deploy_refused'          => 'GitHub non l’ha avviato (:status). Controlla il token di rilascio.',
    'upload_too_large'        => 'Era troppo da inviare in una volta: condividi meno file, o più leggeri.',
    'platform_settings_saved' => 'Le impostazioni della piattaforma sono state salvate.',
];
