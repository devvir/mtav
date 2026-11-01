<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted'        => 'Você precisa aceitar :attribute.',
    'accepted_if'     => 'Você precisa aceitar :attribute quando :other for :value.',
    'active_url'      => 'O campo :attribute deve ser uma URL válida.',
    'after'           => 'O campo :attribute deve ser uma data posterior a :date.',
    'after_or_equal'  => 'O campo :attribute deve ser uma data posterior ou igual a :date.',
    'alpha'           => 'O campo :attribute deve conter apenas letras.',
    'alpha_dash'      => 'O campo :attribute deve conter apenas letras, números, hífens e sublinhados.',
    'alpha_num'       => 'O campo :attribute deve conter apenas letras e números.',
    'any_of'          => 'O campo :attribute não é válido.',
    'array'           => 'O campo :attribute deve ser uma lista.',
    'ascii'           => 'O campo :attribute deve conter apenas caracteres alfanuméricos e símbolos simples.',
    'before'          => 'O campo :attribute deve ser uma data anterior a :date.',
    'before_or_equal' => 'O campo :attribute deve ser uma data anterior ou igual a :date.',
    'between'         => [
        'array'   => 'O campo :attribute deve ter entre :min e :max itens.',
        'file'    => 'O campo :attribute deve ter entre :min e :max kilobytes.',
        'numeric' => 'O campo :attribute precisa estar entre :min e :max.',
        'string'  => 'O campo :attribute precisa ter entre :min e :max caracteres.',
    ],
    'boolean'           => 'O campo :attribute deve ser verdadeiro ou falso.',
    'can'               => 'O campo :attribute contém um valor não permitido.',
    'confirmed'         => 'Os dois campos de :attribute não são iguais. Tente de novo.',
    'contains'          => 'Falta um valor obrigatório no campo :attribute.',
    'current_password'  => 'Essa senha não está certa.',
    'date'              => 'O campo :attribute não tem uma data válida. Confira e tente de novo.',
    'date_equals'       => 'O campo :attribute deve ser uma data igual a :date.',
    'date_format'       => 'O campo :attribute deve seguir o formato :format.',
    'decimal'           => 'O campo :attribute deve ter :decimal casas decimais.',
    'declined'          => 'Você precisa recusar :attribute.',
    'declined_if'       => 'Você precisa recusar :attribute quando :other for :value.',
    'different'         => 'Os campos :attribute e :other devem ser diferentes.',
    'digits'            => 'O campo :attribute deve ter :digits dígitos.',
    'digits_between'    => 'O campo :attribute deve ter entre :min e :max dígitos.',
    'dimensions'        => 'As dimensões da imagem do campo :attribute não são válidas.',
    'distinct'          => 'O campo :attribute tem um valor repetido.',
    'doesnt_end_with'   => 'O campo :attribute não deve terminar com nenhum destes: :values.',
    'doesnt_start_with' => 'O campo :attribute não deve começar com nenhum destes: :values.',
    'email'             => 'O campo :attribute não parece um e-mail. Confira e tente de novo.',
    'ends_with'         => 'O campo :attribute deve terminar com um destes: :values.',
    'enum'              => 'Escolha uma das opções de :attribute.',
    'exists'            => 'Escolha uma das opções de :attribute.',
    'extensions'        => 'O campo :attribute deve ter uma destas extensões: :values.',
    'file'              => 'O campo :attribute deve ser um arquivo.',
    'filled'            => 'O campo :attribute deve ter um valor.',
    'gt'                => [
        'array'   => 'O campo :attribute deve ter mais de :value itens.',
        'file'    => 'O campo :attribute deve ter mais de :value kilobytes.',
        'numeric' => 'O campo :attribute deve ser maior que :value.',
        'string'  => 'O campo :attribute deve ter mais de :value caracteres.',
    ],
    'gte' => [
        'array'   => 'O campo :attribute deve ter :value itens ou mais.',
        'file'    => 'O campo :attribute deve ter pelo menos :value kilobytes.',
        'numeric' => 'O campo :attribute deve ser maior ou igual a :value.',
        'string'  => 'O campo :attribute deve ter pelo menos :value caracteres.',
    ],
    'hex_color'     => 'O campo :attribute deve ser uma cor hexadecimal válida.',
    'image'         => 'O campo :attribute precisa ser uma imagem.',
    'in'            => 'Escolha uma das opções de :attribute.',
    'in_array'      => 'O campo :attribute deve existir em :other.',
    'in_array_keys' => 'O campo :attribute deve conter pelo menos uma destas chaves: :values.',
    'integer'       => 'O campo :attribute precisa ser um número inteiro.',
    'ip'            => 'O campo :attribute deve ser um endereço IP válido.',
    'ipv4'          => 'O campo :attribute deve ser um endereço IPv4 válido.',
    'ipv6'          => 'O campo :attribute deve ser um endereço IPv6 válido.',
    'json'          => 'O campo :attribute deve ser um texto JSON válido.',
    'list'          => 'O campo :attribute deve ser uma lista.',
    'lowercase'     => 'O campo :attribute deve estar em minúsculas.',
    'lt'            => [
        'array'   => 'O campo :attribute deve ter menos de :value itens.',
        'file'    => 'O campo :attribute deve ter menos de :value kilobytes.',
        'numeric' => 'O campo :attribute deve ser menor que :value.',
        'string'  => 'O campo :attribute deve ter menos de :value caracteres.',
    ],
    'lte' => [
        'array'   => 'O campo :attribute não deve ter mais de :value itens.',
        'file'    => 'O campo :attribute deve ter no máximo :value kilobytes.',
        'numeric' => 'O campo :attribute deve ser menor ou igual a :value.',
        'string'  => 'O campo :attribute deve ter no máximo :value caracteres.',
    ],
    'mac_address' => 'O campo :attribute deve ser um endereço MAC válido.',
    'max'         => [
        'array'   => 'O campo :attribute pode ter até :max itens.',
        'file'    => 'Grande demais: o campo :attribute pode ter até :max KB.',
        'numeric' => 'O campo :attribute pode ir até :max.',
        'string'  => 'O campo :attribute pode ter até :max caracteres.',
    ],
    'max_digits' => 'O campo :attribute não deve ter mais de :max dígitos.',
    'mimes'      => 'O campo :attribute precisa ser de um destes tipos de arquivo: :values.',
    'mimetypes'  => 'O campo :attribute precisa ser de um destes tipos de arquivo: :values.',
    'min'        => [
        'array'   => 'O campo :attribute precisa ter pelo menos :min itens.',
        'file'    => 'O campo :attribute deve ter pelo menos :min kilobytes.',
        'numeric' => 'O campo :attribute precisa ser pelo menos :min.',
        'string'  => 'O campo :attribute precisa ter pelo menos :min caracteres.',
    ],
    'min_digits'       => 'O campo :attribute deve ter pelo menos :min dígitos.',
    'missing'          => 'O campo :attribute deve estar ausente.',
    'missing_if'       => 'O campo :attribute deve estar ausente quando :other for :value.',
    'missing_unless'   => 'O campo :attribute deve estar ausente a menos que :other seja :value.',
    'missing_with'     => 'O campo :attribute deve estar ausente quando :values estiver presente.',
    'missing_with_all' => 'O campo :attribute deve estar ausente quando :values estiverem presentes.',
    'multiple_of'      => 'O campo :attribute deve ser múltiplo de :value.',
    'not_in'           => 'Escolha outra opção em :attribute.',
    'not_regex'        => 'O formato do campo :attribute não é válido.',
    'numeric'          => 'O campo :attribute precisa ser um número.',
    'password'         => [
        'letters'       => 'O campo :attribute deve conter pelo menos uma letra.',
        'mixed'         => 'O campo :attribute deve conter pelo menos uma letra maiúscula e uma minúscula.',
        'numbers'       => 'O campo :attribute deve conter pelo menos um número.',
        'symbols'       => 'O campo :attribute deve conter pelo menos um símbolo.',
        'uncompromised' => 'Essa :attribute apareceu em um vazamento de dados. Escolha outra :attribute.',
    ],
    'present'                => 'O campo :attribute deve estar presente.',
    'present_if'             => 'O campo :attribute deve estar presente quando :other for :value.',
    'present_unless'         => 'O campo :attribute deve estar presente a menos que :other seja :value.',
    'present_with'           => 'O campo :attribute deve estar presente quando :values estiver presente.',
    'present_with_all'       => 'O campo :attribute deve estar presente quando :values estiverem presentes.',
    'prohibited'             => 'O campo :attribute é proibido.',
    'prohibited_if'          => 'O campo :attribute é proibido quando :other for :value.',
    'prohibited_if_accepted' => 'O campo :attribute é proibido quando :other for aceito.',
    'prohibited_if_declined' => 'O campo :attribute é proibido quando :other for recusado.',
    'prohibited_unless'      => 'O campo :attribute é proibido a menos que :other esteja em :values.',
    'prohibits'              => 'O campo :attribute impede que :other esteja presente.',
    'regex'                  => 'Confira o campo :attribute: não está escrito como deveria.',
    'required'               => 'Preencha o campo :attribute.',
    'required_array_keys'    => 'O campo :attribute deve conter entradas para: :values.',
    'required_if'            => 'O campo :attribute é obrigatório quando :other for :value.',
    'required_if_accepted'   => 'O campo :attribute é obrigatório quando :other for aceito.',
    'required_if_declined'   => 'O campo :attribute é obrigatório quando :other for recusado.',
    'required_unless'        => 'O campo :attribute é obrigatório a menos que :other esteja em :values.',
    'required_with'          => 'O campo :attribute é obrigatório quando :values estiver presente.',
    'required_with_all'      => 'O campo :attribute é obrigatório quando :values estiverem presentes.',
    'required_without'       => 'O campo :attribute é obrigatório quando :values não estiver presente.',
    'required_without_all'   => 'O campo :attribute é obrigatório quando nenhum de :values estiver presente.',
    'same'                   => 'O campo :attribute deve coincidir com :other.',
    'size'                   => [
        'array'   => 'O campo :attribute deve conter :size itens.',
        'file'    => 'O campo :attribute deve ter :size kilobytes.',
        'numeric' => 'O campo :attribute deve ser :size.',
        'string'  => 'O campo :attribute deve ter :size caracteres.',
    ],
    'starts_with' => 'O campo :attribute deve começar com um destes: :values.',
    'string'      => 'O campo :attribute deve ser um texto.',
    'timezone'    => 'O campo :attribute deve ser um fuso horário válido.',
    'unique'      => 'Esse valor de :attribute já está em uso.',
    'uploaded'    => 'Não foi possível enviar :attribute. Tente de novo.',
    'uppercase'   => 'O campo :attribute deve estar em maiúsculas.',
    'url'         => 'O campo :attribute não tem um link válido. Confira e tente de novo.',
    'ulid'        => 'O campo :attribute deve ser um ULID válido.',
    'uuid'        => 'O campo :attribute deve ser um UUID válido.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Field names, as people read them
    |--------------------------------------------------------------------------
    */

    'attributes' => [
        'firstname'          => 'nome',
        'lastname'           => 'sobrenome',
        'email'              => 'e-mail',
        'password'           => 'senha',
        'current_password'   => 'senha atual',
        'phone'              => 'telefone',
        'legal_id'           => 'número do documento',
        'about'              => 'sobre você',
        'name'               => 'nome',
        'description'        => 'descrição',
        'organization'       => 'organização',
        'identifier'         => 'nome ou número',
        'unit_type_id'       => 'tipo de moradia',
        'new_unit_type.name' => 'nome do tipo de moradia',
        'family_id'          => 'família',
        'title'              => 'título',
        'location'           => 'local',
        'start_date'         => 'data de início',
        'end_date'           => 'data de término',
        'files'              => 'arquivos',
        'files.*'            => 'arquivo',
        'reason'             => 'motivo',
        'confirmation'       => 'confirmação',
    ],

    /*
    |--------------------------------------------------------------------------
    | Business rules
    |--------------------------------------------------------------------------
    */

    'already_in_project'        => 'Esta pessoa já participa do projeto com essa função.',
    'cannot_revoke_self'        => 'Você não pode retirar sua própria função de superadministrador.',
    'no_account_with_email'     => 'Não há nenhuma conta com este e-mail.',
    'email_taken'               => 'Outra conta já usa este e-mail.',
    'family_has_unit'           => 'Esta família já recebeu uma moradia no sorteio: não pode ser removida.',
    'owner_stays'               => 'Quem é dono da plataforma mantém sua conta.',
    'owner_keeps_role'          => 'Quem é dono da plataforma mantém esta função.',
    'email_changes_limit'       => 'Você mudou seu e-mail várias vezes há pouco. Tente de novo mais tarde.',
    'superadmin_unconfirmed'    => 'Esta conta não confirmou o e-mail ou está bloqueada: não pode ter esta função.',
    'only_admin_of'             => 'Você é o único administrador de :projects. Nomeie outro administrador primeiro, ou exclua o projeto.',
    'last_admin'                => 'Um projeto precisa de pelo menos um administrador.',
    'member_needs_family'       => 'Quem não é administrador precisa fazer parte de uma família.',
    'preferences_foreign_units' => 'Você só pode ordenar moradias do tipo da sua família.',
    'unit_assigned'             => 'Esta moradia já foi dada a uma família no sorteio: não pode ser removida.',
    'plan_unit_items'           => 'Só a forma de uma moradia é uma moradia. Adicione moradias pela paleta da planta ou pela página de moradias.',
    'unit_type_in_use'          => 'Ainda há famílias ou moradias usando este tipo.',

    /** Limits of projects awaiting approval */
    'limit_families'         => 'Por enquanto este projeto tem espaço para até :max famílias. Peça ilimitado para tirar o limite.',
    'limit_units'            => 'Por enquanto este projeto tem espaço para até :max moradias. Peça ilimitado para tirar o limite.',
    'limit_members'          => 'Por enquanto este projeto tem espaço para até :max pessoas. Peça ilimitado para tirar o limite.',
    'limit_invitations'      => 'Por enquanto este projeto pode enviar até :max convites por dia. Peça ilimitado para tirar o limite.',
    'limit_storage'          => 'Por enquanto este projeto tem espaço para :max MB de arquivos. Peça ilimitado para tirar o limite.',
    'image_too_large'        => 'É possível compartilhar imagens de até :max megapixels. Esta tem mais.',
    'chat_people'            => 'Escolha pessoas do projeto para conversar.',
    'push_device'            => 'Este dispositivo não pode receber notificações daqui.',
    'chat_group_limit'       => 'Você está em :max grupos, o máximo. Saia de algum para criar outro.',
    'chat_group_limit_other' => ':name está em :max grupos, o máximo.',
    'guests_remain'          => 'Ainda há convidados (:count, contando convites a caminho). Primeiro coloque essas pessoas em uma família ou remova-as.',
    'account_blocked'        => 'Esta conta está bloqueada: não pode ser convidada.',
    'account_protected'      => 'Nem a sua própria conta, nem a de um superadministrador: primeiro retire a função.',
    'feature_off'            => 'Este projeto desativou este tipo de arquivo.',
    'family_already_in'      => 'Você já está nesta família.',
    'family_declined'        => 'Esta família recusou seu pedido.',
    'family_request_open'    => 'Você já está aguardando a resposta de uma família. Cancele esse pedido primeiro.',
    'family_none'            => 'Você ainda não entrou em uma família aqui.',
    'family_cannot_leave'    => 'Aqui todos participam em família. Peça para entrar na sua, ou crie a sua.',
    'family_declined_them'   => 'Esta família recusou esta pessoa. Só alguém da família pode reverter.',
];
