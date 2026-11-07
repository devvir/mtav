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

    'accepted'        => 'Devi accettare «:attribute».',
    'accepted_if'     => 'Devi accettare «:attribute» quando :other è :value.',
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
        'numeric' => '«:attribute» deve essere tra :min e :max.',
        'string'  => '«:attribute» deve avere tra :min e :max caratteri.',
    ],
    'boolean'           => 'Il campo :attribute deve essere vero o falso.',
    'can'               => 'Il campo :attribute contiene un valore non consentito.',
    'confirmed'         => 'Le due caselle di «:attribute» non coincidono. Riprova.',
    'contains'          => 'Nel campo :attribute manca un valore obbligatorio.',
    'current_password'  => 'La password non è corretta.',
    'date'              => '«:attribute» non è una data valida. Controlla e riprova.',
    'date_equals'       => 'Il campo :attribute deve essere una data uguale al :date.',
    'date_format'       => 'Il campo :attribute deve rispettare il formato :format.',
    'decimal'           => 'Il campo :attribute deve avere :decimal decimali.',
    'declined'          => 'Devi rifiutare «:attribute».',
    'declined_if'       => 'Devi rifiutare «:attribute» quando :other è :value.',
    'different'         => 'I campi :attribute e :other devono essere diversi.',
    'digits'            => 'Il campo :attribute deve avere :digits cifre.',
    'digits_between'    => 'Il campo :attribute deve avere tra :min e :max cifre.',
    'dimensions'        => 'Le dimensioni dell’immagine del campo :attribute non sono valide.',
    'distinct'          => 'Il campo :attribute ha un valore duplicato.',
    'doesnt_end_with'   => 'Il campo :attribute non deve finire con uno di questi: :values.',
    'doesnt_start_with' => 'Il campo :attribute non deve iniziare con uno di questi: :values.',
    'email'             => '«:attribute» non sembra un indirizzo email. Controlla e riprova.',
    'ends_with'         => 'Il campo :attribute deve finire con uno di questi: :values.',
    'enum'              => 'Scegli una delle opzioni di «:attribute».',
    'exists'            => 'Scegli una delle opzioni di «:attribute».',
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
    'image'         => '«:attribute» deve essere un’immagine.',
    'in'            => 'Scegli una delle opzioni di «:attribute».',
    'in_array'      => 'Il campo :attribute deve esistere in :other.',
    'in_array_keys' => 'Il campo :attribute deve contenere almeno una di queste chiavi: :values.',
    'integer'       => '«:attribute» deve essere un numero intero.',
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
        'array'   => '«:attribute» può avere fino a :max elementi.',
        'file'    => '«:attribute» è troppo grande. Può arrivare a :max KB.',
        'numeric' => '«:attribute» può arrivare a :max.',
        'string'  => '«:attribute» può avere fino a :max caratteri.',
    ],
    'max_digits' => 'Il campo :attribute non deve avere più di :max cifre.',
    'mimes'      => '«:attribute» deve essere di uno di questi tipi di file: :values.',
    'mimetypes'  => '«:attribute» deve essere di uno di questi tipi di file: :values.',
    'min'        => [
        'array'   => '«:attribute» deve avere almeno :min elementi.',
        'file'    => 'Il campo :attribute deve essere almeno di :min kilobyte.',
        'numeric' => '«:attribute» deve essere almeno :min.',
        'string'  => '«:attribute» deve avere almeno :min caratteri.',
    ],
    'min_digits'       => 'Il campo :attribute deve avere almeno :min cifre.',
    'missing'          => 'Il campo :attribute deve mancare.',
    'missing_if'       => 'Il campo :attribute deve mancare quando :other è :value.',
    'missing_unless'   => 'Il campo :attribute deve mancare a meno che :other sia :value.',
    'missing_with'     => 'Il campo :attribute deve mancare quando :values è presente.',
    'missing_with_all' => 'Il campo :attribute deve mancare quando :values sono presenti.',
    'multiple_of'      => 'Il campo :attribute deve essere un multiplo di :value.',
    'not_in'           => 'Il valore scelto per «:attribute» non è valido.',
    'not_regex'        => 'Il formato del campo :attribute non è valido.',
    'numeric'          => '«:attribute» deve essere un numero.',
    'password'         => [
        'letters'       => 'Il campo :attribute deve contenere almeno una lettera.',
        'mixed'         => 'Il campo :attribute deve contenere almeno una lettera maiuscola e una minuscola.',
        'numbers'       => 'Il campo :attribute deve contenere almeno un numero.',
        'symbols'       => 'Il campo :attribute deve contenere almeno un simbolo.',
        'uncompromised' => 'Questa :attribute è comparsa in una fuga di dati. Scegli un’altra :attribute.',
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
    'regex'                  => 'Controlla «:attribute»: non è scritto nel modo giusto.',
    'required'               => 'Compila «:attribute».',
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
    'unique'      => '«:attribute» è già in uso.',
    'uploaded'    => 'Non è stato possibile inviare «:attribute». Riprova.',
    'uppercase'   => 'Il campo :attribute deve essere in maiuscolo.',
    'url'         => '«:attribute» non è un link valido. Controlla e riprova.',
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
        'identifier'         => 'nome o numero',
        'unit_type_id'       => 'tipo di alloggio',
        'new_unit_type.name' => 'nome del tipo di alloggio',
        'family_id'          => 'famiglia',
        'title'              => 'titolo',
        'location'           => 'luogo',
        'start_date'         => 'data di inizio',
        'end_date'           => 'data di fine',
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
    'cannot_revoke_self'        => 'Non puoi togliere il tuo stesso ruolo di superamministratore.',
    'no_account_with_email'     => 'Non c’è nessun account con questa email.',
    'email_taken'               => 'Un altro account usa già questa email.',
    'family_has_unit'           => 'Questa famiglia ha già ricevuto un alloggio nel sorteggio: non si può togliere.',
    'family_has_people'         => 'In questa famiglia ci sono ancora delle persone: spostale in un’altra famiglia o toglile prima dal progetto.',
    'owner_stays'               => 'Chi possiede la piattaforma mantiene il suo account.',
    'owner_keeps_role'          => 'Chi possiede la piattaforma mantiene questo ruolo.',
    'email_changes_limit'       => 'Hai cambiato email più volte di recente: riprova più tardi.',
    'superadmin_unconfirmed'    => 'Questo account non ha confermato l’email o è bloccato: non può avere questo ruolo.',
    'only_admin_of'             => 'Sei l’unico amministratore di :projects. Nomina prima un altro amministratore, o elimina il progetto.',
    'last_admin'                => 'Un progetto ha bisogno di almeno un amministratore.',
    'member_needs_family'       => 'Chi non è amministratore deve far parte di una famiglia.',
    'preferences_foreign_units' => 'Puoi mettere in ordine solo alloggi del tipo della tua famiglia.',
    'unit_assigned'             => 'Questo alloggio è già stato assegnato a una famiglia nel sorteggio: non si può togliere.',
    'plan_unit_items'           => 'Solo la forma di un alloggio è un alloggio. Aggiungi gli alloggi dalla tavolozza della planimetria o dalla pagina degli alloggi.',
    'unit_type_in_use'          => 'Famiglie o alloggi usano ancora questo tipo.',

    /** Limits of projects awaiting approval */
    'limit_families'         => 'Per ora questo progetto ha spazio per :max famiglie al massimo. Chiedi di togliere i limiti.',
    'limit_units'            => 'Per ora questo progetto ha spazio per :max alloggi al massimo. Chiedi di togliere i limiti.',
    'limit_members'          => 'Per ora questo progetto ha spazio per :max persone al massimo. Chiedi di togliere i limiti.',
    'limit_invitations'      => 'Per ora questo progetto può inviare :max inviti al giorno al massimo. Chiedi di togliere i limiti.',
    'limit_storage'          => 'Per ora questo progetto ha spazio per :max MB di file. Chiedi di togliere i limiti.',
    'image_too_large'        => 'Si possono condividere immagini fino a :max megapixel; questa ne ha di più.',
    'chat_people'            => 'Scegli persone del progetto con cui parlare.',
    'push_device'            => 'Questo dispositivo non può ricevere le notifiche da qui.',
    'chat_group_limit'       => 'Sei in :max gruppi, il massimo: lasciane uno per crearne un altro.',
    'chat_group_limit_other' => ':name è in :max gruppi, il massimo.',
    'guests_remain'          => 'Ci sono ancora ospiti (:count, contando gli inviti in arrivo). Prima assegna loro una famiglia o toglili.',
    'account_blocked'        => 'Questo account è bloccato: non può essere invitato.',
    'account_protected'      => 'Né il tuo account né quello di un superamministratore: prima va tolto il ruolo.',
    'feature_off'            => 'Questo progetto ha disattivato questo tipo di file.',
    'family_already_in'      => 'Sei già in questa famiglia.',
    'family_declined'        => 'Questa famiglia ha rifiutato la tua richiesta.',
    'family_request_open'    => 'Stai già aspettando la risposta di una famiglia: annulla prima quella richiesta.',
    'family_none'            => 'Non fai ancora parte di nessuna famiglia qui.',
    'family_cannot_leave'    => 'Qui tutti partecipano come famiglia: chiedi di entrare nella tua, oppure creala.',
    'family_declined_them'   => 'Questa famiglia ha rifiutato questa persona: solo un suo componente può togliere il rifiuto.',
    'support_email_taken'    => 'Questa email ha già un account qui. Un account di supporto ha bisogno di un indirizzo suo.',
    'support_by_invitation'  => 'Il ruolo di supporto arriva solo con un invito, a un indirizzo non ancora usato qui.',
];
