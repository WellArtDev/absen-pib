<?php
declare(strict_types=1);

namespace App\Utils;

class Validator
{
    private array $errors = [];

    public function required(string $field, mixed $value, string $label = ''): self
    {
        $label = $label ?: $field;
        if ($value === null || $value === '' || (is_array($value) && count($value) === 0)) {
            $this->errors[$field] = "{$label} wajib diisi";
        }
        return $this;
    }

    public function email(string $field, mixed $value): self
    {
        if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = 'Format email tidak valid';
        }
        return $this;
    }

    public function minLength(string $field, string $value, int $min, string $label = ''): self
    {
        $label = $label ?: $field;
        if (strlen($value) < $min) {
            $this->errors[$field] = "{$label} minimal {$min} karakter";
        }
        return $this;
    }

    public function numeric(string $field, mixed $value): self
    {
        if ($value !== null && $value !== '' && !is_numeric($value)) {
            $this->errors[$field] = 'Harus berupa angka';
        }
        return $this;
    }

    public function inArray(string $field, mixed $value, array $allowed): self
    {
        if ($value !== null && $value !== '' && !in_array($value, $allowed, true)) {
            $this->errors[$field] = 'Nilai tidak valid: ' . implode(', ', $allowed);
        }
        return $this;
    }

    public function date(string $field, mixed $value): self
    {
        if ($value !== null && $value !== '' && !strtotime($value)) {
            $this->errors[$field] = 'Format tanggal tidak valid';
        }
        return $this;
    }

    public function hasErrors(): bool
    {
        return count($this->errors) > 0;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function validate(): void
    {
        if ($this->hasErrors()) {
            \App\Response::error('Validasi gagal', 422, $this->errors);
        }
    }
}
