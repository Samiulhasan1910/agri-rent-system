<?php
declare(strict_types=1);

/**
 * Fluent validator that records the first failing message per field, so each
 * field shows the specific message required by the PRD.
 */
final class Validator
{
    private array $errors = [];

    public function __construct(private array $data)
    {
    }

    public function value(string $field): string
    {
        $value = $this->data[$field] ?? '';
        return is_array($value) ? '' : trim((string)$value);
    }

    /** Generic rule: adds $message when $passes is false (only the first error per field is kept). */
    public function check(string $field, bool $passes, string $message): self
    {
        if (!$passes && !isset($this->errors[$field])) {
            $this->errors[$field] = $message;
        }
        return $this;
    }

    public function required(string $field, string $message): self
    {
        return $this->check($field, $this->value($field) !== '', $message);
    }

    public function email(string $field, string $message): self
    {
        $v = $this->value($field);
        return $this->check($field, $v === '' || filter_var($v, FILTER_VALIDATE_EMAIL) !== false, $message);
    }

    public function min(string $field, int $length, string $message): self
    {
        $v = $this->value($field);
        return $this->check($field, $v === '' || mb_strlen($v) >= $length, $message);
    }

    public function max(string $field, int $length, string $message): self
    {
        return $this->check($field, mb_strlen($this->value($field)) <= $length, $message);
    }

    public function numeric(string $field, string $message): self
    {
        $v = $this->value($field);
        return $this->check($field, $v === '' || (is_numeric($v) && is_finite((float)$v)), $message);
    }

    public function positiveInt(string $field, string $message): self
    {
        $v = $this->value($field);
        return $this->check($field, $v === '' || (ctype_digit($v) && (int)$v > 0), $message);
    }

    public function in(string $field, array $allowed, string $message): self
    {
        $v = $this->value($field);
        return $this->check($field, $v === '' || in_array($v, $allowed, true), $message);
    }

    public function date(string $field, string $message): self
    {
        $v = $this->value($field);
        return $this->check($field, $v === '' || valid_date($v), $message);
    }

    public function same(string $field, string $other, string $message): self
    {
        return $this->check($field, $this->value($field) === $this->value($other), $message);
    }

    public function regex(string $field, string $pattern, string $message): self
    {
        $v = $this->value($field);
        return $this->check($field, $v === '' || preg_match($pattern, $v) === 1, $message);
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function first(): ?string
    {
        return $this->errors ? reset($this->errors) : null;
    }

    public function addError(string $field, string $message): self
    {
        $this->errors[$field] = $message;
        return $this;
    }
}
