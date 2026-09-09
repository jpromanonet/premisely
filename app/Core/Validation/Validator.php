<?php

declare(strict_types=1);

namespace Premisely\Core\Validation;

final class Validator
{
    /** @var array<string, list<string>> */
    private array $errors = [];

    /** @param array<string, mixed> $data @param array<string, string> $rules */
    public function __construct(
        private array $data,
        private array $rules,
    ) {
    }

    /** @param array<string, mixed> $data @param array<string, string> $rules */
    public static function make(array $data, array $rules): self
    {
        return new self($data, $rules);
    }

    public function fails(): bool
    {
        $this->errors = [];
        foreach ($this->rules as $field => $ruleString) {
            $rules = explode('|', $ruleString);
            $value = $this->data[$field] ?? null;
            foreach ($rules as $rule) {
                if ($rule === 'required' && ($value === null || $value === '')) {
                    $this->errors[$field][] = "El campo {$field} es obligatorio.";
                }
                if (str_starts_with($rule, 'max:') && is_string($value)) {
                    $max = (int) substr($rule, 4);
                    if (mb_strlen($value) > $max) {
                        $this->errors[$field][] = "El campo {$field} no puede superar {$max} caracteres.";
                    }
                }
                if ($rule === 'email' && $value !== null && $value !== '' && !filter_var((string) $value, FILTER_VALIDATE_EMAIL)) {
                    $this->errors[$field][] = 'El email no es válido.';
                }
                if (str_starts_with($rule, 'min:') && is_string($value)) {
                    $min = (int) substr($rule, 4);
                    if (mb_strlen($value) < $min) {
                        $this->errors[$field][] = "El campo {$field} debe tener al menos {$min} caracteres.";
                    }
                }
                if ($rule === 'numeric' && $value !== null && $value !== '' && !is_numeric($value)) {
                    $this->errors[$field][] = "El campo {$field} debe ser numérico.";
                }
                if (str_starts_with($rule, 'in:')) {
                    $allowed = explode(',', substr($rule, 3));
                    if ($value !== null && $value !== '' && !in_array((string) $value, $allowed, true)) {
                        $this->errors[$field][] = "El campo {$field} no es válido.";
                    }
                }
                if ($rule === 'confirmed') {
                    $confirm = $this->data[$field . '_confirmation'] ?? null;
                    if ((string) $value !== (string) $confirm) {
                        $this->errors[$field][] = 'La confirmación no coincide.';
                    }
                }
            }
        }
        return $this->errors !== [];
    }

    /** @return array<string, list<string>> */
    public function errors(): array
    {
        return $this->errors;
    }

    /** @return array<string, mixed> */
    public function validated(): array
    {
        $out = [];
        foreach (array_keys($this->rules) as $field) {
            if (array_key_exists($field, $this->data)) {
                $out[$field] = $this->data[$field];
            }
        }
        return $out;
    }

    public function firstError(): ?string
    {
        foreach ($this->errors as $messages) {
            return $messages[0] ?? null;
        }
        return null;
    }
}
