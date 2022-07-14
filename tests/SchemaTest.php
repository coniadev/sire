<?php

declare(strict_types=1);
// phpcs:disable Generic.Files.LineLength

use Conia\Seiher\Schema;


test('Type int', function () {
    $testData = [
        'valid_int_1' => '13',
        'valid_int_2' => 13,
        'invalid_int_1' => '23invalid',
        'invalid_int_2' => '23.23',
    ];

    $schema = new Schema();
    $schema->add('invalid_int_1', 'Int 1', 'int');
    $schema->add('invalid_int_2', 'Int 2', 'int');
    $schema->add('valid_int_1', 'Int', 'int');
    $schema->add('valid_int_2', 'Int', 'int');

    expect($schema->validate($testData))->toBeFalse();
    $errors = $schema->errors();
    expect($errors['errors'][0]['error'])->toEqual('Invalid number in field "Int 1"');
    expect($errors['errors'][1]['error'])->toEqual('Invalid number in field "Int 2"');
    expect($errors['map']['invalid_int_1'][0])->toEqual('Invalid number in field "Int 1"');
    expect($errors['map']['invalid_int_2'][0])->toEqual('Invalid number in field "Int 2"');
    expect(isset($errors['map']['valid_int_1']))->toBeFalse();
    expect(isset($errors['map']['valid_int_2']))->toBeFalse();

    $values = $schema->values();
    expect(13)->toBe($values['valid_int_1']);
    expect(13)->toBe($values['valid_int_2']);
    expect('23invalid')->toBe($values['invalid_int_1']);

    $pristine = $schema->pristineValues();
    expect('13')->toBe($pristine['valid_int_1']);
    expect(13)->toBe($pristine['valid_int_2']);
});


test('Type float', function () {
    $testData = [
        'valid_float_1' => '13',
        'valid_float_2' => '13.13',
        'valid_float_3' => 13,
        'valid_float_4' => 13.13,
        'invalid_float' => '23.23invalid',
    ];

    $schema = new Schema;
    $schema->add('invalid_float', 'Float', 'float');
    $schema->add('valid_float_1', 'Float', 'float');
    $schema->add('valid_float_2', 'Float', 'float');
    $schema->add('valid_float_3', 'Float', 'float');
    $schema->add('valid_float_4', 'Float', 'float');

    expect($schema->validate($testData))->toBeFalse();
    $errors = $schema->errors();
    expect($errors['errors'][0]['error'])->toEqual('Invalid number in field "Float"');
    expect($errors['map']['invalid_float'][0])->toEqual('Invalid number in field "Float"');
    expect(isset($errors['map']['valid_float_1']))->toBeFalse();
    expect(isset($errors['map']['valid_float_2']))->toBeFalse();
    expect(isset($errors['map']['valid_float_3']))->toBeFalse();
    expect(isset($errors['map']['valid_float_4']))->toBeFalse();
});


test('Type boolean', function () {
    $testData = [
        'valid_bool_1' => true,
        'valid_bool_2' => false,
        'valid_bool_3' => 'yes',
        'valid_bool_4' => 'off',
        'valid_bool_5' => 'true',
        'valid_bool_6' => 'null',
        'valid_bool_8' => null,
        'invalid_bool_1' => 'invalid',
        'invalid_bool_2' => 13,
    ];

    $schema = new Schema();
    $schema->add('valid_bool_1', 'Bool', 'bool');
    $schema->add('valid_bool_2', 'Bool', 'bool');
    $schema->add('valid_bool_3', 'Bool', 'bool');
    $schema->add('valid_bool_4', 'Bool', 'bool');
    $schema->add('valid_bool_5', 'Bool', 'bool');
    $schema->add('valid_bool_6', 'Bool', 'bool');
    $schema->add('valid_bool_7', 'Bool', 'bool');
    $schema->add('valid_bool_8', 'Bool', 'bool');
    $schema->add('invalid_bool_1', 'Bool 1', 'bool');
    $schema->add('invalid_bool_2', 'Bool 2', 'bool');

    expect($schema->validate($testData))->toBeFalse();
    $errors = $schema->errors();
    expect($errors['errors'][0]['error'])->toEqual('Invalid value in field "Bool 1"');
    expect($errors['errors'][1]['error'])->toEqual('Invalid value in field "Bool 2"');
    expect($errors['map']['invalid_bool_1'][0])->toEqual('Invalid value in field "Bool 1"');
    expect($errors['map']['invalid_bool_2'][0])->toEqual('Invalid value in field "Bool 2"');
    expect(isset($errors['map']['valid_bool_1']))->toBeFalse();
    expect(isset($errors['map']['valid_bool_2']))->toBeFalse();

    $values = $schema->values();
    expect(true)->toBe($values['valid_bool_1']);
    expect(false)->toBe($values['valid_bool_2']);
    expect(true)->toBe($values['valid_bool_3']);
    expect(false)->toBe($values['valid_bool_4']);
    expect(true)->toBe($values['valid_bool_5']);
    expect(false)->toBe($values['valid_bool_6']);
    expect(false)->toBe($values['valid_bool_7']);
    expect(false)->toBe($values['valid_bool_8']);

    $pristine = $schema->pristineValues();
    expect('yes')->toBe($pristine['valid_bool_3']);
    expect('invalid')->toBe($pristine['invalid_bool_1']);
    expect(13)->toBe($pristine['invalid_bool_2']);
});


test('Type text', function () {
    $testData = [
        'valid_text_1' => 'Lorem ipsum',
        'valid_text_2' => false,
        'valid_text_3' => true,
        'valid_text_4' => '<a href="/test">Test</a>',
    ];

    $schema = new Schema();
    $schema->add('valid_text_1', 'Text', 'text');
    $schema->add('valid_text_2', 'Text', 'text');
    $schema->add('valid_text_3', 'Text', 'text');
    $schema->add('valid_text_4', 'Text', 'text');
    $schema->add('valid_text_5', 'Text', 'text');

    expect($schema->validate($testData))->toBeTrue();
    expect($schema->errors()['errors'])->toHaveCount(0);

    $values = $schema->values();
    expect('Lorem ipsum')->toBe($values['valid_text_1']);
    expect(null)->toBe($values['valid_text_2']); // empty(false) === true
    expect('1')->toBe($values['valid_text_3']);
    expect('&lt;a href=&quot;/test&quot;&gt;Test&lt;/a&gt;')->toBe($values['valid_text_4']);
    expect(null)->toBe($values['valid_text_5']);

    $pristine = $schema->pristineValues();
    expect(false)->toBe($pristine['valid_text_2']);
    expect(null)->toBe($pristine['valid_text_5']);
});


test('Type skip empty', function () {
    $testData = [
        'valid_text' => '',
    ];

    $schema = new Schema();
    $schema->add('valid_text', 'Text', 'text', 'maxlen');

    expect($schema->validate($testData))->toBeTrue();
});


test('Type plain', function () {
    $testData = [
        'valid_plain_1' => '<a onclick="">Test</a><script></script>',
        'valid_plain_2' => true,
    ];

    $schema = new Schema();
    $schema->add('valid_plain_1', 'Plain', 'plain');
    $schema->add('valid_plain_2', 'Plain', 'plain');
    $schema->add('valid_plain_3', 'Plain', 'plain');

    expect($schema->validate($testData))->toBeTrue();
    expect($schema->errors()['errors'])->toHaveCount(0);

    $values = $schema->values();
    expect('<a onclick="">Test</a><script></script>')->toBe($values['valid_plain_1']);
    expect('1')->toBe($values['valid_plain_2']);
    expect(null)->toBe($values['valid_plain_3']);

    $pristine = $schema->pristineValues();
    expect(true)->toBe($pristine['valid_plain_2']);
    expect(null)->toBe($pristine['valid_plain_3']);
});


test('Type list', function () {
    $testData = [
        'valid_list_1' => [1, 2],
        'valid_list_2' => [['key' => 'data']],
        'invalid_list_1' => 'invalid',
        'invalid_list_2' => 13,
    ];

    $schema = new Schema();
    $schema->add('valid_list_1', 'List', 'list');
    $schema->add('valid_list_2', 'List', 'list');
    $schema->add('invalid_list_1', 'List 1', 'list');
    $schema->add('invalid_list_2', 'List 2', 'list');

    expect($schema->validate($testData))->toBeFalse();
    $errors = $schema->errors();
    expect($errors['errors'][0]['error'])->toEqual('-schema-invalid-list-List 1-');
    expect($errors['errors'][1]['error'])->toEqual('-schema-invalid-list-List 2-');
    expect($errors['map']['invalid_list_1'][0])->toEqual('-schema-invalid-list-List 1-');
    expect($errors['map']['invalid_list_2'][0])->toEqual('-schema-invalid-list-List 2-');
    expect(isset($errors['map']['valid_list_1']))->toBeFalse();
    expect(isset($errors['map']['valid_list_2']))->toBeFalse();

    $values = $schema->values();
    expect([1, 2])->toBe($values['valid_list_1']);
    expect([['key' => 'data']])->toBe($values['valid_list_2']);

    $pristine = $schema->pristineValues();
    expect([1, 2])->toBe($pristine['valid_list_1']);
    expect('invalid')->toBe($pristine['invalid_list_1']);
    expect(13)->toBe($pristine['invalid_list_2']);
});


test('Wrong type', function () {
    $schema = new Schema();
    $schema->add('invalid_field', 'Invalid', 'invalid');
    $schema->validate(['invalid_field' => false]);
})->throws(ValueError::class, 'Wrong schema type');


test('Unknown data', function () {
    $testData = [
        'unknown_1' => 'Test',
        'unknown_2' => '13',
        'unknown_3' => 'Unknown',
        'unknown_4' => '23',
    ];

    $schema = new Schema();
    $schema->add('unknown_1', 'Unknown', 'text');
    $schema->add('unknown_2', 'Unknown', 'int');

    expect($schema->validate($testData))->toBeTrue();
    expect($schema->errors()['errors'])->toHaveCount(0);

    $values = $schema->values();
    expect('Test')->toBe($values['unknown_1']);
    expect(13)->toBe($values['unknown_2']);
    expect(isset($values['unknown_3']))->toBeFalse();

    $pristine = $schema->pristineValues();
    expect('Test')->toBe($pristine['unknown_1']);
    expect('13')->toBe($pristine['unknown_2']);
    expect(isset($pristine['unknown_3']))->toBeFalse();

    // ... now keep them
    $schema = new  Schema(false, true);
    $schema->add('unknown_1', 'Unknown', 'text');
    $schema->add('unknown_2', 'Unknown', 'int');

    expect($schema->validate($testData))->toBeTrue();
    expect($schema->errors()['errors'])->toHaveCount(0);

    $values = $schema->values();
    expect('Test')->toBe($values['unknown_1']);
    expect(13)->toBe($values['unknown_2']);
    expect('Unknown')->toBe($values['unknown_3']);
    expect('23')->toBe($values['unknown_4']);

    $pristine = $schema->pristineValues();
    expect('Test')->toBe($pristine['unknown_1']);
    expect('13')->toBe($pristine['unknown_2']);
    expect('Unknown')->toBe($pristine['unknown_3']);
    expect('23')->toBe($pristine['unknown_4']);
});


test('Required validator', function () {
    $testData = [
        'valid_1' => 'value',
        'valid_2' => false,
        'valid_3' => 0,
        'valid_4' => 0.0,
        'valid_5' => [1],
        'invalid_3' => [],
        'invalid_4' => '',
    ];

    $schema = new Schema();
    $schema->add('valid_1', 'Required', 'text', 'required');
    $schema->add('valid_2', 'Required', 'bool', 'required');
    $schema->add('valid_3', 'Required', 'int', 'required');
    $schema->add('valid_4', 'Required', 'float', 'required');
    $schema->add('valid_5', 'Required', 'list', 'required');
    $schema->add('invalid_1', 'Required 1', 'text', 'required');
    $schema->add('invalid_2', 'Required 2', 'float', 'required');
    $schema->add('invalid_3', 'Required 3', 'list', 'required');
    $schema->add('invalid_4', 'Required 4', 'plain', 'required');

    expect($schema->validate($testData))->toBeFalse();
    $errors = $schema->errors();
    expect($errors['errors'])->toHaveCount(4);
    expect($errors['map']['invalid_1'][0])->toEqual('-schema-required-Required 1-');
    expect($errors['map']['invalid_2'][0])->toEqual('-schema-required-Required 2-');
    expect($errors['map']['invalid_3'][0])->toEqual('-schema-required-Required 3-');
    expect($errors['map']['invalid_4'][0])->toEqual('-schema-required-Required 4-');
});


test('Email validator', function () {
    $testData = [
        'valid_email' => 'valid@email.com',
        'invalid_email' => 'invalid@email',
    ];

    $schema = new Schema();
    $schema->add('invalid_email', 'Email', 'text', 'email');
    $schema->add('valid_email', 'Email', 'text', 'email');

    expect($schema->validate($testData))->toBeFalse();
    $errors = $schema->errors();
    expect($errors['errors'])->toHaveCount(1);
    expect($errors['map']['invalid_email'][0])->toEqual('-schema-invalid-email-Email-invalid@email-');
});


test('Email validator :: with DNS check', function () {
    $testData = [
        'valid_email' => 'valid@gmail.com',
        'invalid_email' => 'invalid@test.tld',
    ];

    $schema = new Schema();
    $schema->add('invalid_email', 'Email', 'text', 'email:checkdns');
    $schema->add('valid_email', 'Email', 'text', 'email:checkdns');

    expect($schema->validate($testData))->toBeFalse();
    $errors = $schema->errors();
    expect($errors['errors'])->toHaveCount(1);
    expect($errors['map']['invalid_email'][0])->toEqual('-schema-invalid-email-Email-invalid@test.tld-');
});


test('Min value validator', function () {
    $testData = [
        'valid_1' => 13,
        'valid_2' => 13,
        'valid_3' => 10,
        'valid_4' => 10,
        'invalid_1' => 7,
        'invalid_2' => 7.13,
    ];

    $schema = new Schema();
    $schema->add('valid_1', 'Min', 'int', 'min:10');
    $schema->add('valid_2', 'Min', 'float', 'min:10');
    $schema->add('valid_3', 'Min', 'int', 'min:10');
    $schema->add('valid_4', 'Min', 'float', 'min:10');
    $schema->add('invalid_1', 'Min', 'int', 'min:10');
    $schema->add('invalid_2', 'Min', 'float', 'min:10');

    expect($schema->validate($testData))->toBeFalse();
    $errors = $schema->errors();
    expect($errors['errors'])->toHaveCount(2);
    expect($errors['map']['invalid_1'][0])->toEqual('-schema-min-Min-7-10-');
    expect($errors['map']['invalid_2'][0])->toEqual('-schema-min-Min-7.13-10-');
});


test('Max value validator', function () {
    $testData = [
        'valid_1' => 13,
        'valid_2' => 13,
        'valid_3' => 10,
        'valid_4' => 10,
        'invalid_1' => 23,
        'invalid_2' => 23.13,
    ];

    $schema = new Schema();
    $schema->add('valid_1', 'Max', 'int', 'max:13');
    $schema->add('valid_2', 'Max', 'float', 'max:13');
    $schema->add('valid_3', 'Max', 'int', 'max:13');
    $schema->add('valid_4', 'Max', 'float', 'max:13');
    $schema->add('invalid_1', 'Max', 'int', 'max:13');
    $schema->add('invalid_2', 'Max', 'float', 'max:13');

    expect($schema->validate($testData))->toBeFalse();
    $errors = $schema->errors();
    expect($errors['errors'])->toHaveCount(2);
    expect($errors['map']['invalid_1'][0])->toEqual('-schema-max-Max-23-13-');
    expect($errors['map']['invalid_2'][0])->toEqual('-schema-max-Max-23.13-13-');
});


test('Min length validator', function () {
    $testData = [
        'valid_1' => 'abcdefghijklm',
        'valid_2' => 'abcdefghij',
        'invalid' => 'abcdefghi',
    ];

    $schema = new Schema();
    $schema->add('valid_1', 'Minlen', 'text', 'minlen:10');
    $schema->add('valid_2', 'Minlen', 'text', 'minlen:10');
    $schema->add('invalid', 'Minlen', 'text', 'minlen:10');

    expect($schema->validate($testData))->toBeFalse();
    $errors = $schema->errors();
    expect($errors['errors'])->toHaveCount(1);
    expect($errors['map']['invalid'][0])->toEqual('-schema-minlen-Minlen-10-');
});


test('Max length validator', function () {
    $testData = [
        'valid_1' => 'abcdefghi',
        'valid_2' => 'abcdefghij',
        'invalid' => 'abcdefghiklm',
    ];

    $schema = new Schema();
    $schema->add('valid_1', 'Maxlen', 'text', 'maxlen:10');
    $schema->add('valid_2', 'Maxlen', 'text', 'maxlen:10');
    $schema->add('invalid', 'Maxlen', 'text', 'maxlen:10');

    expect($schema->validate($testData))->toBeFalse();
    $errors = $schema->errors();
    expect($errors['errors'])->toHaveCount(1);
    expect($errors['map']['invalid'][0])->toEqual('-schema-maxlen-Maxlen-10-');
});


test('Regex validator ', function () {
    $testData = [
        'valid' => 'abcdefghi',
        'invalid' => 'abcdefghiklm',
        'valid_colon' => 'abcdef:ghi:klm:',
        'invalid_colon' => 'abcdef:ghi:klm',
    ];

    $schema = new Schema();
    $schema->add('valid', 'Regex', 'text', 'regex:/^abcdefghi$/');
    $schema->add('invalid', 'Regex', 'text', 'regex:/^abcdefghi$/');
    $schema->add('valid_colon', 'Regex', 'text', 'regex:/^[a-z:]+:$/');
    $schema->add('invalid_colon', 'Regex', 'text', 'regex:/^[a-z:]+:$/');

    expect($schema->validate($testData))->toBeFalse();
    $errors = $schema->errors();
    expect($errors['errors'])->toHaveCount(2);
    expect($errors['map']['invalid'][0])->toEqual("-schema-regex-Regex-abcdefghiklm-");
});


test('In validator ', function () {
    $testData = [
        'valid1' => 'valid',
        'valid2' => 'alsovalid',
        'invalid' => 'invalid',
    ];

    $schema = new Schema();
    $schema->add('valid1', 'In', 'text', 'in:valid,alsovalid');
    $schema->add('valid2', 'In', 'text', 'in:valid,alsovalid');
    $schema->add('invalid', 'In', 'text', 'in:valid,alsovalid');

    expect($schema->validate($testData))->toBeFalse();
    $errors = $schema->errors();
    expect($errors['errors'])->toHaveCount(1);
    expect($errors['map']['invalid'][0])->toEqual('-schema-in-In-valid,alsovalid-');
});


class SubSchema extends Schema
{
    public function rules(): void
    {
        $this->add('inner_int', 'Int', 'int', 'required');
        $this->add('inner_email', 'Email', 'text', 'required', 'email');
    }
}


test('Sub schema', function () {
    $testData = [
        'int' => 13,
        'text' => 'Text',
        'schema' => [
            'inner_int' => 23,
            'inner_email' => 'test@example.com',
        ],
    ];

    $schema = new Schema();
    $schema->add('int', 'Int', 'int', 'required');
    $schema->add('text', 'Text', 'text', 'required');
    $schema->add('schema', 'Schema', new SubSchema());

    expect($schema->validate($testData))->toBeTrue();
});


test('Invalid data in sub schema', function () {
    $testData = [
        'int' => 13,
        'schema' => [
            'inner_int' => 23,
            'inner_email' => 'test INVALID example.com',
        ],
    ];

    $schema = new Schema();
    $schema->add('int', 'Int', 'int', 'required');
    $schema->add('text', 'Text', 'text', 'required');
    $schema->add('schema', 'Schema', new SubSchema());

    expect($schema->validate($testData))->toBeFalse();
    $errors = $schema->errors();
    expect($errors['errors'])->toHaveCount(2);
    expect($errors['map']['text'][0])->toEqual('-schema-required-Text-');
    expect($errors['map']['schema']['inner_email'][0])->toEqual('-schema-invalid-email-Email-test INVALID example.com-');
});


test('List schema', function () {
    $testData = [[
        'int' => 13,
        'text' => 'Text 1',
        'single_schema' => [
            'inner_int' => 23,
            'inner_email' => 'test@example.com',
        ],
    ], [
        'int' => 17,
        'text' => 'Text 2',
        'single_schema' => [
            'inner_int' => '31',
            'inner_email' => 'example@example.com',
        ],
        'list_schema' => [[
            'inner_int' => '43',
            'inner_email' => 'example@example.com',
        ], [
            'inner_int' => '47',
            'inner_email' => 'example@example.com',
        ]],
    ]];

    $schema = new Schema(true);
    $schema->add('int', 'Int', 'int', 'required');
    $schema->add('text', 'Text', 'text', 'required');
    $schema->add('single_schema', 'Schema', new SubSchema());
    $schema->add('list_schema', 'Schema', new SubSchema(true));

    expect($schema->validate($testData))->toBeTrue();
    $values = $schema->values();
    expect($values[0]['int'])->toEqual(13);
    expect($values[0]['single_schema']['inner_int'])->toEqual(23);
    expect($values[0]['list_schema'])->toEqual(null);
    expect($values[1]['text'])->toEqual('Text 2');
    expect($values[1]['single_schema']['inner_email'])->toEqual('example@example.com');
    expect($values[1]['list_schema'][0]['inner_email'])->toEqual('example@example.com');
    expect($values[1]['list_schema'][1]['inner_int'])->toEqual(47);
});


function getListData(): array
{
    return [
        [
            'int' => 13,
            'email' => 'chuck@example.com',
            'single_schema' => [
                'inner_email' => 'test@example.com',
            ],
            'list_schema' => [[
                'inner_int' => 23,
                'inner_email' => 'test@example.com',
            ]],
        ],
        [
            'int' => 73,
            'email' => 'chuck',
            'list_schema' => [
                [
                    'inner_int' => 43,
                    'inner_email' => 'test@example.com',
                ]
            ],
        ],
        [ // the valid record
            'int' => 23,
            'text' => 'Text 23',
            'single_schema' => [
                'inner_int' => 97,
                'inner_email' => 'test@example.com',
            ],
            'list_schema' => [[
                'inner_int' => 83,
                'inner_email' => 'test@example.com',
            ]],
        ],
        [
            'int' => 17,
            'text' => 'Text 2',
            'single_schema' => [
                'inner_int' => 23,
                'inner_email' => 'test INVALID example.com',
            ],
            'list_schema' => [[
                'inner_int' => 'invalid',
                'inner_email' => 'example@example.com',
            ], [
                'inner_int' => 29,
                'inner_email' => 'example@example.com',
            ], [
                'inner_int' => "37",
                'inner_email' => 'example INVALID example.com',
            ]],
        ]
    ];
}


function getListSchema(): Schema
{
    return new class(title: 'List Root', list: true) extends Schema
    {
        protected function rules(): void
        {
            $this->add('int', 'Int', 'int', 'required');
            $this->add('text', 'Text', 'text', 'required');
            $this->add('email', 'Text', 'text', 'email', 'minlen:10');
            $this->add(
                'single_schema',
                'Single Schema',
                new SubSchema(title: 'Single Sub'),
                'required'
            );
            $this->add('list_schema', 'List Schema', new SubSchema(title: 'List Sub', list: true));
        }
    };
}


test('Invalid list schema', function () {
    $testData = getListData();
    $schema = getListSchema();

    expect($schema->validate($testData))->toBeFalse();
    $errors = $schema->errors();
    expect($errors)->toHaveCount(5);
    expect($errors['map'][0]['text'][0])->toEqual('-schema-required-Text-');
    expect($errors['map'][0]['single_schema']['inner_int'][0])->toEqual('-schema-required-Int-');
    expect($errors['map'][1]['single_schema'][0])->toEqual('-schema-required-Single Schema-');
    expect($errors['map'][1]['text'][0])->toEqual('-schema-required-Text-');
    expect($errors['map'][1]['email'][0])->toEqual('-schema-invalid-email-Text-chuck-');
    expect($errors['map'][1]['email'][1])->toEqual('-schema-minlen-Text-10-');
    expect($errors['map'][3]['single_schema']['inner_email'][0])->toEqual('-schema-invalid-email-Email-test INVALID example.com-');
    expect($errors['map'][3]['list_schema'][0]['inner_int'][0])->toEqual('Invalid number in field "Int"');
    expect($errors['map'][3]['list_schema'][2]['inner_email'][0])->toEqual('-schema-invalid-email-Email-example INVALID example.com-');
});


test('Grouped errors', function () {
    $testData = getListData();
    $schema = getListSchema();

    expect($schema->validate($testData))->toBeFalse();
    $groups = $schema->errors(grouped: true)['errors'];
    expect($groups)->toHaveCount(3);
    expect($groups[0]['title'])->toEqual('List Root');
    expect($groups[0]['errors'][2]['error'])->toEqual('-schema-invalid-email-Text-chuck-');
    expect($groups[1]['title'])->toEqual('List Sub');
    expect($groups[1]['errors'][0]['error'])->toEqual('Invalid number in field "Int"');
    expect($groups[2]['title'])->toEqual('Single Sub');
    expect($groups[2]['errors'][1]['error'])->toEqual('-schema-invalid-email-Email-test INVALID example.com-');
});


test('Empty field name', function () {
    $schema = new class(langs: ['de', 'en']) extends Schema
    {
        protected function rules(): void
        {
            $this->add('', 'Int', 'int');
        }
    };
    $schema->validate([]);
})->throws(ValueError::class, 'must not be empty');
