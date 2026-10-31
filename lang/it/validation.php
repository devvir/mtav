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

    'accepted'        => 'Il campo :attribute deve essere accettato.',
    'accepted_if'     => 'Il campo :attribute deve essere accettato quando :other è :value.',
    'active_url'      => 'Il campo :attribute deve essere un URL valido.',
    'after'           => 'Il campo :attribute deve essere una data successiva al :date.',
    'after_or_equal'  => 'Il campo :attribute deve essere una data successiva o uguale al :date.',
    'alpha'           => 'Il campo :attribute deve contenere solo lettere.',
    'alpha_dash'      => 'Il campo :attribute deve contenere solo lettere, numeri, trattini e trattini bassi.',
    'alpha_num'       => 'Il campo :attribute deve contenere solo lettere e numeri.',
    'any_of'          => 'Il campo :attribute non è valido.',
    'array'           => 'Il campo :attribute deve essere un elenco.',
    'ascii'           => 'Il campo :attribute deve contenere solo caratteri alfanumerici e simboli semplici.',
    'before'          => 'Il campo :attribute deve essere una data precedente al :date.',
    'before_or_equal' => 'Il campo :attribute deve essere una data precedente o uguale al :date.',
    'between'         => [
        'array'   => 'Il campo :attribute deve avere tra :min e :max elementi.',
        'file'    => 'Il campo :attribute deve essere tra :min e :max kilobyte.',
        'numeric' => 'Il campo :attribute deve essere tra :min e :max.',
        'string'  => 'Il campo :attribute deve avere tra :min e :max caratteri.',
    ],
    'boolean'           => 'Il campo :attribute deve essere vero o falso.',
    'can'               => 'Il campo :attribute contiene un valore non consentito.',
    'confirmed'         => 'La conferma del campo :attribute non corrisponde.',
    'contains'          => 'Nel campo :attribute manca un valore obbligatorio.',
    'current_password'  => 'La password non è corretta.',
    'date'              => 'Il campo :attribute deve essere una data valida.',
    'date_equals'       => 'Il campo :attribute deve essere una data uguale al :date.',
    'date_format'       => 'Il campo :attribute deve rispettare il formato :format.',
    'decimal'           => 'Il campo :attribute deve avere :decimal decimali.',
    'declined'          => 'Il campo :attribute deve essere rifiutato.',
    'declined_if'       => 'Il campo :attribute deve essere rifiutato quando :other è :value.',
    'different'         => 'I campi :attribute e :other devono essere diversi.',
    'digits'            => 'Il campo :attribute deve avere :digits cifre.',
    'digits_between'    => 'Il campo :attribute deve avere tra :min e :max cifre.',
    'dimensions'        => 'Le dimensioni dell’immagine del campo :attribute non sono valide.',
    'distinct'          => 'Il campo :attribute ha un valore duplicato.',
    'doesnt_end_with'   => 'Il campo :attribute non deve finire con uno di questi: :values.',
    'doesnt_start_with' => 'Il campo :attribute non deve iniziare con uno di questi: :values.',
    'email'             => 'Il campo :attribute deve essere un indirizzo email valido.',
    'ends_with'         => 'Il campo :attribute deve finire con uno di questi: :values.',
    'enum'              => 'Il valore scelto per :attribute non è valido.',
    'exists'            => 'Il valore scelto per :attribute non è valido.',
    'extensions'        => 'Il campo :attribute deve avere una di queste estensioni: :values.',
    'file'              => 'Il campo :attribute deve essere un file.',
    'filled'            => 'Il campo :attribute deve avere un valore.',
    'gt'                => [
        'array'   => 'Il campo :attribute deve avere più di :value elementi.',
        'file'    => 'Il campo :attribute deve superare :value kilobyte.',
        'numeric' => 'Il campo :attribute deve essere maggiore di :value.',
        'string'  => 'Il campo :attribute deve avere più di :value caratteri.',
    ],
    'gte' => [
        'array'   => 'Il campo :attribute deve avere almeno :value elementi.',
        'file'    => 'Il campo :attribute deve essere almeno di :value kilobyte.',
        'numeric' => 'Il campo :attribute deve essere maggiore o uguale a :value.',
        'string'  => 'Il campo :attribute deve avere almeno :value caratteri.',
    ],
    'hex_color'     => 'Il campo :attribute deve essere un colore esadecimale valido.',
    'image'         => 'Il campo :attribute deve essere un’immagine.',
    'in'            => 'Il valore scelto per :attribute non è valido.',
    'in_array'      => 'Il campo :attribute deve esistere in :other.',
    'in_array_keys' => 'Il campo :attribute deve contenere almeno una di queste chiavi: :values.',
    'integer'       => 'Il campo :attribute deve essere un numero intero.',
    'ip'            => 'Il campo :attribute deve essere un indirizzo IP valido.',
    'ipv4'          => 'Il campo :attribute deve essere un indirizzo IPv4 valido.',
    'ipv6'          => 'Il campo :attribute deve essere un indirizzo IPv6 valido.',
    'json'          => 'Il campo :attribute deve essere un testo JSON valido.',
    'list'          => 'Il campo :attribute deve essere un elenco.',
    'lowercase'     => 'Il campo :attribute deve essere in minuscolo.',
    'lt'            => [
        'array'   => 'Il campo :attribute deve avere meno di :value elementi.',
        'file'    => 'Il campo :attribute deve essere meno di :value kilobyte.',
        'numeric' => 'Il campo :attribute deve essere minore di :value.',
        'string'  => 'Il campo :attribute deve avere meno di :value caratteri.',
    ],
    'lte' => [
        'array'   => 'Il campo :attribute non deve avere più di :value elementi.',
        'file'    => 'Il campo :attribute deve essere al massimo di :value kilobyte.',
        'numeric' => 'Il campo :attribute deve essere minore o uguale a :value.',
        'string'  => 'Il campo :attribute deve avere al massimo :value caratteri.',
    ],
    'mac_address' => 'Il campo :attribute deve essere un indirizzo MAC valido.',
    'max'         => [
        'array'   => 'Il campo :attribute non deve avere più di :max elementi.',
        'file'    => 'Il campo :attribute non deve superare :max kilobyte.',
        'numeric' => 'Il campo :attribute non deve essere maggiore di :max.',
        'string'  => 'Il campo :attribute non deve superare :max caratteri.',
    ],
    'max_digits' => 'Il campo :attribute non deve avere più di :max cifre.',
    'mimes'      => 'Il campo :attribute deve essere un file di tipo: :values.',
    'mimetypes'  => 'Il campo :attribute deve essere un file di tipo: :values.',
    'min'        => [
        'array'   => 'Il campo :attribute deve avere almeno :min elementi.',
        'file'    => 'Il campo :attribute deve essere almeno di :min kilobyte.',
        'numeric' => 'Il campo :attribute deve essere almeno :min.',
        'string'  => 'Il campo :attribute deve avere almeno :min caratteri.',
    ],
    'min_digits'       => 'Il campo :attribute deve avere almeno :min cifre.',
    'missing'          => 'Il campo :attribute deve mancare.',
    'missing_if'       => 'Il campo :attribute deve mancare quando :other è :value.',
    'missing_unless'   => 'Il campo :attribute deve mancare a meno che :other sia :value.',
    'missing_with'     => 'Il campo :attribute deve mancare quando :values è presente.',
    'missing_with_all' => 'Il campo :attribute deve mancare quando :values sono presenti.',
    'multiple_of'      => 'Il campo :attribute deve essere un multiplo di :value.',
    'not_in'           => 'Il valore scelto per :attribute non è valido.',
    'not_regex'        => 'Il formato del campo :attribute non è valido.',
    'numeric'          => 'Il campo :attribute deve essere un numero.',
    'password'         => [
        'letters'       => 'Il campo :attribute deve contenere almeno una lettera.',
        'mixed'         => 'Il campo :attribute deve contenere almeno una lettera maiuscola e una minuscola.',
        'numbers'       => 'Il campo :attribute deve contenere almeno un numero.',
        'symbols'       => 'Il campo :attribute deve contenere almeno un simbolo.',
        'uncompromised' => 'Questo :attribute è comparso in una fuga di dati. Scegli un altro :attribute.',
    ],
    'present'                => 'Il campo :attribute deve essere presente.',
    'present_if'             => 'Il campo :attribute deve essere presente quando :other è :value.',
    'present_unless'         => 'Il campo :attribute deve essere presente a meno che :other sia :value.',
    'present_with'           => 'Il campo :attribute deve essere presente quando :values è presente.',
    'present_with_all'       => 'Il campo :attribute deve essere presente quando :values sono presenti.',
    'prohibited'             => 'Il campo :attribute non è ammesso.',
    'prohibited_if'          => 'Il campo :attribute non è ammesso quando :other è :value.',
    'prohibited_if_accepted' => 'Il campo :attribute non è ammesso quando :other è accettato.',
    'prohibited_if_declined' => 'Il campo :attribute non è ammesso quando :other è rifiutato.',
    'prohibited_unless'      => 'Il campo :attribute non è ammesso a meno che :other sia in :values.',
    'prohibits'              => 'Il campo :attribute impedisce che :other sia presente.',
    'regex'                  => 'Il formato del campo :attribute non è valido.',
    'required'               => 'Il campo :attribute è obbligatorio.',
    'required_array_keys'    => 'Il campo :attribute deve contenere voci per: :values.',
    'required_if'            => 'Il campo :attribute è obbligatorio quando :other è :value.',
    'required_if_accepted'   => 'Il campo :attribute è obbligatorio quando :other è accettato.',
    'required_if_declined'   => 'Il campo :attribute è obbligatorio quando :other è rifiutato.',
    'required_unless'        => 'Il campo :attribute è obbligatorio a meno che :other sia in :values.',
    'required_with'          => 'Il campo :attribute è obbligatorio quando :values è presente.',
    'required_with_all'      => 'Il campo :attribute è obbligatorio quando :values sono presenti.',
    'required_without'       => 'Il campo :attribute è obbligatorio quando :values non è presente.',
    'required_without_all'   => 'Il campo :attribute è obbligatorio quando nessuno di :values è presente.',
    'same'                   => 'Il campo :attribute deve corrispondere a :other.',
    'size'                   => [
        'array'   => 'Il campo :attribute deve contenere :size elementi.',
        'file'    => 'Il campo :attribute deve essere di :size kilobyte.',
        'numeric' => 'Il campo :attribute deve essere :size.',
        'string'  => 'Il campo :attribute deve avere :size caratteri.',
    ],
    'starts_with' => 'Il campo :attribute deve iniziare con uno di questi: :values.',
    'string'      => 'Il campo :attribute deve essere un testo.',
    'timezone'    => 'Il campo :attribute deve essere un fuso orario valido.',
    'unique'      => 'Questo :attribute è già in uso.',
    'uploaded'    => 'Il caricamento del campo :attribute non è riuscito.',
    'uppercase'   => 'Il campo :attribute deve essere in maiuscolo.',
    'url'         => 'Il campo :attribute deve essere un URL valido.',
    'ulid'        => 'Il campo :attribute deve essere un ULID valido.',
    'uuid'        => 'Il campo :attribute deve essere un UUID valido.',

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
        'lastname'           => 'cognome',
        'email'              => 'email',
        'password'           => 'password',
        'current_password'   => 'password attuale',
        'phone'              => 'telefono',
        'legal_id'           => 'numero del documento',
        'about'              => 'presentazione',
        'name'               => 'nome',
        'description'        => 'descrizione',
        'organization'       => 'organizzazione',
        'identifier'         => 'identificativo',
        'unit_type_id'       => 'tipo di unità',
        'new_unit_type.name' => 'nome del tipo di unità',
        'family_id'          => 'famiglia',
        'title'              => 'titolo',
        'location'           => 'luogo',
        'start_date'         => 'inizio',
        'end_date'           => 'fine',
        'files'              => 'file',
        'files.*'            => 'file',
        'reason'             => 'motivo',
        'confirmation'       => 'conferma',
    ],

    /*
    |--------------------------------------------------------------------------
    | Business rules
    |--------------------------------------------------------------------------
    */

    'already_in_project'        => 'Questa persona partecipa già al progetto con quel ruolo.',
    'cannot_revoke_self'        => 'Non puoi togliere il tuo stesso ruolo di superadmin.',
    'no_account_with_email'     => 'Non c’è nessun account con questa email.',
    'email_taken'               => 'Un altro account usa già questa email.',
    'family_has_unit'           => 'Questa famiglia ha già ricevuto un’unità nel sorteggio: non si può togliere.',
    'confirm_with_email'        => 'Scrivi la tua email esattamente com’è.',
    'owner_stays'               => 'Chi possiede la piattaforma mantiene il suo account.',
    'only_admin_of'             => 'Sei l’unico admin di :projects: nomina prima un altro admin, o elimina il progetto.',
    'last_admin'                => 'Un progetto ha bisogno di almeno un admin.',
    'member_needs_family'       => 'Chi non è admin deve appartenere a una famiglia.',
    'preferences_foreign_units' => 'Puoi mettere in ordine solo unità del tipo della tua famiglia.',
    'unit_assigned'             => 'Questa unità è già stata assegnata a una famiglia nel sorteggio: non si può togliere.',
    'plan_unit_items'           => 'Solo la forma di un’unità è un’unità: aggiungi le unità dalla tavolozza della planimetria o dalla pagina delle unità.',
    'unit_type_in_use'          => 'Famiglie o unità usano ancora questo tipo di unità.',

    /** Limits of projects awaiting approval */
    'limit_families'         => 'Per ora questo progetto ha spazio per fino a :max famiglie: chiedi illimitato per togliere il limite.',
    'limit_units'            => 'Per ora questo progetto ha spazio per fino a :max unità: chiedi illimitato per togliere il limite.',
    'limit_members'          => 'Per ora questo progetto ha spazio per fino a :max persone: chiedi illimitato per togliere il limite.',
    'limit_invitations'      => 'Per ora questo progetto può inviare fino a :max inviti al giorno: chiedi illimitato per togliere il limite.',
    'limit_storage'          => 'Per ora questo progetto ha spazio per :max MB di file: chiedi illimitato per togliere il limite.',
    'image_too_large'        => 'Si possono condividere immagini fino a :max megapixel; questa ne ha di più.',
    'chat_people'            => 'Scegli persone del progetto con cui parlare.',
    'chat_group_limit'       => 'Sei in :max gruppi, il massimo: lasciane uno per crearne un altro.',
    'chat_group_limit_other' => ':name è in :max gruppi, il massimo.',
    'guests_remain'          => 'Ci sono ancora ospiti (:count, inviti in corso compresi): prima assegna loro una famiglia o rimuovili.',
    'account_blocked'        => 'Questo account è bloccato: non può essere invitato.',
    'account_protected'      => 'Né il tuo account né quello di un superadmin: prima va tolto il ruolo.',
    'feature_off'            => 'Questo progetto ha disattivato questo tipo di file.',
];
