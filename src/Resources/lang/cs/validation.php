<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | such as the size rules. Feel free to tweak each of these messages.
    |
    */

    'accepted'             => 'Pole musí být akceptován.',
    'active_url'           => 'Pole není platnou URL adresou.',
    'after'                => 'Pole musí být datum po :date.',
    'alpha'                => 'Pole může obsahovat pouze písmena.',
    'alpha_dash'           => 'Pole může obsahovat pouze písmena, číslice, pomlčky a podtržítka. České znaky (á, é, í, ó, ú, ů, ž, š, č, ř, ď, ť, ň) nejsou podporovány.',
    'alpha_num'            => 'Pole může obsahovat pouze písmena a číslice.',
    'array'                => 'Pole musí být pole.',
    'before'               => 'Pole musí být datum před :date.',
    'between'              => [
        'numeric' => 'Pole musí být hodnota mezi :min a :max.',
        'file'    => 'Pole musí být větší než :min a menší než :max Kilobytů.',
        'string'  => 'Pole musí být delší než :min a kratší než :max znaků.',
        'array'   => 'Pole musí obsahovat nejméně :min a nesmí obsahovat více než :max prvků.',
    ],
    'boolean'              => 'Pole musí být true nebo false',
    'confirmed'            => 'Pole se nezhoduje.',
    'date'                 => 'Pole musí být platné datum.',
    'date_format'          => 'Pole není platný formát data podle :format.',
    'different'            => 'Pole a :other se musí lišit.',
    'digits'               => 'Pole musí být :digits pozic dlouhé.',
    'digits_between'       => 'Pole musí být dlouhé nejméně :min a nejvíce :max pozic.',
    'distinct'             => 'The Pole field has a duplicate value.',
    'email'                => 'Pole není platný formát.',
    'exists'               => 'Zvolená hodnota pro Pole není platná.',
    'filled'               => 'Pole musí být vyplněno.',
    'image'                => 'Pole musí být obrázek.',
    'in'                   => 'Zvolená hodnota pro Pole není platná.',
    'in_array'             => 'The Pole field does not exist in :other.',
    'integer'              => 'Pole musí být celé číslo.',
    'ip'                   => 'Pole musí být platnou IP adresou.',
    'json'                 => 'Pole musí být platný JSON řetězec.',
    'max'                  => [
        'numeric' => 'Pole musí být nižší než :max.',
        'file'    => 'Pole musí být menší než :max Kilobytů.',
        'string'  => 'Pole musí být kratší než :max znaků.',
        'array'   => 'Pole nesmí obsahovat více než :max prvků.',
    ],
    'mimes'                => 'Pole musí být jeden z následujících datových typů :values.',
    'min'                  => [
        'numeric' => 'Pole musí být větší než :min.',
        'file'    => 'Pole musí být větší než :min Kilobytů.',
        'string'  => 'Pole musí být delší než :min znaků.',
        'array'   => 'Pole musí obsahovat více než :min prvků.',
    ],
    'not_in'               => 'Zvolená hodnota pro Pole je neplatná.',
    'numeric'              => 'Pole musí být číslo.',
    'present'              => 'The Pole field must be present.',
    'regex'                => 'Pole nemá správný formát.',
    'required'             => 'Pole musí být vyplněno.',
    'required_if'          => 'Pole musí být vyplněno.',
    'required_unless'      => 'Pole musí být vyplněno.',
    'required_with'        => 'Pole musí být vyplněno.',
    'required_with_all'    => 'Pole musí být vyplněno.',
    'required_without'     => 'Pole musí být vyplněno.',
    'required_without_all' => 'Pole musí být vyplněno.',
    'same'                 => 'Pole a :other se musí shodovat.',
    'size'                 => [
        'numeric' => 'Pole musí být přesně :size.',
        'file'    => 'Pole musí mít přesně :size Kilobytů.',
        'string'  => 'Pole musí být přesně :size znaků dlouhý.',
        'array'   => 'Pole musí obsahovat právě :size prvků.',
    ],
    'string'               => 'Pole musí být řetězec znaků.',
    'timezone'             => 'Pole musí být platná časová zóna.',
    'unique'               => 'Pole musí být unikátní.',
    'url'                  => 'Formát Pole je neplatný.',

    /*
     * My custom rules
     */
    'extensions'            => 'Pole musí byť súbor s koncovkou :values.',
    'iban'                  => 'Zadajte správny IBAN formát.',
    'phone'                 => 'Pole musí být v tvaru tel. čísla.',

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

    'custom'               => [
        //
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap attribute place-holders
    | with something more reader friendly such as E-Mail Address instead
    | of "email". This simply helps us make messages a little cleaner.
    |
    */

    'attributes'           => [
        //
    ],

];
