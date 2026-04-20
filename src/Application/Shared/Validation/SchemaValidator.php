<?php

namespace CharosEMR\Application\Shared\Validation;

use PDO;

class SchemaValidator implements ValidatorInterface
{
    private PDO $pdo;
    private array $schemaCache = [];

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

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

    public function validateAgainstSchema(string $table, array $data): ValidationResult
    {
        $result = new ValidationResult();
        $schema = $this->getTableSchema($table);

        foreach ($schema as $column) {
            $fieldName = $column['Field'];
            $value = $data[$fieldName] ?? null;

            // Apply validation based on column properties
            $this->validateColumn($fieldName, $value, $column, $result);
        }

        return $result;
    }

    private function getTableSchema(string $table): array
    {
        if (isset($this->schemaCache[$table])) {
            return $this->schemaCache[$table];
        }

        $stmt = $this->pdo->query("DESCRIBE `{$table}`");
        $schema = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->schemaCache[$table] = $schema;

        return $schema;
    }

    private function validateColumn(string $field, $value, array $column, ValidationResult $result): void
    {
        $nullable = $column['Null'] === 'YES';
        $type = strtoupper($column['Type']);
        $key = $column['Key'];

        // Check NOT NULL constraint
        if (!$nullable && ($value === null || $value === '')) {
            $result->addError($field, "The {$field} field is required.");
            return;
        }

        // Skip validation for nullable fields with null values
        if ($nullable && ($value === null || $value === '')) {
            return;
        }

        // Extract length from type (e.g., VARCHAR(255) -> 255)
        $length = null;
        if (preg_match('/\((\d+)\)/', $type, $matches)) {
            $length = (int) $matches[1];
        }

        // String length validation
        if (str_starts_with($type, 'VARCHAR') || str_starts_with($type, 'CHAR')) {
            if ($length && strlen($value) > $length) {
                $result->addError($field, "The {$field} must not exceed {$length} characters.");
            }
        }

        // Email validation for email fields
        if ($field === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $result->addError($field, "The {$field} must be a valid email address.");
        }

        // Password validation (minimum 8 characters for security)
        if ($field === 'password' || $field === 'password_hash') {
            if (strlen($value) < 8) {
                $result->addError($field, "The {$field} must be at least 8 characters.");
            }
        }

        // Integer validation
        if (str_starts_with($type, 'INT') || str_starts_with($type, 'TINYINT') || str_starts_with($type, 'SMALLINT') || str_starts_with($type, 'BIGINT')) {
            if (!filter_var($value, FILTER_VALIDATE_INT)) {
                $result->addError($field, "The {$field} must be an integer.");
            }
        }

        // Decimal/numeric validation
        if (str_starts_with($type, 'DECIMAL') || str_starts_with($type, 'FLOAT') || str_starts_with($type, 'DOUBLE')) {
            if (!is_numeric($value)) {
                $result->addError($field, "The {$field} must be a number.");
            }
        }

        // Date validation
        if (str_starts_with($type, 'DATE') || str_starts_with($type, 'DATETIME') || str_starts_with($type, 'TIMESTAMP')) {
            if (!strtotime($value)) {
                $result->addError($field, "The {$field} must be a valid date.");
            }
        }

        // Boolean validation
        if (str_starts_with($type, 'TINYINT(1)') || str_starts_with($type, 'BOOLEAN')) {
            if (!in_array($value, [0, 1, '0', '1', true, false], true)) {
                $result->addError($field, "The {$field} must be a boolean value.");
            }
        }
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

        if ($rule === 'password') {
            if (strlen($value) < 8) {
                $result->addError($field, "The {$field} must be at least 8 characters.");
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
