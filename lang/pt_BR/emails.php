<?php

return [
    'greeting'      => 'Olá!',
    'greeting_name' => 'Olá, :name!',

    'invitation_subject'      => 'Você recebeu um convite para :project',
    'invitation_family'       => ':inviter convida você para entrar na família :family em :project.',
    'invitation_guest'        => ':inviter convida você para entrar em :project.',
    'invitation_admin'        => ':inviter convida você para ajudar a administrar :project.',
    'invitation_admin_family' => ':inviter convida você para ajudar a administrar :project, como integrante da família :family.',
    'invitation_action'       => 'Aceitar o convite',
    'invitation_expiry'       => 'O convite vale até :date.',
    'invitation_ignore'       => 'Se você não esperava este convite, pode ignorar este e-mail.',

    'joined_project_subject' => 'Você já está em :project',
    'joined_project'         => 'Os administradores de :project aceitaram seu pedido: você já está no projeto. Crie sua família lá, ou alguém dela pode adicionar você.',
    'joined_family_subject'  => 'Você já está na família :family',
    'joined_family'          => ':name adicionou você à família :family em :project. Não é preciso fazer mais nada.',
    'joined_action'          => 'Abrir o projeto',

    'reset_password_subject' => 'Escolha uma nova senha',
    'reset_password_intro'   => 'Alguém pediu para mudar a senha da sua conta.',
    'reset_password_action'  => 'Escolher uma nova senha',
    'reset_password_expiry'  => 'O link vale por :minutes minutos.',
    'reset_password_ignore'  => 'Se não foi você, ignore este e-mail. Sua senha continua a mesma.',

    'verify_email_subject'   => 'Confirme seu e-mail',
    'verify_email_intro'     => 'Confirme que este é o seu e-mail.',
    'verify_new_email_intro' => 'Confirme que este é o seu novo e-mail. Sua conta continua usando o antigo até você confirmar.',
    'verify_email_action'    => 'Confirmar meu e-mail',
    'verify_email_ignore'    => 'Se você não criou uma conta, ignore este e-mail.',

    'email_change_subject' => 'O e-mail da sua conta vai mudar',
    'email_change_intro'   => 'Alguém pediu para mudar o e-mail da sua conta para :email. Ele muda quando a confirmação for feita nesse endereço.',
    'email_change_ignore'  => 'Se não foi você, mude sua senha agora. Até a confirmação, sua conta continua com este e-mail.',

    'new_error_subject'      => 'Algo deu errado no :app',
    'new_error_intro'        => 'O aplicativo teve um erro que não tinha visto antes:',
    'new_error_action'       => 'Ver os erros',
    'family_request_subject' => ':name pede para entrar na família :family',
    'family_request'         => ':name pede para entrar na família :family em :project. Qualquer pessoa da família pode responder.',
    'family_request_action'  => 'Responder',
];
