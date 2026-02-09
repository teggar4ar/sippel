<?php

declare(strict_types=1);

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

    'accepted' => 'Isian :attribute harus diterima.',
    'accepted_if' => 'Isian :attribute harus diterima bila :other adalah :value.',
    'active_url' => 'Isian :attribute bukan URL yang valid.',
    'after' => 'Isian :attribute harus tanggal setelah :date.',
    'after_or_equal' => 'Isian :attribute harus tanggal setelah atau sama dengan :date.',
    'alpha' => 'Isian :attribute hanya boleh berisi huruf.',
    'alpha_dash' => 'Isian :attribute hanya boleh berisi huruf, angka, tanda hubung, dan garis bawah.',
    'alpha_num' => 'Isian :attribute hanya boleh berisi huruf dan angka.',
    'array' => 'Isian :attribute harus berupa array.',
    'ascii' => 'Isian :attribute hanya boleh berisi karakter alfanumerik dan simbol byte tunggal.',
    'before' => 'Isian :attribute harus tanggal sebelum :date.',
    'before_or_equal' => 'Isian :attribute harus tanggal sebelum atau sama dengan :date.',
    'between' => [
        'array' => 'Isian :attribute harus memiliki antara :min dan :max item.',
        'file' => 'Isian :attribute harus antara :min dan :max kilobyte.',
        'numeric' => 'Isian :attribute harus antara :min dan :max.',
        'string' => 'Isian :attribute harus antara :min dan :max karakter.',
    ],
    'boolean' => 'Isian :attribute harus berupa true atau false.',
    'can' => 'Isian :attribute berisi nilai yang tidak sah.',
    'confirmed' => 'Konfirmasi :attribute tidak cocok.',
    'contains' => 'Isian :attribute kehilangan nilai yang diperlukan.',
    'current_password' => 'Kata sandi salah.',
    'date' => 'Isian :attribute bukan tanggal yang valid.',
    'date_equals' => 'Isian :attribute harus tanggal yang sama dengan :date.',
    'date_format' => 'Isian :attribute tidak cocok dengan format :format.',
    'decimal' => 'Isian :attribute harus memiliki :decimal tempat desimal.',
    'declined' => 'Isian :attribute harus ditolak.',
    'declined_if' => 'Isian :attribute harus ditolak bila :other adalah :value.',
    'different' => 'Isian :attribute dan :other harus berbeda.',
    'digits' => 'Isian :attribute harus :digits digit.',
    'digits_between' => 'Isian :attribute harus antara :min dan :max digit.',
    'dimensions' => 'Isian :attribute memiliki dimensi gambar yang tidak valid.',
    'distinct' => 'Isian :attribute memiliki nilai duplikat.',
    'doesnt_end_with' => 'Isian :attribute tidak boleh diakhiri dengan salah satu dari berikut: :values.',
    'doesnt_start_with' => 'Isian :attribute tidak boleh dimulai dengan salah satu dari berikut: :values.',
    'email' => 'Isian :attribute harus alamat email yang valid.',
    'ends_with' => 'Isian :attribute harus diakhiri dengan salah satu dari berikut: :values.',
    'enum' => ':attribute yang dipilih tidak valid.',
    'exists' => ':attribute yang dipilih tidak valid.',
    'extensions' => 'Isian :attribute harus memiliki salah satu ekstensi berikut: :values.',
    'file' => 'Isian :attribute harus berupa file.',
    'filled' => 'Isian :attribute harus memiliki nilai.',
    'gt' => [
        'array' => 'Isian :attribute harus memiliki lebih dari :value item.',
        'file' => 'Isian :attribute harus lebih besar dari :value kilobyte.',
        'numeric' => 'Isian :attribute harus lebih besar dari :value.',
        'string' => 'Isian :attribute harus lebih besar dari :value karakter.',
    ],
    'gte' => [
        'array' => 'Isian :attribute harus memiliki :value item atau lebih.',
        'file' => 'Isian :attribute harus lebih besar dari atau sama dengan :value kilobyte.',
        'numeric' => 'Isian :attribute harus lebih besar dari atau sama dengan :value.',
        'string' => 'Isian :attribute harus lebih besar dari atau sama dengan :value karakter.',
    ],
    'hex_color' => 'Isian :attribute harus berupa warna heksadesimal yang valid.',
    'image' => 'Isian :attribute harus berupa gambar.',
    'in' => ':attribute yang dipilih tidak valid.',
    'in_array' => 'Isian :attribute harus ada di :other.',
    'integer' => 'Isian :attribute harus berupa bilangan bulat.',
    'ip' => 'Isian :attribute harus alamat IP yang valid.',
    'ipv4' => 'Isian :attribute harus alamat IPv4 yang valid.',
    'ipv6' => 'Isian :attribute harus alamat IPv6 yang valid.',
    'json' => 'Isian :attribute harus berupa string JSON yang valid.',
    'list' => 'Isian :attribute harus berupa daftar.',
    'lowercase' => 'Isian :attribute harus huruf kecil.',
    'lt' => [
        'array' => 'Isian :attribute harus memiliki kurang dari :value item.',
        'file' => 'Isian :attribute harus kurang dari :value kilobyte.',
        'numeric' => 'Isian :attribute harus kurang dari :value.',
        'string' => 'Isian :attribute harus kurang dari :value karakter.',
    ],
    'lte' => [
        'array' => 'Isian :attribute tidak boleh memiliki lebih dari :value item.',
        'file' => 'Isian :attribute harus kurang dari atau sama dengan :value kilobyte.',
        'numeric' => 'Isian :attribute harus kurang dari atau sama dengan :value.',
        'string' => 'Isian :attribute harus kurang dari atau sama dengan :value karakter.',
    ],
    'mac_address' => 'Isian :attribute harus alamat MAC yang valid.',
    'max' => [
        'array' => 'Isian :attribute tidak boleh memiliki lebih dari :max item.',
        'file' => 'Isian :attribute tidak boleh lebih besar dari :max kilobyte.',
        'numeric' => 'Isian :attribute tidak boleh lebih besar dari :max.',
        'string' => 'Isian :attribute tidak boleh lebih besar dari :max karakter.',
    ],
    'max_digits' => 'Isian :attribute tidak boleh memiliki lebih dari :max digit.',
    'mimes' => 'Isian :attribute harus berupa file dengan tipe: :values.',
    'mimetypes' => 'Isian :attribute harus berupa file dengan tipe: :values.',
    'min' => [
        'array' => 'Isian :attribute harus memiliki setidaknya :min item.',
        'file' => 'Isian :attribute harus setidaknya :min kilobyte.',
        'numeric' => 'Isian :attribute harus setidaknya :min.',
        'string' => 'Isian :attribute harus setidaknya :min karakter.',
    ],
    'min_digits' => 'Isian :attribute harus memiliki setidaknya :min digit.',
    'missing' => 'Isian :attribute harus hilang.',
    'missing_if' => 'Isian :attribute harus hilang bila :other adalah :value.',
    'missing_unless' => 'Isian :attribute harus hilang kecuali :other adalah :value.',
    'missing_with' => 'Isian :attribute harus hilang bila :values ada.',
    'missing_with_all' => 'Isian :attribute harus hilang bila :values ada.',
    'multiple_of' => 'Isian :attribute harus kelipatan dari :value.',
    'not_in' => ':attribute yang dipilih tidak valid.',
    'not_regex' => 'Format isian :attribute tidak valid.',
    'numeric' => 'Isian :attribute harus berupa angka.',
    'password' => [
        'letters' => 'Isian :attribute harus mengandung setidaknya satu huruf.',
        'mixed' => 'Isian :attribute harus mengandung setidaknya satu huruf besar dan satu huruf kecil.',
        'numbers' => 'Isian :attribute harus mengandung setidaknya satu angka.',
        'symbols' => 'Isian :attribute harus mengandung setidaknya satu simbol.',
        'uncompromised' => ':attribute yang diberikan telah muncul dalam kebocoran data. Silakan pilih :attribute yang berbeda.',
    ],
    'present' => 'Isian :attribute harus ada.',
    'present_if' => 'Isian :attribute harus ada bila :other adalah :value.',
    'present_unless' => 'Isian :attribute harus ada kecuali :other adalah :value.',
    'present_with' => 'Isian :attribute harus ada bila :values ada.',
    'present_with_all' => 'Isian :attribute harus ada bila :values ada.',
    'prohibited' => 'Isian :attribute dilarang.',
    'prohibited_if' => 'Isian :attribute dilarang bila :other adalah :value.',
    'prohibited_unless' => 'Isian :attribute dilarang kecuali :other ada di :values.',
    'prohibits' => 'Isian :attribute melarang :other dari keberadaan.',
    'regex' => 'Format isian :attribute tidak valid.',
    'required' => 'Isian :attribute wajib diisi.',
    'required_array_keys' => 'Isian :attribute harus berisi entri untuk: :values.',
    'required_if' => 'Isian :attribute wajib diisi bila :other adalah :value.',
    'required_if_accepted' => 'Isian :attribute wajib diisi bila :other diterima.',
    'required_if_declined' => 'Isian :attribute wajib diisi bila :other ditolak.',
    'required_unless' => 'Isian :attribute wajib diisi kecuali :other ada di :values.',
    'required_with' => 'Isian :attribute wajib diisi bila :values ada.',
    'required_with_all' => 'Isian :attribute wajib diisi bila :values ada.',
    'required_without' => 'Isian :attribute wajib diisi bila :values tidak ada.',
    'required_without_all' => 'Isian :attribute wajib diisi bila tidak ada :values yang ada.',
    'same' => 'Isian :attribute harus cocok dengan :other.',
    'size' => [
        'array' => 'Isian :attribute harus berisi :size item.',
        'file' => 'Isian :attribute harus :size kilobyte.',
        'numeric' => 'Isian :attribute harus :size.',
        'string' => 'Isian :attribute harus :size karakter.',
    ],
    'starts_with' => 'Isian :attribute harus dimulai dengan salah satu dari berikut: :values.',
    'string' => 'Isian :attribute harus berupa string.',
    'timezone' => 'Isian :attribute harus zona waktu yang valid.',
    'unique' => ':attribute sudah digunakan.',
    'uploaded' => ':attribute gagal diunggah.',
    'uppercase' => 'Isian :attribute harus huruf besar.',
    'url' => 'Isian :attribute harus URL yang valid.',
    'ulid' => 'Isian :attribute harus ULID yang valid.',
    'uuid' => 'Isian :attribute harus UUID yang valid.',

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

    'attributes' => [
        'name' => 'nama',
        'username' => 'nama pengguna',
        'email' => 'alamat email',
        'password' => 'kata sandi',
        'password_confirmation' => 'konfirmasi kata sandi',
    ],

];
