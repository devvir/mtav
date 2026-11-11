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

    'accepted'        => 'Vous devez accepter « :attribute ».',
    'accepted_if'     => 'Vous devez accepter « :attribute » quand :other vaut :value.',
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
        'numeric' => '« :attribute » doit être entre :min et :max.',
        'string'  => '« :attribute » doit avoir entre :min et :max caractères.',
    ],
    'boolean'           => 'Le champ :attribute doit être vrai ou faux.',
    'can'               => 'Le champ :attribute contient une valeur non autorisée.',
    'confirmed'         => 'Les deux cases « :attribute » ne correspondent pas. Réessayez.',
    'contains'          => 'Il manque une valeur obligatoire dans le champ :attribute.',
    'current_password'  => 'Ce mot de passe n’est pas correct.',
    'date'              => '« :attribute » n’est pas une date valable. Vérifiez et réessayez.',
    'date_equals'       => 'Le champ :attribute doit être une date égale au :date.',
    'date_format'       => 'Le champ :attribute doit respecter le format :format.',
    'decimal'           => 'Le champ :attribute doit avoir :decimal décimales.',
    'declined'          => 'Vous devez refuser « :attribute ».',
    'declined_if'       => 'Vous devez refuser « :attribute » quand :other vaut :value.',
    'different'         => 'Les champs :attribute et :other doivent être différents.',
    'digits'            => 'Le champ :attribute doit comporter :digits chiffres.',
    'digits_between'    => 'Le champ :attribute doit comporter entre :min et :max chiffres.',
    'dimensions'        => 'Les dimensions de l’image du champ :attribute ne sont pas valables.',
    'distinct'          => 'Le champ :attribute contient une valeur en double.',
    'doesnt_end_with'   => 'Le champ :attribute ne doit pas se terminer par l’une des valeurs suivantes : :values.',
    'doesnt_start_with' => 'Le champ :attribute ne doit pas commencer par l’une des valeurs suivantes : :values.',
    'email'             => '« :attribute » ne ressemble pas à une adresse e-mail. Vérifiez et réessayez.',
    'ends_with'         => 'Le champ :attribute doit se terminer par l’une des valeurs suivantes : :values.',
    'enum'              => 'Choisissez l’une des options pour « :attribute ».',
    'exists'            => 'Choisissez l’une des options pour « :attribute ».',
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
    'image'         => '« :attribute » doit être une image.',
    'in'            => 'Choisissez l’une des options pour « :attribute ».',
    'in_array'      => 'Le champ :attribute doit exister dans :other.',
    'in_array_keys' => 'Le champ :attribute doit contenir au moins l’une des clés suivantes : :values.',
    'integer'       => '« :attribute » doit être un nombre entier.',
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
        'array'   => '« :attribute » peut avoir jusqu’à :max éléments.',
        'file'    => '« :attribute » est trop volumineux. Il peut faire jusqu’à :max Ko.',
        'numeric' => '« :attribute » peut aller jusqu’à :max.',
        'string'  => '« :attribute » peut avoir jusqu’à :max caractères.',
    ],
    'max_digits' => 'Le champ :attribute ne doit pas avoir plus de :max chiffres.',
    'mimes'      => '« :attribute » doit être de l’un de ces types de fichier : :values.',
    'mimetypes'  => '« :attribute » doit être de l’un de ces types de fichier : :values.',
    'min'        => [
        'array'   => '« :attribute » doit avoir au moins :min éléments.',
        'file'    => 'Le champ :attribute doit faire au moins :min kilo-octets.',
        'numeric' => '« :attribute » doit être d’au moins :min.',
        'string'  => '« :attribute » doit avoir au moins :min caractères.',
    ],
    'min_digits'       => 'Le champ :attribute doit avoir au moins :min chiffres.',
    'missing'          => 'Le champ :attribute doit être absent.',
    'missing_if'       => 'Le champ :attribute doit être absent quand :other vaut :value.',
    'missing_unless'   => 'Le champ :attribute doit être absent sauf si :other vaut :value.',
    'missing_with'     => 'Le champ :attribute doit être absent quand :values est présent.',
    'missing_with_all' => 'Le champ :attribute doit être absent quand :values sont présents.',
    'multiple_of'      => 'Le champ :attribute doit être un multiple de :value.',
    'not_in'           => 'La valeur choisie pour « :attribute » n’est pas valable.',
    'not_regex'        => 'Le format du champ :attribute n’est pas valable.',
    'numeric'          => '« :attribute » doit être un nombre.',
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
    'regex'                  => 'Vérifiez « :attribute » : ce n’est pas écrit comme il faut.',
    'required'               => 'Remplissez « :attribute ».',
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
    'unique'      => '« :attribute » est déjà utilisé.',
    'uploaded'    => '« :attribute » n’a pas pu être envoyé. Réessayez.',
    'uppercase'   => 'Le champ :attribute doit être en majuscules.',
    'url'         => '« :attribute » n’est pas un lien valable. Vérifiez et réessayez.',
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
        'identifier'         => 'nom ou numéro',
        'unit_type_id'       => 'type de logement',
        'new_unit_type.name' => 'nom du type de logement',
        'family_id'          => 'famille',
        'title'              => 'titre',
        'location'           => 'lieu',
        'start_date'         => 'date de début',
        'end_date'           => 'date de fin',
        'files'              => 'fichiers',
        'files.*'            => 'fichier',
        'items.*'            => 'nom',
        'reason'             => 'motif',
        'confirmation'       => 'confirmation',
    ],

    /*
    |--------------------------------------------------------------------------
    | Business rules
    |--------------------------------------------------------------------------
    */

    'already_in_project'        => 'Cette personne participe déjà au projet avec ce rôle.',
    'cannot_revoke_self'        => 'Vous ne pouvez pas retirer votre propre rôle de superadministrateur.',
    'no_account_with_email'     => 'Aucun compte n’utilise cet e-mail.',
    'email_taken'               => 'Un autre compte utilise déjà cet e-mail.',
    'family_has_unit'           => 'Cette famille a déjà reçu un logement lors du tirage : elle ne peut pas être retirée.',
    'family_has_people'         => 'Cette famille compte encore des personnes : déplacez-les vers une autre famille ou retirez-les d’abord du projet.',
    'owner_stays'               => 'Le propriétaire de la plateforme garde son compte.',
    'owner_keeps_role'          => 'Le propriétaire de la plateforme garde ce rôle.',
    'email_changes_limit'       => 'Vous avez changé d’e-mail plusieurs fois récemment : réessayez plus tard.',
    'superadmin_unconfirmed'    => 'Ce compte n’a pas confirmé son e-mail ou est bloqué : il ne peut pas avoir ce rôle.',
    'only_admin_of'             => 'Vous êtes le seul administrateur de :projects : nommez d’abord un autre administrateur, ou supprimez le projet.',
    'last_admin'                => 'Un projet a besoin d’au moins un administrateur.',
    'member_needs_family'       => 'Une personne qui n’est pas administrateur doit faire partie d’une famille.',
    'preferences_foreign_units' => 'Vous ne pouvez classer que des logements du type de votre famille.',
    'unit_assigned'             => 'Ce logement a déjà été attribué à une famille lors du tirage : il ne peut pas être retiré.',
    'plan_unit_items'           => 'Seule la forme d’un logement est un logement : ajoutez des logements depuis la palette du plan ou la page des logements.',
    'unit_type_in_use'          => 'Des familles ou des logements utilisent encore ce type de logement.',

    /** Limits of projects awaiting approval */
    'limit_families'            => 'Pour l’instant, ce projet peut accueillir jusqu’à :max familles : demandez un projet illimité pour lever cette limite.',
    'limit_units'               => 'Pour l’instant, ce projet peut accueillir jusqu’à :max logements : demandez un projet illimité pour lever cette limite.',
    'limit_members'             => 'Pour l’instant, ce projet peut accueillir jusqu’à :max personnes : demandez un projet illimité pour lever cette limite.',
    'limit_invitations'         => 'Pour l’instant, ce projet peut envoyer jusqu’à :max invitations par jour : demandez un projet illimité pour lever cette limite.',
    'limit_invitations_address' => 'Cette adresse a déjà reçu plusieurs invitations de ce projet dans l’heure : réessayez un peu plus tard.',
    'limit_storage'             => 'Pour l’instant, ce projet peut accueillir :max Mo de fichiers : demandez un projet illimité pour lever cette limite.',
    'image_too_large'           => 'Les images peuvent faire jusqu’à :max mégapixels. Celle-ci en a plus.',
    'chat_people'               => 'Choisissez des personnes du projet avec qui échanger.',
    'push_device'               => 'Cet appareil ne peut pas recevoir les notifications d’ici.',
    'chat_group_limit'          => 'Vous êtes dans :max groupes, le maximum : quittez-en un pour en créer un autre.',
    'chat_group_limit_other'    => ':name est dans :max groupes, le maximum.',
    'guests_remain'             => 'Il reste des invités (:count, invitations en cours comprises) : donnez-leur d’abord une famille ou retirez-les.',
    'account_blocked'           => 'Ce compte est bloqué : il ne peut pas être invité.',
    'account_protected'         => 'Ni votre propre compte, ni celui d’un superadministrateur : retirez d’abord son rôle.',
    'feature_off'               => 'Ce projet a désactivé ce type de fichier.',
    'family_already_in'         => 'Vous êtes déjà dans cette famille.',
    'family_declined'           => 'Cette famille a refusé votre demande.',
    'family_request_open'       => 'Vous attendez déjà la réponse d’une famille : annulez d’abord cette demande.',
    'family_none'               => 'Vous n’avez pas encore rejoint de famille ici.',
    'family_cannot_leave'       => 'Ici, chacun participe en famille : demandez à rejoindre la vôtre, ou créez-la.',
    'family_declined_them'      => 'Cette famille a refusé cette personne : seul l’un de ses membres peut l’annuler.',
    'support_email_taken'       => 'Cet e-mail a déjà un compte ici. Un compte de support a besoin de sa propre adresse.',
    'support_by_invitation'     => 'Le rôle de support ne s’obtient que par une invitation, à une adresse pas encore utilisée ici.',
];
