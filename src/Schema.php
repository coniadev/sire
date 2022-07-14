<?php

declare(strict_types=1);

namespace Conia\Seiher;

use \TypeError;
use \ValueError;
use \RuntimeException;
use Conia\Seiher\{Validator, Value};
use Conia\Chuck\Util\{Arrays, Html};


class Schema implements SchemaInterface
{
    protected array $validators = [];

    public array $errorList = [];  // Alist of errorList to be displayed in frontend

    protected int $level = 0;
    protected array $rules = [];
    protected array $errorMap = [];     // A dictonary of errorList with the fieldname as key
    protected ?array $cachedValues = null;
    protected ?array $validatedValues = null;
    protected ?array $cachedPristine = null;
    protected array $validatorMessages = [];

    public function __construct(
        protected bool $list = false,
        protected bool $keepUnknown = false,
        protected array $langs = [],
        protected ?string $title = null,
    ) {
        $this->loadMessages();
        $this->loadDefaultValidators();
    }

    public function add(
        string $field,
        string $label,
        string|SchemaInterface $type,
        string ...$validators
    ): void {
        if (!$field) {
            throw new ValueError(
                'Schema definition error: field must not be empty'
            );
        }

        $this->rules[$field] = [
            'type' => $type,
            'label' => $label,
            'validators' => $validators,
        ];
    }

    /**
     * This method is called before validation starts.
     *
     * It can be overwritten to add rules in a reusable schema
     */
    protected function rules(): void
    {
    }

    protected function addSubError(string $field, array|string|null $error, ?int $listIndex): void
    {
        foreach ($error['errors'] ?? [] as $err) {
            $this->errorList[] = $err;
        }

        if ($listIndex === null) {
            $this->errorMap[$field] = $error['map'] ?? [];
        } else {
            $this->errorMap[$listIndex][$field] = $error['map'] ?? [];
        }
    }

    protected function addError(string $field, array|string|null $error, ?int $listIndex = null): void
    {
        $e = [
            'error' => $error,
            'title' => $this->title,
            'level' => $this->level,
            'item' => null,
        ];

        if ($listIndex === null) {
            if (!isset($this->errorMap[$field])) {
                $this->errorMap[$field] = [];
            }

            $this->errorMap[$field][] = $error;
        } else {
            $e['item'] = $listIndex;

            if (!isset($this->errorMap[$listIndex][$field])) {
                $this->errorMap[$listIndex][$field] = [];
            }

            $this->errorMap[$listIndex][$field][] = $error;
        }

        $this->errorList[] = $e;
    }

    protected function validateField(
        string $field,
        Value $value,
        string $validatorDefinition,
        ?int $listIndex
    ): void {
        $validatorArray = explode(':', $validatorDefinition);
        $validatorName = $validatorArray[0];
        $validatorArgs = array_slice($validatorArray, 1);

        $validator = $this->validators[$validatorName];

        if (is_array($value->value)) {
            if (empty($value->value) && $validator->skipNull) {
                return;
            }
        } else {
            if (
                empty($value->value)
                && strlen((string)$value->value) === 0
                && $validator->skipNull
            ) {
                return;
            }
        }

        if (!$validator->validate($value, ...$validatorArgs)) {
            $this->addError(
                $field,
                sprintf(
                    $validator->message,
                    $this->rules[$field]['label'],
                    $value->pristine,
                    $field,
                    ...$validatorArgs
                ),
                $listIndex
            );
        }
    }

    protected function toBool(mixed $pristine, string $label): Value
    {
        if (is_bool($pristine)) {
            return new Value($pristine, $pristine);
        }

        if (!$pristine) {
            return new Value(false, $pristine);
        }

        $tmp = strtolower((string)$pristine);

        if (in_array($tmp, ['1', 'on', 'true', 'yes'])) {
            return new Value(true, $pristine);
        }

        if (in_array($tmp, ['0', 'off', 'false', 'no', 'null'])) {
            return new Value(false, $pristine);
        }

        return new Value(
            $pristine,
            $pristine,
            sprintf($this->messages['bool'], $label)
        );
    }

    protected function toHtml(mixed $pristine, array $args): Value
    {
        $count = count($args);

        $value = match ($count) {
            0 => trim(Html::clean((string)$pristine)),
            1 => trim(Html::clean((string)$pristine, $this->{$args[0]}())),
            default => throw new RuntimeException('Too many arguments for html validator'),
        };

        if (empty($value)) {
            $value = null;
        }

        return new Value($value, $pristine);
    }

    protected function toText(mixed $pristine): Value
    {
        $value = trim(htmlspecialchars((string)$pristine));

        if (empty($value)) {
            $value = null;
        }

        return new Value($value, $pristine);
    }

    protected function toPlain(mixed $pristine): Value
    {
        return new Value((string)$pristine, $pristine);
    }

    protected function toList(mixed $pristine, string $label): Value
    {
        if (is_array($pristine) && !Arrays::isAssoc($pristine)) {
            return new Value($pristine, $pristine);
        }

        return new Value(
            $pristine,
            $pristine,
            sprintf($this->messages['list'], $label)
        );
    }

    protected function toFloat(mixed $pristine, string $label): Value
    {
        if (is_float($pristine) || is_null($pristine)) {
            return new Value($pristine, $pristine);
        }

        if (is_int($pristine)) {
            return new Value((float)$pristine, $pristine);
        }

        $tmp = trim((string)$pristine);

        if (preg_match('/^[-+]?[0-9]*\.?[0-9]+([eE][-+]?[0-9]+)?$/', $tmp)) {
            return new Value((float)$tmp, $pristine);
        }

        return new Value(
            $pristine,
            $pristine,
            sprintf($this->messages['float'], $label)
        );
    }

    protected function toInt(mixed $pristine, string $label): Value
    {
        if (is_int($pristine) || is_null($pristine)) {
            return new Value($pristine, $pristine);
        }

        if (preg_match('/^([0-9]|-[1-9]|-?[1-9][0-9]*)$/i', trim($pristine))) {
            return new Value((int)$pristine, $pristine);
        }

        return new Value(
            $pristine,
            $pristine,
            sprintf($this->messages['int'], $label)
        );
    }

    protected function toSubValues(mixed $pristine, SchemaInterface $schema): Value
    {
        if ($schema->validate($pristine, $this->level + 1)) {
            return new Value($pristine, $schema->values());
        }
        return new Value($pristine, $pristine, $schema->errors());
    }

    protected function readFromData(array $data, ?int $listIndex = null): array
    {
        $values = [];

        foreach ($data as $field => $value) {
            $rule = $this->rules[$field] ?? null;

            if ($rule) {
                if (is_string($rule['type'])) {
                    $typeArray = explode(':', $rule['type']);
                    $typeName = $typeArray[0];
                    $typeArgs = array_slice($typeArray, 1);
                } else {
                    $typeName = 'schema';
                    $typeArgs = [];
                }

                $label = $rule['label'];

                switch ($typeName) {
                    case 'text':
                        $valObj = $this->toText($value);
                        break;
                    case 'int':
                        $valObj = $this->toInt($value, $label);
                        break;
                    case 'bool':
                        $valObj = $this->toBool($value, $label);
                        break;
                    case 'float':
                        $valObj = $this->toFloat($value, $label);
                        break;
                    case 'html':
                        $valObj = $this->toHtml($value, $typeArgs);
                        break;
                    case 'plain':
                        $valObj = $this->toPlain($value);
                        break;
                    case 'list':
                        $valObj = $this->toList($value, $label);
                        break;
                    case 'schema':
                        $schema = $rule['type'];
                        $valObj = $this->toSubValues($value, $schema);
                        break;
                    default:
                        throw new ValueError('Wrong schema type');
                }

                if ($valObj->error !== null) {
                    if ($typeName === 'schema') {
                        $this->addSubError($field, $valObj->error, $listIndex);
                    } else {
                        $this->addError($field, $valObj->error, $listIndex);
                    }
                }

                $values[$field] = $valObj;
            } else {
                if ($this->keepUnknown) {
                    $values[$field] = new Value($value, $value);
                }
            }
        }

        return $values;
    }

    protected function readFromRules(array $values): array
    {
        foreach ($this->rules as $field => $rule) {
            if (!isset($values[$field])) {
                try {
                    $type = explode(':', $rule['type'])[0];
                } catch (TypeError) {
                    $type = 'schema';
                }

                if ($type == 'bool') {
                    $values[$field] = new Value(false, null);
                    continue;
                }

                $values[$field] = new Value(null, null);
            }
        }

        return $values;
    }

    protected function readValues(array $data): array
    {
        if ($this->list) {
            $values = [];

            foreach ($data as $listIndex => $item) {
                $subValues = $this->readFromData($item, $listIndex);
                $values[] = $this->readFromRules($subValues);
            }

            return $values;
        } else {
            $values = $this->readFromData($data);
            return $this->readFromRules($values);
        }
    }

    protected function validateItem(array $values, ?int $listIndex = null): array
    {
        foreach ($this->rules as $field => $rule) {
            foreach ($rule['validators'] as $validator) {
                $this->validateField(
                    $field,
                    $values[$field],
                    $validator,
                    $listIndex
                );
            }
        }

        return $values;
    }

    public function validate(array $data, int $level = 1): bool
    {
        $this->level = $level;
        $this->errorList = [];
        $this->errorMap = [];
        $this->cachedValues = null;
        $this->cachedPristine = null;

        $this->rules();

        $values = $this->readValues($data);

        if ($this->list) {
            $this->validatedValues = [];

            foreach ($values as $listIndex => $subValues) {
                // add an empty array for this item which will be
                // filled in case of error. Allows to show errors
                // next to the field in frontend (still TODO)
                if (!isset($this->errorMap[$listIndex])) {
                    $this->errorMap[$listIndex] = [];
                }

                $this->validatedValues[] =  $this->validateItem(
                    $subValues,
                    $listIndex
                );
            }
        } else {
            $this->validatedValues = $this->validateItem($values);
        }

        if (count($this->errorList) === 0) {
            $this->review();
        }

        return count($this->errorList) === 0;
    }

    protected function review(): void
    {
        // Can be overwritten in subclasses to make additional checks
        //
        // Implementations should call $this->addError('field_name', 'Error message');
        // in case of error.
    }

    /**
     * Groups errors by schema and sub schema
     *
     * Example:
     *    [
     *        [
     *            'title': 'Main Schema',
     *            'errors': [
     *                [
     *                   'error': 'First Error',
     *                   ...
     *                ], [
     *                   ...
     *                ]
     *            ]
     *        ], [
     *           'title': 'First Sub Schema',
     *           ....
     *        ]
     *    ]
     */
    protected function groupErrors(array $errors): array
    {
        $sections = [];

        foreach ($errors as $error) {
            $item = ['title' => $error['title'], 'level' => (string)$error['level']];

            if (in_array($item, $sections)) {
                continue;
            }

            $sections[] = $item;
        }

        usort($sections, function ($a, $b) {
            $aa = $a['level'] . $a['title'];
            $bb = $b['level'] . $b['title'];

            return $aa > $bb ? 1 : -1;
        });

        $groups = Arrays::groupBy(array_values($errors), 'title');
        $result = [];

        foreach ($sections as $section) {
            $result[] = [
                'title' => $section['title'],
                'errors' => $groups[$section['title']],
            ];
        }

        return $result;
    }

    public function errors(bool $grouped = false): array
    {
        $result = [
            'isList' => $this->list,
            'title' => $this->title,
            'map' => $this->errorMap,
            'grouped' => $grouped,
        ];

        if ($grouped) {
            $result['errors'] = $this->groupErrors($this->errorList);
        } else {
            $result['errors'] = array_values($this->errorList);
        }

        return $result;
    }

    protected function getValues(array $values): array
    {
        return array_map(
            function (Value $item): mixed {
                return $item->value;
            },
            $values
        );
    }

    public function values(): array
    {
        if ($this->cachedValues === null) {
            if ($this->list) {
                $this->cachedValues = [];

                foreach ($this->validatedValues ?? [] as $values) {
                    $this->cachedValues[] = $this->getValues($values);
                }
            } else {
                $this->cachedValues = $this->getValues($this->validatedValues ?? []);
            }
        }

        return $this->cachedValues;
    }

    public function pristineValues(): array
    {
        if ($this->cachedPristine === null) {
            $this->cachedPristine = array_map(
                function (Value $item): mixed {
                    return $item->pristine;
                },
                $this->validatedValues ?? []
            );
        }

        return $this->cachedPristine;
    }

    protected function loadMessages(): void
    {
        $this->messages = [
            // List:
            'list' => 'Invalid list in field "%1$s"',

            // Types:
            'bool' => 'Invalid value in field "%1$s"',
            'float' => 'Invalid number in field "%1$s"',
            'int' => 'Invalid number in field "%1$s"',

            // Validators:
            //
            // In error messages
            //   %1$s  is the field label
            //   %2$s  is the value
            //   %3$s  is the field name
            //   %4$s  is the first validator parameter
            //   %5$s  is the next validator parameter
            //   %6$s  is the next ... and so on
            'required' => 'Required value "%1$s"',
            'email' => 'Invalid email address in field "%1$s": %2$s-',
            'minlen' => 'The value of field "%1$s" is shorter than the minimum length of %4$s characters',
            'maxlen' => 'The value of field "%1$s" is longer than the maximum length of %4$s characters',
            'min' => 'The value %2$s of field "%1$s" is lower than the required minimum of %4$s',
            'max' => 'The value %2$s of field "%1$s" is higher than the allowed maximum of %4$s',
            'regex' => 'The value of field "%1$s" does not match the required pattern',
            'in' => 'Invalid value in field "%1$s"',
        ];
    }

    protected function loadDefaultValidators(): void
    {
        $this->validators['required'] = new Validator(
            'required',
            $this->messages['required'],
            function (Value $value, string ...$args) {
                $val = $value->value;

                if (is_null($val)) {
                    return false;
                } elseif (is_string($val) && trim($val) === '') {
                    return false;
                } elseif (is_array($val) && count($val) === 0) {
                    return false;
                }

                return true;
            },
            false
        );

        $this->validators['email'] = new Validator(
            'email',
            $this->messages['email'],
            function (Value $value, string ...$args) {
                $email = filter_var(
                    trim((string)$value->value),
                    \FILTER_VALIDATE_EMAIL
                );

                if ($email !== false && ($args[0] ?? null) === 'checkdns') {
                    [, $mailDomain] = explode("@", $email);

                    return checkdnsrr($mailDomain, 'MX');
                }

                return $email !== false;
            },
            true
        );

        $this->validators['minlen'] = new Validator(
            'minlen',
            $this->messages['minlen'],
            function (Value $value, string ...$args) {
                return strlen($value->value) >= (int)$args[0];
            },
            true
        );

        $this->validators['maxlen'] = new Validator(
            'maxlen',
            $this->messages['maxlen'],
            function (Value $value, string ...$args) {
                return strlen($value->value) <= (int)$args[0];
            },
            true
        );

        $this->validators['min'] = new Validator(
            'min',
            $this->messages['min'],
            function (Value $value, string ...$args) {
                return (float)$value->value >= (float)$args[0];
            },
            true
        );

        $this->validators['max'] = new Validator(
            'max',
            $this->messages['max'],
            function (Value $value, string ...$args) {
                return $value->value <= (float)$args[0];
            },
            true
        );

        $this->validators['regex'] = new Validator(
            'regex',
            $this->messages['regex'],
            function (Value $value, string ...$args) {
                // As regex patterns could contain colons ':' and validator
                // args are separated by colons and split at their position
                // we need to join them again
                return preg_match(implode(':', $args), $value->value) === 1;
            },
            true
        );

        $this->validators['in'] = new Validator(
            'in',
            $this->messages['regex'],
            function (Value $value, string ...$args) {
                // Allowed values must be passed as validator arg
                // seperated by comma.
                // Like: in:firstval,secondval,thirdval
                $allowed = explode(',', $args[0]);
                return in_array($value->value, $allowed);
            },
            true
        );
    }
}
