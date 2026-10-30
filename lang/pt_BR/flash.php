<?php

/**
 * Confirmation messages shown after an action.
 */
return [
    'not_allowed' => 'Você não tem permissão para fazer isso.',

    /** Accounts */
    'verification_sent'       => 'Enviamos um novo link de confirmação para você.',
    'email_verified'          => 'Seu endereço de e-mail está confirmado. Boas-vindas!',
    'email_verification_sent' => 'Enviamos um link de confirmação para :email. Seu endereço muda quando você confirmar.',
    'email_changed'           => 'Seu endereço de e-mail foi alterado.',
    'profile_updated'         => 'Seu perfil foi salvo.',
    'password_updated'        => 'Sua senha foi alterada.',
    'avatar_updated'          => 'Sua foto foi alterada.',

    /** Projects */
    'project_created'    => 'Seu projeto está pronto. Comece adicionando os tipos de unidade.',
    'project_updated'    => 'O projeto foi salvo.',
    'project_deleted'    => 'O projeto foi excluído.',
    'project_left'       => 'Você saiu de :project.',
    'approval_requested' => 'Pedimos à equipe do :app que torne o seu projeto ilimitado. Em breve você terá notícias.',
    'project_approved'   => ':project foi aprovado: os limites foram retirados.',
    'project_restored'   => ':project voltou, como estava.',
    'project_purged'     => ':project foi excluído definitivamente.',

    /** People */
    'invitation_sent'     => 'Convite enviado para :email.',
    'invitation_revoked'  => 'O convite foi retirado.',
    'invitation_accepted' => 'Boas-vindas a :project!',
    'invitation_declined' => 'Você recusou o convite.',
    'invitation_sign_in'  => 'Este convite é para :email. Entre com essa conta para aceitá-lo.',
    'member_updated'      => 'Salvo.',
    'member_removed'      => 'A pessoa foi removida do projeto.',
    'superadmin_granted'  => ':name agora é superadmin.',
    'superadmin_revoked'  => ':name não é mais superadmin.',

    /** Structure */
    'family_created'    => 'A família foi adicionada.',
    'family_updated'    => 'A família foi salva.',
    'family_deleted'    => 'A família foi removida.',
    'unit_type_created' => 'O tipo de unidade foi adicionado.',
    'unit_type_updated' => 'O tipo de unidade foi salvo.',
    'unit_type_deleted' => 'O tipo de unidade foi removido.',
    'unit_created'      => 'A unidade foi adicionada.',
    'unit_updated'      => 'A unidade foi salva.',
    'unit_deleted'      => 'A unidade foi removida.',
    'plan_updated'      => 'A planta foi salva.',

    /** Community */
    'event_created'      => 'O evento foi criado.',
    'event_updated'      => 'O evento foi salvo.',
    'event_deleted'      => 'O evento foi cancelado.',
    'rsvp_attending'     => 'Ótimo, até lá!',
    'rsvp_not_attending' => 'Obrigado por avisar.',
    'media_uploaded'     => 'Seu arquivo foi compartilhado.|Seus :count arquivos foram compartilhados.',
    'media_updated'      => 'A descrição foi salva.',
    'media_deleted'      => 'O arquivo foi removido.',

    /** Lottery */
    'lottery_scheduled'   => 'O sorteio foi agendado.',
    'lottery_started'     => 'O sorteio está em andamento. Os resultados aparecem aqui em instantes.',
    'lottery_invalidated' => 'O sorteio foi anulado. Todos no projeto podem ler por quê.',

    'notifications_read' => 'Todas as notificações foram marcadas como lidas.',

    'deploy_dev'              => 'O dev está sendo implantado a partir de :branch. Acompanhe abaixo.',
    'deploy_production'       => 'A produção está sendo implantada: aguarda a aprovação no GitHub.',
    'deploy_rollback'         => ':environment volta para a versão anterior.',
    'deploy_refused'          => 'O GitHub não iniciou (:status). Verifique o token de implantação.',
    'upload_too_large'        => 'Era demais para enviar de uma vez: compartilhe menos arquivos, ou menores.',
    'platform_settings_saved' => 'As configurações da plataforma foram salvas.',
    'page_expired'            => 'A página ficou aberta por muito tempo: tente de novo.',
    'account_deleted'         => 'Sua conta foi excluída.',
    'topic_opened'            => 'Seu tópico está aberto.',
    'topic_removed'           => 'O tópico foi removido.',
    'error_solved'            => 'Marcado como resolvido.',
];
