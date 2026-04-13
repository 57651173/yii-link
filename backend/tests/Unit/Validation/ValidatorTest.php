<?php

declare(strict_types=1);

namespace Tests\Unit\Validation;

use App\Validation\Validator;
use PHPUnit\Framework\TestCase;

/**
 * Validator 单元测试
 */
class ValidatorTest extends TestCase
{
    public function testRequiredValidation(): void
    {
        $validator = Validator::make([
            'name' => '',
            'email' => 'test@example.com',
        ]);
        
        $validator->required('name');
        
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors());
    }
    
    public function testEmailValidation(): void
    {
        $validator = Validator::make([
            'email' => 'invalid-email',
        ]);
        
        $validator->email('email');
        
        $this->assertTrue($validator->fails());
        $this->assertEquals('email 邮箱格式不正确', $validator->firstError());
    }
    
    public function testMinLengthValidation(): void
    {
        $validator = Validator::make([
            'password' => '123',
        ]);
        
        $validator->minLength('password', 6);
        
        $this->assertTrue($validator->fails());
    }
    
    public function testChainedValidation(): void
    {
        $validator = Validator::make([
            'email' => 'test@example.com',
            'password' => '123456',
        ]);
        
        $validator
            ->required('email')
            ->email('email')
            ->required('password')
            ->minLength('password', 6);
        
        $this->assertTrue($validator->passes());
    }
    
    public function testInValidation(): void
    {
        $validator = Validator::make([
            'status' => 'invalid',
        ]);
        
        $validator->in('status', ['active', 'inactive', 'banned']);
        
        $this->assertTrue($validator->fails());
    }
    
    public function testCustomValidation(): void
    {
        $validator = Validator::make([
            'age' => 15,
        ]);
        
        $validator->custom('age', fn($value) => $value >= 18, '年龄必须大于等于18岁');
        
        $this->assertTrue($validator->fails());
        $this->assertEquals('年龄必须大于等于18岁', $validator->firstError());
    }
    
    public function testBatchValidation(): void
    {
        $data = [
            'email' => 'test@example.com',
            'password' => '123456',
        ];
        
        $errors = Validator::validate($data, [
            'email' => ['required', 'email'],
            'password' => ['required', ['min', 6]],
        ]);
        
        $this->assertNull($errors);
    }
    
    public function testBatchValidationWithErrors(): void
    {
        $data = [
            'email' => 'invalid',
            'password' => '123',
        ];
        
        $errors = Validator::validate($data, [
            'email' => ['required', 'email'],
            'password' => ['required', ['min', 6]],
        ]);
        
        $this->assertIsArray($errors);
        $this->assertArrayHasKey('email', $errors);
        $this->assertArrayHasKey('password', $errors);
    }
}
