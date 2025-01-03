<?php

namespace App\Core;

class Validator
{
    private $data;
    private $rules;
    private $errors = [];
    private $messages = [
        'required' => 'The :field field is required.',
        'email' => 'The :field must be a valid email address.',
        'string' => 'The :field must be a string.',
        'numeric' => 'The :field must be a number.',
        'min' => 'The :field must be at least :min.',
        'max' => 'The :field must not exceed :max.',
        'between' => 'The :field must be between :min and :max.',
        'in' => 'The selected :field is invalid.',
        'url' => 'The :field must be a valid URL.',
    ];

    private $sanitized = [];

    public function __construct(array $data, array $rules)
    {
        $this->data = $data;
        $this->rules = $rules;
    }

    public function validate(): bool
    {
        foreach ($this->rules as $field => $ruleString) {
            $rules = is_array($ruleString) ? $ruleString : explode('|', $ruleString);

            foreach ($rules as $rule) {
                $parameters = [];

                // Handle rules with parameters like min:3
                if (strpos($rule, ':') !== false) {
                    [$rule, $parameter] = explode(':', $rule, 2);
                    $parameters = explode(',', $parameter);
                }

                $this->validateField($field, $rule, $parameters);
            }

            // If field passes validation, sanitize it
            if (!isset($this->errors[$field])) {
                $this->sanitizeField($field, $rules);
            }
        }

        return empty($this->errors);
    }

    private function validateField($field, $rule, $parameters = [])
    {
        // Skip validation if field is not required and empty
        if ($rule !== 'required' && !isset($this->data[$field])) {
            return;
        }

        $value = $this->data[$field] ?? null;

        switch ($rule) {
            case 'required':
                if (!isset($this->data[$field]) || $value === '' || $value === null) {
                    $this->addError($field, $rule);
                }
                break;

            case 'email':
                if ($value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, $rule);
                }
                break;

            case 'string':
                if ($value && !is_string($value)) {
                    $this->addError($field, $rule);
                }
                break;

            case 'numeric':
                if ($value && !is_numeric($value)) {
                    $this->addError($field, $rule);
                }
                break;

            case 'min':
                if (is_string($value) && strlen($value) < $parameters[0]) {
                    $this->addError($field, $rule, ['min' => $parameters[0]]);
                } elseif (is_numeric($value) && $value < $parameters[0]) {
                    $this->addError($field, $rule, ['min' => $parameters[0]]);
                }
                break;

            case 'max':
                if (is_string($value) && strlen($value) > $parameters[0]) {
                    $this->addError($field, $rule, ['max' => $parameters[0]]);
                } elseif (is_numeric($value) && $value > $parameters[0]) {
                    $this->addError($field, $rule, ['max' => $parameters[0]]);
                }
                break;

            case 'in':
                if ($value && !in_array($value, $parameters)) {
                    $this->addError($field, $rule);
                }
                break;

            case 'url':
                if ($value && !filter_var($value, FILTER_VALIDATE_URL)) {
                    $this->addError($field, $rule);
                }
                break;
        }
    }

    private function sanitizeField($field, $rules)
    {
        $value = $this->data[$field] ?? null;

        if ($value === null) {
            $this->sanitized[$field] = null;
            return;
        }

        // Apply sanitization based on rules
        if (in_array('string', $rules)) {
            $value = strip_tags($value);
            $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        }

        if (in_array('email', $rules)) {
            $value = filter_var($value, FILTER_SANITIZE_EMAIL);
        }

        if (in_array('numeric', $rules)) {
            $value = filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        }

        if (in_array('url', $rules)) {
            $value = filter_var($value, FILTER_SANITIZE_URL);
        }

        $this->sanitized[$field] = $value;
    }

    private function addError($field, $rule, $parameters = [])
    {
        $message = $this->messages[$rule];
        $message = str_replace(':field', $field, $message);

        foreach ($parameters as $key => $value) {
            $message = str_replace(':' . $key, $value, $message);
        }

        $this->errors[$field][] = $message;
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function sanitized(): array
    {
        return $this->sanitized;
    }
}
