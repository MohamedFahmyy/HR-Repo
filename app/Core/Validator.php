<?php

declare(strict_types=1);

namespace App\Core;

class Validator
{
    private array $data;
    private array $rules;
    private array $errors = [];
    private array $validated = [];

    public function __construct(array $data, array $rules)
    {
        $this->data = $data;
        $this->rules = $rules;
    }

    public function validate(): bool
    {
        $this->errors = [];
        $this->validated = [];

        foreach ($this->rules as $field => $ruleString) {
            $ruleList = is_array($ruleString) ? $ruleString : explode('|', $ruleString);
            $value = $this->data[$field] ?? null;

            $isRequired = in_array('required', $ruleList, true);
            $isEmpty = $value === null || $value === '' || (is_array($value) && empty($value));

            if ($isEmpty && !$isRequired) {
                $this->validated[$field] = $value;
                continue;
            }

            foreach ($ruleList as $rule) {
                $ruleName = $rule;
                $parameter = null;

                if (str_contains($rule, ':')) {
                    [$ruleName, $parameter] = explode(':', $rule, 2);
                }

                $this->applyRule($field, $value, $ruleName, $parameter);
            }

            if (!isset($this->errors[$field])) {
                $this->validated[$field] = $value;
            }
        }

        return empty($this->errors);
    }

    private function applyRule(string $field, mixed $value, string $rule, ?string $parameter): void
    {
        switch ($rule) {
            case 'required':
                if ($value === null || $value === '' || (is_array($value) && empty($value))) {
                    $this->addError($field, "الحقل {$field} مطلوب.");
                }
                break;

            case 'email':
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, "الحقل {$field} يجب أن يكون بريداً إلكترونياً صحيحاً.");
                }
                break;

            case 'numeric':
                if (!is_numeric($value)) {
                    $this->addError($field, "الحقل {$field} يجب أن يكون قيمة رقمية.");
                }
                break;

            case 'integer':
                if (filter_var($value, FILTER_VALIDATE_INT) === false) {
                    $this->addError($field, "الحقل {$field} يجب أن يكون عدداً صحيحاً.");
                }
                break;

            case 'min':
                if (is_numeric($value)) {
                    if ((float)$value < (float)$parameter) {
                        $this->addError($field, "الحقل {$field} يجب ألا يقل عن {$parameter}.");
                    }
                } elseif (is_string($value)) {
                    if (mb_strlen($value) < (int)$parameter) {
                        $this->addError($field, "الحقل {$field} يجب أن يحتوي على {$parameter} أحرف على الأقل.");
                    }
                }
                break;

            case 'max':
                if (is_numeric($value)) {
                    if ((float)$value > (float)$parameter) {
                        $this->addError($field, "الحقل {$field} يجب ألا يتجاوز {$parameter}.");
                    }
                } elseif (is_string($value)) {
                    if (mb_strlen($value) > (int)$parameter) {
                        $this->addError($field, "الحقل {$field} يجب ألا يتجاوز {$parameter} أحرف.");
                    }
                }
                break;

            case 'date':
                if (strtotime((string)$value) === false) {
                    $this->addError($field, "الحقل {$field} يجب أن يكون تاريخاً صحيحاً.");
                }
                break;

            case 'date_format':
                $d = \DateTime::createFromFormat($parameter, (string)$value);
                if (!$d || $d->format($parameter) !== (string)$value) {
                    $this->addError($field, "الحقل {$field} لا يطابق الصيغة المطلوبة {$parameter}.");
                }
                break;

            case 'in':
                $allowed = explode(',', (string)$parameter);
                if (!in_array((string)$value, $allowed, true)) {
                    $this->addError($field, "القيمة المحددة للحقل {$field} غير مقبولة.");
                }
                break;

            case 'unique':
                // parameter: table,column[,ignore_id[,id_column]]
                $parts = explode(',', (string)$parameter);
                $table = $parts[0] ?? '';
                $column = $parts[1] ?? $field;
                $ignoreId = $parts[2] ?? null;
                $idColumn = $parts[3] ?? 'id';

                $sql = "SELECT COUNT(*) FROM `{$table}` WHERE `{$column}` = :val";
                $params = ['val' => $value];
                if ($ignoreId !== null && $ignoreId !== '') {
                    $sql .= " AND `{$idColumn}` != :ignore_id";
                    $params['ignore_id'] = $ignoreId;
                }

                $count = (int)Database::fetchColumn($sql, $params);
                if ($count > 0) {
                    $this->addError($field, "قيمة {$field} مسجلة مسبقاً ومستخدمة.");
                }
                break;

            case 'exists':
                $parts = explode(',', (string)$parameter);
                $table = $parts[0] ?? '';
                $column = $parts[1] ?? $field;

                $sql = "SELECT COUNT(*) FROM `{$table}` WHERE `{$column}` = :val";
                $count = (int)Database::fetchColumn($sql, ['val' => $value]);
                if ($count === 0) {
                    $this->addError($field, "السجل المحدد في {$field} غير موجود.");
                }
                break;

            case 'same':
                $otherValue = $this->data[$parameter] ?? null;
                if ($value !== $otherValue) {
                    $this->addError($field, "الحقل {$field} يجب أن يطابق الحقل {$parameter}.");
                }
                break;
        }
    }

    private function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function validated(): array
    {
        return $this->validated;
    }
}
