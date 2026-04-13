<?php

declare(strict_types=1);

namespace App\Validation;

/**
 * 数据验证器
 * 
 * 提供统一的数据验证功能，支持链式调用。
 */
class Validator
{
    private array $errors = [];
    private array $data;
    
    public function __construct(array $data)
    {
        $this->data = $data;
    }
    
    /**
     * 创建验证器实例
     */
    public static function make(array $data): self
    {
        return new self($data);
    }
    
    /**
     * 必填验证
     */
    public function required(string $field, ?string $message = null): self
    {
        if (!isset($this->data[$field]) || $this->data[$field] === '' || $this->data[$field] === null) {
            $this->errors[$field][] = $message ?? "{$field} 不能为空";
        }
        
        return $this;
    }
    
    /**
     * 邮箱格式验证
     */
    public function email(string $field, ?string $message = null): self
    {
        $value = $this->data[$field] ?? '';
        
        if ($value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field][] = $message ?? "{$field} 邮箱格式不正确";
        }
        
        return $this;
    }
    
    /**
     * 最小长度验证
     */
    public function minLength(string $field, int $min, ?string $message = null): self
    {
        $value = (string)($this->data[$field] ?? '');
        
        if ($value !== '' && mb_strlen($value) < $min) {
            $this->errors[$field][] = $message ?? "{$field} 长度不能少于 {$min} 位";
        }
        
        return $this;
    }
    
    /**
     * 最大长度验证
     */
    public function maxLength(string $field, int $max, ?string $message = null): self
    {
        $value = (string)($this->data[$field] ?? '');
        
        if ($value !== '' && mb_strlen($value) > $max) {
            $this->errors[$field][] = $message ?? "{$field} 长度不能超过 {$max} 位";
        }
        
        return $this;
    }
    
    /**
     * 长度范围验证
     */
    public function length(string $field, int $min, int $max, ?string $message = null): self
    {
        $value = (string)($this->data[$field] ?? '');
        $len = mb_strlen($value);
        
        if ($value !== '' && ($len < $min || $len > $max)) {
            $this->errors[$field][] = $message ?? "{$field} 长度必须在 {$min}-{$max} 位之间";
        }
        
        return $this;
    }
    
    /**
     * 正则表达式验证
     */
    public function regex(string $field, string $pattern, ?string $message = null): self
    {
        $value = (string)($this->data[$field] ?? '');
        
        if ($value !== '' && !preg_match($pattern, $value)) {
            $this->errors[$field][] = $message ?? "{$field} 格式不正确";
        }
        
        return $this;
    }
    
    /**
     * 数值范围验证
     */
    public function between(string $field, $min, $max, ?string $message = null): self
    {
        $value = $this->data[$field] ?? null;
        
        if ($value !== null && ($value < $min || $value > $max)) {
            $this->errors[$field][] = $message ?? "{$field} 必须在 {$min} 到 {$max} 之间";
        }
        
        return $this;
    }
    
    /**
     * 枚举值验证
     */
    public function in(string $field, array $values, ?string $message = null): self
    {
        $value = $this->data[$field] ?? null;
        
        if ($value !== null && !in_array($value, $values, true)) {
            $this->errors[$field][] = $message ?? "{$field} 的值必须是：" . implode(', ', $values);
        }
        
        return $this;
    }
    
    /**
     * 自定义验证规则
     */
    public function custom(string $field, callable $callback, string $message): self
    {
        $value = $this->data[$field] ?? null;
        
        if (!$callback($value, $this->data)) {
            $this->errors[$field][] = $message;
        }
        
        return $this;
    }
    
    /**
     * 检查验证是否通过
     */
    public function passes(): bool
    {
        return empty($this->errors);
    }
    
    /**
     * 检查验证是否失败
     */
    public function fails(): bool
    {
        return !$this->passes();
    }
    
    /**
     * 获取所有错误
     */
    public function errors(): array
    {
        return $this->errors;
    }
    
    /**
     * 获取第一个错误消息
     */
    public function firstError(): ?string
    {
        foreach ($this->errors as $fieldErrors) {
            return $fieldErrors[0] ?? null;
        }
        
        return null;
    }
    
    /**
     * 获取指定字段的所有错误
     */
    public function getErrors(string $field): array
    {
        return $this->errors[$field] ?? [];
    }
    
    /**
     * 批量验证（静态方法）
     * 
     * @param array $data 要验证的数据
     * @param array $rules 验证规则 ['field' => ['required', 'email', ...]]
     * @return array|null 验证通过返回 null，失败返回错误数组
     */
    public static function validate(array $data, array $rules): ?array
    {
        $validator = new self($data);
        
        foreach ($rules as $field => $fieldRules) {
            foreach ($fieldRules as $rule) {
                if (is_string($rule)) {
                    match($rule) {
                        'required' => $validator->required($field),
                        'email' => $validator->email($field),
                        default => null,
                    };
                } elseif (is_array($rule)) {
                    $ruleName = $rule[0] ?? '';
                    match($ruleName) {
                        'min' => $validator->minLength($field, $rule[1] ?? 0),
                        'max' => $validator->maxLength($field, $rule[1] ?? 999),
                        'length' => $validator->length($field, $rule[1] ?? 0, $rule[2] ?? 999),
                        'between' => $validator->between($field, $rule[1] ?? 0, $rule[2] ?? 999),
                        'in' => $validator->in($field, $rule[1] ?? []),
                        'regex' => $validator->regex($field, $rule[1] ?? ''),
                        default => null,
                    };
                }
            }
        }
        
        return $validator->fails() ? $validator->errors() : null;
    }
}
