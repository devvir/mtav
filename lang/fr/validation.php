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

    'accepted'        => 'Le champ :attribute doit être accepté.',
    'accepted_if'     => 'Le champ :attribute doit être accepté quand :other vaut :value.',
    'active_url'      => 'Le champ :attribute doit être une URL valide.',
    'after'           => 'Le champ :attribute doit être une date postérieure au :date.',
    'after_or_equal'  => 'Le champ :attribute doit être une date postérieure ou égale au :date.',
    'alpha'           => 'Le champ :attribute ne doit contenir que des lettres.',
    'alpha_dash'      => 'Le champ :attribute ne doit contenir que des lettres, des chiffres, des tirets et des tirets bas.',
    'alpha_num'       => 'Le champ :attribute ne doit contenir que des lettres et des chiffres.',
    'any_of'          => 'Le champ :attribute n’est pas valable.',
    'array'           => 'Le champ :attribute doit être une liste.',
    'ascii'           => 'Le champ :attribute ne doit contenir que des caractères alphanumériques et des symboles simples.',
    'before'          => 'Le champ :attribute doit être une date antérieure au :date.',
    'before_or_equal' => 'Le champ :attribute doit être une date antérieure ou égale au :date.',
    'between'         => [
        'array'   => 'Le champ :attribute doit avoir entre :min et :max éléments.',
        'file'    => 'Le champ :attribute doit faire entre :min et :max kilo-octets.',
        'numeric' => 'Le champ :attribute doit être compris entre :min et :max.',
        'string'  => 'Le champ :attribute doit contenir entre :min et :max caractères.',
    ],
    'boolean'           => 'Le champ :attribute doit être vrai ou faux.',
    'can'               => 'Le champ :attribute contient une valeur non autorisée.',
    'confirmed'         => 'La confirmation du champ :attribute ne correspond pas.',
    'contains'          => 'Il manque une valeur obligatoire dans le champ :attribute.',
    'current_password'  => 'Le mot de passe est incorrect.',
    'date'              => 'Le champ :attribute doit être une date valide.',
    'date_equals'       => 'Le champ :attribute doit être une date égale au :date.',
    'date_format'       => 'Le champ :attribute doit respecter le format :format.',
    'decimal'           => 'Le champ :attribute doit avoir :decimal décimales.',
    'declined'          => 'Le champ :attribute doit être refusé.',
    'declined_if'       => 'Le champ :attribute doit être refusé quand :other vaut :value.',
    'different'         => 'Les champs :attribute et :other doivent être différents.',
    'digits'            => 'Le champ :attribute doit comporter :digits chiffres.',
    'digits_between'    => 'Le champ :attribute doit comporter entre :min et :max chiffres.',
    'dimensions'        => 'Les dimensions de l’image du champ :attribute ne sont pas valables.',
    'distinct'          => 'Le champ :attribute contient une valeur en double.',
    'doesnt_end_with'   => 'Le champ :attribute ne doit pas se terminer par l’une des valeurs suivantes : :values.',
    'doesnt_start_with' => 'Le champ :attribute ne doit pas commencer par l’une des valeurs suivantes : :values.',
    'email'             => 'Le champ :attribute doit être une adresse e-mail valide.',
    'ends_with'         => 'Le champ :attribute doit se terminer par l’une des valeurs suivantes : :values.',
    'enum'              => 'La valeur choisie pour :attribute n’est pas valable.',
    'exists'            => 'La valeur choisie pour :attribute n’est pas valable.',
    'extensions'        => 'Le champ :attribute doit avoir l’une des extensions suivantes : :values.',
    'file'              => 'Le champ :attribute doit être un fichier.',
    'filled'            => 'Le champ :attribute doit avoir une valeur.',
    'gt'                => [
        'array'   => 'Le champ :attribute doit avoir plus de :value éléments.',
        'file'    => 'Le champ :attribute doit faire plus de :value kilo-octets.',
        'numeric' => 'Le champ :attribute doit être supérieur à :value.',
        'string'  => 'Le champ :attribute doit contenir plus de :value caractères.',
    ],
    'gte' => [
        'array'   => 'Le champ :attribute doit avoir au moins :value éléments.',
        'file'    => 'Le champ :attribute doit faire au moins :value kilo-octets.',
        'numeric' => 'Le champ :attribute doit être supérieur ou égal à :value.',
        'string'  => 'Le champ :attribute doit contenir au moins :value caractères.',
    ],
    'hex_color'     => 'Le champ :attribute doit être une couleur hexadécimale valide.',
    'image'         => 'Le champ :attribute doit être une image.',
    'in'            => 'La valeur choisie pour :attribute n’est pas valable.',
    'in_array'      => 'Le champ :attribute doit exister dans :other.',
    'in_array_keys' => 'Le champ :attribute doit contenir au moins l’une des clés suivantes : :values.',
    'integer'       => 'Le champ :attribute doit être un nombre entier.',
    'ip'            => 'Le champ :attribute doit être une adresse IP valide.',
    'ipv4'          => 'Le champ :attribute doit être une adresse IPv4 valide.',
    'ipv6'          => 'Le champ :attribute doit être une adresse IPv6 valide.',
    'json'          => 'Le champ :attribute doit être un texte JSON valide.',
    'list'          => 'Le champ :attribute doit être une liste.',
    'lowercase'     => 'Le champ :attribute doit être en minuscules.',
    'lt'            => [
        'array'   => 'Le champ :attribute doit avoir moins de :value éléments.',
        'file'    => 'Le champ :attribute doit faire moins de :value kilo-octets.',
        'numeric' => 'Le champ :attribute doit être inférieur à :value.',
        'string'  => 'Le champ :attribute doit contenir moins de :value caractères.',
    ],
    'lte' => [
        'array'   => 'Le champ :attribute ne doit pas avoir plus de :value éléments.',
        'file'    => 'Le champ :attribute doit faire au plus :value kilo-octets.',
        'numeric' => 'Le champ :attribute doit être inférieur ou égal à :value.',
        'string'  => 'Le champ :attribute doit contenir au plus :value caractères.',
    ],
    'mac_address' => 'Le champ :attribute doit être une adresse MAC valide.',
    'max'         => [
        'array'   => 'Le champ :attribute ne doit pas avoir plus de :max éléments.',
        'file'    => 'Le champ :attribute ne doit pas dépasser :max kilo-octets.',
        'numeric' => 'Le champ :attribute ne doit pas être supérieur à :max.',
        'string'  => 'Le champ :attribute ne doit pas dépasser :max caractères.',
    ],
    'max_digits' => 'Le champ :attribute ne doit pas avoir plus de :max chiffres.',
    'mimes'      => 'Le champ :attribute doit être un fichier de type : :values.',
    'mimetypes'  => 'Le champ :attribute doit être un fichier de type : :values.',
    'min'        => [
        'array'   => 'Le champ :attribute doit avoir au moins :min éléments.',
        'file'    => 'Le champ :attribute doit faire au moins :min kilo-octets.',
        'numeric' => 'Le champ :attribute doit être au moins égal à :min.',
        'string'  => 'Le champ :attribute doit contenir au moins :min caractères.',
    ],
    'min_digits'       => 'Le champ :attribute doit avoir au moins :min chiffres.',
    'missing'          => 'Le champ :attribute doit être absent.',
    'missing_if'       => 'Le champ :attribute doit être absent quand :other vaut :value.',
    'missing_unless'   => 'Le champ :attribute doit être absent sauf si :other vaut :value.',
    'missing_with'     => 'Le champ :attribute doit être absent quand :values est présent.',
    'missing_with_all' => 'Le champ :attribute doit être absent quand :values sont présents.',
    'multiple_of'      => 'Le champ :attribute doit être un multiple de :value.',
    'not_in'           => 'La valeur choisie pour :attribute n’est pas valable.',
    'not_regex'        => 'Le format du champ :attribute n’est pas valable.',
    'numeric'          => 'Le champ :attribute doit être un nombre.',
    'password'         => [
        'letters'       => 'Le champ :attribute doit contenir au moins une lettre.',
        'mixed'         => 'Le champ :attribute doit contenir au moins une majuscule et une minuscule.',
        'numbers'       => 'Le champ :attribute doit contenir au moins un chiffre.',
        'symbols'       => 'Le champ :attribute doit contenir au moins un symbole.',
        'uncompromised' => 'Ce :attribute est apparu dans une fuite de données. Veuillez choisir un autre :attribute.',
    ],
    'present'                => 'Le champ :attribute doit être présent.',
    'present_if'             => 'Le champ :attribute doit être présent quand :other vaut :value.',
    'present_unless'         => 'Le champ :attribute doit être présent sauf si :other vaut :value.',
    'present_with'           => 'Le champ :attribute doit être présent quand :values est présent.',
    'present_with_all'       => 'Le champ :attribute doit être présent quand :values sont présents.',
    'prohibited'             => 'Le champ :attribute est interdit.',
    'prohibited_if'          => 'Le champ :attribute est interdit quand :other vaut :value.',
    'prohibited_if_accepted' => 'Le champ :attribute est interdit quand :other est accepté.',
    'prohibited_if_declined' => 'Le champ :attribute est interdit quand :other est refusé.',
    'prohibited_unless'      => 'Le champ :attribute est interdit sauf si :other fait partie de :values.',
    'prohibits'              => 'Le champ :attribute empêche :other d’être présent.',
    'regex'                  => 'Le format du champ :attribute n’est pas valable.',
    'required'               => 'Le champ :attribute est obligatoire.',
    'required_array_keys'    => 'Le champ :attribute doit contenir des entrées pour : :values.',
    'required_if'            => 'Le champ :attribute est obligatoire quand :other vaut :value.',
    'required_if_accepted'   => 'Le champ :attribute est obligatoire quand :other est accepté.',
    'required_if_declined'   => 'Le champ :attribute est obligatoire quand :other est refusé.',
    'required_unless'        => 'Le champ :attribute est obligatoire sauf si :other fait partie de :values.',
    'required_with'          => 'Le champ :attribute est obligatoire quand :values est présent.',
    'required_with_all'      => 'Le champ :attribute est obligatoire quand :values sont présents.',
    'required_without'       => 'Le champ :attribute est obligatoire quand :values est absent.',
    'required_without_all'   => 'Le champ :attribute est obligatoire quand aucun de :values n’est présent.',
    'same'                   => 'Le champ :attribute doit correspondre à :other.',
    'size'                   => [
        'array'   => 'Le champ :attribute doit contenir :size éléments.',
        'file'    => 'Le champ :attribute doit faire :size kilo-octets.',
        'numeric' => 'Le champ :attribute doit valoir :size.',
        'string'  => 'Le champ :attribute doit contenir :size caractères.',
    ],
    'starts_with' => 'Le champ :attribute doit commencer par l’une des valeurs suivantes : :values.',
    'string'      => 'Le champ :attribute doit être un texte.',
    'timezone'    => 'Le champ :attribute doit être un fuseau horaire valide.',
    'unique'      => 'Ce :attribute est déjà utilisé.',
    'uploaded'    => 'Le téléversement du champ :attribute a échoué.',
    'uppercase'   => 'Le champ :attribute doit être en majuscules.',
    'url'         => 'Le champ :attribute doit être une URL valide.',
    'ulid'        => 'Le champ :attribute doit être un ULID valide.',
    'uuid'        => 'Le champ :attribute doit être un UUID valide.',

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
        'firstname'          => 'prénom',
        'lastname'           => 'nom',
        'email'              => 'e-mail',
        'password'           => 'mot de passe',
        'current_password'   => 'mot de passe actuel',
        'phone'              => 'téléphone',
        'legal_id'           => 'numéro de pièce d’identité',
        'about'              => 'présentation',
        'name'               => 'nom',
        'description'        => 'description',
        'organization'       => 'organisation',
        'identifier'         => 'identifiant',
        'unit_type_id'       => 'type de logement',
        'new_unit_type.name' => 'nom du type de logement',
        'family_id'          => 'famille',
        'title'              => 'titre',
        'location'           => 'lieu',
        'start_date'         => 'début',
        'end_date'           => 'fin',
        'files'              => 'fichiers',
        'files.*'            => 'fichier',
        'reason'             => 'raison',
        'confirmation'       => 'confirmation',
    ],

    /*
    |--------------------------------------------------------------------------
    | Business rules
    |--------------------------------------------------------------------------
    */

    'already_in_project'        => 'Cette personne participe déjà au projet avec ce rôle.',
    'cannot_revoke_self'        => 'Vous ne pouvez pas retirer votre propre rôle de superadmin.',
    'no_account_with_email'     => 'Aucun compte n’utilise cet e-mail.',
    'email_taken'               => 'Un autre compte utilise déjà cet e-mail.',
    'family_has_unit'           => 'Cette famille a déjà reçu un logement lors du tirage : elle ne peut pas être retirée.',
    'confirm_with_email'        => 'Tapez votre e-mail exactement tel qu’il est.',
    'owner_stays'               => 'Le propriétaire de la plateforme garde son compte.',
    'only_admin_of'             => 'Vous êtes le seul admin de :projects : nommez d’abord un autre admin, ou supprimez le projet.',
    'last_admin'                => 'Un projet a besoin d’au moins un admin.',
    'member_needs_family'       => 'Une personne qui n’est pas admin doit faire partie d’une famille.',
    'preferences_foreign_units' => 'Vous ne pouvez classer que des logements du type de votre famille.',
    'unit_assigned'             => 'Ce logement a déjà été attribué à une famille lors du tirage : il ne peut pas être retiré.',
    'plan_unit_items'           => 'Seule la forme d’un logement est un logement : ajoutez des logements depuis la palette du plan ou la page des logements.',
    'unit_type_in_use'          => 'Des familles ou des logements utilisent encore ce type de logement.',

    /** Limits of projects awaiting approval */
    'limit_families'    => 'Pour l’instant, ce projet peut accueillir jusqu’à :max familles : demandez l’illimité pour lever la limite.',
    'limit_units'       => 'Pour l’instant, ce projet peut accueillir jusqu’à :max logements : demandez l’illimité pour lever la limite.',
    'limit_members'     => 'Pour l’instant, ce projet peut accueillir jusqu’à :max personnes : demandez l’illimité pour lever la limite.',
    'limit_invitations' => 'Pour l’instant, ce projet peut envoyer jusqu’à :max invitations par jour : demandez l’illimité pour lever la limite.',
    'limit_storage'     => 'Pour l’instant, ce projet peut accueillir :max Mo de fichiers : demandez l’illimité pour lever la limite.',
    'image_too_large'   => 'Les images peuvent avoir jusqu’à :max mégapixels ; celle-ci en a davantage.',
    'chat_people'       => 'Choisissez des personnes du projet avec qui échanger.',
    'guests_remain' => 'Il reste des invités (:count, invitations en cours comprises) : donnez-leur d’abord une famille ou retirez-les.',
    'account_blocked' => 'Ce compte est bloqué : il ne peut pas être invité.',
    'account_protected' => 'Ni votre propre compte, ni celui d’un superadmin : retirez d’abord son rôle.',
    'feature_off' => 'Ce projet a désactivé ce type de fichier.',
];
