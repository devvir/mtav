<?php

/**
 * Confirmation messages shown after an action.
 */
return [
    'not_allowed' => 'Non hai il permesso di farlo.',

    /** Accounts */
    'verification_sent'       => 'Ti abbiamo inviato un nuovo link di conferma.',
    'email_verified'          => 'Il tuo indirizzo email è confermato. Ti diamo il benvenuto.',
    'email_verification_sent' => 'Abbiamo inviato un link di conferma a :email. Il tuo indirizzo cambia quando lo confermi.',
    'email_changed'           => 'Il tuo indirizzo email è stato cambiato.',
    'profile_updated'         => 'Il tuo profilo è stato salvato.',
    'password_updated'        => 'La tua password è stata cambiata.',
    'password_confirmed'      => 'Confermato. Ora rifallo.',
    'avatar_updated'          => 'La tua foto è stata cambiata.',

    /** Projects */
    'project_created'    => 'Il tuo progetto è pronto. Inizia aggiungendo i tipi di alloggio.',
    'project_updated'    => 'Il progetto è stato salvato.',
    'project_deleted'    => 'Il progetto è stato eliminato.',
    'project_left'       => 'Non fai più parte di :project.',
    'approval_requested' => 'Abbiamo chiesto al team di :app di togliere i limiti al tuo progetto. Ti faremo sapere presto.',
    'project_approved'   => ':project è approvato: i limiti sono stati tolti.',
    'project_restored'   => ':project è tornato, com’era.',
    'project_purged'     => ':project è stato eliminato definitivamente.',

    /** People */
    'invitation_sent'     => 'Invito inviato a :email.',
    'invitation_revoked'  => 'L’invito è stato ritirato.',
    'invitation_accepted' => 'Ti diamo il benvenuto in :project.',
    'invitation_declined' => 'Hai rifiutato l’invito.',
    'invitation_sign_in'  => 'Questo invito è per :email. Accedi con quell’account per accettarlo.',
    'restored'            => 'È di nuovo qui.',
    'account_blocked'     => ':name è bloccato: fuori da tutti i progetti e senza accesso.',
    'account_unblocked'   => ':name può accedere di nuovo.',
    'account_deleted'     => 'L’account di :name è stato eliminato.',

    'added_to_family'    => ':name ora è nella famiglia :family.',
    'join_requested'     => 'La tua richiesta è arrivata agli amministratori di :project.',
    'join_withdrawn'     => 'Hai ritirato la tua richiesta.',
    'join_accepted'      => ':name ora è nel progetto.',
    'join_declined'      => 'Hai rifiutato la richiesta di :name.',
    'member_updated'     => 'Salvato.',
    'member_removed'     => 'La persona è stata tolta dal progetto.',
    'superadmin_granted' => ':name ora è superamministratore.',
    'superadmin_revoked' => ':name non è più superamministratore.',

    /** Structure */
    'family_created'          => 'La famiglia è stata aggiunta.',
    'family_updated'          => 'La famiglia è stata salvata.',
    'family_deleted'          => 'La famiglia è stata tolta.',
    'family_deleted_for_good' => 'La famiglia è stata eliminata definitivamente.',
    'unit_type_created'       => 'Il tipo di alloggio è stato aggiunto.',
    'unit_type_updated'       => 'Il tipo di alloggio è stato salvato.',
    'unit_type_deleted'       => 'Il tipo di alloggio è stato tolto.',
    'unit_created'            => 'L’alloggio è stato aggiunto.',
    'unit_updated'            => 'L’alloggio è stato salvato.',
    'unit_deleted'            => 'L’alloggio è stato tolto.',
    'plan_updated'            => 'La planimetria è stata salvata.',

    /** Community */
    'event_created'      => 'L’evento è stato creato.',
    'event_updated'      => 'L’evento è stato salvato.',
    'event_deleted'      => 'L’evento è stato annullato.',
    'rsvp_attending'     => 'Bene, ci vediamo lì.',
    'rsvp_not_attending' => 'Grazie per avercelo detto.',
    'media_uploaded'     => 'Il tuo file è condiviso.|I tuoi :count file sono condivisi.',
    'media_updated'      => 'La descrizione è stata salvata.',
    'media_deleted'      => 'Il file è stato tolto.',

    /** Lottery */
    'lottery_scheduled'   => 'La data del sorteggio è stata fissata.',
    'lottery_started'     => 'Il sorteggio è in corso. I risultati compaiono qui tra un momento.',
    'lottery_invalidated' => 'Il sorteggio è stato annullato. Tutti nel progetto possono leggere perché.',

    'notifications_read' => 'Tutte le notifiche sono segnate come lette.',

    'deploy_dev'              => 'Dev è in fase di rilascio da :branch. Seguilo qui sotto.',
    'deploy_production'       => 'La produzione è in fase di rilascio: attende l’approvazione su GitHub.',
    'deploy_rollback'         => ':environment torna alla versione precedente.',
    'deploy_fresh'            => ':environment riparte da zero, con i dati demo. Seguilo qui sotto.',
    'deploy_refused'          => 'GitHub non l’ha avviato (:status). Controlla il token di rilascio.',
    'deploy_unreachable'      => 'GitHub non ha risposto, quindi non è partito nulla. Riprova tra poco.',
    'upload_too_large'        => 'Era troppo da inviare in una volta. Condividi meno file, o più leggeri.',
    'platform_settings_saved' => 'Le impostazioni della piattaforma sono state salvate.',
    'page_expired'            => 'La pagina è rimasta aperta a lungo. Riprova.',
    'too_many_attempts'       => 'Troppi tentativi di seguito. Aspetta un momento e riprova.',
    'changed_meanwhile'       => 'Nel frattempo questo è cambiato. Controlla e riprova.',
    'own_account_deleted'     => 'Il tuo account è stato eliminato.',
    'topic_opened'            => 'Il tuo argomento è aperto.',
    'topic_removed'           => 'L’argomento è stato tolto.',
    'error_solved'            => 'Segnato come risolto.',
    'conversation_left'       => 'Non fai più parte della conversazione.',
    'family_asked'            => 'Hai chiesto di entrare nella famiglia :family.',
    'family_ask_cancelled'    => 'La tua richiesta è stata annullata.',
    'family_ask_accepted'     => ':name ora è nella tua famiglia.',
    'family_ask_declined'     => 'Hai rifiutato :name.',
    'family_ask_undeclined'   => ':name può chiedere di nuovo.',
    'family_left'             => 'Non fai più parte della tua famiglia.',
    'support_account_ready'   => 'Il tuo account di supporto è pronto.',
];
