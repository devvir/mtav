<?php

/**
 * Confirmation messages shown after an action.
 */
return [
    'not_allowed' => 'Você não tem permissão para fazer isso.',

    /** Accounts */
    'verification_sent'       => 'Enviamos um novo link de confirmação para você.',
    'email_verified'          => 'Seu e-mail está confirmado. Boas-vindas.',
    'email_verification_sent' => 'Enviamos um link de confirmação para :email. Seu e-mail muda quando você confirmar.',
    'email_changed'           => 'Seu e-mail foi mudado.',
    'signed_up'               => 'Enviamos um e-mail para :email. Abra o link que está nele para continuar.',
    'profile_updated'         => 'Seu perfil foi salvo.',
    'password_updated'        => 'Sua senha foi mudada.',
    'password_confirmed'      => 'Confirmado. Agora faça de novo.',
    'avatar_updated'          => 'Sua foto foi mudada.',

    /** Projects */
    'project_created'    => 'Seu projeto está pronto. Comece adicionando os tipos de moradia.',
    'project_updated'    => 'O projeto foi salvo.',
    'project_deleted'    => 'O projeto foi excluído.',
    'project_left'       => 'Você saiu de :project.',
    'approval_requested' => 'Pedimos à equipe do :app que torne o seu projeto ilimitado. Em breve você terá notícias.',
    'project_approved'   => ':project foi aprovado: não tem mais limites.',
    'project_restored'   => ':project voltou, como estava.',
    'project_purged'     => ':project foi excluído para sempre.',

    /** People */
    'invitation_sent'     => 'Convite enviado para :email.',
    'invitation_revoked'  => 'O convite foi retirado.',
    'invitation_accepted' => 'Boas-vindas a :project.',
    'invitation_declined' => 'Você recusou o convite.',
    'invitation_sign_in'  => 'Este convite é para :email. Entre com essa conta para aceitá-lo.',
    'restored'            => 'Está de volta.',
    'account_blocked'     => ':name foi bloqueado: fora de todos os projetos e sem poder entrar.',
    'account_unblocked'   => ':name pode entrar de novo.',
    'account_deleted'     => 'A conta de :name foi excluída.',

    'added_to_family'    => ':name agora está na família :family.',
    'join_requested'     => 'Seu pedido chegou aos administradores de :project.',
    'join_withdrawn'     => 'Você retirou seu pedido.',
    'join_accepted'      => ':name agora está no projeto.',
    'join_declined'      => 'Você recusou o pedido de :name.',
    'member_updated'     => 'Salvo.',
    'member_removed'     => 'A pessoa foi removida do projeto.',
    'superadmin_granted' => ':name agora é superadministrador.',
    'superadmin_revoked' => ':name não é mais superadministrador.',

    /** Structure */
    'family_created'          => 'A família foi adicionada.',
    'family_updated'          => 'A família foi salva.',
    'family_deleted'          => 'A família foi removida.',
    'family_deleted_for_good' => 'A família foi excluída definitivamente.',
    'unit_type_created'       => 'O tipo de moradia foi adicionado.',
    'unit_type_updated'       => 'O tipo de moradia foi salvo.',
    'unit_type_deleted'       => 'O tipo de moradia foi removido.',
    'unit_created'            => 'A moradia foi adicionada.',
    'unit_updated'            => 'A moradia foi salva.',
    'unit_deleted'            => 'A moradia foi removida.',
    'plan_updated'            => 'A planta foi salva.',

    /** Community */
    'event_created'      => 'O evento foi criado.',
    'event_updated'      => 'O evento foi salvo.',
    'event_deleted'      => 'O evento foi cancelado.',
    'rsvp_attending'     => 'Ótimo, até lá.',
    'rsvp_not_attending' => 'Obrigado por avisar.',
    'media_uploaded'     => 'Seu arquivo foi compartilhado.|Seus :count arquivos foram compartilhados.',
    'media_updated'      => 'A descrição foi salva.',
    'media_deleted'      => 'O arquivo foi removido.',

    /** Lottery */
    'lottery_scheduled'   => 'A data do sorteio foi marcada.',
    'lottery_started'     => 'O sorteio está em andamento. Os resultados aparecem aqui em instantes.',
    'lottery_invalidated' => 'O sorteio foi anulado. Todos no projeto podem ler por quê.',

    'notifications_read' => 'Todas as notificações foram marcadas como lidas.',

    'deploy_dev'              => 'O dev está sendo implantado a partir de :branch. Acompanhe abaixo.',
    'deploy_production'       => ':environment está sendo implantado: aguarda a aprovação no GitHub.',
    'deploy_rollback'         => ':environment volta para a versão anterior.',
    'deploy_fresh'            => ':environment recomeça do zero, com os dados de demonstração. Acompanhe abaixo.',
    'deploy_refused'          => 'O GitHub não iniciou (:status). Verifique o token de implantação.',
    'deploy_unreachable'      => 'O GitHub não respondeu, então nada foi iniciado. Tente de novo daqui a pouco.',
    'upload_too_large'        => 'Era demais para enviar de uma vez. Compartilhe menos arquivos, ou arquivos menores.',
    'platform_settings_saved' => 'As configurações da plataforma foram salvas.',
    'page_expired'            => 'A página ficou aberta por muito tempo. Tente de novo.',
    'too_many_attempts'       => 'Tentativas demais em sequência. Espere um pouco e tente de novo.',
    'changed_meanwhile'       => 'Isso mudou nesse meio-tempo. Confira e tente de novo.',
    'own_account_deleted'     => 'Sua conta foi excluída.',
    'topic_opened'            => 'Seu tópico está aberto.',
    'topic_removed'           => 'O tópico foi removido.',
    'error_solved'            => 'Marcado como resolvido.',
    'conversation_left'       => 'Você saiu da conversa.',
    'family_asked'            => 'Você pediu para entrar na família :family.',
    'family_ask_cancelled'    => 'Seu pedido foi cancelado.',
    'family_ask_accepted'     => ':name agora está na sua família.',
    'family_ask_declined'     => 'Você recusou :name.',
    'family_ask_undeclined'   => ':name pode pedir de novo.',
    'family_left'             => 'Você saiu da sua família.',
    'support_account_ready'   => 'Sua conta de suporte está pronta.',
];
