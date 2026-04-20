<?php

namespace CharosEMR\Application\Shared\Validation;

class Validator implements ValidatorInterface
{
    public function validate(array $data, array $rules): ValidationResult
    {
        $result = new ValidationResult();

        foreach ($rules as $field => $ruleString) {
            $fieldRules = explode('|', $ruleString);
            $value = $data[$field] ?? null;

            foreach ($fieldRules as $rule) {
                $this->applyRule($field, $value, $rule, $result);
            }
        }

        return $result;
    }

    private function applyRule(string $field, $value, string $rule, ValidationResult $result): void
    {
        if (str_starts_with($rule, 'required')) {
            if ($value === null || $value === '') {
                $result->addError($field, "The {$field} field is required.");
                return;
            }
        }

        if ($value === null || $value === '') {
            return;
        }

        if (str_starts_with($rule, 'min:')) {
            $min = (int) substr($rule, 4);
            if (strlen($value) < $min) {
                $result->addError($field, "The {$field} must be at least {$min} characters.");
            }
        }

        if (str_starts_with($rule, 'max:')) {
            $max = (int) substr($rule, 4);
            if (strlen($value) > $max) {
                $result->addError($field, "The {$field} must not exceed {$max} characters.");
            }
        }

        if ($rule === 'email') {
            if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $result->addError($field, "The {$field} must be a valid email address.");
            }
        }

        if ($rule === 'date') {
            if (!strtotime($value)) {
                $result->addError($field, "The {$field} must be a valid date.");
            }
        }

        if (str_starts_with($rule, 'in:')) {
            $allowed = explode(',', substr($rule, 3));
            if (!in_array($value, $allowed)) {
                $result->addError($field, "The {$field} must be one of: " . implode(', ', $allowed));
            }
        }

        if ($rule === 'integer') {
            if (!filter_var($value, FILTER_VALIDATE_INT)) {
                $result->addError($field, "The {$field} must be an integer.");
            }
        }
    }
}
