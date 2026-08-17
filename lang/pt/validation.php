<?php

declare(strict_types=1);

/**
 * Retorna as mensagens de validação em português.
 *
 * As chaves permanecem em inglês por corresponderem aos nomes das regras
 * utilizadas pelo sistema de validação do Laravel.
 *
 * @since 1.0.0
 */
return [
    'accepted' => 'O campo :attribute deve ser aceite.',
    'accepted_if' => 'O campo :attribute deve ser aceite quando :other for :value.',
    'active_url' => 'O campo :attribute deve ser um URL válido.',
    'after' => 'O campo :attribute deve ser uma data posterior a :date.',
    'after_or_equal' => 'O campo :attribute deve ser uma data posterior ou igual a :date.',
    'alpha' => 'O campo :attribute deve conter apenas letras.',
    'alpha_dash' => 'O campo :attribute deve conter apenas letras, números, hífenes e sublinhados.',
    'alpha_num' => 'O campo :attribute deve conter apenas letras e números.',
    'any_of' => 'O campo :attribute é inválido.',
    'array' => 'O campo :attribute deve ser uma matriz.',
    'ascii' => 'O campo :attribute deve conter apenas caracteres alfanuméricos e símbolos de byte único.',
    'before' => 'O campo :attribute deve ser uma data anterior a :date.',
    'before_or_equal' => 'O campo :attribute deve ser uma data anterior ou igual a :date.',

    'between' => [
        'array' => 'O campo :attribute deve ter entre :min e :max elementos.',
        'file' => 'O ficheiro :attribute deve ter entre :min e :max quilobytes.',
        'numeric' => 'O campo :attribute deve ter um valor entre :min e :max.',
        'string' => 'O campo :attribute deve ter entre :min e :max caracteres.',
    ],

    'boolean' => 'O campo :attribute deve ser verdadeiro ou falso.',
    'can' => 'O campo :attribute contém um valor não autorizado.',
    'confirmed' => 'A confirmação do campo :attribute não coincide.',
    'contains' => 'O campo :attribute não contém um valor obrigatório.',
    'current_password' => 'A palavra-passe está incorreta.',
    'date' => 'O campo :attribute deve ser uma data válida.',
    'date_equals' => 'O campo :attribute deve ser uma data igual a :date.',
    'date_format' => 'O campo :attribute deve respeitar o formato :format.',
    'decimal' => 'O campo :attribute deve ter :decimal casas decimais.',
    'declined' => 'O campo :attribute deve ser recusado.',
    'declined_if' => 'O campo :attribute deve ser recusado quando :other for :value.',
    'different' => 'Os campos :attribute e :other devem ser diferentes.',
    'digits' => 'O campo :attribute deve ter :digits dígitos.',
    'digits_between' => 'O campo :attribute deve ter entre :min e :max dígitos.',
    'dimensions' => 'O campo :attribute tem dimensões de imagem inválidas.',
    'distinct' => 'O campo :attribute contém um valor duplicado.',
    'doesnt_contain' => 'O campo :attribute não deve conter nenhum dos seguintes valores: :values.',
    'doesnt_end_with' => 'O campo :attribute não deve terminar com nenhum dos seguintes valores: :values.',
    'doesnt_start_with' => 'O campo :attribute não deve começar com nenhum dos seguintes valores: :values.',
    'email' => 'O campo :attribute deve ser um endereço de email válido.',
    'encoding' => 'O campo :attribute deve usar a codificação :encoding.',
    'ends_with' => 'O campo :attribute deve terminar com um dos seguintes valores: :values.',
    'enum' => 'O valor selecionado para :attribute é inválido.',
    'exists' => 'O valor selecionado para :attribute é inválido.',
    'extensions' => 'O ficheiro :attribute deve ter uma das seguintes extensões: :values.',
    'file' => 'O campo :attribute deve ser um ficheiro.',
    'filled' => 'O campo :attribute deve ter um valor.',

    'gt' => [
        'array' => 'O campo :attribute deve ter mais de :value elementos.',
        'file' => 'O ficheiro :attribute deve ter mais de :value quilobytes.',
        'numeric' => 'O campo :attribute deve ter um valor superior a :value.',
        'string' => 'O campo :attribute deve ter mais de :value caracteres.',
    ],

    'gte' => [
        'array' => 'O campo :attribute deve ter, pelo menos, :value elementos.',
        'file' => 'O ficheiro :attribute deve ter, pelo menos, :value quilobytes.',
        'numeric' => 'O campo :attribute deve ter um valor superior ou igual a :value.',
        'string' => 'O campo :attribute deve ter, pelo menos, :value caracteres.',
    ],

    'hex_color' => 'O campo :attribute deve ser uma cor hexadecimal válida.',
    'image' => 'O campo :attribute deve ser uma imagem.',
    'in' => 'O valor selecionado para :attribute é inválido.',
    'in_array' => 'O campo :attribute deve existir em :other.',
    'in_array_keys' => 'O campo :attribute deve conter, pelo menos, uma das seguintes chaves: :values.',
    'integer' => 'O campo :attribute deve ser um número inteiro.',
    'ip' => 'O campo :attribute deve ser um endereço IP válido.',
    'ipv4' => 'O campo :attribute deve ser um endereço IPv4 válido.',
    'ipv6' => 'O campo :attribute deve ser um endereço IPv6 válido.',
    'json' => 'O campo :attribute deve ser uma cadeia JSON válida.',
    'list' => 'O campo :attribute deve ser uma lista.',
    'lowercase' => 'O campo :attribute deve estar em minúsculas.',

    'lt' => [
        'array' => 'O campo :attribute deve ter menos de :value elementos.',
        'file' => 'O ficheiro :attribute deve ter menos de :value quilobytes.',
        'numeric' => 'O campo :attribute deve ter um valor inferior a :value.',
        'string' => 'O campo :attribute deve ter menos de :value caracteres.',
    ],

    'lte' => [
        'array' => 'O campo :attribute não deve ter mais de :value elementos.',
        'file' => 'O ficheiro :attribute deve ter, no máximo, :value quilobytes.',
        'numeric' => 'O campo :attribute deve ter um valor inferior ou igual a :value.',
        'string' => 'O campo :attribute deve ter, no máximo, :value caracteres.',
    ],

    'mac_address' => 'O campo :attribute deve ser um endereço MAC válido.',

    'max' => [
        'array' => 'O campo :attribute não deve ter mais de :max elementos.',
        'file' => 'O ficheiro :attribute não deve ter mais de :max quilobytes.',
        'numeric' => 'O campo :attribute não deve ter um valor superior a :max.',
        'string' => 'O campo :attribute não deve ter mais de :max caracteres.',
    ],

    'max_digits' => 'O campo :attribute não deve ter mais de :max dígitos.',
    'mimes' => 'O ficheiro :attribute deve ser de um dos seguintes tipos: :values.',
    'mimetypes' => 'O ficheiro :attribute deve ser de um dos seguintes tipos: :values.',

    'min' => [
        'array' => 'O campo :attribute deve ter, pelo menos, :min elementos.',
        'file' => 'O ficheiro :attribute deve ter, pelo menos, :min quilobytes.',
        'numeric' => 'O campo :attribute deve ter um valor mínimo de :min.',
        'string' => 'O campo :attribute deve ter, pelo menos, :min caracteres.',
    ],

    'min_digits' => 'O campo :attribute deve ter, pelo menos, :min dígitos.',
    'missing' => 'O campo :attribute deve estar ausente.',
    'missing_if' => 'O campo :attribute deve estar ausente quando :other for :value.',
    'missing_unless' => 'O campo :attribute deve estar ausente, exceto quando :other for :value.',
    'missing_with' => 'O campo :attribute deve estar ausente quando :values estiver presente.',
    'missing_with_all' => 'O campo :attribute deve estar ausente quando todos os campos :values estiverem presentes.',
    'multiple_of' => 'O campo :attribute deve ser um múltiplo de :value.',
    'not_in' => 'O valor selecionado para :attribute é inválido.',
    'not_regex' => 'O formato do campo :attribute é inválido.',
    'numeric' => 'O campo :attribute deve ser um número.',

    'password' => [
        'letters' => 'O campo :attribute deve conter, pelo menos, uma letra.',
        'mixed' => 'O campo :attribute deve conter, pelo menos, uma letra maiúscula e uma letra minúscula.',
        'numbers' => 'O campo :attribute deve conter, pelo menos, um número.',
        'symbols' => 'O campo :attribute deve conter, pelo menos, um símbolo.',
        'uncompromised' => 'O valor indicado para :attribute apareceu numa fuga de dados. Escolhe um valor diferente.',
    ],

    'present' => 'O campo :attribute deve estar presente.',
    'present_if' => 'O campo :attribute deve estar presente quando :other for :value.',
    'present_unless' => 'O campo :attribute deve estar presente, exceto quando :other for :value.',
    'present_with' => 'O campo :attribute deve estar presente quando :values estiver presente.',
    'present_with_all' => 'O campo :attribute deve estar presente quando todos os campos :values estiverem presentes.',
    'prohibited' => 'O campo :attribute é proibido.',
    'prohibited_if' => 'O campo :attribute é proibido quando :other for :value.',
    'prohibited_if_accepted' => 'O campo :attribute é proibido quando :other for aceite.',
    'prohibited_if_declined' => 'O campo :attribute é proibido quando :other for recusado.',
    'prohibited_unless' => 'O campo :attribute é proibido, exceto quando :other estiver em :values.',
    'prohibits' => 'O campo :attribute impede que :other esteja presente.',
    'regex' => 'O formato do campo :attribute é inválido.',
    'required' => 'O campo :attribute é obrigatório.',
    'required_array_keys' => 'O campo :attribute deve conter entradas para: :values.',
    'required_if' => 'O campo :attribute é obrigatório quando :other for :value.',
    'required_if_accepted' => 'O campo :attribute é obrigatório quando :other for aceite.',
    'required_if_declined' => 'O campo :attribute é obrigatório quando :other for recusado.',
    'required_unless' => 'O campo :attribute é obrigatório, exceto quando :other estiver em :values.',
    'required_with' => 'O campo :attribute é obrigatório quando :values estiver presente.',
    'required_with_all' => 'O campo :attribute é obrigatório quando todos os campos :values estiverem presentes.',
    'required_without' => 'O campo :attribute é obrigatório quando :values não estiver presente.',
    'required_without_all' => 'O campo :attribute é obrigatório quando nenhum dos campos :values estiver presente.',
    'same' => 'Os campos :attribute e :other devem coincidir.',

    'size' => [
        'array' => 'O campo :attribute deve conter :size elementos.',
        'file' => 'O ficheiro :attribute deve ter :size quilobytes.',
        'numeric' => 'O campo :attribute deve ter o valor :size.',
        'string' => 'O campo :attribute deve ter :size caracteres.',
    ],

    'starts_with' => 'O campo :attribute deve começar com um dos seguintes valores: :values.',
    'string' => 'O campo :attribute deve ser uma cadeia de caracteres.',
    'timezone' => 'O campo :attribute deve ser um fuso horário válido.',
    'unique' => 'O valor do campo :attribute já está a ser utilizado.',
    'uploaded' => 'Não foi possível carregar o ficheiro :attribute.',
    'uppercase' => 'O campo :attribute deve estar em maiúsculas.',
    'url' => 'O campo :attribute deve ser um URL válido.',
    'ulid' => 'O campo :attribute deve ser um ULID válido.',
    'uuid' => 'O campo :attribute deve ser um UUID válido.',

    'custom' => [
        'password' => [
            'min' => 'A tua palavra-passe deve ter, pelo menos, 8 caracteres.',
        ],

        'email' => [
            'exists' => 'Não foi encontrado nenhum utilizador com esse endereço de email.',
        ],
    ],

    'attributes' => [
        'email' => 'email',
        'password' => 'palavra-passe',
        'password_confirmation' => 'confirmação da palavra-passe',
        'name' => 'nome',
        'nome' => 'nome',
        'papel' => 'papel',
        'fotografia' => 'fotografia',
    ],
];
