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

    'accepted' => 'The :attribute Debe aceptarse.',
    'accepted_if' => 'The :attribute Debe aceptarse cuando :other es :value.',
    'active_url' => 'The :attribute No es una URL válida.',
    'after' => 'The :attribute Debe ser una fecha posterior a :date.',
    'after_or_equal' => 'The :attribute Debe ser una fecha posterior o igual a :date.',
    'alpha' => 'The :attribute Solo debe contener letras.',
    'alpha_dash' => 'The :attribute Solo debe contener letras, números, guiones y guiones bajos.',
    'alpha_num' => 'The :attribute Solo debe contener letras y números.',
    'array' => 'The :attribute Debe ser una matriz.',
    'before' => 'The :attribute Debe ser una fecha anterior a :date.',
    'before_or_equal' => 'The :attribute Debe ser una fecha anterior o igual a :date.',
    'between' => [
        'numeric' => 'The :attribute Debe estar entre :min y :max.',
        'file' => 'The :attribute Debe estar entre :min y :max kilobytes.',
        'string' => 'The :attribute Debe estar entre :min y :max caracteres.',
        'array' => 'The :attribute Debe tener entre :min y :max elementos.',
    ],
    'boolean' => 'The :attribute El campo debe ser verdadero o falso.',
    'confirmed' => 'The :attribute La confirmación no coincide.',
    'current_password' => 'The password is incorrect.',
    'date' => 'The :attribute No es una fecha válida.',
    'date_equals' => 'The :attribute Debe ser una fecha igual a :date.',
    'date_format' => 'The :attribute no coincide con el formato :format.',
    'declined' => 'The :attribute debe ser rechazado.',
    'declined_if' => 'The :attribute debe ser rechazado cuando :other es :value.',
    'different' => 'The :attribute y :other debe ser diferente.',
    'digits' => 'The :attribute debe ser :digits dígitos.',
    'digits_between' => 'The :attribute debe estar entre :min y :max dígitos.',
    'dimensions' => 'The :attribute tiene dimensiones de imagen no válidas.',
    'distinct' => 'The :attribute el campo tiene un valor duplicado.',
    'email' => 'The :attribute debe ser una dirección de correo electrónico válida.',
    'ends_with' => 'The :attribute debe terminar con uno de los siguientes: :values.',
    'enum' => 'The selected :attribute no es válido.',
    'exists' => 'The selected :attribute no es válido.',
    'file' => 'The :attribute debe ser un archivo.',
    'filled' => 'The :attribute el campo debe tener un valor.',
    'gt' => [
        'numeric' => 'The :attribute debe ser mayor que :value.',
        'file' => 'The :attribute debe ser mayor que :value kilobytes.',
        'string' => 'The :attribute debe ser mayor que :value caracteres.',
        'array' => 'The :attribute debe tener más de :value elementos.',
    ],
    'gte' => [
        'numeric' => 'The :attribute debe ser mayor o igual que :value.',
        'file' => 'The :attribute debe ser mayor o igual que :value kilobytes.',
        'string' => 'The :attribute debe ser mayor o igual a :value caracteres.',
        'array' => 'The :attribute debe tener :value elementos o más.',
    ],
    'image' => 'The :attribute debe ser una imagen.',
    'in' => 'The selected :attribute no es válido.',
    'in_array' => 'The :attribute el campo no existe en :other.',
    'integer' => 'The :attribute debe ser un entero.',
    'ip' => 'The :attribute debe ser una dirección IP válida.',
    'ipv4' => 'The :attribute debe ser una dirección IPv4 válida.',
    'ipv6' => 'The :attribute debe ser una dirección IPv6 válida.',
    'json' => 'The :attribute debe ser una cadena JSON válida.',
    'lt' => [
        'numeric' => 'The :attribute debe ser menor que :value.',
        'file' => 'The :attribute debe ser menor que :value kilobytes.',
        'string' => 'The :attribute debe ser menor que :value caracteres.',
        'array' => 'The :attribute debe tener menos de :value elementos.',
    ],
    'lte' => [
        'numeric' => 'The :attribute debe ser menor o igual a :value.',
        'file' => 'The :attribute debe ser menor o igual a :value kilobytes.',
        'string' => 'The :attribute debe ser menor o igual a :value caracteres.',
        'array' => 'The :attribute no debe tener más de :value elementos.',
    ],
    'mac_address' => 'The :attribute debe ser una MAC válida dirección.',
    'max' => [
        'numeric' => 'The :attribute no debe ser mayor que :máx.',
        'file' => 'The :attribute no debe ser mayor que :máx kilobytes.',
        'string' => 'The :attribute no debe ser mayor que :máx caracteres.',
        'array' => 'The :attribute no debe tener más de :máx elementos.',
    ],
    'mimes' => 'The :attribute debe ser un archivo de tipo: :valores.',
    'mimetypes' => 'The :attribute debe ser un archivo de tipo: :valores.',
    'min' => [
        'numeric' => 'The :attribute debe tener al menos :mín.',
        'file' => 'The :attribute debe tener al menos :mín kilobytes.',
        'string' => 'The :attribute debe tener al menos :mín caracteres.',
        'array' => 'The :attribute debe tener al menos :mín elementos.',
    ],
    'multiple_of' => 'The :attribute debe ser un múltiplo de :valor.',
    'not_in' => 'The selected :attribute no es válido.',
    'not_regex' => 'The :attribute el formato no es válido.',
    'numeric' => 'The :attribute debe ser un número.',
    'password' => 'The password is incorrect.',
    'present' => 'The :attribute el campo debe estar presente.',
    'prohibited' => 'The :attribute el campo está prohibido.',
    'prohibited_if' => 'The :attribute el campo está prohibido cuando :otro es :valor.',
    'prohibited_unless' => 'The :attribute el campo está prohibido a menos que :otro esté en :valores.',
    'prohibits' => 'The :attribute el campo prohíbe que :otro esté presente.',
    'regex' => 'The :attribute el formato es No válido',
    'required' => 'The :attribute El campo es obligatorio',
    'required_array_keys' => 'The :attribute El campo debe contener entradas para: :values',
    'required_if' => 'The :attribute El campo es obligatorio cuando :other es :value',
    'required_unless' => 'The :attribute El campo es obligatorio a menos que :other esté en :values',
    'required_with' => 'The :attribute El campo es obligatorio cuando :values ​​está presente',
    'required_with_all' => 'The :attribute El campo es obligatorio cuando :values ​​está presente',
    'required_without' => 'The :attribute El campo es obligatorio cuando :values ​​no está presente',
    'required_without_all' => 'The :attribute El campo es obligatorio cuando ninguno de los :values ​​está presente',
    'same' => 'The :attribute y :other debe coincidir',
    'size' => [
        'numeric' => 'The :attribute Debe ser :size',
        'file' => 'The :attribute Debe ser :size kilobytes',
        'string' => 'The :attribute Debe ser :size caracteres',
        'array' => 'The :attribute Debe contener :size elementos',
    ],
    'starts_with' => 'The :attribute Debe comenzar con uno de los siguientes: :values',
    'string' => 'The :attribute Debe ser una cadena',
    'timezone' => 'The :attribute Debe ser una zona horaria válida',
    'unique' => 'El :attribute Ya existe en la base de datos',
    'uploaded' => 'The :attribute Error al cargar',
    'url' => 'The :attribute Debe ser una URL válida',
    'uuid' => 'The :attribute Debe ser una URL válida UUID.',

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
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [],

];
